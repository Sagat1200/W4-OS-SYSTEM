<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDbusRuntimeHostCliTest extends TestCase
{
    public function testDescribeHostReturnsRuntimeHostContract(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeHost',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-host', $payload['kind']);
        self::assertSame('local-runtime-host-ready', $payload['status']);
        self::assertSame('stdio-ndjson-host', $payload['transport']);
        self::assertContains('DescribeHost', $payload['commands']);
        self::assertContains('DescribeNativeRuntimeService', $payload['commands']);
        self::assertContains('StartNativeRuntimeService', $payload['commands']);
        self::assertContains('DescribeNativeRuntimeCoupling', $payload['commands']);
        self::assertContains('ObserveNativeRuntimeServiceState', $payload['commands']);
        self::assertContains('MaterializeNativeBusOwnership', $payload['commands']);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $payload['adapter_specification']['kind']);
    }

    public function testServeDbusReturnsStructuredActivationReport(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'serve-dbus',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['kind']);
        self::assertSame('local-runtime-host', $payload['bridge_kind']);
        self::assertSame('serve-dbus', $payload['requested_command']);
    }

    public function testServeDbusReturnsMaterializedBackendReportWhenConfigured(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'serve-dbus',
        ], '', array_merge(
            $this->readyRuntimeEnvironment(),
            $this->materializedBackendEnvironment()
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['kind']);
        self::assertSame('local-runtime-host', $payload['bridge_kind']);
        self::assertSame('experimental-runtime-backend-materialized', $payload['status']);
        self::assertTrue($payload['materialized']);
        self::assertSame(0, $payload['exit_code']);
    }

    public function testServeStdioHostDelegatesToHostAndAdapterMethods(): void
    {
        $inputLines = [
            json_encode([
                'method' => 'DescribeHost',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'DescribeSpecification',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'Call',
                'arguments' => [
                    'request' => [
                        'api_version' => '1.0',
                        'request_id' => 'runtime-host-call-001',
                        'method' => 'system.dbus-runtime',
                        'params' => [],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        $result = $this->runPhpScript(
            [
                $this->scriptPath(),
                'serve-stdio-host',
            ],
            implode(PHP_EOL, $inputLines) . PHP_EOL
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(3, $responses);
        self::assertSame('system-api-dbus-runtime-host', $responses[0]['kind']);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $responses[1]['kind']);
        self::assertSame('runtime-host-call-001', $responses[2]['request_id']);
        self::assertSame('system.dbus-runtime', $responses[2]['result']['method']);
    }

    public function testStartNativeRuntimeServiceCanBeInvokedDirectlyThroughHostScript(): void
    {
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-runtime-host-start-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            $result = $this->runPhpScript([
                $this->scriptPath(),
                'StartNativeRuntimeService',
            ], '', array_merge(
                $this->readyRuntimeEnvironment(),
                $this->nativeCompatibleBackendEnvironment($readyMarkerPath, $statusFilePath, $pidFilePath)
            ));

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertSame('system-api-dbus-native-runtime-startup', $payload['kind']);
            self::assertSame('native-runtime-service-bootstrap-recorded', $payload['status']);
            self::assertTrue($payload['startup_acknowledged']);
            self::assertFileExists($readyMarkerPath);
            self::assertFileExists($statusFilePath);
            self::assertFileExists($pidFilePath);
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testStartNativeRuntimeServiceCanLaunchBackgroundProcessThroughHostScript(): void
    {
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-runtime-host-launch-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            $result = $this->runPhpScript([
                $this->scriptPath(),
                'StartNativeRuntimeService',
            ], '', array_merge(
                $this->readyRuntimeEnvironment(),
                $this->nativeCompatibleBackendEnvironment($readyMarkerPath, $statusFilePath, $pidFilePath),
                [
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STARTUP_MODE' => 'launch-process',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS' => '2',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_REQUEST_NAME_STATE' => 'owned',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_REGISTER_OBJECT_STATE' => 'registered',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_WAIT_LOOP_STATE' => 'running',
                ]
            ));

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertSame('native-runtime-bus-owned', $payload['status']);
            self::assertSame('bus-name-owned', $payload['ownership_state']);
            self::assertNull($payload['blocking_reason']);
            self::assertSame('runtime-service-active', $payload['next_action']);
            self::assertTrue($payload['launched']);
            self::assertGreaterThan(0, $payload['pid']);
            self::assertFileExists($pidFilePath);
            self::assertSame($payload['pid'] . "\n", file_get_contents($pidFilePath));
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testMaterializeNativeBusOwnershipCanBeInvokedDirectlyThroughHostScript(): void
    {
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-runtime-host-own-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            $environment = array_merge(
                $this->readyRuntimeEnvironment(),
                $this->nativeFakeDriverEnvironment($readyMarkerPath, $statusFilePath, $pidFilePath)
            );

            $startup = $this->runPhpScript([
                $this->scriptPath(),
                'StartNativeRuntimeService',
            ], '', $environment);
            self::assertSame(0, $startup['exitCode'], $startup['stderr']);
            file_put_contents($pidFilePath, "4242\n");

            $ownership = $this->runPhpScript([
                $this->scriptPath(),
                'MaterializeNativeBusOwnership',
            ], '', $environment);
            self::assertSame(0, $ownership['exitCode'], $ownership['stderr']);

            $payload = $this->decodeJson($ownership['stdout']);
            self::assertSame('system-api-dbus-native-runtime-ownership', $payload['kind']);
            self::assertSame('native-runtime-bus-owned', $payload['status']);
            self::assertSame('bus-name-owned', $payload['ownership_state']);
            self::assertSame("native-runtime-bus-owned\n", file_get_contents($readyMarkerPath));
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    /**
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(array $command, string $stdin = '', array $extraEnvironment = []): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $environment = array_merge($_ENV, $extraEnvironment);
        $process = proc_open($command, $descriptorSpec, $pipes, dirname(__DIR__), $environment);
        self::assertIsResource($process, 'No se pudo ejecutar system_api_dbus_runtime_host.php');

        fwrite($pipes[0], $stdin);
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
     * @return list<array<string, mixed>>
     */
    private function decodeNdjson(string $content): array
    {
        $responses = [];
        $lines = preg_split('/\r?\n/', trim($content)) ?: [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
            $responses[] = $decoded;
        }

        return $responses;
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_dbus_runtime_host.php';
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
     * @return array<string, string>
     */
    private function materializedBackendEnvironment(): array
    {
        return [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'experimental-embedded-runtime',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function nativeCompatibleBackendEnvironment(string $readyMarkerPath, string $statusFilePath, string $pidFilePath): array
    {
        return [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
            'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
            'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function nativeFakeDriverEnvironment(string $readyMarkerPath, string $statusFilePath, string $pidFilePath): array
    {
        return array_merge(
            $this->nativeCompatibleBackendEnvironment($readyMarkerPath, $statusFilePath, $pidFilePath),
            [
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
            ]
        );
    }

    private function cleanupDirectory(string $path): void
    {
        if ($path === '' || !is_dir($path)) {
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
                $this->cleanupDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
