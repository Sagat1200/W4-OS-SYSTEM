<?php

declare(strict_types=1);

function fail(string $message): void
{
    fwrite(STDERR, sprintf("ERROR: %s\n", $message));
    exit(1);
}

/**
 * @return array<string, mixed>
 */
function readJsonObject(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        fail(sprintf('No se pudo leer el archivo JSON: %s', $path));
    }

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        fail(sprintf('JSON invalido en %s: %s', $path, $exception->getMessage()));
    }

    if (!is_array($data)) {
        fail(sprintf('JSON invalido en %s: la raiz debe ser un objeto o arreglo JSON', $path));
    }

    return $data;
}

/**
 * @param array<string, mixed> $data
 */
function writeJsonObject(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        fail(sprintf('No se pudo serializar el archivo JSON: %s', $path));
    }

    $written = file_put_contents($path, $json . PHP_EOL);
    if ($written === false) {
        fail(sprintf('No se pudo escribir el archivo: %s', $path));
    }
}

function nextEventSequence(string $path): int
{
    if (!is_file($path)) {
        return 1;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        fail(sprintf('No se pudo leer el log de eventos: %s', $path));
    }

    return count($lines) + 1;
}

/**
 * @param array<string, mixed> $event
 */
function appendEvent(string $path, array $event): void
{
    $event['sequence'] = nextEventSequence($path);
    $event['timestamp'] = gmdate('c');
    $json = json_encode($event, JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        fail(sprintf('No se pudo serializar el evento en %s', $path));
    }

    $written = file_put_contents($path, $json . PHP_EOL, FILE_APPEND);
    if ($written === false) {
        fail(sprintf('No se pudo escribir el log de eventos: %s', $path));
    }
}

/**
 * @param array<string, mixed> $operation
 * @param array<string, mixed>|null $lastError
 * @return array<string, mixed>
 */
function transitionOperation(array $operation, string $nextStage, ?array $lastError = null): array
{
    $currentStage = $operation['stage'] ?? null;
    if (!is_string($currentStage) || $currentStage === '') {
        fail('operation.stage debe ser un string no vacio');
    }

    $allowedTransitions = [
        'planned' => ['downloading', 'failed'],
        'downloading' => ['ready', 'failed'],
        'ready' => ['prepared', 'failed'],
        'prepared' => ['applying_offline', 'failed'],
        'applying_offline' => ['pending_health', 'failed'],
        'pending_health' => ['confirmed', 'failed'],
        'confirmed' => [],
        'failed' => [],
    ];

    $allowedNextStages = $allowedTransitions[$currentStage] ?? null;
    if ($allowedNextStages === null) {
        fail(sprintf('Estado actual no soportado: %s', $currentStage));
    }

    if (!in_array($nextStage, $allowedNextStages, true)) {
        fail(sprintf('Transicion no valida desde %s hacia %s', $currentStage, $nextStage));
    }

    $operation['stage'] = $nextStage;
    $operation['last_error'] = $nextStage === 'failed' ? $lastError : null;
    /** @var array<string, mixed> $timestamps */
    $timestamps = is_array($operation['timestamps'] ?? null) ? $operation['timestamps'] : [];
    $timestamps['updated_at'] = gmdate('c');
    $operation['timestamps'] = $timestamps;

    return $operation;
}

function resolveReconciledStage(string $currentStage, string $observedStage): string
{
    if ($observedStage === 'failed') {
        return 'failed';
    }

    if ($observedStage === $currentStage) {
        return $currentStage;
    }

    $reconcilable = [
        'applying_offline' => ['pending_health', 'failed'],
        'pending_health' => ['confirmed', 'failed'],
        'confirmed' => ['confirmed'],
        'failed' => ['failed'],
    ];

    $allowed = $reconcilable[$currentStage] ?? [];
    if (!in_array($observedStage, $allowed, true)) {
        fail(sprintf('No se puede reconciliar %s con observed_stage=%s', $currentStage, $observedStage));
    }

    return $observedStage;
}

/**
 * @param array<string, mixed> $plan
 * @param array<string, mixed> $operation
 * @param array<string, mixed>|null $lastError
 * @return array<string, mixed>
 */
function buildHealthReport(array $plan, array $operation, ?array $lastError = null): array
{
    $requiredChecks = $plan['health_check_manifest']['required'] ?? null;
    $testFilePath = $plan['health_check_manifest']['test_file_path'] ?? null;

    if (!is_array($requiredChecks) || !is_string($testFilePath) || $testFilePath === '') {
        fail('update-plan.json no contiene health_check_manifest valido');
    }

    $stage = (string) ($operation['stage'] ?? '');
    $status = 'pending';
    if ($stage === 'confirmed') {
        $status = 'ok';
    } elseif ($stage === 'failed') {
        $status = 'failed';
    }

    return [
        'health_report_schema_version' => 1,
        'kind' => 'health-report',
        'operation_id' => $operation['operation_id'] ?? 'unknown-operation',
        'stage' => $stage,
        'status' => $status,
        'required_checks' => $requiredChecks,
        'test_file_path' => $testFilePath,
        'last_error' => $lastError,
        'updated_at' => gmdate('c'),
    ];
}

/**
 * @param array<string, mixed> $payload
 */
function printJsonPayload(array $payload): void
{
    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        fail('No se pudo serializar el payload de salida');
    }

    fwrite(STDOUT, $json . PHP_EOL);
}

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
            fail(sprintf('Falta el valor para %s', $argument));
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
                    fail(sprintf('Detalle invalido: %s', $value));
                }
                $details[$parts[0]] = $parts[1];
                break;

            default:
                fail(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($storeDir === null || trim($storeDir) === '') {
        fail('Debe indicar un STORE_DIR usando --store-dir');
    }

    if ($observedStage === null || trim($observedStage) === '') {
        fail('Debe indicar un estado usando --observed-stage');
    }

    if ($component === null || trim($component) === '') {
        fail('Debe indicar un componente usando --component');
    }

    $operationStages = [
        'planned',
        'downloading',
        'ready',
        'prepared',
        'applying_offline',
        'pending_health',
        'confirmed',
        'failed',
    ];
    if (!in_array($observedStage, $operationStages, true)) {
        fail(sprintf('observed_stage no soportado: %s', $observedStage));
    }

    $observedError = null;
    if ($observedStage === 'failed') {
        if ($errorCode === null || $errorMessage === null) {
            fail('Para --observed-stage failed debe indicar --error-code y --error-message');
        }

        $observedError = [
            'code' => $errorCode,
            'message' => $errorMessage,
        ];
    }

    $plan = readJsonObject($storeDir . DIRECTORY_SEPARATOR . 'update-plan.json');
    $operation = readJsonObject($storeDir . DIRECTORY_SEPARATOR . 'operation.json');
    $healthReport = readJsonObject($storeDir . DIRECTORY_SEPARATOR . 'health-report.json');
    $eventsPath = $storeDir . DIRECTORY_SEPARATOR . 'events.ndjson';

    $currentStage = $operation['stage'] ?? null;
    if (!is_string($currentStage) || $currentStage === '') {
        fail('operation.json no contiene operation.stage valido');
    }

    $targetStage = resolveReconciledStage($currentStage, $observedStage);
    $lastError = $targetStage === 'failed' ? $observedError : null;

    if ($targetStage === $currentStage) {
        appendEvent(
            $eventsPath,
            [
                'event_schema_version' => 1,
                'event_type' => 'operation-reconciled',
                'operation_id' => $operation['operation_id'] ?? 'unknown-operation',
                'stage' => $currentStage,
                'component' => $component,
                'outcome' => $targetStage === 'failed' ? 'error' : 'ok',
                'details' => array_merge(
                    $details,
                    [
                        'observed_stage' => $observedStage,
                        'reconciled' => false,
                    ]
                ),
                'last_error' => $lastError,
            ]
        );

        printJsonPayload([
            'status' => 'ok',
            'store_dir' => $storeDir,
            'reconciled' => false,
            'operation_id' => $operation['operation_id'] ?? 'unknown-operation',
            'stage' => $currentStage,
            'health_status' => $healthReport['status'] ?? 'pending',
        ]);
        exit(0);
    }

    $updatedOperation = transitionOperation($operation, $targetStage, $lastError);
    $updatedHealthReport = buildHealthReport($plan, $updatedOperation, $lastError);

    writeJsonObject($storeDir . DIRECTORY_SEPARATOR . 'operation.json', $updatedOperation);
    writeJsonObject($storeDir . DIRECTORY_SEPARATOR . 'health-report.json', $updatedHealthReport);
    appendEvent(
        $eventsPath,
        [
            'event_schema_version' => 1,
            'event_type' => 'operation-reconciled',
            'operation_id' => $updatedOperation['operation_id'] ?? 'unknown-operation',
            'stage' => $updatedOperation['stage'] ?? $targetStage,
            'component' => $component,
            'outcome' => $targetStage === 'failed' ? 'error' : 'ok',
            'details' => array_merge(
                $details,
                [
                    'observed_stage' => $observedStage,
                    'reconciled' => true,
                ]
            ),
            'last_error' => $lastError,
        ]
    );

    printJsonPayload([
        'status' => 'ok',
        'store_dir' => $storeDir,
        'reconciled' => true,
        'operation_id' => $updatedOperation['operation_id'] ?? 'unknown-operation',
        'stage' => $updatedOperation['stage'] ?? $targetStage,
        'health_status' => $updatedHealthReport['status'] ?? 'pending',
    ]);
} catch (Throwable $throwable) {
    fail($throwable->getMessage());
}
