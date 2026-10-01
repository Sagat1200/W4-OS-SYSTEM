<?php

declare(strict_types=1);

use W4\OS\Support\ArtifactMetadataToolkit;

require_once __DIR__ . '/lib/InstallerToolkit.php';

$rootDir = dirname(__DIR__);
$defaultBuildInputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'inputs';
$defaultProfilesDir = $rootDir . DIRECTORY_SEPARATOR . 'installer-profiles';
$defaultBundleDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install';

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
 * @return array<string, mixed>
 */
function readEditionPolicy(string $rootDir, string $profileId): array
{
    $policyPath = $rootDir
        . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR . 'editions'
        . DIRECTORY_SEPARATOR . str_replace('w4-os-', '', $profileId)
        . DIRECTORY_SEPARATOR . 'policy.json';

    $policy = readJsonFile($policyPath);
    if (($policy['profile_id'] ?? null) !== $profileId) {
        throw new ValidationError(sprintf('La politica %s no corresponde al profile_id %s', $policyPath, $profileId));
    }

    return $policy;
}

/**
 * @param array<string, mixed> $plan
 */
function validateInstallationPlan(array $plan, string $sourcePath): void
{
    if (($plan['installation_plan_schema_version'] ?? null) !== 1) {
        throw new ValidationError(sprintf('%s: installation_plan_schema_version debe ser 1', basename($sourcePath)));
    }

    if (($plan['kind'] ?? null) !== 'installation-plan') {
        throw new ValidationError(sprintf('%s: kind debe ser installation-plan', basename($sourcePath)));
    }
}

/**
 * @param array<string, mixed> $bundleManifest
 */
function validateInstallationBundle(array $bundleManifest, string $sourcePath): void
{
    if (($bundleManifest['installation_bundle_schema_version'] ?? null) !== 1) {
        throw new ValidationError(sprintf('%s: installation_bundle_schema_version debe ser 1', basename($sourcePath)));
    }

    if (($bundleManifest['kind'] ?? null) !== 'installation-bundle') {
        throw new ValidationError(sprintf('%s: kind debe ser installation-bundle', basename($sourcePath)));
    }
}

/**
 * @param list<array<string, mixed>> $disks
 * @return array<string, mixed>
 */
function selectDisk(array $disks, ?string $device, string $selection): array
{
    if ($device !== null) {
        foreach ($disks as $disk) {
            if (($disk['device'] ?? null) === $device) {
                if (($disk['read_only'] ?? false) === true) {
                    throw new ValidationError(sprintf('El disco solicitado es de solo lectura: %s', $device));
                }

                if (($disk['is_installation_media'] ?? false) === true) {
                    throw new ValidationError(sprintf('El disco solicitado corresponde al medio instalador: %s', $device));
                }

                return $disk;
            }
        }

        throw new ValidationError(sprintf('El inventario no contiene el disco solicitado: %s', $device));
    }

    $candidates = array_values(array_filter(
        $disks,
        static function (array $disk): bool {
            return (($disk['read_only'] ?? false) !== true)
                && (($disk['is_installation_media'] ?? false) !== true)
                && (($disk['has_partitions'] ?? false) !== true)
                && (($disk['has_filesystem_signatures'] ?? false) !== true);
        }
    ));

    if ($candidates === []) {
        throw new ValidationError('No existe un disco vacio, escribible y no instalador disponible en el inventario');
    }

    if ($selection === 'largest-empty-writable') {
        usort(
            $candidates,
            static fn (array $left, array $right): int => ((int) $right['size_bytes']) <=> ((int) $left['size_bytes'])
        );

        return $candidates[0];
    }

    throw new ValidationError(sprintf('Estrategia de seleccion no soportada: %s', $selection));
}

/**
 * @param array<string, mixed> $disk
 * @return array<string, string>
 */
function buildSelector(array $disk): array
{
    $selector = [];

    foreach (['serial', 'wwid', 'by_path'] as $field) {
        $value = $disk[$field] ?? null;
        if (is_string($value) && $value !== '') {
            $selector[$field] = $value;
        }
    }

    if ($selector === []) {
        throw new ValidationError(sprintf(
            'El disco %s no expone serial, wwid ni by_path reutilizables para selector unattended',
            (string) ($disk['device'] ?? 'desconocido')
        ));
    }

    return $selector;
}

/**
 * @param array<string, mixed> $baseProfile
 * @param array<string, mixed> $disk
 * @return array<string, mixed>
 */
function createRetargetedProfile(array $baseProfile, array $disk, array $selector, string $inventoryId): array
{
    $profile = $baseProfile;
    $profile['target']['disk_selector'] = $selector;

    $device = (string) ($disk['device'] ?? 'unknown');
    $suffix = preg_replace('/[^a-z0-9]+/i', '-', trim($device, '/')) ?? 'disk';
    $suffix = trim($suffix, '-');
    if ($suffix === '') {
        $suffix = 'disk';
    }

    $profile['id'] = sprintf('%s-%s', (string) $baseProfile['id'], strtolower($suffix));
    $profile['name'] = sprintf('%s (%s)', (string) $baseProfile['name'], $device);

    $notes = $profile['notes'] ?? [];
    if (!is_array($notes)) {
        $notes = [];
    }

    $notes[] = sprintf('Perfil reorientado automaticamente desde el inventario %s.', $inventoryId);
    $notes[] = sprintf('Disco objetivo detectado: %s.', $device);
    $profile['notes'] = $notes;

    return $profile;
}

/**
 * @param array<string, mixed> $plan
 */
function buildCheckOnlyReadme(
    array $plan,
    string $inventoryPath,
    string $installProfilePath,
    string $bundleDir
): string {
    $disk = $plan['plan_binding']['selected_disk'];

    return str_replace(["\r\n", "\r"], "\n", sprintf(
        "W4 OS Check-Only Preparation\n\n".
        "Perfil: %s\n".
        "Disco objetivo: %s\n".
        "Inventario usado: %s\n".
        "Perfil derivado: %s\n".
        "Bundle: %s\n\n".
        "Secuencia recomendada dentro de la ISO live:\n".
        "1. Confirmar que el disco sigue vacio y coincide con el selector del perfil derivado.\n".
        "2. Ejecutar `bash apply-installation.sh` sin `W4_INSTALL_EXECUTE=1` para validar el binding y el estado del disco.\n".
        "3. Ejecutar `bash verify-installation.sh` solo despues de una corrida real o de un montaje manual del target.\n\n".
        "Este bundle sigue sin escribir en disco por defecto. La instalacion real exige secretos externos y una fuente explicita de rootfs o squashfs.\n",
        $plan['profile_name'],
        $disk['device'],
        $inventoryPath,
        $installProfilePath,
        $bundleDir
    ));
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $inventoryPath = null;
    $device = null;
    $selection = 'largest-empty-writable';
    $buildInputPath = null;
    $baseProfilePath = null;
    $bundleDir = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--profile':
                $profileId = $value;
                break;

            case '--disk-inventory':
                $inventoryPath = $value;
                break;

            case '--device':
                $device = $value;
                break;

            case '--select':
                $selection = $value;
                break;

            case '--build-input':
                $buildInputPath = $value;
                break;

            case '--install-profile':
                $baseProfilePath = $value;
                break;

            case '--bundle-dir':
                $bundleDir = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null) {
        throw new ValidationError('Debe indicar un PROFILE_ID usando --profile');
    }

    if ($inventoryPath === null) {
        throw new ValidationError('Debe indicar un inventario de discos usando --disk-inventory');
    }

    $buildInputPath ??= $defaultBuildInputDir . DIRECTORY_SEPARATOR . $profileId . '.build-input.json';
    $baseProfilePath ??= $defaultProfilesDir . DIRECTORY_SEPARATOR . $profileId . '.vm-install.json';
    $bundleDir ??= $defaultBundleDir . DIRECTORY_SEPARATOR . $profileId;

    $toolkit = new InstallerToolkit();
    $metadataToolkit = new ArtifactMetadataToolkit();

    $buildInput = $toolkit->readJsonFile($buildInputPath);
    $toolkit->validateBuildInput($buildInput, $buildInputPath);

    $baseProfile = $toolkit->readJsonFile($baseProfilePath);
    $toolkit->validateInstallationProfile($baseProfile, $baseProfilePath);

    $inventory = $toolkit->readJsonFile($inventoryPath);
    $toolkit->validateDiskInventory($inventory, $inventoryPath);

    /** @var list<array<string, mixed>> $disks */
    $disks = $inventory['disks'];
    $selectedDisk = selectDisk($disks, $device, $selection);
    $selector = buildSelector($selectedDisk);
    $retargetedProfile = createRetargetedProfile(
        $baseProfile,
        $selectedDisk,
        $selector,
        (string) ($inventory['id'] ?? 'inventory')
    );
    $toolkit->validateInstallationProfile($retargetedProfile, $baseProfilePath);

    if (!is_dir($bundleDir) && !mkdir($bundleDir, 0777, true) && !is_dir($bundleDir)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta del bundle: %s', $bundleDir));
    }

    $derivedProfilePath = $bundleDir . DIRECTORY_SEPARATOR . 'installation-profile.derived.json';
    $inventoryCopyPath = $bundleDir . DIRECTORY_SEPARATOR . 'disk-inventory.json';
    $editionPolicyPath = $bundleDir . DIRECTORY_SEPARATOR . 'edition-policy.json';
    $planPath = $bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json';
    $bundleManifestPath = $bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json';
    $summaryPath = $bundleDir . DIRECTORY_SEPARATOR . 'INSTALLATION_SUMMARY.txt';
    $checkOnlyReadmePath = $bundleDir . DIRECTORY_SEPARATOR . 'CHECK_ONLY_PREPARATION.txt';

    $editionPolicy = readEditionPolicy($rootDir, $profileId);

    writeJsonFile($derivedProfilePath, $retargetedProfile);
    writeJsonFile($inventoryCopyPath, $inventory);
    writeJsonFile($editionPolicyPath, $editionPolicy);

    $plan = $toolkit->createInstallationPlan(
        $retargetedProfile,
        $buildInput,
        $inventory,
        $editionPolicy,
        'edition-policy.json'
    );
    validateInstallationPlan($plan, $planPath);
    writeJsonFile($planPath, $plan);

    $bundleManifest = $toolkit->createInstallationBundleManifest(
        $retargetedProfile,
        $buildInput,
        $inventory,
        $plan,
        $editionPolicy,
        'edition-policy.json'
    );
    validateInstallationBundle($bundleManifest, $bundleManifestPath);
    $bundleManifest['installation_profile_id'] = $retargetedProfile['id'];
    $bundleManifest['generated_artifacts'] = $metadataToolkit->normalizeGeneratedFiles(array_merge(
        $bundleManifest['generated_artifacts'] ?? [],
        [
            'edition-policy.json',
            'installation-profile.derived.json',
            'CHECK_ONLY_PREPARATION.txt',
        ]
    ));
    writeJsonFile($bundleManifestPath, $bundleManifest);

    $humanSummary = $toolkit->buildHumanSummary($plan);
    if (file_put_contents($summaryPath, $humanSummary) === false) {
        throw new ValidationError(sprintf('No se pudo escribir el resumen en %s', $summaryPath));
    }

    if (file_put_contents(
        $checkOnlyReadmePath,
        buildCheckOnlyReadme($plan, $inventoryPath, $derivedProfilePath, $bundleDir)
    ) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $checkOnlyReadmePath));
    }

    $executorCommand = sprintf(
        'php %s --bundle-dir %s',
        escapeshellarg(__DIR__ . DIRECTORY_SEPARATOR . 'generate_installation_executor.php'),
        escapeshellarg($bundleDir)
    );
    $executorOutput = [];
    $executorExitCode = 0;
    exec($executorCommand . ' 2>&1', $executorOutput, $executorExitCode);
    if ($executorExitCode !== 0) {
        throw new ValidationError(trim(implode(PHP_EOL, $executorOutput)) ?: 'No se pudo generar el ejecutor de instalacion');
    }

    $finalBundleManifest = readJsonFile($bundleManifestPath);
    validateInstallationBundle($finalBundleManifest, $bundleManifestPath);

    printJson($metadataToolkit->createSuccessPayload(
        $profileId,
        [
            'bundle_dir' => $bundleDir,
            'selected_disk' => $selectedDisk['device'],
            'selector' => $selector,
            'derived_install_profile' => $derivedProfilePath,
            'edition_policy' => $editionPolicyPath,
            'inventory_copy' => $inventoryCopyPath,
            'plan' => $planPath,
            'executor' => $bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh',
            'generated_artifacts' => $finalBundleManifest['generated_artifacts'] ?? [],
        ]
    ));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
