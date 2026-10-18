<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiDbusRuntimeLauncherCliTest extends TestCase
{
    public function testDescribeLauncherReturnsLauncherContract(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeLauncher',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-launcher', $payload['kind']);
        self::assertSame('launcher-ready', $payload['status']);
        self::assertSame('child-process-stdio-relay', $payload['handoff_mode']);
        self::assertContains('DescribeActivationBootstrap', $payload['commands']);
        self::assertContains('DescribeActivationPreparation', $payload['commands']);
        self::assertContains('AttemptActivation', $payload['commands']);
        self::assertContains('ExecuteActivation', $payload['commands']);
        self::assertContains('DescribeLaunchPlan', $payload['commands']);
        self::assertContains('DescribeNativeRuntimeService', $payload['commands']);
        self::assertContains('StartNativeRuntimeService', $payload['commands']);
        self::assertContains('DescribeNativeRuntimeCoupling', $payload['commands']);
        self::assertContains('ObserveNativeRuntimeServiceState', $payload['commands']);
        self::assertContains('MaterializeNativeBusOwnership', $payload['commands']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $payload['launch_plan']['kind']);
        self::assertSame('system-api-dbus-runtime-bootstrap', $payload['launch_plan']['activation_bootstrap']['kind']);
        self::assertSame('child-process-stdio-relay', $payload['launch_plan']['handoff']['mode']);
        self::assertSame('serve-stdio-host', $payload['launch_plan']['target_runtime']['command']);
    }

    public function testDescribeActivationBootstrapReturnsBootstrapContract(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeActivationBootstrap',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-bootstrap', $payload['kind']);
        self::assertSame('pending-runtime-adapter', $payload['status']);
        self::assertSame('serve-dbus', $payload['launcher']['command']);
        self::assertSame('child-process-stdio-relay', $payload['host_handoff']['mode']);
        self::assertContains('bus-name-ownership', $payload['runtime_adapter']['required_capabilities']);
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
        self::assertSame('pending-runtime-adapter', $payload['status']);
        self::assertSame('scripts/system_api_dbus_runtime_launcher.php', $payload['resolved_contract']['runtime_launcher_entry']);
        self::assertSame('scripts/system_api_dbus_runtime_host.php', $payload['resolved_contract']['runtime_host_entry']);
        self::assertSame('scripts/system_api_dbus_runtime_adapter.php', $payload['resolved_contract']['runtime_adapter_entry']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['recovery_plan']);
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
        self::assertSame('StartNativeRuntimeService', $payload['execution_plan']['runtime_backend']['command']);
        self::assertSame('native-runtime-service-bootstrap', $payload['execution_plan']['runtime_backend']['command_role']);
        self::assertSame('long-running-dbus-service', $payload['execution_plan']['runtime_backend']['activation_mode']);
    }

    public function testDescribeActivationPreparationPublishesActionableNativeRecoveryWhenApiIsIncomplete(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'DescribeActivationPreparation',
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
        self::assertSame('system-api-dbus-runtime-activation-context', $payload['kind']);
        self::assertSame('inspect-native-dbus-extension-api', $payload['next_action']);
        self::assertSame('inspect-native-dbus-extension-api', $payload['native_runtime_service']['next_action']);
        self::assertSame('dbus-extension-api-incomplete', $payload['native_runtime_service']['blocking_reason']);
        self::assertSame(['inspect-native-dbus-extension-api'], $payload['recovery_plan']);
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
        self::assertSame('local-runtime-launcher', $payload['bridge_kind']);
        self::assertSame('blocked-missing-capabilities', $payload['status']);
        self::assertSame('missing-runtime-capabilities', $payload['blocking_reason']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['recovery_plan']);
    }

    public function testAttemptActivationPublishesActionableNativeRecoveryWhenApiIsIncomplete(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'AttemptActivation',
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
        self::assertSame('blocked-native-runtime-backend', $payload['status']);
        self::assertSame('dbus-extension-api-incomplete', $payload['blocking_reason']);
        self::assertSame('inspect-native-dbus-extension-api', $payload['next_action']);
        self::assertSame('inspect-native-dbus-extension-api', $payload['native_runtime_service']['next_action']);
        self::assertSame(['inspect-native-dbus-extension-api'], $payload['recovery_plan']);
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
        self::assertSame('local-runtime-launcher', $payload['bridge_kind']);
        self::assertSame('execution-blocked', $payload['status']);
        self::assertSame('missing-runtime-capabilities', $payload['blocking_reason']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['recovery_plan']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $payload['activation_attempt']['kind']);
    }

    public function testExecuteActivationPublishesActionableNativeRecoveryWhenApiIsIncomplete(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'ExecuteActivation',
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

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('execution-blocked', $payload['status']);
        self::assertSame('dbus-extension-api-incomplete', $payload['blocking_reason']);
        self::assertSame('inspect-native-dbus-extension-api', $payload['next_action']);
        self::assertSame('inspect-native-dbus-extension-api', $payload['activation_attempt']['next_action']);
        self::assertSame(['inspect-native-dbus-extension-api'], $payload['recovery_plan']);
    }

    public function testAttemptActivationPublishesTwoStepRecoveryPlanWhenRuntimeIsMissingAndNativeApiIsIncomplete(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'AttemptActivation',
        ], '', [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked-missing-capabilities', $payload['status']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
        self::assertSame(
            ['materialize-runtime-adapter', 'inspect-native-dbus-extension-api'],
            $payload['recovery_plan']
        );
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
        self::assertSame('StartNativeRuntimeService', $payload['target_command']['command']);
        self::assertSame('native-runtime-service-bootstrap', $payload['target_command']['command_role']);
        self::assertSame('long-running-dbus-service', $payload['target_command']['activation_mode']);
        self::assertSame('system-api-dbus-native-runtime-startup', $payload['command_result']['payload']['kind']);
        self::assertSame('native-runtime-service-bootstrap-recorded', $payload['command_result']['payload']['status']);
        self::assertSame('launch-native-runtime-service', $payload['next_action']);
    }

    public function testServeDbusReturnsActivationWithLaunchPlan(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'serve-dbus',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['kind']);
        self::assertSame('local-runtime-launcher', $payload['bridge_kind']);
        self::assertSame('system-api-dbus-runtime-bootstrap', $payload['activation_bootstrap']['kind']);
        self::assertSame('system-api-dbus-runtime-activation-context', $payload['activation_preparation']['kind']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $payload['activation_attempt']['kind']);
        self::assertSame('system-api-dbus-runtime-activation-result', $payload['activation_execution']['kind']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $payload['launch_plan']['kind']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['recovery_plan']);
    }

    public function testServeDbusPublishesTwoStepRecoveryPlanWhenRuntimeIsMissingAndNativeApiIsIncomplete(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            'serve-dbus',
        ], '', [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['kind']);
        self::assertSame('materialize-runtime-adapter', $payload['next_action']);
        self::assertSame(
            ['materialize-runtime-adapter', 'inspect-native-dbus-extension-api'],
            $payload['recovery_plan']
        );
    }

    public function testServeDbusCompletesActivationWhenMaterializedBackendIsConfigured(): void
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
        self::assertSame('system-api-dbus-runtime-activation-result', $payload['kind']);
        self::assertSame('execution-completed', $payload['status']);
        self::assertSame('local-runtime-launcher', $payload['bridge_kind']);
        self::assertSame('serve-dbus', $payload['requested_command']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $payload['launch_plan']['kind']);
        self::assertSame(0, $payload['exit_code']);
    }

    public function testServeStdioLauncherDelegatesToLauncherAndHostMethods(): void
    {
        $inputLines = [
            json_encode([
                'method' => 'DescribeLauncher',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'DescribeLaunchPlan',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'DescribeActivationBootstrap',
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
                'method' => 'Call',
                'arguments' => [
                    'request' => [
                        'api_version' => '1.0',
                        'request_id' => 'runtime-launcher-call-001',
                        'method' => 'system.dbus-runtime',
                        'params' => [],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ];

        $result = $this->runPhpScript(
            [
                $this->scriptPath(),
                'serve-stdio-launcher',
            ],
            implode(PHP_EOL, $inputLines) . PHP_EOL
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $responses = $this->decodeNdjson($result['stdout']);
        self::assertCount(7, $responses);
        self::assertSame('system-api-dbus-runtime-launcher', $responses[0]['kind']);
        self::assertSame('system-api-dbus-runtime-launch-plan', $responses[1]['kind']);
        self::assertSame('system-api-dbus-runtime-bootstrap', $responses[2]['kind']);
        self::assertSame('system-api-dbus-runtime-activation-context', $responses[3]['kind']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $responses[4]['kind']);
        self::assertSame('system-api-dbus-runtime-activation-result', $responses[5]['kind']);
        self::assertSame('runtime-launcher-call-001', $responses[6]['request_id']);
        self::assertSame('system.dbus-runtime', $responses[6]['result']['method']);
    }

    public function testStartNativeRuntimeServiceCanBeInvokedDirectlyThroughLauncherScript(): void
    {
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-runtime-launcher-start-' . bin2hex(random_bytes(6));
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
            self::assertSame('native-runtime-service-bootstrap-recorded', $payload['status']);
            self::assertFileExists($readyMarkerPath);
            self::assertFileExists($statusFilePath);
            self::assertFileExists($pidFilePath);
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testServeStdioLauncherRelaysNativeRuntimeCommandsToHost(): void
    {
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-runtime-launcher-relay-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';
        $inputLines = [
            json_encode([
                'method' => 'DescribeNativeRuntimeService',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
            json_encode([
                'method' => 'StartNativeRuntimeService',
                'arguments' => [],
            ], JSON_THROW_ON_ERROR),
        ];

        try {
            $result = $this->runPhpScript(
                [
                    $this->scriptPath(),
                    'serve-stdio-launcher',
                ],
                implode(PHP_EOL, $inputLines) . PHP_EOL,
                array_merge(
                    $this->readyRuntimeEnvironment(),
                    $this->nativeCompatibleBackendEnvironment(),
                    [
                        'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                        'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                        'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                    ]
                )
            );

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $responses = $this->decodeNdjson($result['stdout']);
            self::assertCount(2, $responses);
            self::assertSame('system-api-dbus-native-runtime-service', $responses[0]['kind']);
            self::assertSame('system-api-dbus-native-runtime-startup', $responses[1]['kind']);
            self::assertSame('native-runtime-service-bootstrap-recorded', $responses[1]['status']);
            self::assertFileExists($readyMarkerPath);
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
        self::assertIsResource($process, 'No se pudo ejecutar system_api_dbus_runtime_launcher.php');

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
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_dbus_runtime_launcher.php';
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
