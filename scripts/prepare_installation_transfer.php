<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install';
$defaultTransferRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install-transfer';

/**
 * @return array<string, mixed>
 */
function readJsonFile(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer el archivo JSON: %s', $path));
    }

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('JSON invalido en %s: %s', $path, $exception->getMessage()));
    }

    if (!is_array($data)) {
        throw new ValidationError(sprintf('JSON invalido en %s: la raiz debe ser un objeto o arreglo JSON', $path));
    }

    return $data;
}

/**
 * @param array<string, mixed> $data
 */
function writeJsonFile(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new ValidationError(sprintf('No se pudo serializar JSON para %s', $path));
    }

    if (file_put_contents($path, $json . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $path));
    }
}

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta %s', $path));
    }
}

function copyFileOrFail(string $sourcePath, string $targetPath): void
{
    if (!is_file($sourcePath)) {
        throw new ValidationError(sprintf('No existe el archivo fuente %s', $sourcePath));
    }

    ensureDirectory(dirname($targetPath));

    if (!copy($sourcePath, $targetPath)) {
        throw new ValidationError(sprintf('No se pudo copiar %s a %s', $sourcePath, $targetPath));
    }
}

function removePath(string $path): void
{
    if (is_file($path) || is_link($path)) {
        if (!@unlink($path)) {
            throw new ValidationError(sprintf('No se pudo eliminar %s', $path));
        }

        return;
    }

    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);
    if ($items === false) {
        throw new ValidationError(sprintf('No se pudo listar %s', $path));
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        removePath($path . DIRECTORY_SEPARATOR . $item);
    }

    if (!@rmdir($path)) {
        throw new ValidationError(sprintf('No se pudo eliminar la carpeta %s', $path));
    }
}

function copyDirectoryRecursively(string $sourceDir, string $targetDir): void
{
    if (!is_dir($sourceDir)) {
        throw new ValidationError(sprintf('No existe la carpeta fuente %s', $sourceDir));
    }

    ensureDirectory($targetDir);

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $fullPath = $item->getPathname();
        $relativePath = substr($fullPath, strlen(rtrim($sourceDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR));
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $relativePath;

        if ($item->isDir()) {
            ensureDirectory($targetPath);
            continue;
        }

        copyFileOrFail($item->getPathname(), $targetPath);
    }
}

function relativePath(string $fromDir, string $targetPath): string
{
    $from = str_replace('\\', '/', realpath($fromDir) ?: $fromDir);
    $target = str_replace('\\', '/', realpath($targetPath) ?: $targetPath);

    $fromParts = array_values(array_filter(explode('/', trim($from, '/')), static fn (string $part): bool => $part !== ''));
    $targetParts = array_values(array_filter(explode('/', trim($target, '/')), static fn (string $part): bool => $part !== ''));

    while ($fromParts !== [] && $targetParts !== [] && strcasecmp($fromParts[0], $targetParts[0]) === 0) {
        array_shift($fromParts);
        array_shift($targetParts);
    }

    $prefix = array_fill(0, count($fromParts), '..');
    $parts = array_merge($prefix, $targetParts);

    return $parts === [] ? '.' : implode('/', $parts);
}

/**
 * @param array<string, mixed> $plan
 * @return array<string, mixed>
 */
function installationPolicy(array $plan): array
{
    /** @var array<string, mixed> $policy */
    $policy = is_array($plan['edition_policy'] ?? null) ? $plan['edition_policy'] : [];
    /** @var array<string, mixed> $branding */
    $branding = is_array($policy['branding'] ?? null) ? $policy['branding'] : [];
    /** @var array<string, mixed> $boot */
    $boot = is_array($policy['boot'] ?? null) ? $policy['boot'] : [];
    /** @var array<string, mixed> $ssh */
    $ssh = is_array($policy['ssh'] ?? null) ? $policy['ssh'] : [];
    /** @var array<string, mixed> $firewall */
    $firewall = is_array($policy['firewall'] ?? null) ? $policy['firewall'] : [];

    $edition = (string) ($plan['installation_profile']['edition'] ?? $plan['summary']['edition'] ?? 'home');
    $defaultTarget = (string) ($boot['default_target'] ?? ($edition === 'server' ? 'multi-user.target' : 'graphical.target'));
    $hostnamePrefix = (string) ($branding['hostname_prefix'] ?? ('w4-' . strtolower($edition)));
    $sshEnabled = ($ssh['enabled'] ?? ($edition === 'server')) === true;

    return [
        'path' => (string) ($policy['path'] ?? 'edition-policy.json'),
        'branding' => [
            'hostname_prefix' => $hostnamePrefix,
        ],
        'boot' => [
            'default_target' => $defaultTarget,
        ],
        'ssh' => [
            'enabled' => $sshEnabled,
            'authentication' => (string) ($ssh['authentication'] ?? ($sshEnabled ? 'publickey' : 'disabled')),
        ],
        'firewall' => [
            'backend' => (string) ($firewall['backend'] ?? 'ufw'),
            'incoming' => (string) ($firewall['incoming'] ?? 'deny'),
            'outgoing' => (string) ($firewall['outgoing'] ?? 'allow'),
        ],
    ];
}

/**
 * @return array{type:string,path:string}
 */
function detectSource(string $bundleDir, ?string $sourceRootfs, ?string $sourceSquashfs): array
{
    if ($sourceRootfs !== null && $sourceSquashfs !== null) {
        throw new ValidationError('Debe indicar solo una fuente: --source-rootfs o --source-squashfs');
    }

    if ($sourceRootfs !== null) {
        if (!is_dir($sourceRootfs)) {
            throw new ValidationError(sprintf('La carpeta source-rootfs no existe: %s', $sourceRootfs));
        }

        return ['type' => 'rootfs', 'path' => $sourceRootfs];
    }

    if ($sourceSquashfs !== null) {
        if (!is_file($sourceSquashfs)) {
            throw new ValidationError(sprintf('La fuente squashfs no existe: %s', $sourceSquashfs));
        }

        return ['type' => 'squashfs', 'path' => $sourceSquashfs];
    }

    $runtimeManifestPath = $bundleDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'installation-runtime.json';
    if (!is_file($runtimeManifestPath)) {
        throw new ValidationError('No existe installation-runtime.json y no se indico una fuente manual');
    }

    $runtimeManifest = readJsonFile($runtimeManifestPath);
    $source = $runtimeManifest['source'] ?? null;
    if (!is_array($source) || !isset($source['type'], $source['path'])) {
        throw new ValidationError('El installation-runtime.json no contiene una fuente valida');
    }

    $type = (string) $source['type'];
    $path = (string) $source['path'];

    if ($type === 'rootfs' && !is_dir($path)) {
        throw new ValidationError(sprintf('La carpeta rootfs del runtime no existe: %s', $path));
    }

    if ($type === 'squashfs' && !is_file($path)) {
        throw new ValidationError(sprintf('La fuente squashfs del runtime no existe: %s', $path));
    }

    return ['type' => $type, 'path' => $path];
}

/**
 * @param list<string> $secretFiles
 * @param array<string, mixed> $editionPolicy
 */
function rewriteInstallEnv(string $runtimeDir, string $sourcePath, string $sourceType, array $secretFiles, array $editionPolicy): string
{
    $sourceVariable = $sourceType === 'rootfs' ? 'W4_INSTALL_SOURCE_ROOTFS' : 'W4_INSTALL_SOURCE_SQUASHFS';
    $lines = [
        '#!/usr/bin/env bash',
        'set -euo pipefail',
        '',
        'export W4_INSTALL_EXECUTE=1',
        sprintf('export %s="%s"', $sourceVariable, relativePath($runtimeDir, $sourcePath)),
        'export W4_BUNDLE_DIR=".."',
        'export W4_EDITION_POLICY_FILE="../edition-policy.json"',
        sprintf('export W4_DEFAULT_TARGET="%s"', (string) $editionPolicy['boot']['default_target']),
        sprintf('export W4_HOSTNAME_PREFIX="%s"', (string) $editionPolicy['branding']['hostname_prefix']),
        sprintf('export W4_SSH_ENABLED="%s"', ($editionPolicy['ssh']['enabled'] ?? false) === true ? '1' : '0'),
        sprintf('export W4_SSH_AUTHENTICATION="%s"', (string) $editionPolicy['ssh']['authentication']),
        sprintf('export W4_FIREWALL_BACKEND="%s"', (string) $editionPolicy['firewall']['backend']),
        sprintf('export W4_FIREWALL_INCOMING="%s"', (string) $editionPolicy['firewall']['incoming']),
        sprintf('export W4_FIREWALL_OUTGOING="%s"', (string) $editionPolicy['firewall']['outgoing']),
    ];

    if (in_array('disk-passphrase.txt', $secretFiles, true)) {
        $lines[] = 'export W4_DISK_PASSPHRASE_FILE="disk-passphrase.txt"';
    } else {
        $lines[] = '# export W4_DISK_PASSPHRASE_FILE="disk-passphrase.txt"';
    }

    if (in_array('local-user-password.txt', $secretFiles, true)) {
        $lines[] = 'export W4_LOCAL_USER_PASSWORD_FILE="local-user-password.txt"';
    } else {
        $lines[] = '# export W4_LOCAL_USER_PASSWORD_FILE="local-user-password.txt"';
    }

    $lines[] = '';

    return implode("\n", $lines) . "\n";
}

/**
 * @param list<string> $secretFiles
 * @param array<string, mixed> $editionPolicy
 */
function buildTransferReadme(array $plan, string $sourceType, array $secretFiles, array $editionPolicy): string
{
    return str_replace(["\r\n", "\r"], "\n", sprintf(
        "W4 OS Installation Transfer Package\n\n".
        "Perfil: %s\n".
        "Disco objetivo: %s\n".
        "Target por defecto: %s\n".
        "Hostname prefix: %s\n".
        "Fuente empaquetada: %s\n".
        "Secretos incluidos: %s\n\n".
        "Contenido:\n".
        "- apply-installation.sh\n".
        "- edition-policy.json\n".
        "- installation-plan.json\n".
        "- verify-installation.sh\n".
        "- runtime/\n".
        "- source/\n\n".
        "Uso en la VM live:\n".
        "1. Copie este directorio al destino ~/w4-transfer.\n".
        "2. Entre a ~/w4-transfer/runtime.\n".
        "3. Revise install.env y edition-policy.json.\n".
        "4. Ejecute bash run-check-only.sh.\n".
        "4. Ejecute bash run-installation.sh solo cuando quiera escribir en disco.\n",
        $plan['profile_name'],
        $plan['plan_binding']['selected_disk']['device'],
        $editionPolicy['boot']['default_target'],
        $editionPolicy['branding']['hostname_prefix'],
        $sourceType,
        $secretFiles !== [] ? 'si' : 'no'
    ));
}

/**
 * @param array<string, mixed> $plan
 * @param list<string> $secretFiles
 * @param array<string, mixed> $editionPolicy
 * @return array<string, mixed>
 */
function buildTransferRuntimeManifest(
    array $plan,
    string $profileId,
    string $transferDir,
    string $runtimeDir,
    string $sourcePath,
    string $sourceType,
    array $secretFiles,
    array $editionPolicy
): array
{
    return [
        'installation_runtime_schema_version' => 1,
        'kind' => 'installation-runtime',
        'profile_id' => $profileId,
        'bundle_dir' => dirname($runtimeDir),
        'runtime_dir' => $runtimeDir,
        'selected_disk' => $plan['plan_binding']['selected_disk']['device'] ?? null,
        'edition_policy' => [
            'path' => '../edition-policy.json',
            'default_target' => $editionPolicy['boot']['default_target'],
            'hostname_prefix' => $editionPolicy['branding']['hostname_prefix'],
            'ssh_enabled' => $editionPolicy['ssh']['enabled'],
            'firewall_backend' => $editionPolicy['firewall']['backend'],
            'firewall_incoming' => $editionPolicy['firewall']['incoming'],
            'firewall_outgoing' => $editionPolicy['firewall']['outgoing'],
        ],
        'source' => [
            'type' => $sourceType,
            'path' => $sourcePath,
            'packaged' => true,
        ],
        'generated_secrets' => [
            'enabled' => $secretFiles !== [],
            'files' => $secretFiles,
        ],
        'commands' => [
            'check_only' => 'bash run-check-only.sh',
            'install' => 'bash run-installation.sh',
        ],
        'transfer_dir' => $transferDir,
    ];
}

/**
 * @param array<string, mixed> $plan
 * @param list<string> $secretFiles
 * @param array<string, mixed> $editionPolicy
 */
function buildTransferRuntimeReadme(array $plan, string $sourceType, string $sourcePath, array $secretFiles, array $editionPolicy): string
{
    $sourceVariable = $sourceType === 'rootfs' ? 'W4_INSTALL_SOURCE_ROOTFS' : 'W4_INSTALL_SOURCE_SQUASHFS';

    return str_replace(["\r\n", "\r"], "\n", sprintf(
        "W4 OS Installation Runtime\n\n".
        "Perfil: %s\n".
        "Disco objetivo: %s\n".
        "Target por defecto: %s\n".
        "Hostname prefix: %s\n".
        "Fuente empaquetada: %s\n".
        "Ruta de fuente empaquetada: %s\n".
        "Secretos incluidos: %s\n\n".
        "Uso recomendado en la sesion live:\n".
        "1. Revise install.env, confirme la fuente %s y valide edition-policy.json.\n".
        "2. Si faltan secretos, cree disk-passphrase.txt y local-user-password.txt a partir de los templates.\n".
        "3. Ejecute bash run-check-only.sh para la ultima validacion.\n".
        "4. Ejecute bash run-installation.sh solo cuando quiera escribir en disco.\n",
        $plan['profile_name'],
        $plan['plan_binding']['selected_disk']['device'],
        $editionPolicy['boot']['default_target'],
        $editionPolicy['branding']['hostname_prefix'],
        $sourceType,
        $sourcePath,
        $secretFiles !== [] ? 'si' : 'no',
        $sourceVariable
    ));
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $bundleDir = null;
    $transferDir = null;
    $sourceRootfs = null;
    $sourceSquashfs = null;
    $includeSecrets = true;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        switch ($argument) {
            case '--no-secrets':
                $includeSecrets = false;
                break;

            case '--profile':
            case '--bundle-dir':
            case '--transfer-dir':
            case '--source-rootfs':
            case '--source-squashfs':
                if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
                    throw new ValidationError(sprintf('Falta el valor para %s', $argument));
                }

                $value = $arguments[++$index];

                switch ($argument) {
                    case '--profile':
                        $profileId = $value;
                        break;
                    case '--bundle-dir':
                        $bundleDir = $value;
                        break;
                    case '--transfer-dir':
                        $transferDir = $value;
                        break;
                    case '--source-rootfs':
                        $sourceRootfs = $value;
                        break;
                    case '--source-squashfs':
                        $sourceSquashfs = $value;
                        break;
                }
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null && $bundleDir === null) {
        throw new ValidationError('Debe indicar --profile o --bundle-dir');
    }

    $bundleDir ??= $defaultBundleRoot . DIRECTORY_SEPARATOR . $profileId;
    $planPath = $bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json';
    $bundleManifestPath = $bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json';
    $runtimeDir = $bundleDir . DIRECTORY_SEPARATOR . 'runtime';

    if (!is_file($planPath)) {
        throw new ValidationError(sprintf('No existe el plan de instalacion: %s', $planPath));
    }

    if (!is_file($bundleManifestPath)) {
        throw new ValidationError(sprintf('No existe installation-bundle.json: %s', $bundleManifestPath));
    }

    if (!is_dir($runtimeDir)) {
        throw new ValidationError(sprintf('No existe la carpeta runtime del bundle: %s', $runtimeDir));
    }

    $plan = readJsonFile($planPath);
    $profileId ??= (string) ($plan['profile_id'] ?? '');
    if ($profileId === '') {
        throw new ValidationError('No se pudo determinar el profile_id para el transfer');
    }
    $editionPolicy = installationPolicy($plan);

    $transferDir ??= $defaultTransferRoot . DIRECTORY_SEPARATOR . $profileId;
    ensureDirectory($transferDir);
    foreach ([
        $transferDir . DIRECTORY_SEPARATOR . 'runtime',
        $transferDir . DIRECTORY_SEPARATOR . 'source',
    ] as $pathToReset) {
        removePath($pathToReset);
        ensureDirectory($pathToReset);
    }

    $source = detectSource($bundleDir, $sourceRootfs, $sourceSquashfs);
    $transferRuntimeDir = $transferDir . DIRECTORY_SEPARATOR . 'runtime';

    copyFileOrFail($bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh', $transferDir . DIRECTORY_SEPARATOR . 'apply-installation.sh');
    copyFileOrFail($bundleDir . DIRECTORY_SEPARATOR . 'edition-policy.json', $transferDir . DIRECTORY_SEPARATOR . 'edition-policy.json');
    copyFileOrFail($bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json', $transferDir . DIRECTORY_SEPARATOR . 'installation-plan.json');
    copyFileOrFail($bundleDir . DIRECTORY_SEPARATOR . 'verify-installation.sh', $transferDir . DIRECTORY_SEPARATOR . 'verify-installation.sh');

    foreach ([
        'run-check-only.sh',
        'run-installation.sh',
        'disk-passphrase.txt.template',
        'local-user-password.txt.template',
    ] as $runtimeFile) {
        copyFileOrFail(
            $runtimeDir . DIRECTORY_SEPARATOR . $runtimeFile,
            $transferRuntimeDir . DIRECTORY_SEPARATOR . $runtimeFile
        );
    }

    $copiedSecrets = [];
    if ($includeSecrets) {
        foreach (['disk-passphrase.txt', 'local-user-password.txt'] as $secretFile) {
            $secretPath = $runtimeDir . DIRECTORY_SEPARATOR . $secretFile;
            if (is_file($secretPath)) {
                copyFileOrFail(
                    $secretPath,
                    $transferRuntimeDir . DIRECTORY_SEPARATOR . $secretFile
                );
                $copiedSecrets[] = $secretFile;
            }
        }
    }

    if ($source['type'] === 'squashfs') {
        $targetSourcePath = $transferDir . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'filesystem.squashfs';
        copyFileOrFail($source['path'], $targetSourcePath);
    } else {
        $targetSourcePath = $transferDir . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'rootfs';
        copyDirectoryRecursively($source['path'], $targetSourcePath);
    }

    if (file_put_contents(
        $transferRuntimeDir . DIRECTORY_SEPARATOR . 'install.env',
        rewriteInstallEnv(
            $transferRuntimeDir,
            $targetSourcePath,
            $source['type'],
            $copiedSecrets,
            $editionPolicy
        )
    ) === false) {
        throw new ValidationError('No se pudo escribir runtime/install.env en el transfer');
    }

    if (file_put_contents(
        $transferRuntimeDir . DIRECTORY_SEPARATOR . 'RUNTIME_PREPARATION.txt',
        buildTransferRuntimeReadme(
            $plan,
            $source['type'],
            relativePath($transferRuntimeDir, $targetSourcePath),
            $copiedSecrets,
            $editionPolicy
        )
    ) === false) {
        throw new ValidationError('No se pudo escribir runtime/RUNTIME_PREPARATION.txt en el transfer');
    }

    writeJsonFile(
        $transferRuntimeDir . DIRECTORY_SEPARATOR . 'installation-runtime.json',
        buildTransferRuntimeManifest(
            $plan,
            $profileId,
            $transferDir,
            $transferRuntimeDir,
            relativePath($transferRuntimeDir, $targetSourcePath),
            $source['type'],
            $copiedSecrets,
            $editionPolicy
        )
    );

    @chmod($transferRuntimeDir . DIRECTORY_SEPARATOR . 'run-check-only.sh', 0755);
    @chmod($transferRuntimeDir . DIRECTORY_SEPARATOR . 'run-installation.sh', 0755);

    $transferManifest = [
        'installation_transfer_schema_version' => 1,
        'kind' => 'installation-transfer',
        'profile_id' => $profileId,
        'bundle_dir' => $bundleDir,
        'transfer_dir' => $transferDir,
        'selected_disk' => $plan['plan_binding']['selected_disk']['device'] ?? null,
        'edition_policy' => [
            'path' => 'edition-policy.json',
            'default_target' => $editionPolicy['boot']['default_target'],
            'hostname_prefix' => $editionPolicy['branding']['hostname_prefix'],
        ],
        'source' => [
            'type' => $source['type'],
            'original_path' => $source['path'],
            'transfer_path' => $targetSourcePath,
            'transfer_relative_path' => relativePath($transferDir, $targetSourcePath),
        ],
        'secrets' => [
            'requested' => $includeSecrets,
            'included' => $copiedSecrets !== [],
            'files' => array_map(
                static fn (string $fileName): string => 'runtime/' . $fileName,
                $copiedSecrets
            ),
        ],
        'vm_commands' => [
            'check_only' => 'cd ~/w4-transfer/runtime && bash run-check-only.sh',
            'install' => 'cd ~/w4-transfer/runtime && bash run-installation.sh',
        ],
    ];

    writeJsonFile($transferDir . DIRECTORY_SEPARATOR . 'transfer-manifest.json', $transferManifest);
    if (file_put_contents(
        $transferDir . DIRECTORY_SEPARATOR . 'TRANSFER_PREPARATION.txt',
        buildTransferReadme($plan, $source['type'], $copiedSecrets, $editionPolicy)
    ) === false) {
        throw new ValidationError('No se pudo escribir TRANSFER_PREPARATION.txt');
    }

    printJson([
        'status' => 'ok',
        'profile_id' => $profileId,
        'bundle_dir' => $bundleDir,
        'transfer_dir' => $transferDir,
        'selected_disk' => $plan['plan_binding']['selected_disk']['device'] ?? null,
        'edition_policy' => $transferManifest['edition_policy'],
        'source' => $transferManifest['source'],
        'secrets' => $transferManifest['secrets'],
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
