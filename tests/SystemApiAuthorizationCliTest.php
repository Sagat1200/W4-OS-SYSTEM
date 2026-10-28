<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiAuthorizationCliTest extends TestCase
{
    public function testDeniesMutationWithoutTokenAndDoesNotWriteOperations(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-auth-' . bin2hex(random_bytes(6));
        $operationsDir = $base . DIRECTORY_SEPARATOR . 'operations';
        $ledgerDir = $base . DIRECTORY_SEPARATOR . 'ledger';
        $requestPath = $base . DIRECTORY_SEPARATOR . 'request.json';

        mkdir($operationsDir, 0777, true);
        mkdir($ledgerDir, 0777, true);

        $policy = json_encode([
            'tokens' => [
                'allow-packages' => [
                    'principal_scope' => 'tests-packages',
                    'permissions' => ['org.w4.packages.modify'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            file_put_contents($requestPath, json_encode([
                'api_version' => '1.0',
                'request_id' => 'auth-deny-001',
                'method' => 'packages.apply',
                'params' => [
                    'plan_id' => 'p-001',
                    'plan_hash' => 'h-001',
                    'idempotency_key' => 'k-001',
                ],
            ], JSON_THROW_ON_ERROR));

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

            self::assertSame(0, $result['exitCode'], $result['stderr']);
            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['result']);
            self::assertSame('DENIED', $payload['error']['code']);
            self::assertSame([], array_values(array_diff(scandir($operationsDir) ?: [], ['.', '..'])));
            self::assertSame([], array_values(array_diff(scandir($ledgerDir) ?: [], ['.', '..'])));
        } finally {
            $this->rmTree($base);
        }
    }

    public function testDeniesMutationWhenTokenHasNoPermission(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-auth-' . bin2hex(random_bytes(6));
        $operationsDir = $base . DIRECTORY_SEPARATOR . 'operations';
        $ledgerDir = $base . DIRECTORY_SEPARATOR . 'ledger';
        $requestPath = $base . DIRECTORY_SEPARATOR . 'request.json';

        mkdir($operationsDir, 0777, true);
        mkdir($ledgerDir, 0777, true);

        $policy = json_encode([
            'tokens' => [
                'allow-nothing' => [
                    'principal_scope' => 'tests-none',
                    'permissions' => [],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            file_put_contents($requestPath, json_encode([
                'api_version' => '1.0',
                'request_id' => 'auth-deny-002',
                'method' => 'packages.apply',
                'authorization' => ['token' => 'allow-nothing'],
                'params' => [
                    'plan_id' => 'p-001',
                    'plan_hash' => 'h-001',
                    'idempotency_key' => 'k-001',
                ],
            ], JSON_THROW_ON_ERROR));

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

            self::assertSame(0, $result['exitCode'], $result['stderr']);
            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['result']);
            self::assertSame('DENIED', $payload['error']['code']);
            self::assertSame([], array_values(array_diff(scandir($operationsDir) ?: [], ['.', '..'])));
            self::assertSame([], array_values(array_diff(scandir($ledgerDir) ?: [], ['.', '..'])));
        } finally {
            $this->rmTree($base);
        }
    }

    public function testAllowsAuthorizedTokenToReachValidation(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-auth-' . bin2hex(random_bytes(6));
        $operationsDir = $base . DIRECTORY_SEPARATOR . 'operations';
        $ledgerDir = $base . DIRECTORY_SEPARATOR . 'ledger';
        $requestPath = $base . DIRECTORY_SEPARATOR . 'request.json';

        mkdir($operationsDir, 0777, true);
        mkdir($ledgerDir, 0777, true);

        $policy = json_encode([
            'tokens' => [
                'allow-packages' => [
                    'principal_scope' => 'tests-packages',
                    'permissions' => ['org.w4.packages.modify'],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        try {
            file_put_contents($requestPath, json_encode([
                'api_version' => '1.0',
                'request_id' => 'auth-allow-001',
                'method' => 'packages.apply',
                'authorization' => ['token' => 'allow-packages'],
                'params' => [
                    'plan_hash' => 'h-001',
                    'idempotency_key' => 'k-001',
                ],
            ], JSON_THROW_ON_ERROR));

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

            self::assertSame(0, $result['exitCode'], $result['stderr']);
            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['result']);
            self::assertSame('VALIDATION_ERROR', $payload['error']['code']);
            self::assertSame([], array_values(array_diff(scandir($operationsDir) ?: [], ['.', '..'])));
            self::assertSame([], array_values(array_diff(scandir($ledgerDir) ?: [], ['.', '..'])));
        } finally {
            $this->rmTree($base);
        }
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

