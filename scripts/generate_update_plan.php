<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/UpdateToolkit.php';

$rootDir = dirname(__DIR__);
$defaultOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'plans';

try {
    $arguments = $argv ?? [];
    $requestPath = null;
    $outputPath = null;
    $operationId = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--request':
                $requestPath = $value;
                break;

            case '--output':
                $outputPath = $value;
                break;

            case '--operation-id':
                $operationId = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($requestPath === null) {
        throw new ValidationError('Debe indicar un archivo usando --request');
    }

    $toolkit = new UpdateToolkit();
    $request = $toolkit->readJsonFile($requestPath);
    $toolkit->validateUpdateRequest($request, $requestPath);
    $plan = $toolkit->createUpdatePlan($request, $operationId);

    if ($outputPath === null) {
        $outputPath = $defaultOutputDir . DIRECTORY_SEPARATOR . $plan['operation_id'] . '.update-plan.json';
    }

    $outputDirectory = dirname($outputPath);
    if (!is_dir($outputDirectory) && !mkdir($outputDirectory, 0777, true) && !is_dir($outputDirectory)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputDirectory));
    }

    $json = json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new ValidationError('No se pudo serializar el update plan');
    }

    $written = file_put_contents($outputPath, $json . PHP_EOL);
    if ($written === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo de salida: %s', $outputPath));
    }

    printJson([
        'status' => 'ok',
        'output' => $outputPath,
        'operation_id' => $plan['operation_id'],
        'profile_id' => $plan['profile_id'],
        'target_version' => $plan['target_version'],
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
