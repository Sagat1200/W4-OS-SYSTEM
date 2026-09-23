<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/SecurityBaselineToolkit.php';

$rootDir = dirname(__DIR__);
$defaultProfilesDir = $rootDir . DIRECTORY_SEPARATOR . 'installer-profiles';
$defaultBundleDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'security';

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $installerProfilePath = null;
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

            case '--installer-profile':
                $installerProfilePath = $value;
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

    if ($installerProfilePath === null) {
        $installerProfilePath = $defaultProfilesDir . DIRECTORY_SEPARATOR . $profileId . '.vm-install.json';
    }

    if (!is_file($installerProfilePath)) {
        throw new ValidationError(sprintf('No existe el installation profile: %s', $installerProfilePath));
    }

    if ($bundleDir === null) {
        $bundleDir = $defaultBundleDir . DIRECTORY_SEPARATOR . $profileId;
    }

    $toolkit = new SecurityBaselineToolkit($rootDir);
    $baseline = $toolkit->createBaseline($profileId, $installerProfilePath);
    $result = $toolkit->writeBundle($baseline, $bundleDir);

    printJson($result);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
