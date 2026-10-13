<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Support\ValidationError;
use W4\OS\SystemApi\SystemApiDbusRuntimeHost;

$rootDir = dirname(__DIR__);
$outputPath = null;
$requestFile = null;
$requestJson = null;
$requestId = null;
$command = 'DescribeHost';

try {
    $arguments = $argv ?? [];

    if (isset($arguments[1]) && $arguments[1] !== '') {
        $command = $arguments[1];
    }

    for ($index = 2, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

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

    $host = new SystemApiDbusRuntimeHost($rootDir);

    $output = match ($command) {
        'DescribeHost' => $host->describeHost(),
        'DescribeSpecification' => $host->describeSpecification(),
        'DescribeRuntime' => $host->describeRuntime(),
        'DiagnoseRuntime' => $host->diagnoseRuntime(),
        'GetCapabilities' => $host->getCapabilities($requestId),
        'Call' => $host->call(resolveRequestJson($requestFile, $requestJson)),
        'serve-dbus' => $host->serveDbus(),
        'serve-stdio-host' => serveHost($host, $outputPath),
        default => throw new ValidationError(sprintf('Comando no soportado: %s', $command)),
    };

    if ($command !== 'serve-stdio-host') {
        if ($outputPath !== null) {
            ensureOutputDirectory($outputPath);
            if (file_put_contents($outputPath, $output) === false) {
                throw new ValidationError(sprintf('No se pudo escribir la respuesta runtime host en %s', $outputPath));
            }
        }

        echo $output;
    }

    if ($command === 'serve-dbus') {
        exit(3);
    }

    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}

function serveHost(SystemApiDbusRuntimeHost $host, ?string $outputPath): string
{
    $output = STDOUT;

    if ($outputPath !== null) {
        ensureOutputDirectory($outputPath);
        $output = fopen($outputPath, 'wb');
        if ($output === false) {
            throw new ValidationError(sprintf('No se pudo abrir el archivo de salida %s', $outputPath));
        }
    }

    try {
        $host->serveStdioHost(STDIN, $output);
    } finally {
        if (is_resource($output) && $output !== STDOUT) {
            fclose($output);
        }
    }

    return '';
}

function ensureOutputDirectory(string $outputPath): void
{
    $directory = dirname($outputPath);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new ValidationError(sprintf('No se pudo crear el directorio destino: %s', $directory));
    }
}

function resolveRequestJson(?string $requestFile, ?string $requestJson): string
{
    if ($requestFile === null && $requestJson === null) {
        throw new ValidationError('Debe indicar --request-file o --request-json para Call');
    }

    if ($requestFile !== null && $requestJson !== null) {
        throw new ValidationError('Use solo una fuente de request: --request-file o --request-json');
    }

    if ($requestJson !== null) {
        return $requestJson;
    }

    if (!is_file($requestFile)) {
        throw new ValidationError(sprintf('No existe el request file: %s', $requestFile));
    }

    $raw = file_get_contents($requestFile);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer el request file: %s', $requestFile));
    }

    return $raw;
}
