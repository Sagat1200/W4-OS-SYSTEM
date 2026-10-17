<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class PrepareNativeDbusExtensionForkCliTest extends TestCase
{
    private string $repoRoot;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-dbus-fork-workspace-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->tempDir, 0777, true) || is_dir($this->tempDir));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testScriptPreparesForkWorkspaceAndImportsEvidence(): void
    {
        $buildLogPath = $this->tempDir . DIRECTORY_SEPARATOR . 'native-dbus-build.log';
        $buildEvidenceJsonPath = $this->tempDir . DIRECTORY_SEPARATOR . 'native-dbus-extension-build-evidence.json';
        $workspaceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'workspace';
        $sourceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'dbus-source';

        self::assertTrue(mkdir($sourceDir, 0777, true) || is_dir($sourceDir));

        file_put_contents($buildLogPath, <<<TEXT
TSRMLS_DC
zend_parse_parameters
zend_object_store_get_object
make: *** [Makefile:208: dbus.lo] Error 1
TEXT);
        file_put_contents($sourceDir . DIRECTORY_SEPARATOR . 'dbus.c', "<?php84_compat_stub = 1;\n");
        file_put_contents($buildEvidenceJsonPath, json_encode([
            'api_version' => '1.0',
            'kind' => 'system-api-native-dbus-extension-build-evidence',
            'state' => 'build-failed',
            'build_phase' => 'make',
            'reason' => 'legacy-tsrmls-api-incompatible-with-php84',
            'failure_signature' => 'make: *** [Makefile:208: dbus.lo] Error 1',
            'log_path' => $buildLogPath,
            'markers' => [
                'legacy-tsrmls-api-incompatible-with-php84',
                'TSRMLS_DC',
                'zend_parse_parameters',
                'zend_object_store_get_object',
                'make',
            ],
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--workspace-dir',
            $workspaceDir,
            '--source-dir',
            $sourceDir,
            '--build-log-path',
            $buildLogPath,
            '--build-evidence-json-path',
            $buildEvidenceJsonPath,
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('workspace-prepared', $payload['status']);
        self::assertSame(str_replace('\\', '/', $workspaceDir), $payload['workspace_dir']);
        self::assertFileExists($payload['manifest_path']);
        self::assertFileExists($payload['porting_plan_path']);
        self::assertFileExists($payload['patch_queue_readme_path']);
        self::assertSame(
            str_replace('\\', '/', $workspaceDir . DIRECTORY_SEPARATOR . 'upstream' . DIRECTORY_SEPARATOR . 'imported-source'),
            $payload['copied_inputs']['source_dir']
        );
        self::assertSame(
            str_replace('\\', '/', $workspaceDir . DIRECTORY_SEPARATOR . 'fork' . DIRECTORY_SEPARATOR . 'current'),
            $payload['copied_inputs']['fork_current_dir']
        );
        self::assertSame(
            str_replace('\\', '/', $workspaceDir . DIRECTORY_SEPARATOR . 'evidence' . DIRECTORY_SEPARATOR . 'native-dbus-build.log'),
            $payload['copied_inputs']['build_log']
        );
        self::assertSame(
            str_replace('\\', '/', $workspaceDir . DIRECTORY_SEPARATOR . 'evidence' . DIRECTORY_SEPARATOR . 'native-dbus-extension-build-evidence.json'),
            $payload['copied_inputs']['build_evidence_json']
        );

        $manifest = $this->decodeJsonFile($payload['manifest_path']);
        self::assertSame('system-api-native-dbus-fork-workspace', $manifest['kind']);
        self::assertSame('8.4', $manifest['php_target']);
        self::assertSame('DBus-0.1.1', $manifest['pecl_package']);
        self::assertFileExists($workspaceDir . DIRECTORY_SEPARATOR . 'fork' . DIRECTORY_SEPARATOR . 'current' . DIRECTORY_SEPARATOR . 'dbus.c');

        $portingPlan = file_get_contents($payload['porting_plan_path']);
        self::assertNotFalse($portingPlan);
        self::assertStringContainsString('TSRMLS_*', $portingPlan);
        self::assertStringContainsString('zend_parse_parameters', $portingPlan);
        self::assertStringContainsString('zend_object_store_get_object', $portingPlan);
        self::assertStringContainsString('Imported Evidence', $portingPlan);
        self::assertStringContainsString('Editable fork tree', $portingPlan);
    }

    private function scriptPath(): string
    {
        return $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'prepare_native_dbus_extension_fork.php';
    }

    /**
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command): array
    {
        array_unshift($command, PHP_BINARY);
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'rb'],
                1 => ['pipe', 'wb'],
                2 => ['pipe', 'wb'],
            ],
            $pipes,
            $this->repoRoot
        );

        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        self::assertIsString($stdout);
        self::assertIsString($stderr);

        return [
            'exitCode' => $exitCode,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($decoded);

        return $decoded;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer %s', $path));

        return $this->decodeJson($raw);
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
