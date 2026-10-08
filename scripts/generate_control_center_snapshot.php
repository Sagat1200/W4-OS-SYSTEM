<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\ControlCenter\ControlCenterHomeRenderer;
use W4\OS\ControlCenter\ControlCenterHomeToolkit;
use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
$outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . $profileId;

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
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--root-dir':
                $rootDir = $value;
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--output-dir':
                $outputDir = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    ensureDirectory($outputDir);

    $toolkit = new ControlCenterHomeToolkit($rootDir);
    $renderer = new ControlCenterHomeRenderer();
    $metadataToolkit = new ArtifactMetadataToolkit();

    $homeModel = $toolkit->createHomeModel($profileId);

    $generatedFiles = [];
    $homeJsonRelativePath = 'control-center-home.json';
    $homeTextRelativePath = 'control-center-home.txt';
    writeOutputFile($outputDir, $homeJsonRelativePath, $renderer->encodeJsonPayload($homeModel), $generatedFiles);
    writeOutputFile($outputDir, $homeTextRelativePath, $renderer->renderTextPayload($homeModel), $generatedFiles);

    $modules = is_array($homeModel['modules'] ?? null) ? $homeModel['modules'] : [];
    $moduleManifests = [];

    foreach ($modules as $module) {
        if (!is_array($module)) {
            continue;
        }

        $moduleId = trim((string) ($module['id'] ?? ''));
        if ($moduleId === '') {
            continue;
        }

        $detail = $toolkit->createModuleDetailModel($profileId, $moduleId);
        $jsonRelativePath = sprintf('modules/%s.json', $moduleId);
        $textRelativePath = sprintf('modules/%s.txt', $moduleId);

        writeOutputFile($outputDir, $jsonRelativePath, $renderer->encodeJsonPayload($detail), $generatedFiles);
        writeOutputFile($outputDir, $textRelativePath, $renderer->renderTextPayload($detail), $generatedFiles);

        $entrypoints = is_array($module['entrypoints'] ?? null) ? $module['entrypoints'] : [];
        $moduleManifests[] = [
            'id' => $moduleId,
            'title' => (string) ($module['title'] ?? $moduleId),
            'status' => (string) ($module['status'] ?? 'unknown'),
            'class' => (string) ($module['class'] ?? 'W4-augmented'),
            'json_path' => $jsonRelativePath,
            'text_path' => $textRelativePath,
            'entrypoint_count' => count($entrypoints),
        ];
    }

    $generatedFiles[] = 'control-center-snapshot.json';
    $generatedFiles = $metadataToolkit->normalizeGeneratedFiles($generatedFiles);
    $snapshotManifest = $metadataToolkit->createManifest(
        'control_center_snapshot_schema_version',
        'control-center-snapshot',
        $profileId,
        null,
        [
            'generated_at' => (string) ($homeModel['generated_at'] ?? gmdate('c')),
            'mode' => 'read-first',
            'home' => [
                'json_path' => $homeJsonRelativePath,
                'text_path' => $homeTextRelativePath,
            ],
            'modules' => $moduleManifests,
            'generated_files' => $generatedFiles,
        ]
    );

    $snapshotManifestPath = $outputDir . DIRECTORY_SEPARATOR . 'control-center-snapshot.json';
    $snapshotManifestEncoded = json_encode($snapshotManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($snapshotManifestEncoded === false) {
        throw new ValidationError('No se pudo serializar el snapshot de Control Center');
    }

    if (file_put_contents($snapshotManifestPath, $snapshotManifestEncoded . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $snapshotManifestPath));
    }

    printJson($metadataToolkit->createSuccessPayload(
        $profileId,
        [
            'output_dir' => $outputDir,
            'snapshot_manifest' => $snapshotManifestPath,
            'modules_total' => count($moduleManifests),
            'generated_files' => $generatedFiles,
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
