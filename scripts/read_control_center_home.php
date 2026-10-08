<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\ControlCenter\ControlCenterHomeToolkit;
use W4\OS\ControlCenter\ControlCenterHomeRenderer;
use W4\OS\Support\ValidationError;

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
$outputPath = null;
$format = 'json';
$moduleId = null;

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

            case '--format':
                $format = strtolower($value);
                break;

            case '--module':
                $moduleId = strtolower($value);
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if (!in_array($format, ['json', 'text'], true)) {
        throw new ValidationError('--format debe ser json o text');
    }

    $toolkit = new ControlCenterHomeToolkit($rootDir);
    $renderer = new ControlCenterHomeRenderer();
    $payload = $moduleId !== null
        ? $toolkit->createModuleDetailModel($profileId, $moduleId)
        : $toolkit->createHomeModel($profileId);

    $output = $format === 'json'
        ? $renderer->encodeJsonPayload($payload)
        : $renderer->renderTextPayload($payload);

    if ($outputPath !== null) {
        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ValidationError(sprintf('No se pudo crear el directorio destino: %s', $directory));
        }

        if (file_put_contents($outputPath, $output) === false) {
            throw new ValidationError(sprintf('No se pudo escribir el modelo en %s', $outputPath));
        }
    }

    echo $output;
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
