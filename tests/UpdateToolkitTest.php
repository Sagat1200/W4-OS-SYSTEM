<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Support\ValidationError;
use W4\OS\Update\UpdateToolkit;

final class UpdateToolkitTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-update-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal de pruebas');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testCreateUpdatePlanFromRepositoryFixture(): void
    {
        $toolkit = new UpdateToolkit();
        $requestPath = $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json');

        $request = $toolkit->readJsonFile($requestPath);
        $toolkit->validateUpdateRequest($request, $requestPath);
        $plan = $toolkit->createUpdatePlan($request, 'w4-update-fixed-001');

        self::assertSame('update-plan', $plan['kind']);
        self::assertSame('w4-update-fixed-001', $plan['operation_id']);
        self::assertSame('w4-os-home', $plan['profile_id']);
        self::assertSame('1.0.0-lab', $plan['source_version']);
        self::assertSame('1.0.1-lab', $plan['target_version']);
        self::assertSame('apt-offline-snapshot', $plan['execution']['engine']);
        self::assertSame(
            ['planned', 'downloading', 'ready', 'prepared', 'applying_offline', 'pending_health', 'confirmed'],
            $plan['execution']['stages']
        );
        self::assertSame('pre-update-w4-update-fixed-001', $plan['snapshot']['snapshot_name']);
        self::assertContains('test-file-present', $plan['health_check_manifest']['required']);
        self::assertSame('/home/w4/update-proof.txt', $plan['health_check_manifest']['test_file_path']);
        self::assertSame(4, $plan['package_changes']['counts']['total']);
    }

    public function testInitializeOperationStoreCreatesDurableArtifacts(): void
    {
        $toolkit = new UpdateToolkit();
        $requestPath = $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json');

        $request = $toolkit->readJsonFile($requestPath);
        $toolkit->validateUpdateRequest($request, $requestPath);
        $plan = $toolkit->createUpdatePlan($request, 'w4-update-fixed-002');

        $storeDir = $this->tempDir . DIRECTORY_SEPARATOR . 'operation-store';
        $result = $toolkit->initializeOperationStore($plan, $storeDir);

        self::assertSame($storeDir, $result['store_dir']);
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'update-plan.json');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'operation.json');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'events.ndjson');
        self::assertFileExists($storeDir . DIRECTORY_SEPARATOR . 'health-report.json');

        $operation = $this->decodeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'operation.json');
        self::assertSame('planned', $operation['stage']);
        self::assertSame('pre-update-w4-update-fixed-002', $operation['snapshot']['snapshot_name']);
        self::assertNull($operation['last_error']);

        $events = file($storeDir . DIRECTORY_SEPARATOR . 'events.ndjson', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        self::assertIsArray($events);
        self::assertCount(1, $events);
        /** @var array<string, mixed> $event */
        $event = json_decode($events[0], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('operation-created', $event['event_type']);
        self::assertSame('w4-update-fixed-002', $event['operation_id']);
    }

    public function testTransitionOperationValidatesStateMachine(): void
    {
        $toolkit = new UpdateToolkit();
        $operation = [
            'stage' => 'planned',
            'timestamps' => [
                'created_at' => '2026-09-19T00:00:00+00:00',
                'updated_at' => '2026-09-19T00:00:00+00:00',
            ],
            'last_error' => null,
        ];

        $downloading = $toolkit->transitionOperation($operation, 'downloading');
        self::assertSame('downloading', $downloading['stage']);
        self::assertNull($downloading['last_error']);

        $failed = $toolkit->transitionOperation(
            $downloading,
            'failed',
            [
                'code' => 'network-unavailable',
                'message' => 'No se pudo descargar el snapshot de repositorio',
            ]
        );
        self::assertSame('failed', $failed['stage']);
        self::assertSame('network-unavailable', $failed['last_error']['code']);
    }

    public function testPersistOperationTransitionUpdatesStoreAndHealthReport(): void
    {
        $toolkit = new UpdateToolkit();
        $requestPath = $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json');

        $request = $toolkit->readJsonFile($requestPath);
        $toolkit->validateUpdateRequest($request, $requestPath);
        $plan = $toolkit->createUpdatePlan($request, 'w4-update-fixed-003');

        $storeDir = $this->tempDir . DIRECTORY_SEPARATOR . 'transition-store';
        $toolkit->initializeOperationStore($plan, $storeDir);

        $operation = $toolkit->persistOperationTransition(
            $storeDir,
            'downloading',
            'phpunit',
            ['attempt' => '1']
        );

        self::assertSame('downloading', $operation['stage']);

        $storedOperation = $this->decodeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'operation.json');
        self::assertSame('downloading', $storedOperation['stage']);

        $healthReport = $this->decodeJsonFile($storeDir . DIRECTORY_SEPARATOR . 'health-report.json');
        self::assertSame('pending', $healthReport['status']);
        self::assertSame('downloading', $healthReport['stage']);

        $events = file($storeDir . DIRECTORY_SEPARATOR . 'events.ndjson', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        self::assertIsArray($events);
        self::assertCount(2, $events);
        /** @var array<string, mixed> $event */
        $event = json_decode($events[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(2, $event['sequence']);
        self::assertSame('operation-transitioned', $event['event_type']);
        self::assertSame('downloading', $event['stage']);
    }

    public function testReconcileOperationStoreMovesPendingHealthToConfirmed(): void
    {
        $toolkit = new UpdateToolkit();
        $requestPath = $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json');

        $request = $toolkit->readJsonFile($requestPath);
        $toolkit->validateUpdateRequest($request, $requestPath);
        $plan = $toolkit->createUpdatePlan($request, 'w4-update-fixed-004');

        $storeDir = $this->tempDir . DIRECTORY_SEPARATOR . 'reconcile-store';
        $toolkit->initializeOperationStore($plan, $storeDir);
        $toolkit->persistOperationTransition($storeDir, 'downloading', 'phpunit');
        $toolkit->persistOperationTransition($storeDir, 'ready', 'phpunit');
        $toolkit->persistOperationTransition($storeDir, 'prepared', 'phpunit');
        $toolkit->persistOperationTransition($storeDir, 'applying_offline', 'phpunit');

        $pending = $toolkit->reconcileOperationStore(
            $storeDir,
            'pending_health',
            'post-reboot-check',
            ['boot_id' => 'boot-1']
        );
        self::assertTrue($pending['reconciled']);
        self::assertSame('pending_health', $pending['operation']['stage']);

        $confirmed = $toolkit->reconcileOperationStore(
            $storeDir,
            'confirmed',
            'post-reboot-check',
            ['boot_id' => 'boot-2']
        );
        self::assertTrue($confirmed['reconciled']);
        self::assertSame('confirmed', $confirmed['operation']['stage']);
        self::assertSame('ok', $confirmed['health_report']['status']);
        self::assertSame('confirmed', $confirmed['health_report']['stage']);
    }

    public function testRenderOfflineExecutorScriptContainsCoordinatorEntryPoints(): void
    {
        $toolkit = new UpdateToolkit();
        $requestPath = $this->fixturePath('examples/update/home-lab-to-1.0.1.update-request.json');

        $request = $toolkit->readJsonFile($requestPath);
        $toolkit->validateUpdateRequest($request, $requestPath);
        $plan = $toolkit->createUpdatePlan($request, 'w4-update-fixed-005');

        $script = $toolkit->renderOfflineExecutorScript($plan, $this->rootDir);
        $repoLauncherScript = $toolkit->renderRepositoryAwareLauncherScript();
        $healthScript = $toolkit->renderHealthCheckScript($plan);
        $reconcileScript = $toolkit->renderReconcileScript($plan, $this->rootDir);
        $manifest = $toolkit->createUpdateExecutorManifest($plan);

        self::assertStringContainsString('scripts/advance_update_operation.php', $script);
        self::assertStringContainsString('W4_UPDATE_ENGINE_ROOT', $script);
        self::assertStringContainsString('W4_UPDATE_EXECUTE', $script);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_MODE', $script);
        self::assertStringContainsString('W4_UPDATE_APT_CHECK_DATE', $script);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_LINE', $script);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_LINE_DISTS', $script);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_FILE', $script);
        self::assertStringContainsString('apt-get -o Dir::Cache::Archives', $script);
        self::assertStringContainsString('configure_temporary_apt_source', $script);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_MODE no soportado', $script);
        self::assertStringContainsString('snapshot-manifest.json', $script);
        self::assertStringContainsString('staging-manifest.json', $script);
        self::assertStringContainsString('trap \'handle_error $? $LINENO\' ERR', $script);
        self::assertStringContainsString('--stage failed', $script);
        self::assertStringContainsString('W4_UPDATE_FAIL_STAGE', $script);
        self::assertStringContainsString('offline-application.json', $script);
        self::assertStringContainsString('pending_health', $script);
        self::assertStringContainsString('W4_UPDATE_REPOSITORY_DIR', $repoLauncherScript);
        self::assertStringContainsString('W4_UPDATE_REPOSITORY_ENV_FILE', $repoLauncherScript);
        self::assertStringContainsString('W4_REPOSITORY_CHANNEL', $repoLauncherScript);
        self::assertStringContainsString('run-update-offline.sh', $repoLauncherScript);
        self::assertStringContainsString('W4_UPDATE_APT_SOURCE_MODE_DEFAULT', $repoLauncherScript);
        self::assertStringContainsString('W4_UPDATE_APT_CHECK_DATE', $repoLauncherScript);
        self::assertStringContainsString('dpkg --audit', $healthScript);
        self::assertStringContainsString('health-check-results.json', $healthScript);
        self::assertStringContainsString('scripts/reconcile_update_operation.php', $reconcileScript);
        self::assertStringContainsString('W4_UPDATE_OBSERVED_STAGE', $reconcileScript);
        self::assertStringContainsString('run-health-checks.sh', $reconcileScript);
        self::assertContains('run-update-offline.sh', $manifest['generated_artifacts']);
        self::assertContains('run-update-with-repo-env.sh', $manifest['generated_artifacts']);
        self::assertContains('run-health-checks.sh', $manifest['generated_artifacts']);
        self::assertContains('reconcile-after-reboot.sh', $manifest['generated_artifacts']);
    }

    public function testRejectInvalidTransition(): void
    {
        $toolkit = new UpdateToolkit();

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('Transicion no valida desde planned hacia confirmed');

        $toolkit->transitionOperation(
            [
                'stage' => 'planned',
                'timestamps' => [],
                'last_error' => null,
            ],
            'confirmed'
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer el archivo JSON %s', $path));

        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

        return $data;
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
