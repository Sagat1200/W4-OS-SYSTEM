<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\ControlCenter\ControlCenterHomeToolkit;
use W4\OS\Support\ValidationError;

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
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

            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    $toolkit = new ControlCenterHomeToolkit($rootDir);
    $model = $toolkit->createHomeModel($profileId);

    if ($outputPath !== null) {
        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ValidationError(sprintf('No se pudo crear el directorio destino: %s', $directory));
        }

        $encoded = json_encode($model, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($encoded === false || file_put_contents($outputPath, $encoded . PHP_EOL) === false) {
            throw new ValidationError(sprintf('No se pudo escribir el modelo en %s', $outputPath));
        }
    }

    printJson($model);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
