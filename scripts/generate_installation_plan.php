<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/InstallerToolkit.php';

$rootDir = dirname(__DIR__);
$defaultBuildInputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'inputs';
$defaultProfilesDir = $rootDir . DIRECTORY_SEPARATOR . 'installer-profiles';
$defaultOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install';

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

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $buildInputPath = null;
    $installationProfilePath = null;
    $inventoryPath = null;
    $outputDir = null;

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

            case '--build-input':
                $buildInputPath = $value;
                break;

            case '--install-profile':
                $installationProfilePath = $value;
                break;

            case '--disk-inventory':
                $inventoryPath = $value;
                break;

            case '--output-dir':
                $outputDir = $value;
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
    $installationProfilePath ??= $defaultProfilesDir . DIRECTORY_SEPARATOR . $profileId . '.vm-install.json';
    $outputDir ??= $defaultOutputDir . DIRECTORY_SEPARATOR . $profileId;

    if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputDir));
    }

    $toolkit = new InstallerToolkit();

    $buildInput = $toolkit->readJsonFile($buildInputPath);
    $toolkit->validateBuildInput($buildInput, $buildInputPath);

    $installationProfile = $toolkit->readJsonFile($installationProfilePath);
    $toolkit->validateInstallationProfile($installationProfile, $installationProfilePath);

    $diskInventory = $toolkit->readJsonFile($inventoryPath);
    $toolkit->validateDiskInventory($diskInventory, $inventoryPath);

    $plan = $toolkit->createInstallationPlan($installationProfile, $buildInput, $diskInventory);
    $bundleManifest = $toolkit->createInstallationBundleManifest($installationProfile, $buildInput, $diskInventory, $plan);
    $humanSummary = $toolkit->buildHumanSummary($plan);

    writeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'installation-profile.json', $installationProfile);
    writeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'disk-inventory.json', $diskInventory);
    writeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'installation-plan.json', $plan);
    writeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'installation-bundle.json', $bundleManifest);

    if (file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'INSTALLATION_SUMMARY.txt', $humanSummary) === false) {
        throw new ValidationError(sprintf('No se pudo escribir el resumen legible en %s', $outputDir));
    }

    printJson([
        'status' => 'ok',
        'profile_id' => $profileId,
        'build_input' => $buildInputPath,
        'installation_profile' => $installationProfilePath,
        'disk_inventory' => $inventoryPath,
        'output_dir' => $outputDir,
        'selected_disk' => $plan['plan_binding']['selected_disk']['device'],
        'binding_hash' => $plan['plan_binding']['binding_hash'],
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
