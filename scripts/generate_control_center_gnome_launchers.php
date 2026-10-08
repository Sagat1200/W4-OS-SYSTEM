<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\ControlCenter\ControlCenterGnomeLauncherToolkit;
use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
$snapshotDir = null;
$uiBundleDir = null;
$outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-gnome-launchers' . DIRECTORY_SEPARATOR . $profileId;

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
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-gnome-launchers' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--root-dir':
                $rootDir = $value;
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-gnome-launchers' . DIRECTORY_SEPARATOR . $profileId;
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

    $toolkit = new ControlCenterGnomeLauncherToolkit($rootDir);
    $metadataToolkit = new ArtifactMetadataToolkit();
    $bundle = $toolkit->createLauncherBundle($profileId, $snapshotDir, $uiBundleDir);

    $generatedFiles = [];
    writeOutputFile(
        $outputDir,
        'control-center-gnome-launchers.json',
        encodeJson($bundle, 'No se pudo serializar el bundle de launchers GNOME'),
        $generatedFiles
    );
    writeOutputFile(
        $outputDir,
        'control-center-gnome-launchers-summary.txt',
        $toolkit->renderSummaryText($bundle),
        $generatedFiles
    );

    $launchers = is_array($bundle['launchers'] ?? null) ? $bundle['launchers'] : [];
    foreach ($launchers as $launcher) {
        if (!is_array($launcher)) {
            continue;
        }

        $desktopFile = trim((string) ($launcher['desktop_file'] ?? ''));
        if ($desktopFile === '') {
            continue;
        }

        writeOutputFile(
            $outputDir,
            'overlay-root/usr/share/applications/' . $desktopFile,
            $toolkit->renderDesktopEntry($launcher),
            $generatedFiles
        );
    }

    $manifest = $metadataToolkit->createManifest(
        'control_center_gnome_launchers_bundle_schema_version',
        'control-center-gnome-launchers-bundle',
        $profileId,
        null,
        [
            'generated_at' => (string) ($bundle['generated_at'] ?? gmdate('c')),
            'strategy' => (string) ($bundle['strategy'] ?? 'gnome-augmented'),
            'launcher_count' => count($launchers),
            'pending_module_count' => is_countable($bundle['pending_modules'] ?? null) ? count($bundle['pending_modules']) : 0,
            'generated_files' => $metadataToolkit->normalizeGeneratedFiles(array_merge($generatedFiles, ['control-center-gnome-launchers-manifest.json'])),
        ]
    );

    $manifestPath = $outputDir . DIRECTORY_SEPARATOR . 'control-center-gnome-launchers-manifest.json';
    if (file_put_contents($manifestPath, encodeJson($manifest, 'No se pudo serializar el manifest del bundle de launchers GNOME')) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $manifestPath));
    }

    printJson($metadataToolkit->createSuccessPayload(
        $profileId,
        [
            'output_dir' => $outputDir,
            'manifest' => $manifestPath,
            'strategy' => (string) ($bundle['strategy'] ?? 'gnome-augmented'),
            'launcher_count' => count($launchers),
            'pending_module_count' => is_countable($bundle['pending_modules'] ?? null) ? count($bundle['pending_modules']) : 0,
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
