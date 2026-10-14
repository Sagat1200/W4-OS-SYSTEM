<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDbusRuntimeAdapterCliTest extends TestCase
{
    public function testDescribeSpecificationReturnsAdapterContract(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeSpecification',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $payload['kind']);
        self::assertSame('pending-runtime-adapter', $payload['status']);
        self::assertSame('serve-dbus', $payload['activation']['entry_command']);
        self::assertContains('Call', $payload['required_methods']);
        self::assertContains('DescribeRuntime', $payload['optional_methods']);
    }

    public function testDescribeActivationPreparationReturnsResolvedContext(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeActivationPreparation',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-context', $payload['kind']);
        self::assertFalse($payload['ready']);
        self::assertSame('scripts/system_api_dbus_runtime_adapter.php', $payload['resolved_contract']['runtime_adapter_entry']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
    }

    public function testAttemptActivationReturnsStructuredBlockedAttempt(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'AttemptActivation',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $payload['kind']);
        self::assertFalse($payload['attempted']);
        self::assertSame('local-runtime-adapter', $payload['bridge_kind']);
        self::assertSame('blocked-missing-capabilities', $payload['status']);
        self::assertSame('missing-runtime-capabilities', $payload['blocking_reason']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
    }

    public function testExecuteActivationReturnsStructuredBlockedExecution(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'ExecuteActivation',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-result', $payload['kind']);
        self::assertFalse($payload['executed']);
        self::assertSame('local-runtime-adapter', $payload['bridge_kind']);
        self::assertSame('execution-blocked', $payload['status']);
        self::assertSame('missing-runtime-capabilities', $payload['blocking_reason']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $payload['activation_attempt']['kind']);
    }

    public function testDescribeRuntimePublishesNativeDbusCompatibilityDetails(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeRuntime',
        ], '', array_merge(
            $this->readyRuntimeEnvironment(),
            [
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            ]
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-adapter', $payload['kind']);
        self::assertSame('native-dbus-extension', $payload['runtime_backend']['selected_backend']);
        self::assertSame('dbus-extension-api-incomplete', $payload['runtime_backend']['blocking_reason']);
        self::assertSame('Dbus', $payload['runtime_backend']['native_runtime_api']['class_name']);
        self::assertContains('registerObject', $payload['runtime_backend']['native_runtime_api']['missing_api']);
        self::assertFalse($payload['runtime_backend']['native_runtime_api']['compatible_for_service']);
    }

    public function testDescribeActivationPreparationPublishesNativeRuntimeLaunchPlan(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeActivationPreparation',
        ], '', array_merge(
            $this->readyRuntimeEnvironment(),
            $this->nativeCompatibleBackendEnvironment()
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('native-dbus-extension', $payload['runtime_backend']['selected_backend']);
        self::assertSame('launch-native-runtime-service', $payload['next_action']);
        self::assertSame('native-dbus-extension', $payload['execution_plan']['runtime_backend']['selected_backend']);
        self::assertSame('StartNativeRuntimeService', $payload['execution_plan']['runtime_backend']['command']);
        self::assertSame('native-runtime-service-bootstrap', $payload['execution_plan']['runtime_backend']['command_role']);
        self::assertSame('long-running-dbus-service', $payload['execution_plan']['runtime_backend']['activation_mode']);
    }

    public function testStartNativeRuntimeServicePublishesStartupReportAndArtifacts(): void
    {
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            $result = $this->runPhpScript([
                $this->scriptPath(),
                'StartNativeRuntimeService',
            ], '', array_merge(
                $this->readyRuntimeEnvironment(),
                $this->nativeCompatibleBackendEnvironment(),
                [
                    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                ]
            ));

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertSame('system-api-dbus-native-runtime-startup', $payload['kind']);
            self::assertSame('native-runtime-service-started', $payload['status']);
            self::assertTrue($payload['startup_acknowledged']);
            self::assertSame('observe-native-runtime-service-state', $payload['next_action']);
            self::assertFileExists($readyMarkerPath);
            self::assertFileExists($statusFilePath);
            self::assertFileExists($pidFilePath);
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testExecuteActivationRunsTargetCommandWhenRuntimeIsReady(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'ExecuteActivation',
        ], '', $this->readyRuntimeEnvironment());

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-result', $payload['kind']);
        self::assertTrue($payload['executed']);
        self::assertSame('execution-attempted-pending-runtime-adapter', $payload['status']);
        self::assertSame('runtime-adapter-returned-pending', $payload['blocking_reason']);
        self::assertSame('inspect-runtime-adapter-report', $payload['next_action']);
        self::assertSame('scripts/system_api_dbus_runtime_adapter.php', $payload['command_result']['entrypoint']);
        self::assertSame('serve-dbus', $payload['command_result']['command']);
        self::assertSame(3, $payload['command_result']['exit_code']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['command_result']['payload']['kind']);
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
        self::assertSame('experimental-runtime-backend-materialized', $payload['status']);
        self::assertTrue($payload['materialized']);
        self::assertSame(0, $payload['exit_code']);
        self::assertSame('experimental-embedded-runtime', $payload['runtime_backend']['selected_backend']);
        self::assertTrue($payload['runtime_backend']['materialized']);
        self::assertSame('observe-runtime-backend', $payload['next_action']);
    }

    public function testExecuteActivationCompletesWhenMaterializedBackendIsConfigured(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'ExecuteActivation',
        ], '', array_merge(
            $this->readyRuntimeEnvironment(),
            $this->materializedBackendEnvironment()
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation-result', $payload['kind']);
        self::assertTrue($payload['executed']);
        self::assertSame('execution-completed', $payload['status']);
        self::assertNull($payload['blocking_reason']);
        self::assertSame('observe-runtime-adapter-state', $payload['next_action']);
        self::assertSame(0, $payload['command_result']['exit_code']);
        self::assertSame('experimental-runtime-backend-materialized', $payload['command_result']['payload']['status']);
    }

    public function testExecuteActivationUsesNativeRuntimeBootstrapWhenNativeBackendIsCompatible(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'ExecuteActivation',
        ], '', array_merge(
            $this->readyRuntimeEnvironment(),
            $this->nativeCompatibleBackendEnvironment()
        ));

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertTrue($payload['executed']);
        self::assertSame('execution-completed', $payload['status']);
        self::assertSame('native-dbus-extension', $payload['runtime_backend']['selected_backend']);
        self::assertSame('ready-to-launch-native-runtime', $payload['activation_attempt']['status']);
        self::assertSame('execute-native-runtime-service', $payload['activation_attempt']['next_action']);
        self::assertSame('StartNativeRuntimeService', $payload['target_command']['command']);
        self::assertSame('native-runtime-service-bootstrap', $payload['target_command']['command_role']);
        self::assertSame('long-running-dbus-service', $payload['target_command']['activation_mode']);
        self::assertSame(0, $payload['command_result']['exit_code']);
        self::assertSame('system-api-dbus-native-runtime-startup', $payload['command_result']['payload']['kind']);
        self::assertSame('native-runtime-service-started', $payload['command_result']['payload']['status']);
        self::assertSame('observe-native-runtime-service-state', $payload['next_action']);
    }

    public function testCallDelegatesToSystemApiContract(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'adapter-call-001',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            'Call',
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-runtime', $payload['result']['method']);
        self::assertSame('local-system-api-runtime-probe', $payload['result']['resource']['provider']);
    }

    public function testServeStdioAdapterProcessesDbusRuntimeMethods(): void
    {
        $inputLines = [
            json_encode([
                'method' => 'DescribeSpecification',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'DescribeActivationPreparation',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'AttemptActivation',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'ExecuteActivation',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'GetCapabilities',
                'arguments' => [
                    'request_id' => 'adapter-cap-001',
                ],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'Call',
                'arguments' => [
                    'request' => [
                        'api_version' => '1.0',
                        'request_id' => 'adapter-call-stdio-001',
                        'method' => 'profiles.get',
                        'params' => [
                            'profile_id' => 'w4-os-home',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        $result = $this->runPhpScript(
            [
                $this->scriptPath(),
                'serve-stdio-adapter',
            ],
            implode(PHP_EOL, $inputLines) . PHP_EOL
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(6, $responses);
        self::assertSame('system-api-dbus-runtime-adapter-spec', $responses[0]['kind']);
        self::assertSame('system-api-dbus-runtime-activation-context', $responses[1]['kind']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $responses[2]['kind']);
        self::assertSame('system-api-dbus-runtime-activation-result', $responses[3]['kind']);
        self::assertSame('adapter-cap-001', $responses[4]['request_id']);
        self::assertSame('system.capabilities', $responses[4]['result']['method']);
        self::assertSame('adapter-call-stdio-001', $responses[5]['request_id']);
        self::assertSame('profiles.get', $responses[5]['result']['method']);
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
        self::assertIsResource($process, 'No se pudo ejecutar system_api_dbus_runtime_adapter.php');

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
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_dbus_runtime_adapter.php';
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
    private function nativeCompatibleBackendEnvironment(): array
    {
        return [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ];
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
