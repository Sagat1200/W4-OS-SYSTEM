<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\ControlCenter\ControlCenterApiToolkit;
use W4\OS\Support\ValidationError;

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
$snapshotDir = null;
$moduleId = null;
$outputPath = null;

try {
    $arguments = $argv ?? [];

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

            case '--root-dir':
                $rootDir = $value;
                break;

            case '--snapshot-dir':
                $snapshotDir = $value;
                break;

            case '--module':
                $moduleId = strtolower($value);
                break;

            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    $toolkit = new ControlCenterApiToolkit($rootDir);
    $payload = $moduleId !== null
        ? $toolkit->createModuleResponse($profileId, $moduleId, $snapshotDir)
        : $toolkit->createHomeResponse($profileId, $snapshotDir);

    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        throw new ValidationError('No se pudo serializar la respuesta API de Control Center');
    }

    $output = $encoded . PHP_EOL;

    if ($outputPath !== null) {
        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ValidationError(sprintf('No se pudo crear el directorio destino: %s', $directory));
        }

        if (file_put_contents($outputPath, $output) === false) {
            throw new ValidationError(sprintf('No se pudo escribir la respuesta API en %s', $outputPath));
        }
    }

    echo $output;
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
