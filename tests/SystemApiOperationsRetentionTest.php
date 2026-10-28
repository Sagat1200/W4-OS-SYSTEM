<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Support\ValidationError;
use W4\OS\SystemApi\SystemApiOperationsToolkit;

final class SystemApiOperationsRetentionTest extends TestCase
{
    public function testExpiredLedgerEntryDoesNotConflictAndDoesNotReuse(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-ops-retention-' . bin2hex(random_bytes(6));
        $operationsDir = $base . DIRECTORY_SEPARATOR . 'operations';
        $ledgerDir = $base . DIRECTORY_SEPARATOR . 'ledger';

        mkdir($operationsDir, 0777, true);
        mkdir($ledgerDir, 0777, true);

        putenv('W4_SYSTEM_API_OPERATIONS_STORE_DIR=' . $operationsDir);
        putenv('W4_SYSTEM_API_OPERATIONS_LEDGER_DIR=' . $ledgerDir);
        putenv('W4_SYSTEM_API_OPERATIONS_RETENTION_SECONDS=1');

        $toolkit = new SystemApiOperationsToolkit(dirname(__DIR__));

        $operationId = 'op-' . bin2hex(random_bytes(4));
        file_put_contents($operationsDir . DIRECTORY_SEPARATOR . $operationId . '.json', json_encode([
            'kind' => 'system-api-operation',
            'operation_id' => $operationId,
            'principal_scope' => 'scope-a',
            'method' => 'packages.apply',
            'resource' => ['kind' => 'package-operation', 'id' => 'demo'],
            'state' => 'succeeded',
            'status' => 'ok',
            'cancel_requested' => false,
            'cancelable' => false,
            'progress' => ['percent' => 100, 'current_step' => 'completed'],
            'result' => ['kind' => 'system-api-operation-result'],
            'error' => null,
            'created_at' => gmdate('c', time() - 3600),
            'observed_at' => gmdate('c', time() - 3600),
            'completed_at' => gmdate('c', time() - 3600),
        ], JSON_THROW_ON_ERROR));

        $ledgerPath = $ledgerDir . DIRECTORY_SEPARATOR . 'scope-a' . DIRECTORY_SEPARATOR . 'packages.apply--k-001.json';
        mkdir(dirname($ledgerPath), 0777, true);
        file_put_contents($ledgerPath, json_encode([
            'kind' => 'system-api-idempotency-ledger-entry',
            'principal_scope' => 'scope-a',
            'method' => 'packages.apply',
            'idempotency_key' => 'k-001',
            'digest' => 'd-001',
            'operation_id' => $operationId,
            'observed_at' => gmdate('c', time() - 3600),
        ], JSON_THROW_ON_ERROR));

        try {
            self::assertNull($toolkit->reuseIdempotentOperation('scope-a', 'packages.apply', 'k-001', 'd-001'));
        } finally {
            $this->rmTree($base);
        }
    }

    public function testExpiredOperationIsNotVisible(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-ops-retention-' . bin2hex(random_bytes(6));
        $operationsDir = $base . DIRECTORY_SEPARATOR . 'operations';
        $ledgerDir = $base . DIRECTORY_SEPARATOR . 'ledger';

        mkdir($operationsDir, 0777, true);
        mkdir($ledgerDir, 0777, true);

        putenv('W4_SYSTEM_API_OPERATIONS_STORE_DIR=' . $operationsDir);
        putenv('W4_SYSTEM_API_OPERATIONS_LEDGER_DIR=' . $ledgerDir);
        putenv('W4_SYSTEM_API_OPERATIONS_RETENTION_SECONDS=1');

        $toolkit = new SystemApiOperationsToolkit(dirname(__DIR__));

        $operationId = 'op-' . bin2hex(random_bytes(4));
        file_put_contents($operationsDir . DIRECTORY_SEPARATOR . $operationId . '.json', json_encode([
            'kind' => 'system-api-operation',
            'operation_id' => $operationId,
            'principal_scope' => 'scope-a',
            'method' => 'packages.apply',
            'resource' => ['kind' => 'package-operation', 'id' => 'demo'],
            'state' => 'succeeded',
            'status' => 'ok',
            'cancel_requested' => false,
            'cancelable' => false,
            'progress' => ['percent' => 100, 'current_step' => 'completed'],
            'result' => ['kind' => 'system-api-operation-result'],
            'error' => null,
            'created_at' => gmdate('c', time() - 3600),
            'observed_at' => gmdate('c', time() - 3600),
            'completed_at' => gmdate('c', time() - 3600),
        ], JSON_THROW_ON_ERROR));

        try {
            $this->expectException(ValidationError::class);
            $this->expectExceptionMessage('Operacion no encontrada:');
            $toolkit->get($operationId);
        } finally {
            $this->rmTree($base);
        }
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

