<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Home\HomeOnboardingLightUiToolkit;
use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
$liveOutputDir = null;
$outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'home-onboarding-ui' . DIRECTORY_SEPARATOR . $profileId;

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
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'home-onboarding-ui' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--root-dir':
                $rootDir = $value;
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'home-onboarding-ui' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--live-output-dir':
                $liveOutputDir = $value;
                break;

            case '--output-dir':
                $outputDir = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    ensureDirectory($outputDir);

    $toolkit = new HomeOnboardingLightUiToolkit($rootDir);
    $metadataToolkit = new ArtifactMetadataToolkit();
    $bundleResult = $toolkit->createBundle($profileId, $liveOutputDir);
    $bundle = is_array($bundleResult['bundle'] ?? null) ? $bundleResult['bundle'] : [];

    $generatedFiles = [];
    writeOutputFile($outputDir, 'index.html', (string) ($bundleResult['html'] ?? ''), $generatedFiles);
    writeOutputFile($outputDir, 'styles/home-onboarding.css', $toolkit->renderStylesheet(), $generatedFiles);
    writeOutputFile(
        $outputDir,
        'home-onboarding-ui.json',
        encodeJson($bundle, 'No se pudo serializar el bundle UI de onboarding Home'),
        $generatedFiles
    );

    $manifest = $metadataToolkit->createManifest(
        'home_onboarding_light_ui_bundle_schema_version',
        'home-onboarding-light-ui-bundle-manifest',
        $profileId,
        null,
        [
            'generated_at' => (string) ($bundle['generated_at'] ?? gmdate('c')),
            'source_kind' => (string) ($bundle['kind'] ?? 'home-onboarding-light-ui-bundle'),
            'entry_resource' => 'index.html',
            'visible_step_count' => is_countable($bundle['visible_steps'] ?? null) ? count($bundle['visible_steps']) : 0,
            'deferred_step_count' => is_countable($bundle['deferred_steps'] ?? null) ? count($bundle['deferred_steps']) : 0,
            'routes' => [
                'home' => 'index.html',
                'data' => 'home-onboarding-ui.json',
            ],
            'generated_files' => $metadataToolkit->normalizeGeneratedFiles(array_merge($generatedFiles, ['home-onboarding-ui-manifest.json'])),
        ]
    );

    $manifestPath = $outputDir . DIRECTORY_SEPARATOR . 'home-onboarding-ui-manifest.json';
    if (file_put_contents($manifestPath, encodeJson($manifest, 'No se pudo serializar el manifest del bundle UI de onboarding Home')) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $manifestPath));
    }

    printJson($metadataToolkit->createSuccessPayload(
        $profileId,
        [
            'output_dir' => $outputDir,
            'manifest' => $manifestPath,
            'routes' => $manifest['routes'],
            'visible_step_count' => $manifest['visible_step_count'],
            'deferred_step_count' => $manifest['deferred_step_count'],
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

function encodeJson(array $data, string $errorMessage): string
{
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        throw new ValidationError($errorMessage);
    }

    return $encoded . PHP_EOL;
}
