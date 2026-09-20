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
