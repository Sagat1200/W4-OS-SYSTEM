<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiOperationsOwnershipCliTest extends TestCase
{
    public function testOperationsGetIsScopedByPrincipalScope(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-ops-scope-' . bin2hex(random_bytes(6));
        $operationsDir = $base . DIRECTORY_SEPARATOR . 'operations';
        $ledgerDir = $base . DIRECTORY_SEPARATOR . 'ledger';

        mkdir($operationsDir, 0777, true);
        mkdir($ledgerDir, 0777, true);

        $policy = json_encode([
            'tokens' => [
                'token-a' => [
                    'principal_scope' => 'scope-a',
                    'permissions' => ['org.w4.operations.read'],
                ],
                'token-b' => [
                    'principal_scope' => 'scope-b',
                    'permissions' => ['org.w4.operations.read'],
                ],
                'admin' => [
                    'principal_scope' => 'admin',
                    'permissions' => ['*'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $operationId = 'op-' . bin2hex(random_bytes(4));
        file_put_contents($operationsDir . DIRECTORY_SEPARATOR . $operationId . '.json', json_encode([
            'kind' => 'system-api-operation',
            'operation_id' => $operationId,
            'principal_scope' => 'scope-a',
            'method' => 'packages.apply',
            'resource' => ['kind' => 'package-operation', 'id' => 'demo'],
            'state' => 'queued',
            'status' => 'ok',
            'cancel_requested' => false,
            'cancelable' => false,
            'progress' => ['percent' => 5, 'current_step' => 'queued'],
            'result' => ['kind' => 'system-api-operation-result'],
            'error' => null,
            'created_at' => gmdate('c'),
            'observed_at' => gmdate('c'),
            'completed_at' => null,
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        try {
            $payload = $this->callManager([
                'api_version' => '1.0',
                'request_id' => 'ops-get-001',
                'method' => 'operations.get',
                'authorization' => ['token' => 'token-a'],
                'params' => ['operation_id' => $operationId],
            ], $policy, $operationsDir, $ledgerDir);
            self::assertNull($payload['error']);
            self::assertSame('operations.get', $payload['result']['method']);
            self::assertSame($operationId, $payload['result']['data']['operation_id']);

            $payload = $this->callManager([
                'api_version' => '1.0',
                'request_id' => 'ops-get-002',
                'method' => 'operations.get',
                'authorization' => ['token' => 'token-b'],
                'params' => ['operation_id' => $operationId],
            ], $policy, $operationsDir, $ledgerDir);
            self::assertNull($payload['result']);
            self::assertSame('NOT_FOUND', $payload['error']['code']);

            $payload = $this->callManager([
                'api_version' => '1.0',
                'request_id' => 'ops-get-003',
                'method' => 'operations.get',
                'authorization' => ['token' => 'admin'],
                'params' => ['operation_id' => $operationId],
            ], $policy, $operationsDir, $ledgerDir);
            self::assertNull($payload['error']);
            self::assertSame($operationId, $payload['result']['data']['operation_id']);
        } finally {
            $this->rmTree($base);
        }
    }

    public function testOperationsCancelIsScopedByPrincipalScope(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-ops-cancel-' . bin2hex(random_bytes(6));
        $operationsDir = $base . DIRECTORY_SEPARATOR . 'operations';
        $ledgerDir = $base . DIRECTORY_SEPARATOR . 'ledger';

        mkdir($operationsDir, 0777, true);
        mkdir($ledgerDir, 0777, true);

        $policy = json_encode([
            'tokens' => [
                'token-a' => [
                    'principal_scope' => 'scope-a',
                    'permissions' => ['org.w4.operations.cancel', 'org.w4.operations.read'],
                ],
                'token-b' => [
                    'principal_scope' => 'scope-b',
                    'permissions' => ['org.w4.operations.cancel', 'org.w4.operations.read'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $operationId = 'op-' . bin2hex(random_bytes(4));
        $operationPath = $operationsDir . DIRECTORY_SEPARATOR . $operationId . '.json';
        file_put_contents($operationPath, json_encode([
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
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $original = (string) file_get_contents($operationPath);

        try {
            $payload = $this->callManager([
                'api_version' => '1.0',
                'request_id' => 'ops-cancel-001',
                'method' => 'operations.cancel',
                'authorization' => ['token' => 'token-b'],
                'params' => ['operation_id' => $operationId],
            ], $policy, $operationsDir, $ledgerDir);
            self::assertNull($payload['result']);
            self::assertSame('NOT_FOUND', $payload['error']['code']);
            self::assertSame($original, (string) file_get_contents($operationPath));

            $payload = $this->callManager([
                'api_version' => '1.0',
                'request_id' => 'ops-cancel-002',
                'method' => 'operations.cancel',
                'authorization' => ['token' => 'token-a'],
                'params' => ['operation_id' => $operationId],
            ], $policy, $operationsDir, $ledgerDir);
            self::assertNull($payload['error']);
            self::assertSame('operations.cancel', $payload['result']['method']);
            self::assertTrue($payload['result']['data']['cancel_requested']);
        } finally {
            $this->rmTree($base);
        }
    }

    private function callManager(array $request, string $policy, string $operationsDir, string $ledgerDir): array
    {
        $requestPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-request-' . bin2hex(random_bytes(6)) . '.json';
        file_put_contents($requestPath, json_encode($request, JSON_THROW_ON_ERROR));

        try {
            $result = $this->runPhpScript([
                $this->scriptPath(),
                'call',
                '--request-file',
                $requestPath,
            ], [
                'W4_SYSTEM_API_AUTH_MODE' => 'required',
                'W4_SYSTEM_API_AUTH_POLICY_JSON' => $policy,
                'W4_SYSTEM_API_OPERATIONS_STORE_DIR' => $operationsDir,
                'W4_SYSTEM_API_OPERATIONS_LEDGER_DIR' => $ledgerDir,
            ]);
        } finally {
            @unlink($requestPath);
        }

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        return $this->decodeJson($result['stdout']);
    }

    private function runPhpScript(array $command, array $environment = []): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $processEnvironment = array_merge($_ENV, $environment);
        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__), $processEnvironment);
        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    private function decodeJson(string $json): array
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_manager.php';
    }

    private function rmTree(string $path): void
    {
        if (!is_dir($path)) {
            if (is_file($path)) {
                unlink($path);
            }
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
                continue;
            }
            unlink($item->getPathname());
        }

        rmdir($path);
    }
}
