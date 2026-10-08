<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\ControlCenter\ControlCenterApiToolkit;
use W4\OS\ControlCenter\ControlCenterUiToolkit;
use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

$profileId = 'w4-os-home';
$rootDir = dirname(__DIR__);
$snapshotDir = null;
$outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-ui' . DIRECTORY_SEPARATOR . $profileId;

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
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-ui' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--root-dir':
                $rootDir = $value;
                $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-ui' . DIRECTORY_SEPARATOR . $profileId;
                break;

            case '--snapshot-dir':
                $snapshotDir = $value;
                break;

            case '--output-dir':
                $outputDir = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    ensureDirectory($outputDir);

    $uiToolkit = new ControlCenterUiToolkit($rootDir);
    $apiToolkit = new ControlCenterApiToolkit($rootDir);
    $metadataToolkit = new ArtifactMetadataToolkit();

    $homePage = $uiToolkit->createHomePage($profileId, $snapshotDir);
    $homeResponse = is_array($homePage['response'] ?? null) ? $homePage['response'] : [];
    $homeData = is_array($homeResponse['data'] ?? null) ? $homeResponse['data'] : [];
    $modules = is_array($homeData['modules'] ?? null) ? $homeData['modules'] : [];

    $generatedFiles = [];
    writeOutputFile($outputDir, 'index.html', (string) ($homePage['html'] ?? ''), $generatedFiles);
    writeOutputFile($outputDir, 'styles/control-center.css', $uiToolkit->renderStylesheet(), $generatedFiles);

    $routes = [
        'home' => 'index.html',
        'modules' => [],
    ];

    foreach ($modules as $module) {
        if (!is_array($module)) {
            continue;
        }

        $moduleId = trim((string) ($module['id'] ?? ''));
        if ($moduleId === '') {
            continue;
        }

        $modulePage = $uiToolkit->createModulePage($profileId, $moduleId, $snapshotDir);
        $route = (string) ($modulePage['route'] ?? sprintf('modules/%s.html', $moduleId));
        writeOutputFile($outputDir, $route, (string) ($modulePage['html'] ?? ''), $generatedFiles);
        $routes['modules'][$moduleId] = $route;
    }

    $apiResponse = $apiToolkit->createHomeResponse($profileId, $snapshotDir);
    $manifest = $metadataToolkit->createManifest(
        'control_center_ui_bundle_schema_version',
        'control-center-ui-bundle',
        $profileId,
        null,
        [
            'generated_at' => (string) ($apiResponse['generated_at'] ?? gmdate('c')),
            'mode' => (string) ($apiResponse['mode'] ?? 'read-first'),
            'source_kind' => (string) ($apiResponse['kind'] ?? 'control-center-api-response'),
            'entry_resource' => (string) ($apiResponse['resource'] ?? 'home'),
            'routes' => $routes,
            'generated_files' => $metadataToolkit->normalizeGeneratedFiles(array_merge($generatedFiles, ['control-center-ui-manifest.json'])),
        ]
    );

    $manifestPath = $outputDir . DIRECTORY_SEPARATOR . 'control-center-ui-manifest.json';
    $encodedManifest = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($encodedManifest === false) {
        throw new ValidationError('No se pudo serializar el manifest del bundle visual de Control Center');
    }

    if (file_put_contents($manifestPath, $encodedManifest . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $manifestPath));
    }

    printJson($metadataToolkit->createSuccessPayload(
        $profileId,
        [
            'output_dir' => $outputDir,
            'manifest' => $manifestPath,
            'routes' => $routes,
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
