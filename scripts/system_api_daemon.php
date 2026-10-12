<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Support\ValidationError;
use W4\OS\SystemApi\SystemApiDaemon;

$rootDir = dirname(__DIR__);
$command = 'serve-stdio';
$inputFile = null;
$outputPath = null;

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
            case '--input-file':
                $inputFile = $value;
                break;

            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($command !== 'serve-stdio') {
        throw new ValidationError(sprintf('Comando no soportado: %s', $command));
    }

    $daemon = new SystemApiDaemon($rootDir);

    if ($inputFile === null) {
        $daemon->serveStdio(STDIN, STDOUT);
        exit(0);
    }

    if (!is_file($inputFile)) {
        throw new ValidationError(sprintf('No existe el input file: %s', $inputFile));
    }

    $input = fopen($inputFile, 'rb');
    if ($input === false) {
        throw new ValidationError(sprintf('No se pudo abrir el input file: %s', $inputFile));
    }

    try {
        if ($outputPath === null) {
            $daemon->serveStdio($input, STDOUT);
        } else {
            $directory = dirname($outputPath);
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new ValidationError(sprintf('No se pudo crear el directorio destino: %s', $directory));
            }

            $output = fopen($outputPath, 'wb');
            if ($output === false) {
                throw new ValidationError(sprintf('No se pudo abrir el output file: %s', $outputPath));
            }

            try {
                $daemon->serveStdio($input, $output);
            } finally {
                fclose($output);
            }
        }
    } finally {
        fclose($input);
    }

    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
