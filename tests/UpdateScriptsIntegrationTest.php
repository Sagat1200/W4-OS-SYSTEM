<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class UpdateScriptsIntegrationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-update-cli-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal de pruebas');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateUpdatePlanCreatesPlanInCustomPath(): void
    {
        $outputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'home-lab.update-plan.json';

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json'),
                '--operation-id',
                'w4-update-cli-001',
                '--output',
                $outputPath,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($outputPath, $payload['output']);
        self::assertSame('w4-update-cli-001', $payload['operation_id']);

        $plan = $this->decodeJsonFile($outputPath);
        self::assertSame('update-plan', $plan['kind']);
        self::assertSame('w4-update-cli-001', $plan['operation_id']);
        self::assertSame('pending_health', $plan['health_check_manifest']['pending_state']);
    }

    public function testPrepareUpdateOperationCreatesDurableStore(): void
    {
        $planPath = $this->tempDir . DIRECTORY_SEPARATOR . 'prepared.update-plan.json';
        $storeDir = $this->tempDir . DIRECTORY_SEPARATOR . 'operation-store';

        $generate = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json'),
                '--operation-id',
                'w4-update-cli-002',
                '--output',
                $planPath,
            ]
        );
        self::assertSame(0, $generate['exitCode'], $generate['stderr']);

        $prepare = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_update_operation.php'),
            [
                '--plan',
                $planPath,
                '--store-dir',
                $storeDir,
            ]
        );

        self::assertSame(0, $prepare['exitCode'], $prepare['stderr']);

        $payload = $this->decodeJson($prepare['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($storeDir, $payload['store_dir']);
        self::assertSame('w4-update-cli-002', $payload['operation_id']);
        self::assertSame('planned', $payload['stage']);
        self::assertSame('pre-update-w4-update-cli-002', $payload['snapshot_name']);

        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'update-plan.json');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'operation.json');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'events.ndjson');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'health-report.json');
    }

    public function testAdvanceAndReconcileUpdateOperationFlow(): void
    {
        $planPath = $this->tempDir . DIRECTORY_SEPARATOR . 'advance.update-plan.json';
        $storeDir = $this->tempDir . DIRECTORY_SEPARATOR . 'advance-store';

        $generate = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json'),
                '--operation-id',
                'w4-update-cli-003',
                '--output',
                $planPath,
            ]
        );
        self::assertSame(0, $generate['exitCode'], $generate['stderr']);

        $prepare = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_update_operation.php'),
            [
                '--plan',
                $planPath,
                '--store-dir',
                $storeDir,
            ]
        );
        self::assertSame(0, $prepare['exitCode'], $prepare['stderr']);

        foreach (['downloading', 'ready', 'prepared', 'applying_offline'] as $stage) {
            $advance = $this->runPhpScript(
                $this->fixturePath('scripts/advance_update_operation.php'),
                [
                    '--store-dir',
                    $storeDir,
                    '--stage',
                    $stage,
                    '--component',
                    'phpunit-cli',
                    '--detail',
                    'stage=' . $stage,
                ]
            );
            self::assertSame(0, $advance['exitCode'], $advance['stderr']);
        }

        $pendingHealth = $this->runPhpScript(
            $this->fixturePath('scripts/reconcile_update_operation.php'),
            [
                '--store-dir',
                $storeDir,
                '--observed-stage',
                'pending_health',
                '--component',
                'phpunit-reconcile',
                '--detail',
                'boot=first',
            ]
        );
        self::assertSame(0, $pendingHealth['exitCode'], $pendingHealth['stderr']);
        $pendingPayload = $this->decodeJson($pendingHealth['stdout']);
        self::assertTrue($pendingPayload['reconciled']);
        self::assertSame('pending_health', $pendingPayload['stage']);

        $confirmed = $this->runPhpScript(
            $this->fixturePath('scripts/reconcile_update_operation.php'),
            [
                '--store-dir',
                $storeDir,
                '--observed-stage',
                'confirmed',
                '--component',
                'phpunit-reconcile',
                '--detail',
                'boot=second',
            ]
        );
        self::assertSame(0, $confirmed['exitCode'], $confirmed['stderr']);
        $confirmedPayload = $this->decodeJson($confirmed['stdout']);
        self::assertSame('confirmed', $confirmedPayload['stage']);
        self::assertSame('ok', $confirmedPayload['health_status']);
    }

    public function testGenerateUpdateExecutorWritesCoordinatorScripts(): void
    {
        $planPath = $this->tempDir . DIRECTORY_SEPARATOR . 'executor.update-plan.json';
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'executor-bundle';

        $generatePlan = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_plan.php'),
            [
                '--request',
                $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json'),
                '--operation-id',
                'w4-update-cli-004',
                '--output',
                $planPath,
            ]
        );
        self::assertSame(0, $generatePlan['exitCode'], $generatePlan['stderr']);

        $generateExecutor = $this->runPhpScript(
            $this->fixturePath('scripts/generate_update_executor.php'),
            [
                '--plan',
                $planPath,
                '--bundle-dir',
                $bundleDir,
            ]
        );
        self::assertSame(0, $generateExecutor['exitCode'], $generateExecutor['stderr']);

        $payload = $this->decodeJson($generateExecutor['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($bundleDir, $payload['bundle_dir']);

        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'run-update-offline.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'run-health-checks.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'reconcile-after-reboot.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'update-executor.json');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'UPDATE_EXECUTOR_README.txt');

        $offlineScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'run-update-offline.sh');
        self::assertNotFalse($offlineScript);
        self::assertStringContainsString('advance_update_operation.php', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_ENGINE_ROOT', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_EXECUTE', $offlineScript);
        self::assertStringContainsString('W4_UPDATE_FAIL_STAGE', $offlineScript);
        self::assertStringContainsString('pending_health', $offlineScript);
        self::assertStringContainsString('snapshot-manifest.json', $offlineScript);

        $healthScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'run-health-checks.sh');
        self::assertNotFalse($healthScript);
        self::assertStringContainsString('dpkg --audit', $healthScript);
        self::assertStringContainsString('health-check-results.json', $healthScript);

        $reconcileScript = file_get_contents($bundleDir . DIRECTORY_SEPARATOR . 'reconcile-after-reboot.sh');
        self::assertNotFalse($reconcileScript);
        self::assertStringContainsString('reconcile_update_operation.php', $reconcileScript);
        self::assertStringContainsString('W4_UPDATE_OBSERVED_STAGE', $reconcileScript);
        self::assertStringContainsString('run-health-checks.sh', $reconcileScript);
    }

    /**
     * @param list<string> $arguments
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(string $scriptPath, array $arguments): array
    {
        $command = array_merge([PHP_BINARY, $scriptPath], $arguments);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->rootDir);
        self::assertIsResource($process, sprintf('No se pudo ejecutar %s', $scriptPath));

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

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer el archivo JSON %s', $path));

        return $this->decodeJson($raw);
    }

    private function fixturePath(string $relativePath): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
