<?php

declare(strict_types=1);

use W4\OS\ControlCenter\ControlCenterLiveOutputToolkit;
use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

require_once __DIR__ . '/bootstrap.php';

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
$liveOutputDir = null;
$format = 'json';

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

            case '--live-output-dir':
                $liveOutputDir = $value;
                break;

            case '--format':
                $format = strtolower($value);
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if (!in_array($format, ['json', 'text'], true)) {
        throw new ValidationError(sprintf('Formato no soportado: %s', $format));
    }

    $toolkit = new ControlCenterLiveOutputToolkit($rootDir);
    $validation = $toolkit->validateMaterializedLiveOutput($profileId, $liveOutputDir);

    if ($format === 'text') {
        fwrite(STDOUT, $toolkit->renderSummaryText($validation));
        exit(0);
    }

    $metadataToolkit = new ArtifactMetadataToolkit();
    printJson($metadataToolkit->createSuccessPayload($profileId, $validation));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
