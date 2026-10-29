<?php

declare(strict_types=1);

use W4\OS\Support\ValidationError;

require_once __DIR__ . '/bootstrap.php';

try {
    $arguments = $argv ?? [];
    $evidenceDir = null;
    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];
        if ($argument === '--evidence-dir' && isset($arguments[$index + 1])) {
            $evidenceDir = $arguments[++$index];
        }
    }
    if (!is_string($evidenceDir) || trim($evidenceDir) === '') {
        throw new ValidationError('Uso: php run_system_api_operations_auth_scope_worker.php --evidence-dir PATH');
    }

    $evidenceDir = rtrim($evidenceDir, "/ \t\r\n");
    if (!is_dir($evidenceDir) && !mkdir($evidenceDir, 0700, true) && !is_dir($evidenceDir)) {
        throw new ValidationError('No se pudo crear evidence-dir');
    }

    $rootDir = dirname(__DIR__);
    $managerScript = $rootDir . '/scripts/system_api_manager.php';

    $storeDir = $evidenceDir . '/store';
    $operationsDir = $storeDir . '/operations';
    $ledgerDir = $storeDir . '/ledger';
    $requestsDir = $evidenceDir . '/requests';

    foreach ([$operationsDir, $ledgerDir, $requestsDir] as $dir) {
        if (!is_dir($dir) && !mkdir($dir, 0700, true) && !is_dir($dir)) {
            throw new ValidationError('No se pudo preparar el directorio: ' . $dir);
        }
    }

    $policy = [
        'tokens' => [
            'token-a' => [
                'principal_scope' => 'scope-a',
                'permissions' => ['org.w4.operations.read', 'org.w4.operations.cancel'],
            ],
            'token-b' => [
                'principal_scope' => 'scope-b',
                'permissions' => ['org.w4.operations.read', 'org.w4.operations.cancel'],
            ],
            'admin' => [
                'principal_scope' => 'admin',
                'permissions' => ['*'],
            ],
        ],
    ];

    putenv('W4_SYSTEM_API_AUTH_MODE=required');
    putenv('W4_SYSTEM_API_AUTH_POLICY_JSON=' . json_encode($policy, JSON_THROW_ON_ERROR));
    putenv('W4_SYSTEM_API_OPERATIONS_STORE_DIR=' . $operationsDir);
    putenv('W4_SYSTEM_API_OPERATIONS_LEDGER_DIR=' . $ledgerDir);

    $operationId = 'op-' . bin2hex(random_bytes(4));
    $operationPath = $operationsDir . '/' . $operationId . '.json';
    $operationPayload = [
        'kind' => 'system-api-operation',
        'operation_id' => $operationId,
        'principal_scope' => 'scope-a',
        'method' => 'updates.apply',
        'resource' => ['kind' => 'update-operation', 'id' => $operationId],
        'state' => 'running',
        'status' => 'ok',
        'cancel_requested' => false,
        'cancelable' => true,
        'progress' => ['percent' => 50, 'current_step' => 'running'],
        'result' => ['kind' => 'system-api-operation-result'],
        'error' => null,
        'created_at' => gmdate('c'),
        'observed_at' => gmdate('c'),
        'completed_at' => null,
    ];
    if (file_put_contents($operationPath, json_encode($operationPayload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n") === false) {
        throw new ValidationError('No se pudo crear la operacion fixture');
    }
    $operationOriginal = (string) file_get_contents($operationPath);

    $commands = [];
    $commands['get-capabilities'] = runSystemApiManager($managerScript, $requestsDir, 'get-capabilities', null);
    $commands['get-owner'] = runSystemApiManager($managerScript, $requestsDir, 'operations.get', [
        'api_version' => '1.0',
        'request_id' => 'ops-get-owner',
        'method' => 'operations.get',
        'authorization' => ['token' => 'token-a'],
        'params' => ['operation_id' => $operationId],
    ]);
    $commands['get-other'] = runSystemApiManager($managerScript, $requestsDir, 'operations.get', [
        'api_version' => '1.0',
        'request_id' => 'ops-get-other',
        'method' => 'operations.get',
        'authorization' => ['token' => 'token-b'],
        'params' => ['operation_id' => $operationId],
    ]);
    $commands['get-admin'] = runSystemApiManager($managerScript, $requestsDir, 'operations.get', [
        'api_version' => '1.0',
        'request_id' => 'ops-get-admin',
        'method' => 'operations.get',
        'authorization' => ['token' => 'admin'],
        'params' => ['operation_id' => $operationId],
    ]);
    $commands['cancel-other'] = runSystemApiManager($managerScript, $requestsDir, 'operations.cancel', [
        'api_version' => '1.0',
        'request_id' => 'ops-cancel-other',
        'method' => 'operations.cancel',
        'authorization' => ['token' => 'token-b'],
        'params' => ['operation_id' => $operationId],
    ]);
    $operationAfterOther = (string) file_get_contents($operationPath);
    $commands['cancel-owner'] = runSystemApiManager($managerScript, $requestsDir, 'operations.cancel', [
        'api_version' => '1.0',
        'request_id' => 'ops-cancel-owner',
        'method' => 'operations.cancel',
        'authorization' => ['token' => 'token-a'],
        'params' => ['operation_id' => $operationId],
    ]);

    $operationAfterOwner = (string) file_get_contents($operationPath);

    $evidence = [
        'kind' => 'system-api-ops-auth-scope-wsl-worker',
        'evidence_dir' => $evidenceDir,
        'operation_id' => $operationId,
        'policy' => $policy,
        'commands' => $commands,
        'operation_original' => $operationOriginal,
        'operation_after_other' => $operationAfterOther,
        'operation_after_owner' => $operationAfterOwner,
    ];

    $validation = validateSystemApiOperationsAuthScopeEvidence($evidence);
    $report = $validation + ['evidence' => $evidence];
    file_put_contents($evidenceDir . '/report.json', json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

    echo json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    exit(($report['validated'] ?? false) === true ? 0 : 3);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}

function runSystemApiManager(string $managerScript, string $requestsDir, string $label, ?array $request): array
{
    $requestPath = null;
    if ($request !== null) {
        $requestPath = $requestsDir . '/' . $label . '.json';
        file_put_contents($requestPath, json_encode($request, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }

    $command = [
        PHP_BINARY,
        $managerScript,
        $request === null ? 'get-capabilities' : 'call',
    ];
    if ($request === null) {
        $command[] = '--request-id';
        $command[] = $label;
    } else {
        $command[] = '--request-file';
        $command[] = (string) $requestPath;
    }

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        return [
            'command' => $command,
            'exit_code' => 1,
            'stdout' => '',
            'stderr' => 'proc_open failed',
        ];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'command' => $command,
        'exit_code' => $exitCode,
        'stdout' => is_string($stdout) ? $stdout : '',
        'stderr' => is_string($stderr) ? $stderr : '',
    ];
}

function decodeSystemApiResponse(array $commandResult): ?array
{
    if (!is_string($commandResult['stdout'] ?? null) || trim((string) $commandResult['stdout']) === '') {
        return null;
    }

    try {
        $decoded = json_decode((string) $commandResult['stdout'], true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

function validateSystemApiOperationsAuthScopeEvidence(array $evidence): array
{
    $commands = is_array($evidence['commands'] ?? null) ? $evidence['commands'] : [];
    $operationId = is_string($evidence['operation_id'] ?? null) ? trim((string) $evidence['operation_id']) : '';

    $capabilities = decodeSystemApiResponse($commands['get-capabilities'] ?? []);
    $getOwner = decodeSystemApiResponse($commands['get-owner'] ?? []);
    $getOther = decodeSystemApiResponse($commands['get-other'] ?? []);
    $getAdmin = decodeSystemApiResponse($commands['get-admin'] ?? []);
    $cancelOther = decodeSystemApiResponse($commands['cancel-other'] ?? []);
    $cancelOwner = decodeSystemApiResponse($commands['cancel-owner'] ?? []);

    $checks = [];
    $checks['capabilities_permission'] = is_array($capabilities)
        && is_array($capabilities['result']['data']['methods'] ?? null)
        && in_array('org.w4.operations.read', array_column($capabilities['result']['data']['methods'], 'permission'), true);
    $checks['owner_get_ok'] = is_array($getOwner)
        && ($getOwner['error'] ?? null) === null
        && is_array($getOwner['result']['data'] ?? null)
        && (($getOwner['result']['data']['operation_id'] ?? null) === $operationId);
    $checks['other_get_not_found'] = is_array($getOther)
        && ($getOther['result'] ?? null) === null
        && is_array($getOther['error'] ?? null)
        && (($getOther['error']['code'] ?? null) === 'NOT_FOUND');
    $checks['admin_get_ok'] = is_array($getAdmin)
        && ($getAdmin['error'] ?? null) === null
        && is_array($getAdmin['result']['data'] ?? null)
        && (($getAdmin['result']['data']['operation_id'] ?? null) === $operationId);
    $checks['other_cancel_not_found'] = is_array($cancelOther)
        && ($cancelOther['result'] ?? null) === null
        && is_array($cancelOther['error'] ?? null)
        && (($cancelOther['error']['code'] ?? null) === 'NOT_FOUND');
    $checks['owner_cancel_ok'] = is_array($cancelOwner)
        && ($cancelOwner['error'] ?? null) === null
        && is_array($cancelOwner['result']['data'] ?? null)
        && (($cancelOwner['result']['data']['cancel_requested'] ?? null) === true);
    $checks['cancel_other_no_effect'] = ($evidence['operation_original'] ?? null) === ($evidence['operation_after_other'] ?? null);
    $checks['cancel_owner_mutates'] = ($evidence['operation_original'] ?? null) !== ($evidence['operation_after_owner'] ?? null);

    return [
        'validated' => !in_array(false, $checks, true),
        'checks' => $checks,
    ];
}
