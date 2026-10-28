<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Update\UpdateToolkit;

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
        self::assertContains('packages.search', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('packages.info', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('packages.list_installed', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('packages.plan', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('packages.apply', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('updates.check', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('updates.list', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('updates.apply', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('updates.reconcile', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('operations.get', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('operations.cancel', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('services.list', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('services.status', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('services.start', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('services.stop', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('services.restart', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('services.enable', array_column($payload['result']['data']['methods'], 'name'));
        self::assertContains('services.disable', array_column($payload['result']['data']['methods'], 'name'));
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
        self::assertFalse($methodsByName['packages.search']['mutates_state']);
        self::assertFalse($methodsByName['packages.info']['mutates_state']);
        self::assertFalse($methodsByName['packages.list_installed']['mutates_state']);
        self::assertFalse($methodsByName['packages.plan']['mutates_state']);
        self::assertTrue($methodsByName['packages.apply']['mutates_state']);
        self::assertSame(['plan_id', 'plan_hash', 'idempotency_key', 'expected_catalog_revision?', 'plan?', 'plan_path?'], $methodsByName['packages.apply']['params']);
        self::assertFalse($methodsByName['updates.check']['mutates_state']);
        self::assertFalse($methodsByName['updates.list']['mutates_state']);
        self::assertTrue($methodsByName['updates.apply']['mutates_state']);
        self::assertTrue($methodsByName['updates.reconcile']['mutates_state']);
        self::assertSame(['refresh?', 'limit?'], $methodsByName['updates.check']['params']);
        self::assertSame(['limit?'], $methodsByName['updates.list']['params']);
        self::assertSame(['operation_id', 'plan_hash', 'idempotency_key', 'expected_catalog_revision?', 'plan?', 'plan_path?'], $methodsByName['updates.apply']['params']);
        self::assertSame(['operation_id', 'observed_stage?', 'health_result_path?', 'details?', 'observed_error?'], $methodsByName['updates.reconcile']['params']);
        self::assertFalse($methodsByName['operations.get']['mutates_state']);
        self::assertTrue($methodsByName['operations.cancel']['mutates_state']);
        self::assertFalse($methodsByName['services.list']['mutates_state']);
        self::assertFalse($methodsByName['services.status']['mutates_state']);
        self::assertTrue($methodsByName['services.start']['mutates_state']);
        self::assertTrue($methodsByName['services.stop']['mutates_state']);
        self::assertTrue($methodsByName['services.restart']['mutates_state']);
        self::assertTrue($methodsByName['services.enable']['mutates_state']);
        self::assertTrue($methodsByName['services.disable']['mutates_state']);
        self::assertSame(['unit', 'expected_revision?', 'idempotency_key?'], $methodsByName['services.start']['params']);
        self::assertSame(['unit', 'expected_revision?', 'idempotency_key?'], $methodsByName['services.stop']['params']);
        self::assertSame(['unit', 'expected_revision?', 'idempotency_key?'], $methodsByName['services.restart']['params']);
        self::assertSame(['unit', 'expected_revision?', 'idempotency_key?'], $methodsByName['services.enable']['params']);
        self::assertSame(['unit', 'expected_revision?', 'idempotency_key?'], $methodsByName['services.disable']['params']);
        self::assertTrue($methodsByName['system.dbus-native-startup']['mutates_state']);
        self::assertFalse($methodsByName['system.dbus-native-observation']['mutates_state']);
        self::assertFalse($methodsByName['system.dbus-native-coupling']['mutates_state']);
        self::assertTrue($methodsByName['system.dbus-native-ownership']['mutates_state']);
    }

    public function testCapabilitiesResponseProjectsObservedNativeRuntimeState(): void
    {
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-capabilities-observed-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-bootstrap-recorded\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
                'service_definition' => [
                    'startup_mode' => 'launch-process',
                    'blocking_reason' => 'native-runtime-bus-ownership-pending',
                    'next_action' => 'observe-native-runtime-service-state',
                ],
                'next_action' => 'observe-native-runtime-service-state',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "4242\n");

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--capabilities',
                '--request-id',
                'cap-observed-001',
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
            self::assertSame(
                'materialize-native-bus-ownership',
                $payload['result']['data']['binding']['local_dbus_bridge']['activation_next_action']
            );
            self::assertSame(
                'materialize-native-bus-ownership',
                $payload['result']['data']['binding']['local_runtime_adapter']['activation_next_action']
            );
            self::assertSame(
                'materialize-native-bus-ownership',
                $payload['result']['data']['binding']['local_runtime_host']['runtime_next_action']
            );
            self::assertSame(
                'materialize-native-bus-ownership',
                $payload['result']['data']['binding']['local_runtime_launcher']['activation_next_action']
            );
            self::assertSame(
                ['materialize-native-bus-ownership', 'launch-native-runtime-service', 'start-native-runtime-service'],
                $payload['result']['data']['binding']['local_runtime_adapter']['activation_recovery_plan']
            );
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testCapabilitiesResponseProjectsActiveNativeRuntimeOwnershipState(): void
    {
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-capabilities-active-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-bootstrap-recorded\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
                'service_definition' => [
                    'startup_mode' => 'launch-process',
                    'blocking_reason' => 'native-runtime-bus-ownership-pending',
                    'next_action' => 'observe-native-runtime-service-state',
                ],
                'next_action' => 'observe-native-runtime-service-state',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "4242\n");

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--capabilities',
                '--request-id',
                'cap-active-001',
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
                'W4_SYSTEM_API_NATIVE_RUNTIME_ACTIVE' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_OWNERSHIP_STATE' => 'bus-name-owned',
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame(
                'runtime-service-active',
                $payload['result']['data']['binding']['local_dbus_bridge']['activation_next_action']
            );
            self::assertSame(
                'runtime-service-active',
                $payload['result']['data']['binding']['local_runtime_adapter']['activation_next_action']
            );
            self::assertSame(
                'runtime-service-active',
                $payload['result']['data']['binding']['local_runtime_host']['runtime_next_action']
            );
            self::assertSame(
                'runtime-service-active',
                $payload['result']['data']['binding']['local_runtime_launcher']['activation_next_action']
            );
            self::assertSame(
                'runtime-service-active',
                $payload['result']['data']['binding']['local_runtime_adapter']['activation_recovery_plan'][0]
            );
            self::assertContains(
                'launch-native-runtime-service',
                $payload['result']['data']['binding']['local_runtime_adapter']['activation_recovery_plan']
            );
            self::assertContains(
                'start-native-runtime-service',
                $payload['result']['data']['binding']['local_runtime_adapter']['activation_recovery_plan']
            );
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testPackagesSearchMethodReturnsSearchResultsFromFixture(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-packages-search-001',
            'method' => 'packages.search',
            'params' => [
                'query' => 'php',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_PACKAGES_SEARCH_OUTPUT' => "php8.4-cli - command-line interpreter for the PHP scripting language\nphp8.4-dev - files for PHP module development\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('packages.search', $payload['result']['method']);
        self::assertSame('package-search-result', $payload['result']['resource']['kind']);
        self::assertSame('local-system-api-packages-contract', $payload['result']['resource']['provider']);
        self::assertSame('system-api-packages-search', $payload['result']['data']['kind']);
        self::assertSame('php', $payload['result']['data']['query']);
        self::assertCount(2, $payload['result']['data']['items']);
        self::assertSame('php8.4-cli', $payload['result']['data']['items'][0]['name']);
    }

    public function testPackagesInfoMethodReturnsPackageInfoFromFixtures(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-packages-info-001',
            'method' => 'packages.info',
            'params' => [
                'name' => 'php8.4-cli',
                'architecture' => 'amd64',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_PACKAGES_INFO_SHOW_OUTPUT' => "Package: php8.4-cli\nArchitecture: amd64\nVersion: 8.4.3-1\nSource: php8.4\nDescription: command-line interpreter for PHP\n command line runtime\nHomepage: https://www.php.net/\n",
            'W4_SYSTEM_API_PACKAGES_INFO_POLICY_OUTPUT' => "php8.4-cli:\n  Installed: 8.4.3-1\n  Candidate: 8.4.4-1\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('packages.info', $payload['result']['method']);
        self::assertSame('package', $payload['result']['resource']['kind']);
        self::assertSame('system-api-package-info', $payload['result']['data']['kind']);
        self::assertSame('ok', $payload['result']['data']['status']);
        self::assertSame('php8.4-cli', $payload['result']['data']['package']['name']);
        self::assertSame('amd64', $payload['result']['data']['package']['architecture']);
        self::assertSame('8.4.3-1', $payload['result']['data']['package']['installed_version']);
        self::assertSame('8.4.4-1', $payload['result']['data']['package']['candidate_version']);
    }

    public function testPackagesListInstalledMethodReturnsInstalledPackagesFromFixture(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-packages-list-001',
            'method' => 'packages.list_installed',
            'params' => [
                'limit' => 1,
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_PACKAGES_LIST_INSTALLED_OUTPUT' => "bash\tamd64\t5.2.15-2\tii \nphp8.4-cli\tamd64\t8.4.3-1\tii \n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('packages.list_installed', $payload['result']['method']);
        self::assertSame('package-installed-collection', $payload['result']['resource']['kind']);
        self::assertSame('system-api-packages-installed-list', $payload['result']['data']['kind']);
        self::assertSame(1, $payload['result']['data']['count']);
        self::assertSame('bash', $payload['result']['data']['items'][0]['name']);
    }

    public function testUpdatesListReturnsNormalizedUpgradablePackages(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-updates-list-001',
            'method' => 'updates.list',
            'params' => [
                'limit' => 1,
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_UPDATES_LIST_OUTPUT' => "Listing... Done\nphp8.4-cli/stable 8.4.4-1 amd64 [upgradable from: 8.4.3-1]\nbash/stable 5.2.15-2 amd64 [upgradable from: 5.2.15-1]\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('updates.list', $payload['result']['method']);
        self::assertSame('updates-list', $payload['result']['resource']['kind']);
        self::assertSame('system-api-updates-list', $payload['result']['data']['kind']);
        self::assertSame(1, $payload['result']['data']['count']);
        self::assertSame('php8.4-cli', $payload['result']['data']['items'][0]['name']);
        self::assertSame('8.4.4-1', $payload['result']['data']['items'][0]['candidate_version']);
        self::assertSame('8.4.3-1', $payload['result']['data']['items'][0]['installed_version']);
        self::assertSame('stable', $payload['result']['data']['items'][0]['origin']);
        self::assertIsString($payload['result']['data']['catalog_revision']);
    }

    public function testUpdatesCheckRefreshesCatalogAndReturnsAvailability(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-updates-check-001',
            'method' => 'updates.check',
            'params' => [
                'refresh' => true,
                'limit' => 1,
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_UPDATES_REFRESH_STDOUT' => 'Hit:1 http://deb.debian.org/debian stable InRelease',
            'W4_SYSTEM_API_UPDATES_LIST_OUTPUT' => "Listing... Done\nphp8.4-cli/stable 8.4.4-1 amd64 [upgradable from: 8.4.3-1]\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('updates.check', $payload['result']['method']);
        self::assertSame('updates-check', $payload['result']['resource']['kind']);
        self::assertSame('system-api-updates-check', $payload['result']['data']['kind']);
        self::assertSame('updates-available', $payload['result']['data']['status']);
        self::assertTrue($payload['result']['data']['refresh_requested']);
        self::assertTrue($payload['result']['data']['refresh_performed']);
        self::assertSame('apt-get update', $payload['result']['data']['refresh']['command']);
        self::assertSame(1, $payload['result']['data']['count']);
        self::assertSame('php8.4-cli', $payload['result']['data']['updates']['items'][0]['name']);
    }

    public function testPackagesPlanWritesCanonicalPlanFromFixture(): void
    {
        $outputPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-package-plan-' . bin2hex(random_bytes(6)) . '.json';

        try {
            $request = [
                'api_version' => '1.0',
                'request_id' => 'req-packages-plan-001',
                'method' => 'packages.plan',
                'params' => [
                    'action' => 'install',
                    'selection' => [
                        ['name' => 'php8.4-cli', 'architecture' => 'amd64'],
                    ],
                    'conffile_policy' => 'keep-local',
                    'output_path' => $outputPath,
                ],
            ];

            $fixturePlan = [
                'kind' => 'package-plan',
                'status' => 'ok',
                'backend' => 'apt-get',
                'action' => 'install',
                'plan_id' => 'pkg-plan-fixed-001',
                'actor_scope' => 'local-system-api',
                'catalog_revision' => 'catalog-fixed-001',
                'created_at' => '2026-10-24T12:00:00Z',
                'expires_at' => '2026-10-24T12:05:00Z',
                'requested_packages' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'requested_version' => null,
                        'origin' => null,
                        'installed_version' => null,
                        'candidate_version' => '8.4.4-1',
                    ],
                ],
                'installs' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'installed_version' => null,
                        'target_version' => '8.4.4-1',
                    ],
                ],
                'upgrades' => [],
                'removals' => [],
                'download_bytes' => 131072,
                'extra_disk_bytes' => 524288,
                'conffile_policy' => 'keep-local',
                'protected_packages' => [],
                'reboot_estimate' => 'none',
                'summary' => [
                    'requested' => 1,
                    'installs' => 1,
                    'upgrades' => 0,
                    'removals' => 0,
                ],
            ];

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PACKAGES_PLAN_JSON' => json_encode($fixturePlan, JSON_THROW_ON_ERROR),
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('packages.plan', $payload['result']['method']);
            self::assertSame('package-plan', $payload['result']['resource']['kind']);
            self::assertSame('packages:plan', $payload['result']['data']['command']);
            self::assertSame('pkg-plan-fixed-001', $payload['result']['data']['plan_id']);
            self::assertSame($this->normalizePath($outputPath), $payload['result']['data']['output_path']);
            self::assertArrayHasKey('plan_hash', $payload['result']['data']);

            $plan = $this->decodeJsonFile($outputPath);
            self::assertSame('package-plan', $plan['kind']);
            self::assertSame('pkg-plan-fixed-001', $plan['plan_id']);
            self::assertSame($payload['result']['data']['plan_hash'], $plan['plan_hash']);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    public function testPackagesApplyConsumesPlanFileAndReturnsOperationReport(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $planPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-package-plan-apply-' . $suffix . '.json';

        try {
            $plan = [
                'kind' => 'package-plan',
                'status' => 'ok',
                'backend' => 'apt-get',
                'action' => 'install',
                'plan_id' => 'pkg-plan-apply-001',
                'actor_scope' => 'local-system-api',
                'catalog_revision' => 'catalog-apply-001',
                'created_at' => '2026-10-24T12:00:00Z',
                'expires_at' => '2026-10-24T12:05:00Z',
                'requested_packages' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'requested_version' => null,
                        'origin' => null,
                        'installed_version' => null,
                        'candidate_version' => '8.4.4-1',
                    ],
                ],
                'installs' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'installed_version' => null,
                        'target_version' => '8.4.4-1',
                    ],
                ],
                'upgrades' => [],
                'removals' => [],
                'download_bytes' => 131072,
                'extra_disk_bytes' => 524288,
                'conffile_policy' => 'keep-local',
                'protected_packages' => [],
                'reboot_estimate' => 'none',
                'summary' => [
                    'requested' => 1,
                    'installs' => 1,
                    'upgrades' => 0,
                    'removals' => 0,
                ],
            ];
            $plan['plan_hash'] = sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            file_put_contents($planPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $request = [
                'api_version' => '1.0',
                'request_id' => 'req-packages-apply-001',
                'method' => 'packages.apply',
                'params' => [
                    'plan_id' => 'pkg-plan-apply-001',
                    'plan_hash' => $plan['plan_hash'],
                    'idempotency_key' => 'pkg-intent-' . $suffix,
                    'plan_path' => $planPath,
                ],
            ];

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE' => '0',
                'W4_SYSTEM_API_PACKAGES_APPLY_STDOUT' => 'Packages applied',
                'W4_SYSTEM_API_PACKAGES_APPLY_STDERR' => '',
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('packages.apply', $payload['result']['method']);
            self::assertSame('package-operation', $payload['result']['resource']['kind']);
            self::assertSame('system-api-package-operation', $payload['result']['data']['kind']);
            self::assertSame('ok', $payload['result']['data']['status']);
            self::assertSame('succeeded', $payload['result']['data']['state']);
            self::assertSame('pkg-plan-apply-001', $payload['result']['data']['plan_id']);
            self::assertSame('pkg-op-' . substr(sha1(('pkg-intent-' . $suffix) . '|' . $plan['plan_hash']), 0, 12), $payload['result']['operation_id']);
        } finally {
            if (is_file($planPath)) {
                unlink($planPath);
            }
        }
    }

    public function testPackagesApplyAcceptsExpectedCatalogRevisionWhenItMatchesPlan(): void
    {
        $planPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-package-plan-apply-revision-' . bin2hex(random_bytes(6)) . '.json';

        try {
            $plan = [
                'kind' => 'package-plan',
                'status' => 'ok',
                'backend' => 'apt-get',
                'action' => 'install',
                'plan_id' => 'pkg-plan-apply-revision-001',
                'actor_scope' => 'local-system-api',
                'catalog_revision' => 'catalog-apply-revision-001',
                'created_at' => '2026-10-24T12:00:00Z',
                'expires_at' => '2026-10-24T12:05:00Z',
                'requested_packages' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'requested_version' => null,
                        'origin' => null,
                        'installed_version' => null,
                        'candidate_version' => '8.4.4-1',
                    ],
                ],
                'installs' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'installed_version' => null,
                        'target_version' => '8.4.4-1',
                    ],
                ],
                'upgrades' => [],
                'removals' => [],
                'download_bytes' => 131072,
                'extra_disk_bytes' => 524288,
                'conffile_policy' => 'keep-local',
                'protected_packages' => [],
                'reboot_estimate' => 'none',
                'summary' => [
                    'requested' => 1,
                    'installs' => 1,
                    'upgrades' => 0,
                    'removals' => 0,
                ],
            ];
            $plan['plan_hash'] = sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            file_put_contents($planPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $request = [
                'api_version' => '1.0',
                'request_id' => 'req-packages-apply-revision-001',
                'method' => 'packages.apply',
                'params' => [
                    'plan_id' => 'pkg-plan-apply-revision-001',
                    'plan_hash' => $plan['plan_hash'],
                    'idempotency_key' => 'pkg-intent-revision-001',
                    'expected_catalog_revision' => 'catalog-apply-revision-001',
                    'plan_path' => $planPath,
                ],
            ];

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE' => '0',
                'W4_SYSTEM_API_PACKAGES_APPLY_STDOUT' => 'Packages applied with revision',
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);
            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('catalog-apply-revision-001', $payload['result']['data']['expected_catalog_revision']);
            self::assertSame('catalog-apply-revision-001', $payload['result']['data']['observed_catalog_revision']);
        } finally {
            if (is_file($planPath)) {
                unlink($planPath);
            }
        }
    }

    public function testPackagesApplyReturnsConflictWhenPlanHashDoesNotMatch(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-packages-apply-conflict-001',
            'method' => 'packages.apply',
            'params' => [
                'plan_id' => 'pkg-plan-conflict-001',
                'plan_hash' => 'wrong-hash',
                'idempotency_key' => 'pkg-intent-conflict-001',
                'plan' => [
                    'kind' => 'package-plan',
                    'status' => 'ok',
                    'backend' => 'apt-get',
                    'action' => 'install',
                    'plan_id' => 'pkg-plan-conflict-001',
                    'plan_hash' => 'another-hash',
                    'requested_packages' => [
                        ['name' => 'php8.4-cli'],
                    ],
                    'conffile_policy' => 'keep-local',
                ],
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
        self::assertSame('CONFLICT', $payload['error']['code']);
        self::assertStringContainsString('plan_id o plan_hash', $payload['error']['message']);
    }

    public function testPackagesApplyReturnsConflictWhenExpectedCatalogRevisionDoesNotMatch(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-packages-apply-catalog-conflict-001',
            'method' => 'packages.apply',
            'params' => [
                'plan_id' => 'pkg-plan-catalog-conflict-001',
                'plan_hash' => 'placeholder',
                'idempotency_key' => 'pkg-intent-catalog-conflict-001',
                'expected_catalog_revision' => 'catalog-expected-001',
                'plan' => [
                    'kind' => 'package-plan',
                    'status' => 'ok',
                    'backend' => 'apt-get',
                    'action' => 'install',
                    'plan_id' => 'pkg-plan-catalog-conflict-001',
                    'catalog_revision' => 'catalog-observed-001',
                    'requested_packages' => [
                        ['name' => 'php8.4-cli'],
                    ],
                    'conffile_policy' => 'keep-local',
                ],
            ],
        ];
        $request['params']['plan']['plan_hash'] = sha1(json_encode($request['params']['plan'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $request['params']['plan_hash'] = $request['params']['plan']['plan_hash'];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['result']);
        self::assertSame('CONFLICT', $payload['error']['code']);
        self::assertStringContainsString('expected_catalog_revision', $payload['error']['message']);
    }

    public function testOperationsGetReturnsPersistedPackageOperation(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $planPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-package-plan-op-get-' . $suffix . '.json';

        try {
            $plan = [
                'kind' => 'package-plan',
                'status' => 'ok',
                'backend' => 'apt-get',
                'action' => 'install',
                'plan_id' => 'pkg-plan-op-get-001',
                'actor_scope' => 'local-system-api',
                'catalog_revision' => 'catalog-op-get-001',
                'created_at' => '2026-10-24T12:00:00Z',
                'expires_at' => '2026-10-24T12:05:00Z',
                'requested_packages' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'requested_version' => null,
                        'origin' => null,
                        'installed_version' => null,
                        'candidate_version' => '8.4.4-1',
                    ],
                ],
                'installs' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'installed_version' => null,
                        'target_version' => '8.4.4-1',
                    ],
                ],
                'upgrades' => [],
                'removals' => [],
                'download_bytes' => 131072,
                'extra_disk_bytes' => 524288,
                'conffile_policy' => 'keep-local',
                'protected_packages' => [],
                'reboot_estimate' => 'none',
                'summary' => [
                    'requested' => 1,
                    'installs' => 1,
                    'upgrades' => 0,
                    'removals' => 0,
                ],
            ];
            $plan['plan_hash'] = sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            file_put_contents($planPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $applyRequest = [
                'api_version' => '1.0',
                'request_id' => 'req-packages-apply-op-get-001',
                'method' => 'packages.apply',
                'params' => [
                    'plan_id' => 'pkg-plan-op-get-001',
                    'plan_hash' => $plan['plan_hash'],
                    'idempotency_key' => 'pkg-intent-op-get-' . $suffix,
                    'plan_path' => $planPath,
                ],
            ];

            $applyResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($applyRequest, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE' => '0',
                'W4_SYSTEM_API_PACKAGES_APPLY_STDOUT' => 'Packages applied',
            ]);

            self::assertSame(0, $applyResult['exitCode'], $applyResult['stderr']);
            $applyPayload = $this->decodeJson($applyResult['stdout']);
            $operationId = $applyPayload['result']['operation_id'];
            self::assertIsString($operationId);

            $getRequest = [
                'api_version' => '1.0',
                'request_id' => 'req-operations-get-001',
                'method' => 'operations.get',
                'params' => [
                    'operation_id' => $operationId,
                ],
            ];

            $getResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($getRequest, JSON_THROW_ON_ERROR),
            ]);

            self::assertSame(0, $getResult['exitCode'], $getResult['stderr']);

            $payload = $this->decodeJson($getResult['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('operations.get', $payload['result']['method']);
            self::assertSame('system-api-operation', $payload['result']['resource']['kind']);
            self::assertSame($operationId, $payload['result']['data']['operation_id']);
            self::assertSame('packages.apply', $payload['result']['data']['method']);
            self::assertSame('succeeded', $payload['result']['data']['state']);
            self::assertSame('pkg-plan-op-get-001', $payload['result']['data']['result']['plan_id']);
        } finally {
            if (is_file($planPath)) {
                unlink($planPath);
            }
        }
    }

    public function testOperationsGetReturnsNotFoundForUnknownOperation(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-operations-get-missing-001',
            'method' => 'operations.get',
            'params' => [
                'operation_id' => 'non-existent-operation-001',
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
        self::assertSame('NOT_FOUND', $payload['error']['code']);
        self::assertStringContainsString('Operacion no encontrada', $payload['error']['message']);
    }

    public function testPackagesApplyReusesOperationForSameIdempotencyKeyAndDigest(): void
    {
        $planPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-package-plan-reuse-' . bin2hex(random_bytes(6)) . '.json';
        $suffix = bin2hex(random_bytes(6));

        try {
            $plan = [
                'kind' => 'package-plan',
                'status' => 'ok',
                'backend' => 'apt-get',
                'action' => 'install',
                'plan_id' => 'pkg-plan-reuse-' . $suffix,
                'actor_scope' => 'local-system-api',
                'catalog_revision' => 'catalog-reuse-' . $suffix,
                'created_at' => '2026-10-24T12:00:00Z',
                'expires_at' => '2026-10-24T12:05:00Z',
                'requested_packages' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'requested_version' => null,
                        'origin' => null,
                        'installed_version' => null,
                        'candidate_version' => '8.4.4-1',
                    ],
                ],
                'installs' => [
                    [
                        'name' => 'php8.4-cli',
                        'architecture' => 'amd64',
                        'installed_version' => null,
                        'target_version' => '8.4.4-1',
                    ],
                ],
                'upgrades' => [],
                'removals' => [],
                'download_bytes' => 131072,
                'extra_disk_bytes' => 524288,
                'conffile_policy' => 'keep-local',
                'protected_packages' => [],
                'reboot_estimate' => 'none',
                'summary' => [
                    'requested' => 1,
                    'installs' => 1,
                    'upgrades' => 0,
                    'removals' => 0,
                ],
            ];
            $plan['plan_hash'] = sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            file_put_contents($planPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $request = [
                'api_version' => '1.0',
                'request_id' => 'req-packages-apply-reuse-' . $suffix,
                'method' => 'packages.apply',
                'params' => [
                    'plan_id' => $plan['plan_id'],
                    'plan_hash' => $plan['plan_hash'],
                    'idempotency_key' => 'pkg-intent-reuse-' . $suffix,
                    'plan_path' => $planPath,
                ],
            ];

            $firstResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE' => '0',
                'W4_SYSTEM_API_PACKAGES_APPLY_STDOUT' => 'Packages applied once',
            ]);

            self::assertSame(0, $firstResult['exitCode'], $firstResult['stderr']);
            $firstPayload = $this->decodeJson($firstResult['stdout']);
            self::assertNull($firstPayload['error']);

            $secondResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE' => '1',
                'W4_SYSTEM_API_PACKAGES_APPLY_STDERR' => 'No deberia ejecutarse otra vez',
            ]);

            self::assertSame(0, $secondResult['exitCode'], $secondResult['stderr']);
            $secondPayload = $this->decodeJson($secondResult['stdout']);
            self::assertNull($secondPayload['error']);
            self::assertSame($firstPayload['result']['operation_id'], $secondPayload['result']['operation_id']);
            self::assertSame('Packages applied once', $secondPayload['result']['data']['stdout']);
            self::assertSame('succeeded', $secondPayload['result']['data']['state']);
        } finally {
            if (is_file($planPath)) {
                unlink($planPath);
            }
        }
    }

    public function testPackagesApplyReturnsConflictWhenLedgerDigestChanges(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $baseRequest = [
            'api_version' => '1.0',
            'method' => 'packages.apply',
        ];

        $planA = [
            'kind' => 'package-plan',
            'status' => 'ok',
            'backend' => 'apt-get',
            'action' => 'install',
            'plan_id' => 'pkg-plan-ledger-a-' . $suffix,
            'requested_packages' => [
                ['name' => 'php8.4-cli'],
            ],
            'conffile_policy' => 'keep-local',
        ];
        $planA['plan_hash'] = sha1(json_encode($planA, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $firstRequest = $baseRequest + [
            'request_id' => 'req-packages-ledger-a-' . $suffix,
            'params' => [
                'plan_id' => $planA['plan_id'],
                'plan_hash' => $planA['plan_hash'],
                'idempotency_key' => 'pkg-intent-ledger-' . $suffix,
                'plan' => $planA,
            ],
        ];

        $firstResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($firstRequest, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_PACKAGES_APPLY_EXIT_CODE' => '0',
            'W4_SYSTEM_API_PACKAGES_APPLY_STDOUT' => 'Packages applied',
        ]);

        self::assertSame(0, $firstResult['exitCode'], $firstResult['stderr']);
        $firstPayload = $this->decodeJson($firstResult['stdout']);
        self::assertNull($firstPayload['error']);

        $planB = $planA;
        $planB['plan_id'] = 'pkg-plan-ledger-b-' . $suffix;
        $planB['requested_packages'] = [
            ['name' => 'php8.4-fpm'],
        ];
        unset($planB['plan_hash']);
        $planB['plan_hash'] = sha1(json_encode($planB, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $secondRequest = $baseRequest + [
            'request_id' => 'req-packages-ledger-b-' . $suffix,
            'params' => [
                'plan_id' => $planB['plan_id'],
                'plan_hash' => $planB['plan_hash'],
                'idempotency_key' => 'pkg-intent-ledger-' . $suffix,
                'plan' => $planB,
            ],
        ];

        $secondResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($secondRequest, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $secondResult['exitCode'], $secondResult['stderr']);
        $secondPayload = $this->decodeJson($secondResult['stdout']);
        self::assertNull($secondPayload['result']);
        self::assertSame('CONFLICT', $secondPayload['error']['code']);
        self::assertStringContainsString('idempotency_key', $secondPayload['error']['message']);
    }

    public function testOperationsCancelReturnsNotCancelableForTerminalServiceOperation(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $restartRequest = [
            'api_version' => '1.0',
            'request_id' => 'req-services-restart-cancel-' . $suffix,
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $restartResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($restartRequest, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $restartResult['exitCode'], $restartResult['stderr']);
        $restartPayload = $this->decodeJson($restartResult['stdout']);
        $operationId = $restartPayload['result']['operation_id'];
        self::assertIsString($operationId);

        $cancelRequest = [
            'api_version' => '1.0',
            'request_id' => 'req-operations-cancel-' . $suffix,
            'method' => 'operations.cancel',
            'params' => [
                'operation_id' => $operationId,
            ],
        ];

        $cancelResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($cancelRequest, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $cancelResult['exitCode'], $cancelResult['stderr']);
        $cancelPayload = $this->decodeJson($cancelResult['stdout']);
        self::assertNull($cancelPayload['error']);
        self::assertSame('operations.cancel', $cancelPayload['result']['method']);
        self::assertSame('system-api-operation-cancel', $cancelPayload['result']['resource']['kind']);
        self::assertFalse($cancelPayload['result']['data']['cancel_requested']);
        self::assertFalse($cancelPayload['result']['data']['cancelable']);
        self::assertSame('not-cancelable', $cancelPayload['result']['data']['status']);
    }

    public function testServicesRestartReusesOperationForSameIdempotencyKeyAndUnit(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-services-restart-reuse-' . $suffix,
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
                'idempotency_key' => 'svc-intent-restart-' . $suffix,
            ],
        ];

        $firstResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_RESTART_STDOUT' => 'restart-once',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $firstResult['exitCode'], $firstResult['stderr']);
        $firstPayload = $this->decodeJson($firstResult['stdout']);
        self::assertNull($firstPayload['error']);

        $secondResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE' => '1',
            'W4_SYSTEM_API_SERVICES_RESTART_STDERR' => 'No deberia ejecutarse otra vez',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $secondResult['exitCode'], $secondResult['stderr']);
        $secondPayload = $this->decodeJson($secondResult['stdout']);
        self::assertNull($secondPayload['error']);
        self::assertSame($firstPayload['result']['operation_id'], $secondPayload['result']['operation_id']);
        self::assertSame('restart-once', $secondPayload['result']['data']['stdout']);
        self::assertSame('succeeded', $secondPayload['result']['data']['state']);
    }

    public function testServicesRestartAcceptsExpectedRevisionWhenItMatchesObservedState(): void
    {
        $statusRequest = [
            'api_version' => '1.0',
            'request_id' => 'req-services-status-revision-ok-001',
            'method' => 'services.status',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $statusResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($statusRequest, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $statusResult['exitCode'], $statusResult['stderr']);
        $statusPayload = $this->decodeJson($statusResult['stdout']);
        $expectedRevision = $statusPayload['result']['data']['revision'];
        self::assertIsString($expectedRevision);

        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-services-restart-revision-ok-001',
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
                'expected_revision' => $expectedRevision,
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_RESTART_STDOUT' => 'restart-with-revision',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame($expectedRevision, $payload['result']['data']['expected_revision']);
        self::assertSame($expectedRevision, $payload['result']['data']['observed_revision_before']);
        self::assertSame($expectedRevision, $payload['result']['data']['observed_revision_after']);
    }

    public function testServicesRestartReturnsConflictWhenSameIdempotencyKeyTargetsAnotherUnit(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $firstRequest = [
            'api_version' => '1.0',
            'request_id' => 'req-services-restart-ledger-a-' . $suffix,
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
                'idempotency_key' => 'svc-intent-ledger-' . $suffix,
            ],
        ];

        $firstResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($firstRequest, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $firstResult['exitCode'], $firstResult['stderr']);
        $firstPayload = $this->decodeJson($firstResult['stdout']);
        self::assertNull($firstPayload['error']);

        $secondRequest = [
            'api_version' => '1.0',
            'request_id' => 'req-services-restart-ledger-b-' . $suffix,
            'method' => 'services.restart',
            'params' => [
                'unit' => 'cron.service',
                'idempotency_key' => 'svc-intent-ledger-' . $suffix,
            ],
        ];

        $secondResult = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($secondRequest, JSON_THROW_ON_ERROR),
        ]);

        self::assertSame(0, $secondResult['exitCode'], $secondResult['stderr']);
        $secondPayload = $this->decodeJson($secondResult['stdout']);
        self::assertNull($secondPayload['result']);
        self::assertSame('CONFLICT', $secondPayload['error']['code']);
        self::assertStringContainsString('idempotency_key', $secondPayload['error']['message']);
    }

    public function testServicesRestartReturnsConflictWhenExpectedRevisionDoesNotMatch(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-services-restart-revision-conflict-001',
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
                'expected_revision' => 'outdated-revision',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['result']);
        self::assertSame('CONFLICT', $payload['error']['code']);
        self::assertStringContainsString('expected_revision', $payload['error']['message']);
    }

    public function testServicesListMethodReturnsServicesFromFixture(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-services-list-001',
            'method' => 'services.list',
            'params' => [],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_SERVICES_LIST_OUTPUT' => "ssh.service enabled enabled\nw4-system-api.service enabled enabled\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('services.list', $payload['result']['method']);
        self::assertSame('service-collection', $payload['result']['resource']['kind']);
        self::assertSame('system-api-services-list', $payload['result']['data']['kind']);
        self::assertCount(2, $payload['result']['data']['items']);
        self::assertSame('ssh.service', $payload['result']['data']['items'][0]['unit']);
    }

    public function testServicesStatusMethodReturnsServiceStatusFromFixture(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-services-status-001',
            'method' => 'services.status',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('services.status', $payload['result']['method']);
        self::assertSame('service-status', $payload['result']['resource']['kind']);
        self::assertSame('system-api-service-status', $payload['result']['data']['kind']);
        self::assertIsString($payload['result']['data']['revision']);
        self::assertSame($payload['result']['data']['revision'], $payload['result']['data']['service']['revision']);
        self::assertSame('active', $payload['result']['data']['service']['active_state']);
        self::assertSame('enabled', $payload['result']['data']['service']['unit_file_state']);
    }

    public function testServicesRestartMethodReturnsSucceededOperationReport(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-services-restart-001',
            'method' => 'services.restart',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_RESTART_EXIT_CODE' => '0',
            'W4_SYSTEM_API_SERVICES_RESTART_STDOUT' => '',
            'W4_SYSTEM_API_SERVICES_RESTART_STDERR' => '',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('services.restart', $payload['result']['method']);
        self::assertSame('service-operation', $payload['result']['resource']['kind']);
        self::assertSame('system-api-service-operation', $payload['result']['data']['kind']);
        self::assertSame('ok', $payload['result']['data']['status']);
        self::assertSame('succeeded', $payload['result']['data']['state']);
        self::assertSame('restart', $payload['result']['data']['action']);
        self::assertSame('observe-service-state', $payload['result']['data']['next_action']);
        self::assertSame('active', $payload['result']['data']['service']['active_state']);
    }

    public function testServicesDisableMethodReturnsFailedOperationReport(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-services-disable-001',
            'method' => 'services.disable',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            'W4_SYSTEM_API_SERVICES_DISABLE_EXIT_CODE' => '1',
            'W4_SYSTEM_API_SERVICES_DISABLE_STDERR' => 'Access denied',
            'W4_SYSTEM_API_SERVICES_STATUS_OUTPUT' => "Id=ssh.service\nDescription=OpenBSD Secure Shell server\nLoadState=loaded\nActiveState=active\nSubState=running\nUnitFileState=enabled\nFragmentPath=/lib/systemd/system/ssh.service\n",
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['error']);
        self::assertSame('services.disable', $payload['result']['method']);
        self::assertSame('failed', $payload['result']['data']['status']);
        self::assertSame('failed', $payload['result']['data']['state']);
        self::assertSame('inspect-service-state', $payload['result']['data']['next_action']);
        self::assertSame(1, $payload['result']['data']['exit_code']);
        self::assertSame('Access denied', $payload['result']['data']['stderr']);
    }

    public function testServicesStartReturnsNotSupportedWhenBackendIsUnavailable(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-services-start-unsupported-001',
            'method' => 'services.start',
            'params' => [
                'unit' => 'ssh.service',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Windows',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['result']);
        self::assertSame('NOT_SUPPORTED', $payload['error']['code']);
        self::assertStringContainsString('services.start', $payload['error']['message']);
    }

    public function testPackagesSearchReturnsNotSupportedWhenBackendIsUnavailable(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-packages-search-unsupported-001',
            'method' => 'packages.search',
            'params' => [
                'query' => 'php',
            ],
        ];

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Windows',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['result']);
        self::assertSame('NOT_SUPPORTED', $payload['error']['code']);
        self::assertStringContainsString('packages.search', $payload['error']['message']);
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

    public function testDbusRuntimeMethodProjectsObservedNativeRuntimeState(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-runtime-observed-001',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ];
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-runtime-observed-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-bootstrap-recorded\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
                'service_definition' => [
                    'startup_mode' => 'launch-process',
                    'blocking_reason' => 'native-runtime-bus-ownership-pending',
                    'next_action' => 'observe-native-runtime-service-state',
                ],
                'next_action' => 'observe-native-runtime-service-state',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "4242\n");

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
            self::assertSame('materialize-native-bus-ownership', $payload['result']['data']['next_action']);
            self::assertSame(
                ['materialize-native-bus-ownership', 'launch-native-runtime-service', 'start-native-runtime-service'],
                $payload['result']['data']['recovery_plan']
            );
            self::assertSame(
                'materialize-native-bus-ownership',
                $payload['result']['data']['activation_preparation']['next_action']
            );
            self::assertSame(
                'launch-process',
                $payload['result']['data']['native_runtime_service']['startup_mode']
            );
            self::assertSame(
                'native-runtime-bus-ownership-pending',
                $payload['result']['data']['native_runtime_service']['blocking_reason']
            );
            self::assertSame(
                'materialize-native-bus-ownership',
                $payload['result']['data']['native_runtime_service']['next_action']
            );
            self::assertSame(
                'launch-process',
                $payload['result']['data']['activation_preparation']['native_runtime_service']['startup_mode']
            );
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testDbusRuntimeMethodProjectsActiveNativeRuntimeOwnershipState(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-runtime-active-001',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ];
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-runtime-active-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-bootstrap-recorded\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
                'service_definition' => [
                    'startup_mode' => 'launch-process',
                    'blocking_reason' => 'native-runtime-bus-ownership-pending',
                    'next_action' => 'observe-native-runtime-service-state',
                ],
                'next_action' => 'observe-native-runtime-service-state',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "4242\n");

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
                'W4_SYSTEM_API_NATIVE_RUNTIME_ACTIVE' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_OWNERSHIP_STATE' => 'bus-name-owned',
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);

            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('runtime-service-active', $payload['result']['data']['next_action']);
            self::assertSame('runtime-service-active', $payload['result']['data']['recovery_plan'][0]);
            self::assertContains('launch-native-runtime-service', $payload['result']['data']['recovery_plan']);
            self::assertContains('start-native-runtime-service', $payload['result']['data']['recovery_plan']);
            self::assertSame(
                'runtime-service-active',
                $payload['result']['data']['activation_preparation']['next_action']
            );
            self::assertSame('launch-process', $payload['result']['data']['native_runtime_service']['startup_mode']);
            self::assertNull($payload['result']['data']['native_runtime_service']['blocking_reason']);
            self::assertSame(
                'runtime-service-active',
                $payload['result']['data']['native_runtime_service']['next_action']
            );
            self::assertSame(
                'launch-process',
                $payload['result']['data']['activation_preparation']['native_runtime_service']['startup_mode']
            );
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
    }

    public function testDbusRuntimeMethodProjectsRuntimeReactivationPlanAfterObservedLoopExit(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-runtime-reactivate-001',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ];
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-runtime-reactivate-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-stopped\n");
            file_put_contents($statusFilePath, json_encode([
                'status' => 'native-runtime-service-loop-exited',
                'startup_acknowledged' => true,
                'ownership_state' => 'startup-recorded-no-process',
                'last_known_ownership_state' => 'bus-name-owned',
                'service_definition' => [
                    'startup_mode' => 'launch-process',
                    'blocking_reason' => 'native-runtime-service-not-running',
                    'next_action' => 'launch-native-runtime-service',
                ],
                'next_action' => 'launch-native-runtime-service',
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
            self::assertSame('reactivate-native-runtime-service', $payload['result']['data']['next_action']);
            self::assertSame('reactivate-native-runtime-service', $payload['result']['data']['recovery_plan'][0]);
            self::assertContains('inspect-native-runtime-service-report', $payload['result']['data']['recovery_plan']);
            self::assertContains('launch-native-runtime-service', $payload['result']['data']['recovery_plan']);
            self::assertContains('start-native-runtime-service', $payload['result']['data']['recovery_plan']);
            self::assertSame(
                'reactivate-native-runtime-service',
                $payload['result']['data']['activation_preparation']['next_action']
            );
            self::assertSame(
                'reactivate-native-runtime-service',
                $payload['result']['data']['native_runtime_service']['next_action']
            );
            self::assertSame(
                'reactivate-native-runtime-service',
                $payload['result']['data']['activation_preparation']['native_runtime_service']['recovery_plan'][0]
            );
            self::assertContains(
                'inspect-native-runtime-service-report',
                $payload['result']['data']['activation_preparation']['native_runtime_service']['recovery_plan']
            );
        } finally {
            $this->cleanupDirectory($baseDirectory);
        }
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
        self::assertSame('native-runtime-service-bootstrap-recorded', $payload['result']['data']['status']);
        self::assertTrue($payload['result']['data']['startup_acknowledged']);
        self::assertSame('startup-recorded-no-process', $payload['result']['data']['ownership_state']);
        self::assertSame('native-runtime-service-not-running', $payload['result']['data']['blocking_reason']);
        self::assertSame('launch-native-runtime-service', $payload['result']['data']['next_action']);
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
            self::assertSame('native-runtime-service-bootstrap-recorded', $payload['result']['data']['status']);
            self::assertTrue($payload['result']['data']['startup_acknowledged']);
            self::assertFileExists($readyMarkerPath);
            self::assertFileExists($statusFilePath);
            self::assertFileExists($pidFilePath);
            self::assertSame("native-runtime-service-bootstrap-recorded\n", file_get_contents($readyMarkerPath));
            $statusPayload = json_decode((string) file_get_contents($statusFilePath), true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('system-api-dbus-native-runtime-startup', $statusPayload['kind']);
            self::assertSame('startup-recorded-no-process', $statusPayload['ownership_state']);
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
            file_put_contents($readyMarkerPath, "native-runtime-service-bootstrap-recorded\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'startup-recorded-no-process',
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
            self::assertSame('native-runtime-service-not-running', $payload['result']['data']['status']);
            self::assertFalse($payload['result']['data']['observed']);
            self::assertTrue($payload['result']['data']['startup_acknowledged']);
            self::assertSame('startup-recorded-no-process', $payload['result']['data']['ownership_state']);
            self::assertSame('native-runtime-service-not-running', $payload['result']['data']['blocking_reason']);
            self::assertSame('launch-native-runtime-service', $payload['result']['data']['next_action']);
            self::assertSame(
                ['launch-native-runtime-service', 'start-native-runtime-service'],
                $payload['result']['data']['recovery_plan']
            );
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testDbusNativeObservationMethodPublishesReactivationPlanAfterObservedLoopExit(): void
    {
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-dbus-native-observation-003',
            'method' => 'system.dbus-native-observation',
            'params' => [],
        ];
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-system-api-public-observation-reactivate-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-service-stopped\n");
            file_put_contents($statusFilePath, json_encode([
                'status' => 'native-runtime-service-loop-exited',
                'startup_acknowledged' => true,
                'ownership_state' => 'startup-recorded-no-process',
                'last_known_ownership_state' => 'bus-name-owned',
                'service_definition' => [
                    'startup_mode' => 'launch-process',
                    'blocking_reason' => 'native-runtime-service-not-running',
                    'next_action' => 'launch-native-runtime-service',
                ],
                'next_action' => 'launch-native-runtime-service',
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
            self::assertSame('native-runtime-service-not-running', $payload['result']['data']['status']);
            self::assertSame('startup-recorded-no-process', $payload['result']['data']['ownership_state']);
            self::assertSame('bus-name-owned', $payload['result']['data']['last_known_ownership_state']);
            self::assertSame('reactivate-native-runtime-service', $payload['result']['data']['next_action']);
            self::assertSame(
                [
                    'reactivate-native-runtime-service',
                    'inspect-native-runtime-service-report',
                    'launch-native-runtime-service',
                    'start-native-runtime-service',
                ],
                $payload['result']['data']['recovery_plan']
            );
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
            file_put_contents($readyMarkerPath, "native-runtime-service-bootstrap-recorded\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "4242\n");

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
            file_put_contents($readyMarkerPath, "native-runtime-service-bootstrap-recorded\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "4242\n");

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
            self::assertSame("4242\n", file_get_contents($pidFilePath));
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
            self::assertIsString($payload['result']['data']['plan_hash']);

            $plan = $this->decodeJsonFile($outputPath);
            self::assertSame('update-plan', $plan['kind']);
            self::assertSame('system-api-update-001', $plan['operation_id']);
            self::assertSame(
                sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)),
                $payload['result']['data']['plan_hash']
            );
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }
        }
    }

    public function testUpdatesApplyConsumesUpdatePlanAndReturnsPendingRebootOperation(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $operationId = 'system-api-update-apply-' . $suffix;
        $planPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $operationId . '.json';

        try {
            $plan = [
                'update_plan_schema_version' => 1,
                'kind' => 'update-plan',
                'operation_id' => $operationId,
                'profile_id' => 'w4-os-home',
                'source_version' => '1.0.0-lab',
                'target_version' => '1.0.1-lab',
                'repository_snapshot' => [
                    'id' => 'w4-main-2026-09-19T120000Z',
                    'channel' => 'testing',
                ],
                'catalog_revision' => 'updates-catalog-' . $suffix,
                'package_changes' => [
                    'install' => ['w4-recovery-tools'],
                    'upgrade' => ['w4-home-meta'],
                    'remove' => [],
                    'counts' => [
                        'install' => 1,
                        'upgrade' => 1,
                        'remove' => 0,
                        'total' => 2,
                    ],
                ],
                'execution' => [
                    'engine' => 'apt-offline-snapshot',
                    'apply_mode' => 'live-apt',
                    'reboot_required' => true,
                    'stages' => [
                        'planned',
                        'downloading',
                        'ready',
                        'prepared',
                        'applying_offline',
                        'pending_health',
                        'confirmed',
                    ],
                    'failure_state' => 'failed',
                ],
                'snapshot' => [
                    'root_subvolume' => '/@',
                    'snapshot_name' => 'pre-update-' . $operationId,
                    'snapshot_path' => '/.snapshots/pre-update-' . $operationId,
                    'retention' => 'protected-until-confirmed',
                ],
                'staging' => [
                    'directory' => '/var/lib/w4-update/staging/' . $operationId,
                    'estimated_space_kib' => 262144,
                    'cache_policy' => 'protected-until-confirmed',
                ],
                'boot_manifest' => [
                    'kernel_package' => 'linux-image-amd64',
                    'boot_mode' => 'efi',
                    'initramfs_strategy' => 'update-initramfs',
                ],
                'health_check_manifest' => [
                    'required' => ['boot-entry-present', 'dpkg-consistent'],
                    'test_file_path' => '/var/lib/w4-update/test-file',
                    'pending_state' => 'pending_health',
                    'success_state' => 'confirmed',
                    'failure_state' => 'failed',
                ],
                'durable_state_template' => [
                    'update_operation_schema_version' => 1,
                    'kind' => 'update-operation',
                    'operation_id' => $operationId,
                    'stage' => 'planned',
                    'last_error' => null,
                ],
                'evidence' => [
                    'update-plan.json',
                    'operation.json',
                ],
                'notes' => [
                    'Una sola operacion mutante puede estar activa a la vez.',
                ],
            ];
            file_put_contents($planPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $planHash = sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $request = [
                'api_version' => '1.0',
                'request_id' => 'req-updates-apply-001',
                'method' => 'updates.apply',
                'params' => [
                    'operation_id' => $operationId,
                    'plan_hash' => $planHash,
                    'idempotency_key' => 'updates-apply-' . $suffix,
                    'expected_catalog_revision' => 'updates-catalog-' . $suffix,
                    'plan_path' => $planPath,
                ],
            ];

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_UPDATES_APPLY_EXIT_CODE' => '0',
                'W4_SYSTEM_API_UPDATES_APPLY_STDOUT' => 'Update packages applied',
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);
            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('updates.apply', $payload['result']['method']);
            self::assertSame('update-operation', $payload['result']['resource']['kind']);
            self::assertSame('system-api-update-operation', $payload['result']['data']['kind']);
            self::assertSame('pending-reboot', $payload['result']['data']['status']);
            self::assertSame('needs_attention', $payload['result']['data']['state']);
            self::assertSame('pending_health', $payload['result']['data']['stage']);
            self::assertSame($planHash, $payload['result']['data']['plan_hash']);
            self::assertSame('updates-catalog-' . $suffix, $payload['result']['data']['expected_catalog_revision']);
            self::assertSame('updates-catalog-' . $suffix, $payload['result']['data']['observed_catalog_revision']);
            self::assertSame('reboot-into-post-update-health-checks', $payload['result']['data']['next_action']);
            self::assertTrue($payload['result']['data']['reboot_required']);
            self::assertSame('pending_health', $payload['result']['data']['durable_state']['stage']);
            self::assertSame('update-operation', $payload['result']['data']['durable_state']['kind']);
            self::assertSame($planHash, $payload['result']['data']['durable_state']['plan_hash']);
            self::assertSame('pending', $payload['result']['data']['health_report']['status']);
            self::assertSame('pending_health', $payload['result']['data']['health_report']['stage']);
            self::assertSame('pending_health', $payload['result']['data']['post_reboot']['pending_state']);
            self::assertSame('confirmed', $payload['result']['data']['post_reboot']['success_state']);
            self::assertSame(['boot-entry-present', 'dpkg-consistent'], $payload['result']['data']['post_reboot']['required_checks']);
            self::assertSame('pending_health', $payload['result']['data']['post_reboot']['resume_from_stage']);
            self::assertSame('run-post-reboot-health-checks', $payload['result']['data']['post_reboot']['next_action']);
            self::assertSame('system-updates', $payload['result']['data']['coordination']['exclusive_scope']);
            self::assertSame('single-active-operation', $payload['result']['data']['coordination']['mutating_operation_policy']);
            self::assertSame('pending_health', $payload['result']['data']['coordination']['next_stage']);
            self::assertFileExists($payload['result']['data']['durable_store']['executor']['manifest_path']);
            self::assertFileExists($payload['result']['data']['durable_store']['executor']['run_update_offline_path']);
            self::assertFileExists($payload['result']['data']['durable_store']['executor']['run_health_checks_path']);
            self::assertFileExists($payload['result']['data']['durable_store']['executor']['reconcile_after_reboot_path']);
            self::assertFileExists($payload['result']['data']['durable_store']['executor']['env_path']);
            self::assertFileExists($payload['result']['data']['durable_store']['executor']['readme_path']);
            $executorManifestRaw = file_get_contents($payload['result']['data']['durable_store']['executor']['manifest_path']);
            self::assertIsString($executorManifestRaw);
            $executorManifest = json_decode($executorManifestRaw, true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('update-executor', $executorManifest['kind']);
            self::assertContains('run-health-checks.sh', $executorManifest['generated_artifacts']);
            $offlineScript = file_get_contents($payload['result']['data']['durable_store']['executor']['run_update_offline_path']);
            self::assertIsString($offlineScript);
            self::assertStringContainsString('STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}}"', $offlineScript);
            self::assertStringNotContainsString('${SCRIPT_DIR}/store', $offlineScript);
            $executorEnv = file_get_contents($payload['result']['data']['durable_store']['executor']['env_path']);
            self::assertIsString($executorEnv);
            self::assertStringContainsString('export W4_UPDATE_STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}}"', $executorEnv);
            $readme = file_get_contents($payload['result']['data']['durable_store']['executor']['readme_path']);
            self::assertIsString($readme);
            self::assertStringContainsString('source "executor.env"', $readme);
            self::assertStringContainsString('./run-update-offline.sh', $readme);
            self::assertContains('update-plan.json', $payload['result']['data']['evidence']);
            self::assertContains('Una sola operacion mutante puede estar activa a la vez.', $payload['result']['data']['notes']);
            self::assertSame('apt-get -y -o APT::Color=0 install w4-recovery-tools w4-home-meta', $payload['result']['data']['command_sequence'][0]);
        } finally {
            if (is_file($planPath)) {
                unlink($planPath);
            }
        }
    }

    public function testUpdatesReconcileConfirmsPendingHealthOperation(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $planPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-update-reconcile-' . $suffix . '.json';
        $operationId = 'system-api-update-reconcile-' . $suffix;

        try {
            $plan = [
                'update_plan_schema_version' => 1,
                'kind' => 'update-plan',
                'operation_id' => $operationId,
                'profile_id' => 'w4-os-home',
                'source_version' => '1.0.0-lab',
                'target_version' => '1.0.1-lab',
                'repository_snapshot' => [
                    'id' => 'w4-main-2026-09-19T120000Z',
                    'channel' => 'testing',
                ],
                'catalog_revision' => 'updates-reconcile-catalog-' . $suffix,
                'package_changes' => [
                    'install' => ['w4-recovery-tools'],
                    'upgrade' => ['w4-home-meta'],
                    'remove' => [],
                    'counts' => [
                        'install' => 1,
                        'upgrade' => 1,
                        'remove' => 0,
                        'total' => 2,
                    ],
                ],
                'execution' => [
                    'engine' => 'apt-offline-snapshot',
                    'apply_mode' => 'live-apt',
                    'reboot_required' => true,
                    'stages' => [
                        'planned',
                        'downloading',
                        'ready',
                        'prepared',
                        'applying_offline',
                        'pending_health',
                        'confirmed',
                    ],
                    'failure_state' => 'failed',
                ],
                'snapshot' => [
                    'root_subvolume' => '/@',
                    'snapshot_name' => 'pre-update-' . $operationId,
                    'snapshot_path' => '/.snapshots/pre-update-' . $operationId,
                    'retention' => 'protected-until-confirmed',
                ],
                'staging' => [
                    'directory' => '/var/lib/w4-update/staging/' . $operationId,
                    'estimated_space_kib' => 262144,
                    'cache_policy' => 'protected-until-confirmed',
                ],
                'boot_manifest' => [
                    'kernel_package' => 'linux-image-amd64',
                    'boot_mode' => 'efi',
                    'initramfs_strategy' => 'update-initramfs',
                ],
                'health_check_manifest' => [
                    'required' => ['boot-entry-present', 'dpkg-consistent'],
                    'test_file_path' => '/var/lib/w4-update/test-file',
                    'pending_state' => 'pending_health',
                    'success_state' => 'confirmed',
                    'failure_state' => 'failed',
                ],
                'durable_state_template' => [
                    'update_operation_schema_version' => 1,
                    'kind' => 'update-operation',
                    'operation_id' => $operationId,
                    'stage' => 'planned',
                    'last_error' => null,
                ],
                'evidence' => [
                    'update-plan.json',
                    'operation.json',
                ],
                'notes' => [
                    'Una sola operacion mutante puede estar activa a la vez.',
                ],
            ];
            file_put_contents($planPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $planHash = sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $applyRequest = [
                'api_version' => '1.0',
                'request_id' => 'req-updates-reconcile-apply-001',
                'method' => 'updates.apply',
                'params' => [
                    'operation_id' => $operationId,
                    'plan_hash' => $planHash,
                    'idempotency_key' => 'updates-reconcile-apply-' . $suffix,
                    'expected_catalog_revision' => 'updates-reconcile-catalog-' . $suffix,
                    'plan_path' => $planPath,
                ],
            ];

            $applyResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($applyRequest, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_UPDATES_APPLY_EXIT_CODE' => '0',
                'W4_SYSTEM_API_UPDATES_APPLY_STDOUT' => 'Update packages applied',
            ]);

            self::assertSame(0, $applyResult['exitCode'], $applyResult['stderr']);

            $reconcileRequest = [
                'api_version' => '1.0',
                'request_id' => 'req-updates-reconcile-001',
                'method' => 'updates.reconcile',
                'params' => [
                    'operation_id' => $operationId,
                    'observed_stage' => 'confirmed',
                    'details' => [
                        'health_status' => 'ok',
                        'checks_passed' => ['boot-entry-present', 'dpkg-consistent'],
                    ],
                ],
            ];

            $reconcileResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($reconcileRequest, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            ]);

            self::assertSame(0, $reconcileResult['exitCode'], $reconcileResult['stderr']);
            $payload = $this->decodeJson($reconcileResult['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('updates.reconcile', $payload['result']['method']);
            self::assertSame('confirmed', $payload['result']['data']['stage']);
            self::assertSame('succeeded', $payload['result']['data']['state']);
            self::assertSame('ok', $payload['result']['data']['status']);
            self::assertTrue($payload['result']['data']['reconciled']);
            self::assertSame('confirmed', $payload['result']['data']['durable_state']['stage']);
            self::assertSame('ok', $payload['result']['data']['health_report']['status']);
            self::assertSame('record-update-confirmation', $payload['result']['data']['next_action']);
        } finally {
            if (is_file($planPath)) {
                unlink($planPath);
            }
        }
    }

    public function testUpdatesReconcileDerivesObservedStageFromHealthCheckResults(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $planPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-update-health-auto-' . $suffix . '.json';
        $operationId = 'system-api-update-health-auto-' . $suffix;

        try {
            $plan = [
                'update_plan_schema_version' => 1,
                'kind' => 'update-plan',
                'operation_id' => $operationId,
                'profile_id' => 'w4-os-home',
                'source_version' => '1.0.0-lab',
                'target_version' => '1.0.1-lab',
                'repository_snapshot' => [
                    'id' => 'w4-main-2026-09-19T120000Z',
                    'channel' => 'testing',
                ],
                'catalog_revision' => 'updates-health-auto-catalog-' . $suffix,
                'package_changes' => [
                    'install' => ['w4-recovery-tools'],
                    'upgrade' => ['w4-home-meta'],
                    'remove' => [],
                    'counts' => [
                        'install' => 1,
                        'upgrade' => 1,
                        'remove' => 0,
                        'total' => 2,
                    ],
                ],
                'execution' => [
                    'engine' => 'apt-offline-snapshot',
                    'apply_mode' => 'live-apt',
                    'reboot_required' => true,
                    'stages' => [
                        'planned',
                        'downloading',
                        'ready',
                        'prepared',
                        'applying_offline',
                        'pending_health',
                        'confirmed',
                    ],
                    'failure_state' => 'failed',
                ],
                'snapshot' => [
                    'root_subvolume' => '/@',
                    'snapshot_name' => 'pre-update-' . $operationId,
                    'snapshot_path' => '/.snapshots/pre-update-' . $operationId,
                    'retention' => 'protected-until-confirmed',
                ],
                'staging' => [
                    'directory' => '/var/lib/w4-update/staging/' . $operationId,
                    'estimated_space_kib' => 262144,
                    'cache_policy' => 'protected-until-confirmed',
                ],
                'boot_manifest' => [
                    'kernel_package' => 'linux-image-amd64',
                    'boot_mode' => 'efi',
                    'initramfs_strategy' => 'update-initramfs',
                ],
                'health_check_manifest' => [
                    'required' => ['boot-entry-present', 'dpkg-consistent'],
                    'test_file_path' => '/var/lib/w4-update/test-file',
                    'pending_state' => 'pending_health',
                    'success_state' => 'confirmed',
                    'failure_state' => 'failed',
                ],
                'durable_state_template' => [
                    'update_operation_schema_version' => 1,
                    'kind' => 'update-operation',
                    'operation_id' => $operationId,
                    'stage' => 'planned',
                    'last_error' => null,
                ],
            ];
            file_put_contents($planPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $planHash = sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $applyRequest = [
                'api_version' => '1.0',
                'request_id' => 'req-updates-health-auto-apply-001',
                'method' => 'updates.apply',
                'params' => [
                    'operation_id' => $operationId,
                    'plan_hash' => $planHash,
                    'idempotency_key' => 'updates-health-auto-apply-' . $suffix,
                    'expected_catalog_revision' => 'updates-health-auto-catalog-' . $suffix,
                    'plan_path' => $planPath,
                ],
            ];

            $applyResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($applyRequest, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_UPDATES_APPLY_EXIT_CODE' => '0',
                'W4_SYSTEM_API_UPDATES_APPLY_STDOUT' => 'Update packages applied',
            ]);

            self::assertSame(0, $applyResult['exitCode'], $applyResult['stderr']);
            $applyPayload = $this->decodeJson($applyResult['stdout']);
            $healthResultPath = $applyPayload['result']['data']['durable_store']['health_result_path'] ?? null;
            self::assertIsString($healthResultPath);

            file_put_contents($healthResultPath, json_encode([
                'health_check_result_schema_version' => 1,
                'kind' => 'health-check-results',
                'status' => 'ok',
                'results' => [
                    'boot-entry-present' => 'ok',
                    'dpkg-consistent' => 'ok',
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $reconcileRequest = [
                'api_version' => '1.0',
                'request_id' => 'req-updates-health-auto-reconcile-001',
                'method' => 'updates.reconcile',
                'params' => [
                    'operation_id' => $operationId,
                ],
            ];

            $reconcileResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($reconcileRequest, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            ]);

            self::assertSame(0, $reconcileResult['exitCode'], $reconcileResult['stderr']);
            $payload = $this->decodeJson($reconcileResult['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('updates.reconcile', $payload['result']['method']);
            self::assertSame('confirmed', $payload['result']['data']['observed_stage']);
            self::assertSame('confirmed', $payload['result']['data']['stage']);
            self::assertSame('ok', $payload['result']['data']['health_check_results']['status']);
            self::assertSame($healthResultPath, $payload['result']['data']['durable_store']['health_result_path']);
            self::assertSame('ok', $payload['result']['data']['details']['health_status']);
            self::assertSame(['boot-entry-present', 'dpkg-consistent'], $payload['result']['data']['details']['checks_passed']);
            self::assertSame([], $payload['result']['data']['details']['checks_failed']);
        } finally {
            if (is_file($planPath)) {
                unlink($planPath);
            }
        }
    }

    public function testOperationsGetRefreshesDurableUpdateOperationState(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $planPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'system-api-update-op-get-' . $suffix . '.json';
        $operationId = 'system-api-update-op-get-' . $suffix;

        try {
            $plan = [
                'update_plan_schema_version' => 1,
                'kind' => 'update-plan',
                'operation_id' => $operationId,
                'profile_id' => 'w4-os-home',
                'source_version' => '1.0.0-lab',
                'target_version' => '1.0.1-lab',
                'repository_snapshot' => [
                    'id' => 'w4-main-2026-09-19T120000Z',
                    'channel' => 'testing',
                ],
                'catalog_revision' => 'updates-op-get-catalog-' . $suffix,
                'package_changes' => [
                    'install' => ['w4-recovery-tools'],
                    'upgrade' => ['w4-home-meta'],
                    'remove' => [],
                    'counts' => [
                        'install' => 1,
                        'upgrade' => 1,
                        'remove' => 0,
                        'total' => 2,
                    ],
                ],
                'execution' => [
                    'engine' => 'apt-offline-snapshot',
                    'apply_mode' => 'live-apt',
                    'reboot_required' => true,
                    'stages' => [
                        'planned',
                        'downloading',
                        'ready',
                        'prepared',
                        'applying_offline',
                        'pending_health',
                        'confirmed',
                    ],
                    'failure_state' => 'failed',
                ],
                'snapshot' => [
                    'root_subvolume' => '/@',
                    'snapshot_name' => 'pre-update-' . $operationId,
                    'snapshot_path' => '/.snapshots/pre-update-' . $operationId,
                    'retention' => 'protected-until-confirmed',
                ],
                'staging' => [
                    'directory' => '/var/lib/w4-update/staging/' . $operationId,
                    'estimated_space_kib' => 262144,
                    'cache_policy' => 'protected-until-confirmed',
                ],
                'boot_manifest' => [
                    'kernel_package' => 'linux-image-amd64',
                    'boot_mode' => 'efi',
                    'initramfs_strategy' => 'update-initramfs',
                ],
                'health_check_manifest' => [
                    'required' => ['boot-entry-present', 'dpkg-consistent'],
                    'test_file_path' => '/var/lib/w4-update/test-file',
                    'pending_state' => 'pending_health',
                    'success_state' => 'confirmed',
                    'failure_state' => 'failed',
                ],
                'durable_state_template' => [
                    'update_operation_schema_version' => 1,
                    'kind' => 'update-operation',
                    'operation_id' => $operationId,
                    'stage' => 'planned',
                    'last_error' => null,
                ],
            ];
            file_put_contents($planPath, json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
            $planHash = sha1(json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $applyRequest = [
                'api_version' => '1.0',
                'request_id' => 'req-updates-op-get-apply-001',
                'method' => 'updates.apply',
                'params' => [
                    'operation_id' => $operationId,
                    'plan_hash' => $planHash,
                    'idempotency_key' => 'updates-op-get-apply-' . $suffix,
                    'expected_catalog_revision' => 'updates-op-get-catalog-' . $suffix,
                    'plan_path' => $planPath,
                ],
            ];

            $applyResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($applyRequest, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
                'W4_SYSTEM_API_UPDATES_APPLY_EXIT_CODE' => '0',
                'W4_SYSTEM_API_UPDATES_APPLY_STDOUT' => 'Update packages applied',
            ]);

            self::assertSame(0, $applyResult['exitCode'], $applyResult['stderr']);
            $applyPayload = $this->decodeJson($applyResult['stdout']);
            $storeDir = $applyPayload['result']['data']['durable_store']['store_dir'] ?? null;
            self::assertIsString($storeDir);

            (new UpdateToolkit())->reconcileOperationStore(
                $storeDir,
                'confirmed',
                'external-test-reconcile',
                [
                    'health_status' => 'ok',
                ]
            );

            $getRequest = [
                'api_version' => '1.0',
                'request_id' => 'req-updates-op-get-001',
                'method' => 'operations.get',
                'params' => [
                    'operation_id' => $operationId,
                ],
            ];

            $getResult = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($getRequest, JSON_THROW_ON_ERROR),
            ]);

            self::assertSame(0, $getResult['exitCode'], $getResult['stderr']);
            $payload = $this->decodeJson($getResult['stdout']);
            self::assertNull($payload['error']);
            self::assertSame('operations.get', $payload['result']['method']);
            self::assertSame('updates.apply', $payload['result']['data']['method']);
            self::assertSame('succeeded', $payload['result']['data']['state']);
            self::assertSame('confirmed', $payload['result']['data']['result']['stage']);
            self::assertSame('ok', $payload['result']['data']['result']['health_report']['status']);
            self::assertFileExists($payload['result']['data']['result']['durable_store']['executor']['manifest_path']);
        } finally {
            if (is_file($planPath)) {
                unlink($planPath);
            }
        }
    }

    public function testUpdatesApplyRejectsMismatchedExpectedCatalogRevision(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $request = [
            'api_version' => '1.0',
            'request_id' => 'req-updates-apply-catalog-conflict-001',
            'method' => 'updates.apply',
            'params' => [
                'operation_id' => 'system-api-update-catalog-conflict-' . $suffix,
                'plan_hash' => 'placeholder',
                'idempotency_key' => 'updates-apply-catalog-conflict-' . $suffix,
                'expected_catalog_revision' => 'catalog-expected-' . $suffix,
                'plan' => [
                    'update_plan_schema_version' => 1,
                    'kind' => 'update-plan',
                    'operation_id' => 'system-api-update-catalog-conflict-' . $suffix,
                    'profile_id' => 'w4-os-home',
                    'source_version' => '1.0.0-lab',
                    'target_version' => '1.0.1-lab',
                    'repository_snapshot' => [
                        'id' => 'w4-main-2026-09-19T120000Z',
                        'channel' => 'testing',
                    ],
                    'catalog_revision' => 'catalog-observed-' . $suffix,
                    'package_changes' => [
                        'install' => ['w4-recovery-tools'],
                        'upgrade' => [],
                        'remove' => [],
                        'counts' => [
                            'install' => 1,
                            'upgrade' => 0,
                            'remove' => 0,
                            'total' => 1,
                        ],
                    ],
                    'execution' => [
                        'engine' => 'apt-offline-snapshot',
                        'apply_mode' => 'live-apt',
                        'reboot_required' => true,
                        'stages' => [
                            'planned',
                            'downloading',
                            'ready',
                            'prepared',
                            'applying_offline',
                            'pending_health',
                            'confirmed',
                        ],
                        'failure_state' => 'failed',
                    ],
                    'snapshot' => [
                        'root_subvolume' => '/@',
                        'snapshot_name' => 'pre-update-system-api-update-catalog-conflict-' . $suffix,
                        'snapshot_path' => '/.snapshots/pre-update-system-api-update-catalog-conflict-' . $suffix,
                        'retention' => 'protected-until-confirmed',
                    ],
                    'staging' => [
                        'directory' => '/var/lib/w4-update/staging/system-api-update-catalog-conflict-' . $suffix,
                        'estimated_space_kib' => 262144,
                        'cache_policy' => 'protected-until-confirmed',
                    ],
                    'boot_manifest' => [
                        'kernel_package' => 'linux-image-amd64',
                        'boot_mode' => 'efi',
                        'initramfs_strategy' => 'update-initramfs',
                    ],
                    'health_check_manifest' => [
                        'required' => ['boot-entry-present'],
                        'test_file_path' => '/var/lib/w4-update/test-file',
                        'pending_state' => 'pending_health',
                        'success_state' => 'confirmed',
                        'failure_state' => 'failed',
                    ],
                    'durable_state_template' => [
                        'update_operation_schema_version' => 1,
                        'kind' => 'update-operation',
                        'operation_id' => 'system-api-update-catalog-conflict-' . $suffix,
                        'stage' => 'planned',
                        'last_error' => null,
                    ],
                ],
            ],
        ];
        $request['params']['plan_hash'] = sha1(json_encode($request['params']['plan'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--request-json',
            json_encode($request, JSON_THROW_ON_ERROR),
        ], [
            'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertNull($payload['result']);
        self::assertSame('CONFLICT', $payload['error']['code']);
        self::assertStringContainsString('expected_catalog_revision', $payload['error']['message']);
    }

    public function testUpdatesApplyRejectsActiveExclusiveLock(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $ledgerDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'operations-ledger';
        $lockPath = $ledgerDir . DIRECTORY_SEPARATOR . 'lock--system-updates.json';

        if (!is_dir($ledgerDir)) {
            mkdir($ledgerDir, 0777, true);
        }

        file_put_contents($lockPath, json_encode([
            'kind' => 'system-api-exclusive-lock',
            'scope' => 'system-updates',
            'operation_id' => 'other-update-op-' . $suffix,
            'observed_at' => gmdate('c'),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

        try {
            $request = [
                'api_version' => '1.0',
                'request_id' => 'req-updates-apply-lock-conflict-001',
                'method' => 'updates.apply',
                'params' => [
                    'operation_id' => 'system-api-update-lock-conflict-' . $suffix,
                    'plan_hash' => 'placeholder',
                    'idempotency_key' => 'updates-apply-lock-conflict-' . $suffix,
                    'plan' => [
                        'update_plan_schema_version' => 1,
                        'kind' => 'update-plan',
                        'operation_id' => 'system-api-update-lock-conflict-' . $suffix,
                        'profile_id' => 'w4-os-home',
                        'source_version' => '1.0.0-lab',
                        'target_version' => '1.0.1-lab',
                        'repository_snapshot' => [
                            'id' => 'w4-main-2026-09-19T120000Z',
                            'channel' => 'testing',
                        ],
                        'package_changes' => [
                            'install' => ['w4-recovery-tools'],
                            'upgrade' => [],
                            'remove' => [],
                            'counts' => [
                                'install' => 1,
                                'upgrade' => 0,
                                'remove' => 0,
                                'total' => 1,
                            ],
                        ],
                        'execution' => [
                            'engine' => 'apt-offline-snapshot',
                            'apply_mode' => 'live-apt',
                            'reboot_required' => true,
                            'stages' => [
                                'planned',
                                'downloading',
                                'ready',
                                'prepared',
                                'applying_offline',
                                'pending_health',
                                'confirmed',
                            ],
                            'failure_state' => 'failed',
                        ],
                        'snapshot' => [
                            'root_subvolume' => '/@',
                            'snapshot_name' => 'pre-update-system-api-update-lock-conflict-' . $suffix,
                            'snapshot_path' => '/.snapshots/pre-update-system-api-update-lock-conflict-' . $suffix,
                            'retention' => 'protected-until-confirmed',
                        ],
                        'staging' => [
                            'directory' => '/var/lib/w4-update/staging/system-api-update-lock-conflict-' . $suffix,
                            'estimated_space_kib' => 262144,
                            'cache_policy' => 'protected-until-confirmed',
                        ],
                        'boot_manifest' => [
                            'kernel_package' => 'linux-image-amd64',
                            'boot_mode' => 'efi',
                            'initramfs_strategy' => 'update-initramfs',
                        ],
                        'health_check_manifest' => [
                            'required' => ['boot-entry-present'],
                            'test_file_path' => '/var/lib/w4-update/test-file',
                            'pending_state' => 'pending_health',
                            'success_state' => 'confirmed',
                            'failure_state' => 'failed',
                        ],
                        'durable_state_template' => [
                            'update_operation_schema_version' => 1,
                            'kind' => 'update-operation',
                            'operation_id' => 'system-api-update-lock-conflict-' . $suffix,
                            'stage' => 'planned',
                            'last_error' => null,
                        ],
                    ],
                ],
            ];
            $request['params']['plan_hash'] = sha1(json_encode($request['params']['plan'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            $result = $this->runPhpScript([
                $this->scriptPath(),
                '--request-json',
                json_encode($request, JSON_THROW_ON_ERROR),
            ], [
                'W4_SYSTEM_API_HOST_OS_FAMILY' => 'Linux',
            ]);

            self::assertSame(0, $result['exitCode'], $result['stderr']);
            $payload = $this->decodeJson($result['stdout']);
            self::assertNull($payload['result']);
            self::assertSame('CONFLICT', $payload['error']['code']);
            self::assertStringContainsString('mutacion activa', $payload['error']['message']);
        } finally {
            if (is_file($lockPath)) {
                unlink($lockPath);
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
