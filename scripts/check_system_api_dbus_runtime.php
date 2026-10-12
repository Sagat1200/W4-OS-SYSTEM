<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Support\ValidationError;
use W4\OS\SystemApi\SystemApiDbusRuntimeProbeToolkit;

$requireReady = false;

try {
    $arguments = $argv ?? [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        switch ($argument) {
            case '--require-ready':
                $requireReady = true;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    $toolkit = new SystemApiDbusRuntimeProbeToolkit();
    $report = $toolkit->createReport();

    printJson($report);

    if ($requireReady && $report['ready'] !== true) {
        exit(2);
    }

    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
