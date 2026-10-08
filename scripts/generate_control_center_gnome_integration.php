<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\ControlCenter\ControlCenterGnomeIntegrationToolkit;
use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
$snapshotDir = null;
$uiBundleDir = null;
$outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-gnome' . DIRECTORY_SEPARATOR . $profileId;

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
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-gnome' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--root-dir':
                $rootDir = $value;
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-gnome' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--snapshot-dir':
                $snapshotDir = $value;
                break;

            case '--ui-bundle-dir':
                $uiBundleDir = $value;
                break;

            case '--output-dir':
                $outputDir = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    ensureDirectory($outputDir);

    $toolkit = new ControlCenterGnomeIntegrationToolkit($rootDir);
    $metadataToolkit = new ArtifactMetadataToolkit();
    $integrationMap = $toolkit->createIntegrationMap($profileId, $snapshotDir, $uiBundleDir);

    $generatedFiles = [];
    writeOutputFile(
        $outputDir,
        'gnome-integration-map.json',
        encodeJson($integrationMap, 'No se pudo serializar el mapa de integracion GNOME'),
        $generatedFiles
    );
    writeOutputFile(
        $outputDir,
        'gnome-integration-summary.txt',
        $toolkit->renderSummaryText($integrationMap),
        $generatedFiles
    );

    $manifest = $metadataToolkit->createManifest(
        'control_center_gnome_bundle_schema_version',
        'control-center-gnome-bundle',
        $profileId,
        null,
        [
            'generated_at' => (string) ($integrationMap['generated_at'] ?? gmdate('c')),
            'strategy' => (string) ($integrationMap['strategy'] ?? 'gnome-augmented'),
            'ui_bundle_role' => (string) ($integrationMap['ui_bundle_role'] ?? 'reference-only'),
            'routes' => is_array($integrationMap['routes'] ?? null) ? $integrationMap['routes'] : [],
            'generated_files' => $metadataToolkit->normalizeGeneratedFiles(array_merge($generatedFiles, ['control-center-gnome-manifest.json'])),
        ]
    );

    $manifestPath = $outputDir . DIRECTORY_SEPARATOR . 'control-center-gnome-manifest.json';
    $manifestContents = encodeJson($manifest, 'No se pudo serializar el manifest de integracion GNOME');
    if (file_put_contents($manifestPath, $manifestContents) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $manifestPath));
    }

    printJson($metadataToolkit->createSuccessPayload(
        $profileId,
        [
            'output_dir' => $outputDir,
            'manifest' => $manifestPath,
            'strategy' => (string) ($integrationMap['strategy'] ?? 'gnome-augmented'),
            'ui_bundle_role' => (string) ($integrationMap['ui_bundle_role'] ?? 'reference-only'),
            'generated_files' => $manifest['generated_files'],
        ]
    ));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}

function ensureDirectory(string $directory): void
{
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new ValidationError(sprintf('No se pudo crear el directorio %s', $directory));
    }
}

function encodeJson(array $payload, string $errorMessage): string
{
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        throw new ValidationError($errorMessage);
    }

    return $encoded . PHP_EOL;
}

/**
 * @param list<string> $generatedFiles
 */
function writeOutputFile(string $outputDir, string $relativePath, string $contents, array &$generatedFiles): void
{
    $absolutePath = $outputDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    $directory = dirname($absolutePath);
    ensureDirectory($directory);

    if (file_put_contents($absolutePath, $contents) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $absolutePath));
    }

    $generatedFiles[] = str_replace(DIRECTORY_SEPARATOR, '/', $relativePath);
}
