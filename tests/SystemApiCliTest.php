<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiCliTest extends TestCase
{
    public function testCapabilitiesResponseListsSupportedMethods(): void
    {
        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--capabilities',
            '--request-id',
            'cap-001',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('1.0', $payload['api_version']);
        self::assertSame('cap-001', $payload['request_id']);
        self::assertNull($payload['error']);
        self::assertSame('system.capabilities', $payload['result']['method']);
        self::assertSame('system-api-capabilities', $payload['result']['resource']['kind']);
        self::assertSame('scripts/system_api_dbus_bridge.php', $payload['result']['data']['binding']['local_dbus_bridge']['script']);
        self::assertContains('Call', $payload['result']['data']['binding']['local_dbus_bridge']['commands']);
        self::assertSame('pending-runtime-adapter', $payload['result']['data']['binding']['local_dbus_bridge']['adapter_status']);
        self::assertSame('serve-dbus', $payload['result']['data']['binding']['local_dbus_bridge']['activation_command']);
        self::assertSame('system.dbus-activation', $payload['result']['data']['binding']['local_dbus_bridge']['activation_method']);
        self::assertSame('system.dbus-native-service', $payload['result']['data']['binding']['local_dbus_bridge']['native_service_method']);
        self::assertSame('system.dbus-native-startup', $payload['result']['data']['binding']['local_dbus_bridge']['native_startup_method']);
        self::assertSame('system.dbus-native-observation', $payload['result']['data']['binding']['local_dbus_bridge']['native_observation_method']);
        self::assertSame('system.dbus-native-coupling', $payload['result']['data']['binding']['local_dbus_bridge']['native_coupling_method']);
        self::assertSame('system.dbus-native-ownership', $payload['result']['data']['binding']['local_dbus_bridge']['native_ownership_method']);
        self::assertSame('materialize-runtime-adapter', $payload['result']['data']['binding']['local_dbus_bridge']['activation_next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['result']['data']['binding']['local_dbus_bridge']['activation_recovery_plan']);
        self::assertSame('scripts/system_api_dbus_runtime_adapter.php', $payload['result']['data']['binding']['local_runtime_adapter']['script']);
        self::assertSame('spec-ready', $payload['result']['data']['binding']['local_runtime_adapter']['status']);
        self::assertContains('DescribeSpecification', $payload['result']['data']['binding']['local_runtime_adapter']['commands']);
        self::assertContains('experimental-embedded-runtime', $payload['result']['data']['binding']['local_runtime_adapter']['supported_runtime_backends']);
        self::assertContains('native-dbus-extension', $payload['result']['data']['binding']['local_runtime_adapter']['supported_runtime_backends']);
        self::assertSame('pending-runtime-adapter', $payload['result']['data']['binding']['local_runtime_adapter']['runtime_status']);
        self::assertSame('contract-pending', $payload['result']['data']['binding']['local_runtime_adapter']['selected_runtime_backend']);
        self::assertSame('pending-runtime-adapter', $payload['result']['data']['binding']['local_runtime_adapter']['runtime_backend_status']);
        self::assertSame('materialize-runtime-adapter', $payload['result']['data']['binding']['local_runtime_adapter']['activation_next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['result']['data']['binding']['local_runtime_adapter']['activation_recovery_plan']);
        self::assertSame('scripts/system_api_dbus_runtime_host.php', $payload['result']['data']['binding']['local_runtime_host']['script']);
        self::assertSame('host-ready', $payload['result']['data']['binding']['local_runtime_host']['status']);
        self::assertSame('stdio-ndjson-host', $payload['result']['data']['binding']['local_runtime_host']['transport']);
        self::assertSame('pending-runtime-adapter', $payload['result']['data']['binding']['local_runtime_host']['runtime_status']);
        self::assertSame('materialize-runtime-adapter', $payload['result']['data']['binding']['local_runtime_host']['runtime_next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['result']['data']['binding']['local_runtime_host']['runtime_recovery_plan']);
        self::assertSame('scripts/system_api_dbus_runtime_launcher.php', $payload['result']['data']['binding']['local_runtime_launcher']['script']);
        self::assertSame('launcher-ready', $payload['result']['data']['binding']['local_runtime_launcher']['status']);
        self::assertSame('stdio-ndjson-launcher', $payload['result']['data']['binding']['local_runtime_launcher']['transport']);
        self::assertSame('child-process-stdio-relay', $payload['result']['data']['binding']['local_runtime_launcher']['handoff_mode']);
        self::assertSame('contract-pending', $payload['result']['data']['binding']['local_runtime_launcher']['selected_runtime_backend']);
        self::assertSame('pending-runtime-adapter', $payload['result']['data']['binding']['local_runtime_launcher']['runtime_backend_status']);
        self::assertSame('materialize-runtime-adapter', $payload['result']['data']['binding']['local_runtime_launcher']['activation_next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['result']['data']['binding']['local_runtime_launcher']['activation_recovery_plan']);
        self::assertSame('serve-dbus', $payload['result']['data']['binding']['local_runtime_launcher']['runtime_backend_command']);
        self::assertSame('runtime-adapter-activation', $payload['result']['data']['binding']['local_runtime_launcher']['runtime_backend_command_role']);
        self::assertContains('DescribeActivationBootstrap', $payload['result']['data']['binding']['local_runtime_launcher']['commands']);
        self::assertContains('DescribeActivationPreparation', $payload['result']['data']['binding']['local_runtime_launcher']['commands']);
        self::assertContains('AttemptActivation', $payload['result']['data']['binding']['local_runtime_launcher']['commands']);
        self::assertContains('ExecuteActivation', $payload['result']['data']['binding']['local_runtime_launcher']['commands']);
        self::assertContains('AttemptActivation', $payload['result']['data']['binding']['local_runtime_adapter']['commands']);
        self::assertContains('ExecuteActivation', $payload['result']['data']['binding']['local_runtime_adapter']['commands']);
        self::assertContains('DescribeLaunchPlan', $payload['result']['data']['binding']['local_runtime_launcher']['commands']);
        self::assertContains('profiles.list', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('system.dbus-activation', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('system.dbus-native-service', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('system.dbus-native-startup', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('system.dbus-native-observation', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('system.dbus-native-coupling', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('system.dbus-native-ownership', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('system.dbus-runtime', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('updates.plan', array_column($payload['result']['data']['methods'], 'name'));
        $methodsByName = [];
        foreach ($payload['result']['data']['methods'] as $methodDefinition) {
            if (is_array($methodDefinition) && is_string($methodDefinition['name'] ?? null)) {
                $methodsByName[$methodDefinition['name']] = $methodDefinition;
            }
        }
        self::assertFalse($methodsByName['system.dbus-native-service']['mutates_state']);
        self::assertTrue($methodsByName['system.dbus-native-startup']['mutates_state']);
        self::assertFalse($methodsByName['system.dbus-native-observation']['mutates_state']);
        self::assertFalse($methodsByName['system.dbus-native-coupling']['mutates_state']);
        self::assertTrue($methodsByName['system.dbus-native-ownership']['mutates_state']);
    }

    public function testDbusRuntimeMethodReturnsRuntimeDiagnosis(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-runtime-001',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-runtime', $payload['result']['method']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['resource']['kind']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-runtime-contract', $payload['result']['resource']['provider']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['data']['kind']);
        self::assertArrayHasKey('missing_capabilities', $payload['result']['data']);
        self::assertSame('materialize-runtime-adapter', $payload['result']['data']['next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['result']['data']['recovery_plan']);
        self::assertSame('system-api-dbus-runtime-activation-context', $payload['result']['data']['activation_preparation']['kind']);
    }

    public function testDbusActivationMethodReturnsTopLevelActivationReport(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-activation-001',
            'method' => 'system.dbus-activation',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-activation', $payload['result']['method']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['result']['resource']['kind']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-activation-contract', $payload['result']['resource']['provider']);
        self::assertSame('system-api-dbus-runtime-activation', $payload['result']['data']['kind']);
        self::assertSame('local-system-api', $payload['result']['data']['bridge_kind']);
        self::assertSame('materialize-runtime-adapter', $payload['result']['data']['next_action']);
        self::assertSame(['materialize-runtime-adapter'], $payload['result']['data']['recovery_plan']);
        self::assertSame('system-api-dbus-runtime-activation-context', $payload['result']['data']['activation_preparation']['kind']);
        self::assertSame('system-api-dbus-runtime-activation-attempt', $payload['result']['data']['activation_attempt']['kind']);
        self::assertSame('system-api-dbus-runtime-activation-result', $payload['result']['data']['activation_execution']['kind']);
    }

    public function testDbusActivationMethodPublishesTwoStepRecoveryPlanWhenNativeApiIsIncomplete(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-activation-002',
            'method' => 'system.dbus-activation',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-activation', $payload['result']['method']);
        self::assertSame('materialize-runtime-adapter', $payload['result']['data']['next_action']);
        self::assertSame(
            ['materialize-runtime-adapter', 'inspect-native-dbus-extension-api'],
            $payload['result']['data']['recovery_plan']
        );
        self::assertSame('native-dbus-extension', $payload['result']['data']['activation_preparation']['runtime_backend']['selected_backend']);
    }

    public function testDbusNativeServiceMethodReturnsBlockedPublicServiceDefinition(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-service-001',
            'method' => 'system.dbus-native-service',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-native-service', $payload['result']['method']);
        self::assertSame('system-api-dbus-native-runtime-service', $payload['result']['resource']['kind']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-native-service-contract', $payload['result']['resource']['provider']);
        self::assertSame('system-api-dbus-native-runtime-service', $payload['result']['data']['kind']);
        self::assertSame('native-runtime-service-blocked', $payload['result']['data']['status']);
        self::assertFalse($payload['result']['data']['eligible']);
        self::assertSame('install-native-dbus-extension', $payload['result']['data']['next_action']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['data']['runtime_diagnosis']['kind']);
    }

    public function testDbusNativeServiceMethodReturnsReadyPublicServiceDefinition(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-service-002',
            'method' => 'system.dbus-native-service',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-native-service', $payload['result']['method']);
        self::assertSame('native-runtime-service-ready-to-start', $payload['result']['data']['status']);
        self::assertTrue($payload['result']['data']['eligible']);
        self::assertSame('native-dbus-extension', $payload['result']['data']['selected_backend']);
        self::assertSame('start-native-runtime-service', $payload['result']['data']['next_action']);
    }

    public function testDbusNativeStartupMethodReturnsBlockedPublicStartupReport(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-startup-001',
            'method' => 'system.dbus-native-startup',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-native-startup', $payload['result']['method']);
        self::assertSame('system-api-dbus-native-runtime-startup', $payload['result']['resource']['kind']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-native-startup-contract', $payload['result']['resource']['provider']);
        self::assertSame('system-api-dbus-native-runtime-startup', $payload['result']['data']['kind']);
        self::assertSame('native-runtime-service-blocked', $payload['result']['data']['status']);
        self::assertFalse($payload['result']['data']['startup_acknowledged']);
        self::assertSame('install-native-dbus-extension', $payload['result']['data']['next_action']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['data']['runtime_diagnosis']['kind']);
    }

    public function testDbusNativeStartupMethodReturnsStartedPublicStartupReport(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-startup-002',
            'method' => 'system.dbus-native-startup',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-native-startup', $payload['result']['method']);
        self::assertSame('native-runtime-service-started', $payload['result']['data']['status']);
        self::assertTrue($payload['result']['data']['startup_acknowledged']);
        self::assertSame('pending-bus-name-ownership', $payload['result']['data']['ownership_state']);
        self::assertSame('observe-native-runtime-service-state', $payload['result']['data']['next_action']);
        self::assertSame(
            'native-runtime-service-ready-to-start',
            $payload['result']['data']['service_definition']['status']
        );
    }

    public function testDbusNativeStartupMethodPersistsRuntimeArtifacts(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-startup-003',
            'method' => 'system.dbus-native-startup',
            'params' => [],
        ];
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-public-startup-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('native-runtime-service-started', $payload['result']['data']['status']);
            self::assertTrue($payload['result']['data']['startup_acknowledged']);
            self::assertFileExists($readyMarkerPath);
            self::assertFileExists($statusFilePath);
            self::assertFileExists($pidFilePath);
            self::assertSame("native-runtime-service-started\n", file_get_contents($readyMarkerPath));
            $statusPayload = json_decode((string) file_get_contents($statusFilePath), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('system-api-dbus-native-runtime-startup', $statusPayload['kind']);
            self::assertSame('pending-bus-name-ownership', $statusPayload['ownership_state']);
            self::assertSame("0\n", file_get_contents($pidFilePath));
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testDbusNativeObservationMethodReturnsBlockedPublicObservationReport(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-observation-001',
            'method' => 'system.dbus-native-observation',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-native-observation', $payload['result']['method']);
        self::assertSame('system-api-dbus-native-runtime-observation', $payload['result']['resource']['kind']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-native-observation-contract', $payload['result']['resource']['provider']);
        self::assertSame('system-api-dbus-native-runtime-observation', $payload['result']['data']['kind']);
        self::assertSame('native-runtime-service-blocked', $payload['result']['data']['status']);
        self::assertFalse($payload['result']['data']['observed']);
        self::assertSame('install-native-dbus-extension', $payload['result']['data']['next_action']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['data']['runtime_diagnosis']['kind']);
    }

    public function testDbusNativeObservationMethodReturnsPendingOwnershipObservationAgainstArtifacts(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-observation-002',
            'method' => 'system.dbus-native-observation',
            'params' => [],
        ];
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-public-observation-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-started\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "0\n");

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('system.dbus-native-observation', $payload['result']['method']);
            self::assertSame('native-runtime-service-observed-pending-ownership', $payload['result']['data']['status']);
            self::assertTrue($payload['result']['data']['observed']);
            self::assertTrue($payload['result']['data']['startup_acknowledged']);
            self::assertSame('pending-bus-name-ownership', $payload['result']['data']['ownership_state']);
            self::assertSame('native-runtime-bus-ownership-pending', $payload['result']['data']['blocking_reason']);
            self::assertSame('materialize-native-bus-ownership', $payload['result']['data']['next_action']);
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testDbusNativeCouplingMethodReturnsBlockedPublicCouplingReport(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-coupling-001',
            'method' => 'system.dbus-native-coupling',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-native-coupling', $payload['result']['method']);
        self::assertSame('system-api-dbus-native-runtime-coupling', $payload['result']['resource']['kind']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-native-coupling-contract', $payload['result']['resource']['provider']);
        self::assertSame('system-api-dbus-native-runtime-coupling', $payload['result']['data']['kind']);
        self::assertSame('native-runtime-coupling-blocked', $payload['result']['data']['status']);
        self::assertSame('install-native-dbus-extension', $payload['result']['data']['next_action']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['data']['runtime_diagnosis']['kind']);
    }

    public function testDbusNativeCouplingMethodReturnsReadyPublicCouplingReportAgainstFakeRuntime(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-coupling-002',
            'method' => 'system.dbus-native-coupling',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
            'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
            'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
            'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
            'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
            'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
            'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
            'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
            'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
            'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-native-coupling', $payload['result']['method']);
        self::assertSame('native-runtime-coupling-ready', $payload['result']['data']['status']);
        self::assertTrue($payload['result']['data']['compatible']);
        self::assertSame('materialize-native-bus-ownership', $payload['result']['data']['next_action']);
        self::assertSame(
            'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
            $payload['result']['data']['coupling_driver']['class_name']
        );
        self::assertSame(
            'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
            $payload['result']['data']['coupling_driver']['runtime_class_name']
        );
    }

    public function testDbusNativeOwnershipMethodReturnsBlockedPublicOwnershipReport(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-ownership-001',
            'method' => 'system.dbus-native-ownership',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('system.dbus-native-ownership', $payload['result']['method']);
        self::assertSame('system-api-dbus-native-runtime-ownership', $payload['result']['resource']['kind']);
        self::assertSame('org.w4.System1', $payload['result']['resource']['id']);
        self::assertSame('local-system-api-native-ownership-contract', $payload['result']['resource']['provider']);
        self::assertSame('system-api-dbus-native-runtime-ownership', $payload['result']['data']['kind']);
        self::assertSame('native-runtime-service-blocked', $payload['result']['data']['status']);
        self::assertFalse($payload['result']['data']['attempted']);
        self::assertSame('install-native-dbus-extension', $payload['result']['data']['next_action']);
        self::assertSame('system-api-dbus-runtime-diagnosis', $payload['result']['data']['runtime_diagnosis']['kind']);
    }

    public function testDbusNativeOwnershipMethodReturnsOwnedPublicOwnershipReportAgainstFakeRuntime(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-ownership-002',
            'method' => 'system.dbus-native-ownership',
            'params' => [],
        ];
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-public-ownership-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-started\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "0\n");

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('system.dbus-native-ownership', $payload['result']['method']);
            self::assertSame('native-runtime-bus-owned', $payload['result']['data']['status']);
            self::assertTrue($payload['result']['data']['attempted']);
            self::assertSame('native-driver', $payload['result']['data']['execution_mode']);
            self::assertSame('bus-name-owned', $payload['result']['data']['ownership_state']);
            self::assertSame('runtime-service-active', $payload['result']['data']['next_action']);
            self::assertSame(
                'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
                $payload['result']['data']['ownership_driver']['class_name']
            );
            self::assertSame(
                'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
                $payload['result']['data']['ownership_driver']['runtime_class_name']
            );
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testDbusNativeOwnershipMethodPersistsOwnedArtifactsAgainstFakeRuntime(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-ownership-003',
            'method' => 'system.dbus-native-ownership',
            'params' => [],
        ];
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-public-ownership-persist-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-started\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "0\n");

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION' => '1',
                'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => '1',
                'W4_SYSTEM_API_PROBE_HAS_GDBUS' => '0',
                'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => '1',
                'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => '1',
                'W4_SYSTEM_API_PROBE_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PROBE_PHP_BINARY' => '/usr/bin/php',
                'W4_SYSTEM_API_PROBE_PHP_VERSION' => '8.4.3',
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('native-runtime-bus-owned', $payload['result']['data']['status']);
            self::assertTrue($payload['result']['data']['attempted']);
            self::assertSame("native-runtime-bus-owned\n", file_get_contents($readyMarkerPath));
            $statusPayload = json_decode((string) file_get_contents($statusFilePath), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('system-api-dbus-native-runtime-ownership', $statusPayload['kind']);
            self::assertSame('bus-name-owned', $statusPayload['ownership_state']);
            self::assertSame("0\n", file_get_contents($pidFilePath));
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testProfilesGetReusesW4ctlContract(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-profile-001',
            'method' => 'profiles.get',
            'params' => [
                'profile_id' => 'w4-os-home',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('profiles.get', $payload['result']['method']);
        self::assertSame('edition-profile', $payload['result']['resource']['kind']);
        self::assertSame('w4-os-home', $payload['result']['resource']['id']);
        self::assertSame('profile:show', $payload['result']['data']['command']);
        self::assertSame('w4-os-home', $payload['result']['data']['profile']['id']);
        self::assertContains('gdm3', $payload['result']['data']['resolved_profile']['required_packages']);
    }

    public function testUpdatesPlanWritesCanonicalPlan(): void
    {
        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-update-plan-' . bin2hex(random_bytes(6)) . '.json';

        try {
            $request = [
                'api_version' => '1.0',
                'request_id' => 'req-update-001',
                'method' => 'updates.plan',
                'params' => [
                    'request_path' => 'examples/update/home-lab-to-1.0.1.update-request.json',
                    'operation_id' => 'system-api-update-001',
                    'output_path' => $outputPath,
                ],
            ];

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('updates.plan', $payload['result']['method']);
            self::assertSame('update-plan', $payload['result']['resource']['kind']);
            self::assertSame('update:plan', $payload['result']['data']['command']);
            self::assertSame('system-api-update-001', $payload['result']['data']['operation_id']);
            self::assertSame($this->normalizePath($outputPath), $payload['result']['data']['output_path']);

            $plan = $this->decodeJsonFile($outputPath);
            self::assertSame('update-plan', $plan['kind']);
            self::assertSame('system-api-update-001', $plan['operation_id']);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    public function testUnsupportedMethodReturnsProtocolErrorPayload(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-unsupported-001',
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['result']);
        self::assertSame('NOT_SUPPORTED', $payload['error']['code']);
        self::assertStringContainsString('Metodo no soportado', $payload['error']['message']);
    }

    /**
     * @param list<string> $command
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
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
        self::assertIsResource($process, 'No se pudo ejecutar read_system_api.php');

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

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer %s', $path));

        return $this->decodeJson($raw);
    }

    private function normalizePath(string $path): string
    {
        return str_replace(DIRECTORY_SEPARATOR, '/', $path);
    }

    private function scriptPath(): string
    {
        return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'read_system_api.php';
    }
}
