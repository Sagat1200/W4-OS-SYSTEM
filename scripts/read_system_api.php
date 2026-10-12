<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Support\ValidationError;
use W4\OS\SystemApi\SystemApiToolkit;

$rootDir = dirname(__DIR__);
$outputPath = null;
$requestFile = null;
$requestJson = null;
$requestId = null;
$capabilitiesOnly = false;

try {
    $arguments = $argv ?? [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--capabilities') {
            $capabilitiesOnly = true;
            continue;
        }

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--request-file':
                $requestFile = $value;
                break;

            case '--request-json':
                $requestJson = $value;
                break;

            case '--request-id':
                $requestId = $value;
                break;

            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    $toolkit = new SystemApiToolkit($rootDir);

    if ($capabilitiesOnly) {
        $payload = $toolkit->createCapabilitiesResponse($requestId);
    } else {
        if ($requestFile === null && $requestJson === null) {
            throw new ValidationError('Debe indicar --request-file, --request-json o --capabilities');
        }

        if ($requestFile !== null && $requestJson !== null) {
            throw new ValidationError('Use solo una fuente de request: --request-file o --request-json');
        }

        $request = $requestFile !== null
            ? readRequestFile($requestFile)
            : decodeRequestJson($requestJson ?? '');

        $payload = $toolkit->respond($request);
    }

    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        throw new ValidationError('No se pudo serializar la respuesta System API');
    }

    $output = $encoded . PHP_EOL;

    if ($outputPath !== null) {
        $directory = dirname($outputPath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new ValidationError(sprintf('No se pudo crear el directorio destino: %s', $directory));
        }

        if (file_put_contents($outputPath, $output) === false) {
            throw new ValidationError(sprintf('No se pudo escribir la respuesta API en %s', $outputPath));
        }
    }

    echo $output;
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}

/**
 * @return array<string, mixed>
 */
function readRequestFile(string $path): array
{
    if (!is_file($path)) {
        throw new ValidationError(sprintf('No existe el request file: %s', $path));
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer el request file: %s', $path));
    }

    return decodeRequestJson($raw);
}

/**
 * @return array<string, mixed>
 */
function decodeRequestJson(string $json): array
{
    try {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        throw new ValidationError(sprintf('Request JSON invalido: %s', $exception->getMessage()));
    }

    if (!is_array($decoded)) {
        throw new ValidationError('El request JSON debe decodificar a un objeto');
    }

    return $decoded;
}
