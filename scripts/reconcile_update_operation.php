<?php

declare(strict_types=1);

use W4\OS\Support\ArtifactMetadataToolkit;

require_once __DIR__ . '/lib/UpdateToolkit.php';

try {
    $arguments = $argv ?? [];
    $storeDir = null;
    $observedStage = null;
    $component = null;
    $errorCode = null;
    $errorMessage = null;
    $details = [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--store-dir':
                $storeDir = $value;
                break;

            case '--observed-stage':
                $observedStage = $value;
                break;

            case '--component':
                $component = $value;
                break;

            case '--error-code':
                $errorCode = $value;
                break;

            case '--error-message':
                $errorMessage = $value;
                break;

            case '--detail':
                $parts = explode('=', $value, 2);
                if (count($parts) !== 2 || $parts[0] === '') {
                    throw new ValidationError(sprintf('Detalle invalido: %s', $value));
                }
                $details[$parts[0]] = $parts[1];
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($storeDir === null) {
        throw new ValidationError('Debe indicar un STORE_DIR usando --store-dir');
    }

    if ($observedStage === null) {
        throw new ValidationError('Debe indicar un estado usando --observed-stage');
    }

    if ($component === null) {
        throw new ValidationError('Debe indicar un componente usando --component');
    }

    $observedError = null;
    if ($observedStage === 'failed') {
        if ($errorCode === null || $errorMessage === null) {
            throw new ValidationError('Para --observed-stage failed debe indicar --error-code y --error-message');
        }

        $observedError = [
            'code' => $errorCode,
            'message' => $errorMessage,
        ];
    }

    $toolkit = new UpdateToolkit();
    $result = $toolkit->reconcileOperationStore($storeDir, $observedStage, $component, $details, $observedError);

    $metadataToolkit = new ArtifactMetadataToolkit();

    printJson($metadataToolkit->createStatusPayload([
        'store_dir' => $storeDir,
        'reconciled' => $result['reconciled'],
        'operation_id' => $result['operation']['operation_id'],
        'stage' => $result['operation']['stage'],
        'health_status' => $result['health_report']['status'],
    ]));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
