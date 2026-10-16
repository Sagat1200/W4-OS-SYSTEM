<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Support\ValidationError;
use W4\OS\SystemApi\SystemApiNativeDbusHostValidationToolkit;

$rootDir = dirname(__DIR__);
$evidenceDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'validation' . DIRECTORY_SEPARATOR . 'native-dbus-real-host';
$format = 'json';
$attemptOwnership = true;

try {
    $arguments = $argv ?? [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--root-dir':
                $rootDir = $value;
                break;

            case '--evidence-dir':
                $evidenceDir = $value;
                break;

            case '--format':
                $format = strtolower($value);
                break;

            case '--attempt-ownership':
                $attemptOwnership = match (strtolower($value)) {
                    'yes', 'true', '1', 'on' => true,
                    'no', 'false', '0', 'off' => false,
                    default => throw new ValidationError(sprintf('Valor no soportado para --attempt-ownership: %s', $value)),
                };
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if (!in_array($format, ['json', 'text'], true)) {
        throw new ValidationError(sprintf('Formato no soportado: %s', $format));
    }

    $toolkit = new SystemApiNativeDbusHostValidationToolkit($rootDir);
    $report = $toolkit->runValidation($evidenceDir, $attemptOwnership);

    if ($format === 'text') {
        fwrite(STDOUT, $toolkit->renderSummaryText($report));
    } else {
        printJson($report);
    }

    exit((int) ($report['exit_code'] ?? 3));
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
