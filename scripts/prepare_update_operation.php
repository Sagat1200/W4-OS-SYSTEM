<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/UpdateToolkit.php';

$rootDir = dirname(__DIR__);
$defaultStoreRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'operations';

try {
    $arguments = $argv ?? [];
    $planPath = null;
    $storeDir = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--plan':
                $planPath = $value;
                break;

            case '--store-dir':
                $storeDir = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($planPath === null) {
        throw new ValidationError('Debe indicar un archivo usando --plan');
    }

    $toolkit = new UpdateToolkit();
    $plan = $toolkit->readJsonFile($planPath);
    $toolkit->validateUpdatePlan($plan, $planPath);

    if ($storeDir === null) {
        $storeDir = $defaultStoreRoot . DIRECTORY_SEPARATOR . $plan['operation_id'];
    }

    $store = $toolkit->initializeOperationStore($plan, $storeDir);

    printJson([
        'status' => 'ok',
        'store_dir' => $store['store_dir'],
        'operation_id' => $store['operation']['operation_id'],
        'stage' => $store['operation']['stage'],
        'snapshot_name' => $store['operation']['snapshot']['snapshot_name'],
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
