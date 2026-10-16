<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiNativeDbusHostValidationCliTest extends TestCase
{
    private string $repoRoot;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-dbus-host-validation-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->tempDir, 0777, true) || is_dir($this->tempDir));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testValidationReportsBlockedHostWhenNativeRuntimeCapabilitiesAreMissing(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'blocked-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-native-runtime-validation-report', $payload['kind']);
        self::assertSame('blocked', $payload['status']);
        self::assertFalse($payload['ownership_validated']);
        self::assertFalse($payload['coupling_ready']);
        self::assertSame('install-native-dbus-extension', $payload['next_action']);
        self::assertContains('install-native-dbus-extension', $payload['recommended_next_actions']);
        self::assertFileExists($payload['report_path']);
        self::assertFileExists($payload['summary_path']);
        self::assertFileExists($payload['step_files']['diagnosis']);
        self::assertFileExists($payload['step_files']['service_definition']);
        self::assertFileExists($payload['step_files']['startup']);
        self::assertFileExists($payload['step_files']['coupling']);
        self::assertFileExists($payload['step_files']['ownership']);
        self::assertIsArray($payload['preparation_plan']);
        self::assertContains(
            'install-native-dbus-extension',
            array_map(
                static fn (array $step): string => (string) ($step['action'] ?? ''),
                $payload['preparation_plan']
            )
        );

        $savedReport = $this->decodeJsonFile($payload['report_path']);
        self::assertSame('blocked', $savedReport['status']);
        self::assertSame('native-runtime-service-blocked', $savedReport['steps']['startup']['status']);
        self::assertSame('missing-runtime-capabilities', $savedReport['steps']['diagnosis']['blocking_reason'] ?? 'missing-runtime-capabilities');
        $savedSummary = file_get_contents($payload['summary_path']);
        self::assertNotFalse($savedSummary);
        self::assertStringContainsString('## Preparation Plan', $savedSummary);
        self::assertStringContainsString('install-native-dbus-extension', $savedSummary);
    }

    public function testValidationCanPrepareOwnedRuntimeAgainstFakeNativeDbusService(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'fake-owned-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
        ], array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => $this->repoRoot . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
            ]
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ownership-validated', $payload['status']);
        self::assertTrue($payload['ownership_validated']);
        self::assertTrue($payload['coupling_ready']);
        self::assertTrue($payload['startup_acknowledged']);
        self::assertSame('runtime-service-active', $payload['next_action']);
        self::assertSame('native-runtime-bus-owned', $payload['steps']['ownership']['status']);
        self::assertSame('bus-name-owned', $payload['steps']['ownership']['ownership_state']);
        self::assertSame('native-runtime-service-observed-active', $payload['steps']['observation_after_ownership']['status']);
        self::assertFileExists($payload['report_path']);
        self::assertFileExists($payload['summary_path']);
        self::assertDirectoryExists($payload['artifacts_dir']);
        self::assertFileExists($payload['artifacts_dir'] . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.ready');
        self::assertFileExists($payload['artifacts_dir'] . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime-status.json');
        self::assertFileExists($payload['artifacts_dir'] . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.pid');
        self::assertContains(
            'capture-release-evidence',
            array_map(
                static fn (array $step): string => (string) ($step['action'] ?? ''),
                $payload['preparation_plan']
            )
        );

        $savedReport = $this->decodeJsonFile($payload['report_path']);
        self::assertSame('ownership-validated', $savedReport['status']);
        self::assertSame('W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver', $savedReport['steps']['ownership']['ownership_driver']['class_name']);
    }

    public function testValidationCanRenderMarkdownSummaryDirectlyFromRunner(): void
    {
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'markdown-host';

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--evidence-dir',
            $evidenceDir,
            '--format',
            'markdown',
            '--attempt-ownership',
            'false',
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '0',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '0',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '0',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '0',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('# Native D-Bus Runtime Validation', $result['stdout']);
        self::assertStringContainsString('## Preparation Plan', $result['stdout']);
        self::assertStringContainsString('install-native-dbus-extension', $result['stdout']);
    }

    private function scriptPath(): string
    {
        return $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_native_dbus_real_host_validation.php';
    }

    /**
     * @param list<string> $command
     * @param array<string, string> $environment
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command, array $environment = []): array
    {
        array_unshift($command, PHP_BINARY);
        $processEnvironment = [];
        foreach (array_merge($_ENV, $_SERVER, $environment) as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
                $processEnvironment[$key] = (string) $value;
            }
        }

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'rb'],
                1 => ['pipe', 'wb'],
                2 => ['pipe', 'wb'],
            ],
            $pipes,
            $this->repoRoot,
            $processEnvironment
        );

        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function readyRuntimeEnvironment(): array
    {
        return [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
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
