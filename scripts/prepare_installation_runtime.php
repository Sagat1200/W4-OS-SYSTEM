<?php

declare(strict_types=1);

use W4\OS\Support\ValidationError;

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install';
$defaultLiveOutputRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output';

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

/**
 * @return array{type:string,path:string,auto_detected:bool}
 */
function detectSource(
    string $profileId,
    ?string $sourceRootfs,
    ?string $sourceSquashfs,
    string $defaultLiveOutputRoot
): array {
    if ($sourceRootfs !== null && $sourceSquashfs !== null) {
        throw new ValidationError('Debe indicar solo una fuente: --source-rootfs o --source-squashfs');
    }

    if ($sourceRootfs !== null) {
        if (!is_dir($sourceRootfs)) {
            throw new ValidationError(sprintf('La carpeta SOURCE_ROOTFS no existe: %s', $sourceRootfs));
        }

        return [
            'type' => 'rootfs',
            'path' => $sourceRootfs,
            'auto_detected' => false,
        ];
    }

    if ($sourceSquashfs !== null) {
        if (!is_file($sourceSquashfs)) {
            throw new ValidationError(sprintf('La fuente squashfs no existe: %s', $sourceSquashfs));
        }

        return [
            'type' => 'squashfs',
            'path' => $sourceSquashfs,
            'auto_detected' => false,
        ];
    }

    $candidate = $defaultLiveOutputRoot
        . DIRECTORY_SEPARATOR . $profileId
        . DIRECTORY_SEPARATOR . 'image-root'
        . DIRECTORY_SEPARATOR . 'live'
        . DIRECTORY_SEPARATOR . 'filesystem.squashfs';

    if (!is_file($candidate)) {
        throw new ValidationError(sprintf(
            'No se detecto una fuente automatica para %s. Indique --source-rootfs o --source-squashfs',
            $profileId
        ));
    }

    return [
        'type' => 'squashfs',
        'path' => $candidate,
        'auto_detected' => true,
    ];
}

/**
 * @param array<string,mixed> $policy
 */
function applyCredentialPolicy(string $value, array $policy): string
{
    if ($policy['ascii_only'] ?? true) {
        $value = preg_replace('/[^A-Za-z0-9!@#%^&*+=:.]/', '', $value) ?? '';
    }

    $minLength = max(0, (int) ($policy['min_length'] ?? 0));
    $maxLength = max(0, (int) ($policy['max_length'] ?? 0));
    $requireDigit = (bool) ($policy['require_digit'] ?? false);
    $requireAlpha = (bool) ($policy['require_alpha'] ?? false);
    $attempts = 0;
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $digits = '0123456789';
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz';

    while (true) {
        $candidate = $value;
        $candidateLength = strlen($candidate);
        $digitPosition = null;

        if ($minLength > 0 && $candidateLength < $minLength) {
            $missing = $minLength - $candidateLength;
            for ($index = 0; $index < $missing; $index++) {
                $candidate .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        }

        if ($maxLength > 0 && strlen($candidate) > $maxLength) {
            $candidate = substr($candidate, 0, $maxLength);
        }

        if ($requireDigit && !preg_match('/[0-9]/', $candidate)) {
            $digit = $digits[random_int(0, strlen($digits) - 1)];
            if ($maxLength > 0 && strlen($candidate) >= $maxLength) {
                $position = random_int(0, max(0, strlen($candidate) - 1));
                $candidate[$position] = $digit;
                $digitPosition = $position;
            } else {
                $candidate .= $digit;
            }
        }

        if ($requireAlpha && !preg_match('/[A-Za-z]/', $candidate)) {
            $letter = $letters[random_int(0, strlen($letters) - 1)];
            if ($maxLength > 0 && strlen($candidate) >= $maxLength) {
                $upperBound = max(0, strlen($candidate) - 1);
                $position = random_int(0, $upperBound);
                if ($digitPosition !== null && $upperBound > 0 && $position === $digitPosition) {
                    $position = ($position + 1) % ($upperBound + 1);
                }
                $candidate[$position] = $letter;
            } else {
                $candidate .= $letter;
            }
        }

        if ($maxLength > 0 && strlen($candidate) > $maxLength) {
            $candidate = substr($candidate, 0, $maxLength);
        }

        $candidateLength = strlen($candidate);
        if (
            ($minLength === 0 || $candidateLength >= $minLength)
            && ($maxLength === 0 || $candidateLength <= $maxLength)
            && ($requireDigit === false || preg_match('/[0-9]/', $candidate))
            && ($requireAlpha === false || preg_match('/[A-Za-z]/', $candidate))
        ) {
            return $candidate;
        }

        $attempts++;
        if ($attempts > 8) {
            throw new ValidationError('No se pudo generar un secreto que cumpla la politica de credenciales');
        }

        $value .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    }
}

/**
 * @return array{ascii_only:bool,min_length:int,max_length:int,require_digit:bool,require_alpha:bool}
 */
function defaultCredentialPolicy(bool $isPassphrase): array
{
    if ($isPassphrase) {
        return [
            'ascii_only' => true,
            'min_length' => 16,
            'max_length' => 32,
            'require_digit' => true,
            'require_alpha' => true,
        ];
    }

    return [
        'ascii_only' => true,
        'min_length' => 12,
        'max_length' => 24,
        'require_digit' => true,
        'require_alpha' => true,
    ];
}

/**
 * @param array<string,mixed> $policy
 */
function validateCredentialPolicy(array $policy, string $name): void
{
    $minLength = (int) ($policy['min_length'] ?? 0);
    $maxLength = (int) ($policy['max_length'] ?? 0);

    if ($minLength < 0 || $maxLength < 0) {
        throw new ValidationError(sprintf('La politica %s no admite longitudes negativas', $name));
    }

    if ($maxLength !== 0 && $minLength > $maxLength) {
        throw new ValidationError(sprintf('La politica %s tiene min_length mayor que max_length', $name));
    }
}

function randomSecret(int $bytes): string
{
    return rtrim(strtr(base64_encode(random_bytes($bytes)), '+/', '-_'), '=');
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
 * @param array{type:string,path:string,auto_detected:bool} $source
 * @param array{disk_passphrase?:string,local_user_password?:string} $generatedSecrets
 */
function buildRuntimeEnv(string $runtimeDir, string $bundleDir, array $source, array $generatedSecrets): string
{
    $exports = [
        '#!/usr/bin/env bash',
        'set -euo pipefail',
        '',
        'export W4_INSTALL_EXECUTE=1',
    ];

    if ($source['type'] === 'rootfs') {
        $exports[] = sprintf('export W4_INSTALL_SOURCE_ROOTFS="%s"', relativePath($runtimeDir, $source['path']));
    } else {
        $exports[] = sprintf('export W4_INSTALL_SOURCE_SQUASHFS="%s"', relativePath($runtimeDir, $source['path']));
    }

    $diskPassphrasePath = $runtimeDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt';
    $userPasswordPath = $runtimeDir . DIRECTORY_SEPARATOR . 'local-user-password.txt';

    if (array_key_exists('disk_passphrase', $generatedSecrets)) {
        $exports[] = sprintf('export W4_DISK_PASSPHRASE_FILE="%s"', relativePath($runtimeDir, $diskPassphrasePath));
    } else {
        $exports[] = '# export W4_DISK_PASSPHRASE_FILE="./disk-passphrase.txt"';
    }

    if (array_key_exists('local_user_password', $generatedSecrets)) {
        $exports[] = sprintf('export W4_LOCAL_USER_PASSWORD_FILE="%s"', relativePath($runtimeDir, $userPasswordPath));
    } else {
        $exports[] = '# export W4_LOCAL_USER_PASSWORD_FILE="./local-user-password.txt"';
    }

    $exports[] = sprintf('export W4_BUNDLE_DIR="%s"', relativePath($runtimeDir, $bundleDir));
    $exports[] = '';

    return implode("\n", $exports) . "\n";
}

function buildCheckOnlyRunner(string $runtimeDir, string $bundleDir): string
{
    $applyScript = relativePath($runtimeDir, $bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh');

    return str_replace(["\r\n", "\r"], "\n", str_replace(
        '%APPLY_SCRIPT%',
        $applyScript,
        <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
bash "${SCRIPT_DIR}/%APPLY_SCRIPT%"
BASH
    )) . "\n";
}

function buildInstallRunner(string $runtimeDir, string $bundleDir): string
{
    $applyScript = relativePath($runtimeDir, $bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh');

    return str_replace(["\r\n", "\r"], "\n", str_replace(
        '%APPLY_SCRIPT%',
        $applyScript,
        <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/install.env"

[[ -f "${ENV_FILE}" ]] || {
  echo "ERROR: falta ${ENV_FILE}" >&2
  exit 1
}

# shellcheck disable=SC1090
source "${ENV_FILE}"
bash "${SCRIPT_DIR}/%APPLY_SCRIPT%"
BASH
    )) . "\n";
}

/**
 * @param array<string, mixed> $plan
 * @param array{type:string,path:string,auto_detected:bool} $source
 */
function buildRuntimeReadme(array $plan, array $source, bool $secretsGenerated): string
{
    $sourceVariable = $source['type'] === 'rootfs' ? 'W4_INSTALL_SOURCE_ROOTFS' : 'W4_INSTALL_SOURCE_SQUASHFS';

    return str_replace(["\r\n", "\r"], "\n", sprintf(
        "W4 OS Installation Runtime\n\n".
        "Perfil: %s\n".
        "Disco objetivo: %s\n".
        "Fuente detectada: %s\n".
        "Ruta de fuente: %s\n".
        "Deteccion automatica: %s\n".
        "Secretos efimeros generados: %s\n\n".
        "Archivos runtime:\n".
        "- install.env\n".
        "- run-check-only.sh\n".
        "- run-installation.sh\n".
        "- disk-passphrase.txt.template\n".
        "- local-user-password.txt.template\n\n".
        "Uso recomendado en la sesion live:\n".
        "1. Revise install.env y confirme la fuente %s.\n".
        "2. Si no se generaron secretos, cree disk-passphrase.txt y local-user-password.txt a partir de los templates.\n".
        "3. Ejecute bash run-check-only.sh para una validacion final.\n".
        "4. Ejecute bash run-installation.sh solo cuando quiera escribir en disco.\n",
        $plan['profile_name'],
        $plan['plan_binding']['selected_disk']['device'],
        $source['type'],
        $source['path'],
        $source['auto_detected'] ? 'si' : 'no',
        $secretsGenerated ? 'si' : 'no',
        $sourceVariable
    ));
}

/**
 * @param array<string, mixed> $bundleManifest
 */
function mergeRuntimeArtifacts(array $bundleManifest): array
{
    $artifacts = $bundleManifest['generated_artifacts'] ?? [];
    if (!is_array($artifacts)) {
        $artifacts = [];
    }

    $artifacts = array_values(array_unique(array_merge($artifacts, [
        'runtime/install.env',
        'runtime/run-check-only.sh',
        'runtime/run-installation.sh',
        'runtime/disk-passphrase.txt.template',
        'runtime/local-user-password.txt.template',
        'runtime/RUNTIME_PREPARATION.txt',
        'runtime/installation-runtime.json',
    ])));

    sort($artifacts);
    $bundleManifest['generated_artifacts'] = $artifacts;

    return $bundleManifest;
}

/**
 * @return array<string,mixed>
 */
function parseCredentialPolicy(string $argument, ?string $value): array
{
    if ($value === null || $value === '') {
        throw new ValidationError(sprintf('Falta el valor para %s', $argument));
    }

    $parts = explode(',', $value);
    $policy = [];

    foreach ($parts as $part) {
        $pair = explode('=', $part, 2);
        if (count($pair) !== 2) {
            throw new ValidationError(sprintf('Formato invalido en %s; use key=value,key2=value2', $argument));
        }

        [$key, $entryValue] = $pair;
        switch ($key) {
            case 'ascii_only':
            case 'require_digit':
            case 'require_alpha':
                $policy[$key] = in_array($entryValue, ['1', 'true', 'yes', 'on'], true);
                break;
            case 'min_length':
            case 'max_length':
                if (!preg_match('/^\d+$/', $entryValue)) {
                    throw new ValidationError(sprintf('%s.%s debe ser un entero no negativo', $argument, $key));
                }
                $policy[$key] = (int) $entryValue;
                break;
            default:
                throw new ValidationError(sprintf('Politica desconocida %s.%s', $argument, $key));
        }
    }

    return $policy;
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $bundleDir = null;
    $sourceRootfs = null;
    $sourceSquashfs = null;
    $runtimeDir = null;
    $generateSecrets = false;
    $passphrasePolicy = null;
    $passwordPolicy = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        switch ($argument) {
            case '--generate-secrets':
                $generateSecrets = true;
                break;

            case '--profile':
            case '--bundle-dir':
            case '--source-rootfs':
            case '--source-squashfs':
            case '--runtime-dir':
            case '--passphrase-policy':
            case '--password-policy':
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

                    case '--source-rootfs':
                        $sourceRootfs = $value;
                        break;

                    case '--source-squashfs':
                        $sourceSquashfs = $value;
                        break;

                    case '--runtime-dir':
                        $runtimeDir = $value;
                        break;

                    case '--passphrase-policy':
                        $passphrasePolicy = parseCredentialPolicy('--passphrase-policy', $value);
                        break;

                    case '--password-policy':
                        $passwordPolicy = parseCredentialPolicy('--password-policy', $value);
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

    if (!is_file($planPath)) {
        throw new ValidationError(sprintf('No existe el plan de instalacion: %s', $planPath));
    }

    if (!is_file($bundleManifestPath)) {
        throw new ValidationError(sprintf('No existe el installation-bundle.json: %s', $bundleManifestPath));
    }

    $plan = readJsonFile($planPath);
    if (($plan['kind'] ?? null) !== 'installation-plan') {
        throw new ValidationError(sprintf('El plan no es valido: %s', $planPath));
    }

    $bundleManifest = readJsonFile($bundleManifestPath);
    if (($bundleManifest['kind'] ?? null) !== 'installation-bundle') {
        throw new ValidationError(sprintf('El bundle no es valido: %s', $bundleManifestPath));
    }

    $profileId ??= (string) ($plan['profile_id'] ?? '');
    if ($profileId === '') {
        throw new ValidationError('No se pudo determinar el profile_id del bundle');
    }

    $runtimeDir ??= $bundleDir . DIRECTORY_SEPARATOR . 'runtime';
    if (!is_dir($runtimeDir) && !mkdir($runtimeDir, 0777, true) && !is_dir($runtimeDir)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta runtime: %s', $runtimeDir));
    }

    $source = detectSource($profileId, $sourceRootfs, $sourceSquashfs, $defaultLiveOutputRoot);

    $finalPassphrasePolicy = array_merge(defaultCredentialPolicy(true), $passphrasePolicy ?? []);
    $finalPasswordPolicy = array_merge(defaultCredentialPolicy(false), $passwordPolicy ?? []);
    validateCredentialPolicy($finalPassphrasePolicy, 'passphrase-policy');
    validateCredentialPolicy($finalPasswordPolicy, 'password-policy');

    $generatedSecrets = [];
    if ($generateSecrets) {
        $generatedSecrets['disk_passphrase'] = applyCredentialPolicy(randomSecret(24), $finalPassphrasePolicy);
        $generatedSecrets['local_user_password'] = applyCredentialPolicy(randomSecret(18), $finalPasswordPolicy);

        if (trim($generatedSecrets['disk_passphrase']) === '' || strlen($generatedSecrets['disk_passphrase']) < ($finalPassphrasePolicy['min_length'] ?? 1)) {
            throw new ValidationError('No se pudo generar una passphrase de disco valida con la politica definida');
        }

        if (trim($generatedSecrets['local_user_password']) === '' || strlen($generatedSecrets['local_user_password']) < ($finalPasswordPolicy['min_length'] ?? 1)) {
            throw new ValidationError('No se pudo generar una contrasena de usuario valida con la politica definida');
        }

        if (file_put_contents(
            $runtimeDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt',
            $generatedSecrets['disk_passphrase'] . PHP_EOL
        ) === false) {
            throw new ValidationError('No se pudo escribir disk-passphrase.txt');
        }

        if (file_put_contents(
            $runtimeDir . DIRECTORY_SEPARATOR . 'local-user-password.txt',
            $generatedSecrets['local_user_password'] . PHP_EOL
        ) === false) {
            throw new ValidationError('No se pudo escribir local-user-password.txt');
        }
    }

    $diskTemplate = "Cambie este valor por una passphrase LUKS2 efimera antes de ejecutar run-installation.sh.\n";
    $passwordTemplate = "Cambie este valor por el password local efimero antes de ejecutar run-installation.sh.\n";

    if (file_put_contents($runtimeDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt.template', $diskTemplate) === false) {
        throw new ValidationError('No se pudo escribir disk-passphrase.txt.template');
    }

    if (file_put_contents($runtimeDir . DIRECTORY_SEPARATOR . 'local-user-password.txt.template', $passwordTemplate) === false) {
        throw new ValidationError('No se pudo escribir local-user-password.txt.template');
    }

    if (file_put_contents(
        $runtimeDir . DIRECTORY_SEPARATOR . 'install.env',
        buildRuntimeEnv($runtimeDir, $bundleDir, $source, $generatedSecrets)
    ) === false) {
        throw new ValidationError('No se pudo escribir install.env');
    }

    if (file_put_contents(
        $runtimeDir . DIRECTORY_SEPARATOR . 'run-check-only.sh',
        buildCheckOnlyRunner($runtimeDir, $bundleDir)
    ) === false) {
        throw new ValidationError('No se pudo escribir run-check-only.sh');
    }

    if (file_put_contents(
        $runtimeDir . DIRECTORY_SEPARATOR . 'run-installation.sh',
        buildInstallRunner($runtimeDir, $bundleDir)
    ) === false) {
        throw new ValidationError('No se pudo escribir run-installation.sh');
    }

    if (file_put_contents(
        $runtimeDir . DIRECTORY_SEPARATOR . 'RUNTIME_PREPARATION.txt',
        buildRuntimeReadme($plan, $source, $generateSecrets)
    ) === false) {
        throw new ValidationError('No se pudo escribir RUNTIME_PREPARATION.txt');
    }

    @chmod($runtimeDir . DIRECTORY_SEPARATOR . 'run-check-only.sh', 0755);
    @chmod($runtimeDir . DIRECTORY_SEPARATOR . 'run-installation.sh', 0755);

    $runtimeManifest = [
        'installation_runtime_schema_version' => 1,
        'kind' => 'installation-runtime',
        'profile_id' => $profileId,
        'bundle_dir' => $bundleDir,
        'runtime_dir' => $runtimeDir,
        'selected_disk' => $plan['plan_binding']['selected_disk']['device'] ?? null,
        'source' => $source,
        'generated_secrets' => [
            'enabled' => $generateSecrets,
            'files' => $generateSecrets ? [
                'disk-passphrase.txt',
                'local-user-password.txt',
            ] : [],
            'policies' => [
                'passphrase' => $finalPassphrasePolicy,
                'password' => $finalPasswordPolicy,
            ],
        ],
        'commands' => [
            'check_only' => 'bash run-check-only.sh',
            'install' => 'bash run-installation.sh',
        ],
    ];

    writeJsonFile($runtimeDir . DIRECTORY_SEPARATOR . 'installation-runtime.json', $runtimeManifest);
    writeJsonFile($bundleManifestPath, mergeRuntimeArtifacts($bundleManifest));

    printJson([
        'status' => 'ok',
        'profile_id' => $profileId,
        'bundle_dir' => $bundleDir,
        'runtime_dir' => $runtimeDir,
        'selected_disk' => $plan['plan_binding']['selected_disk']['device'] ?? null,
        'source' => $source,
        'generated_secrets' => [
            'enabled' => $generateSecrets,
            'files' => $generateSecrets ? [
                $runtimeDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt',
                $runtimeDir . DIRECTORY_SEPARATOR . 'local-user-password.txt',
            ] : [],
            'policies' => [
                'passphrase' => $finalPassphrasePolicy,
                'password' => $finalPasswordPolicy,
            ],
        ],
        'check_only_command' => 'bash run-check-only.sh',
        'install_command' => 'bash run-installation.sh',
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
