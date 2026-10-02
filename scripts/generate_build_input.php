<?php

declare(strict_types=1);

use W4\OS\Support\ArtifactMetadataToolkit;

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'inputs';

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $channel = 'testing';
    $imageFormat = 'iso';
    $outputPath = null;

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

            case '--channel':
                $channel = $value;
                break;

            case '--format':
                $imageFormat = $value;
                break;

            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null) {
        throw new ValidationError('Debe indicar un PROFILE_ID usando --profile');
    }

    $toolkit = new ManifestToolkit($rootDir);
    $manifests = $toolkit->loadManifests();
    $toolkit->validateAll($manifests);
    $buildInput = $toolkit->createBuildInput($manifests, $profileId, $channel, $imageFormat);

    if ($outputPath === null) {
        $outputPath = $defaultOutputDir . DIRECTORY_SEPARATOR . $profileId . '.build-input.json';
    }

    $outputDirectory = dirname($outputPath);
    if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputDirectory));
    }

    $json = json_encode($buildInput, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new ValidationError('No se pudo serializar el build input');
    }

    $written = file_put_contents($outputPath, $json . PHP_EOL);
    if ($written === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo de salida: %s', $outputPath));
    }

    $metadataToolkit = new ArtifactMetadataToolkit();

    printJson($metadataToolkit->createSuccessPayload(
        $profileId,
        [
            'output' => $outputPath,
            'release_channel' => $channel,
            'image_format' => $imageFormat,
        ]
    ));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
