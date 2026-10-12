<?php

declare(strict_types=1);

use W4\OS\Support\ValidationError;
use W4\OS\SystemApi\SystemApiDbusBindingToolkit;

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$outputPath = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'org.w4.System1';

try {
    $arguments = $argv ?? [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    removeOutputPath($outputPath);
    if (!mkdir($outputPath, 0777, true) && !is_dir($outputPath)) {
        throw new ValidationError(sprintf('No se pudo crear el directorio %s', $outputPath));
    }

    $toolkit = new SystemApiDbusBindingToolkit();
    $bundle = $toolkit->buildBundle();

    foreach ($bundle['files'] as $relativePath => $contents) {
        $destinationPath = $outputPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $destinationDir = dirname($destinationPath);

        if (!is_dir($destinationDir) && !mkdir($destinationDir, 0777, true) && !is_dir($destinationDir)) {
            throw new ValidationError(sprintf('No se pudo crear el directorio %s', $destinationDir));
        }

        if (file_put_contents($destinationPath, $contents . "\n") === false) {
            throw new ValidationError(sprintf('No se pudo escribir %s', $destinationPath));
        }
    }

    $manifestPath = $outputPath . DIRECTORY_SEPARATOR . 'system-api-dbus-binding-manifest.json';
    $manifestJson = json_encode($bundle['manifest'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($manifestJson === false) {
        throw new ValidationError('No se pudo serializar el manifest del binding System API');
    }

    if (file_put_contents($manifestPath, $manifestJson . "\n") === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $manifestPath));
    }

    printJson([
        'status' => 'ok',
        'kind' => 'system-api-dbus-binding',
        'output_path' => normalizePath($outputPath),
        'manifest_path' => normalizePath($manifestPath),
        'generated_files' => $bundle['manifest']['generated_files'],
        'binding_status' => $bundle['manifest']['binding_status'],
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}

function normalizePath(string $path): string
{
    return str_replace(DIRECTORY_SEPARATOR, '/', $path);
}

function removeOutputPath(string $path): void
{
    if (!file_exists($path)) {
        return;
    }

    if (is_dir($path) && !is_link($path)) {
        $items = scandir($path);
        if ($items === false) {
            throw new ValidationError(sprintf('No se pudo listar la carpeta %s', $path));
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            removeOutputPath($path . DIRECTORY_SEPARATOR . $item);
        }

        if (!rmdir($path)) {
            throw new ValidationError(sprintf('No se pudo eliminar la carpeta %s', $path));
        }

        return;
    }

    if (!unlink($path)) {
        throw new ValidationError(sprintf('No se pudo eliminar %s', $path));
    }
}
