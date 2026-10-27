<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\SystemApi\SystemApiDbusRuntimeBackendToolkit;

final class SystemApiDbusRuntimeBackendToolkitTest extends TestCase
{
    public function testCreateReportDescribesCompatibleNativeDbusBackend(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createReport(
            ['ready' => true],
            [
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            ]
        );

        self::assertSame('native-dbus-extension', $report['selected_backend']);
        self::assertSame('native-runtime-backend-materialized', $report['status']);
        self::assertTrue($report['materialized']);
        self::assertSame('Dbus', $report['native_runtime_api']['class_name']);
        self::assertSame('env-override', $report['native_runtime_api']['class_resolution_source']);
        self::assertSame(['Dbus', 'DBus'], $report['native_runtime_api']['class_candidates']);
        self::assertTrue($report['native_runtime_api']['class_available']);
        self::assertTrue($report['native_runtime_api']['has_bus_system_constant']);
        self::assertTrue($report['native_runtime_api']['has_request_name_method']);
        self::assertTrue($report['native_runtime_api']['has_register_object_method']);
        self::assertTrue($report['native_runtime_api']['has_wait_loop_method']);
        self::assertTrue($report['native_runtime_api']['compatible_for_service']);
        self::assertSame([], $report['native_runtime_api']['missing_api']);
        self::assertNull($report['native_runtime_api']['diagnostic_blocking_reason']);
        self::assertSame('start-native-runtime-service', $report['native_runtime_api']['diagnostic_next_action']);
        self::assertNull($report['blocking_reason']);
    }

    public function testCreateReportDetectsIncompleteNativeDbusBackendApi(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createReport(
            ['ready' => true],
            [
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            ]
        );

        self::assertSame('native-dbus-extension', $report['selected_backend']);
        self::assertSame('pending-runtime-adapter', $report['status']);
        self::assertFalse($report['materialized']);
        self::assertSame('dbus-extension-api-incomplete', $report['blocking_reason']);
        self::assertContains('registerObject', $report['native_runtime_api']['missing_api']);
        self::assertFalse($report['native_runtime_api']['has_register_object_method']);
        self::assertFalse($report['native_runtime_api']['compatible_for_service']);
        self::assertSame('dbus-extension-api-incomplete', $report['native_runtime_api']['diagnostic_blocking_reason']);
        self::assertSame('inspect-native-dbus-extension-api', $report['native_runtime_api']['diagnostic_next_action']);
    }

    public function testCreateReportDetectsMissingNativeDbusServiceClass(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createReport(
            ['ready' => true],
            [
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '0',
            ]
        );

        self::assertSame('native-dbus-extension', $report['selected_backend']);
        self::assertSame('pending-runtime-adapter', $report['status']);
        self::assertFalse($report['materialized']);
        self::assertSame('dbus-extension-service-backend-unavailable', $report['blocking_reason']);
        self::assertFalse($report['native_runtime_api']['class_available']);
        self::assertContains('dbus-service-class', $report['native_runtime_api']['missing_api']);
        self::assertFalse($report['native_runtime_api']['extension_loaded']);
        self::assertSame('unresolved', $report['native_runtime_api']['class_resolution_source']);
        self::assertSame('dbus-extension-service-backend-unavailable', $report['native_runtime_api']['diagnostic_blocking_reason']);
        self::assertSame('install-native-dbus-extension', $report['native_runtime_api']['diagnostic_next_action']);
    }

    public function testCreateNativeRuntimeCouplingReportPublishesNativeBackendCandidateWhenBackendIsNotSelected(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createNativeRuntimeCouplingReport(
            ['ready' => true],
            [
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            ]
        );

        self::assertSame('system-api-dbus-native-runtime-coupling', $report['kind']);
        self::assertSame('native-runtime-coupling-blocked', $report['status']);
        self::assertFalse($report['described']);
        self::assertFalse($report['compatible']);
        self::assertSame(3, $report['exit_code']);
        self::assertSame('runtime-backend-not-configured', $report['blocking_reason']);
        self::assertSame('select-native-dbus-extension-backend', $report['next_action']);
        self::assertSame('native-dbus-extension', $report['native_backend_candidate']['selected_backend']);
        self::assertTrue($report['native_backend_candidate']['native_runtime_api']['compatible_for_service']);
        self::assertSame('env-override', $report['native_backend_candidate']['native_runtime_api']['class_resolution_source']);
        self::assertSame('start-native-runtime-service', $report['native_backend_candidate']['native_runtime_api']['diagnostic_next_action']);
    }

    public function testCreateNativeRuntimeServiceDefinitionPublishesRecoveryPlanWhenBackendIsNotSelected(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createNativeRuntimeServiceDefinition(
            ['ready' => true],
            [
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            ]
        );

        self::assertSame('system-api-dbus-native-runtime-service', $report['kind']);
        self::assertSame('native-runtime-service-blocked', $report['status']);
        self::assertFalse($report['eligible']);
        self::assertSame('runtime-backend-not-configured', $report['blocking_reason']);
        self::assertSame('select-native-dbus-extension-backend', $report['next_action']);
        self::assertSame('native-dbus-extension', $report['native_backend_candidate']['selected_backend']);
        self::assertTrue($report['native_backend_candidate']['native_runtime_api']['compatible_for_service']);
    }

    public function testCreateNativeRuntimeServiceStartupReportUsesActionableRecoveryPlanWhenExtensionIsMissing(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createNativeRuntimeServiceStartupReport(
            ['ready' => true],
            [
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '0',
            ]
        );

        self::assertSame('system-api-dbus-native-runtime-startup', $report['kind']);
        self::assertSame('native-runtime-service-blocked', $report['status']);
        self::assertFalse($report['startup_acknowledged']);
        self::assertSame(3, $report['exit_code']);
        self::assertSame('dbus-extension-service-backend-unavailable', $report['blocking_reason']);
        self::assertSame('install-native-dbus-extension', $report['next_action']);
        self::assertSame('install-native-dbus-extension', $report['service_definition']['next_action']);
        self::assertFalse($report['service_definition']['native_backend_candidate']['native_runtime_api']['extension_loaded']);
    }

    public function testCreateNativeRuntimeServiceStartupReportAcknowledgesEligibleNativeBackend(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createNativeRuntimeServiceStartupReport(
            ['ready' => true],
            [
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
            ]
        );

        self::assertSame('system-api-dbus-native-runtime-startup', $report['kind']);
        self::assertSame('native-runtime-service-bootstrap-recorded', $report['status']);
        self::assertTrue($report['startup_acknowledged']);
        self::assertSame(0, $report['exit_code']);
        self::assertSame('startup-recorded-no-process', $report['ownership_state']);
        self::assertSame('native-runtime-service-not-running', $report['blocking_reason']);
        self::assertSame('start-native-runtime-service', $report['service_definition']['next_action']);
        self::assertSame('launch-native-runtime-service', $report['next_action']);
        self::assertSame('native-runtime-service-not-started', $report['service_definition']['blocking_reason']);
    }

    public function testCreateNativeRuntimeServiceObservationReportReadsPersistedArtifacts(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-observe-' . bin2hex(random_bytes(6));
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

            $report = $toolkit->createNativeRuntimeServiceObservationReport(
                ['ready' => true],
                [
                    'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                    'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                ]
            );

            self::assertSame('system-api-dbus-native-runtime-observation', $report['kind']);
            self::assertSame('native-runtime-service-not-running', $report['status']);
            self::assertFalse($report['observed']);
            self::assertTrue($report['startup_acknowledged']);
            self::assertSame('startup-recorded-no-process', $report['ownership_state']);
            self::assertSame('native-runtime-service-not-running', $report['blocking_reason']);
            self::assertSame('launch-native-runtime-service', $report['next_action']);
            self::assertSame(
                ['launch-native-runtime-service', 'start-native-runtime-service'],
                $report['recovery_plan']
            );
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testCreateNativeRuntimeServiceObservationReportPublishesReactivationPlanAfterLoopExit(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-reactivate-' . bin2hex(random_bytes(6));
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

            $report = $toolkit->createNativeRuntimeServiceObservationReport(
                ['ready' => true],
                [
                    'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                    'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                ]
            );

            self::assertSame('system-api-dbus-native-runtime-observation', $report['kind']);
            self::assertSame('native-runtime-service-not-running', $report['status']);
            self::assertFalse($report['observed']);
            self::assertTrue($report['startup_acknowledged']);
            self::assertSame('startup-recorded-no-process', $report['ownership_state']);
            self::assertSame('bus-name-owned', $report['last_known_ownership_state']);
            self::assertSame('native-runtime-service-not-running', $report['blocking_reason']);
            self::assertSame('reactivate-native-runtime-service', $report['next_action']);
            self::assertSame(
                [
                    'reactivate-native-runtime-service',
                    'inspect-native-runtime-service-report',
                    'launch-native-runtime-service',
                    'start-native-runtime-service',
                ],
                $report['recovery_plan']
            );
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testCreateNativeBusOwnershipReportAcknowledgesOwnedLifecycleWhenPhasesSucceed(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-owned-' . bin2hex(random_bytes(6));
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

            $report = $toolkit->createNativeBusOwnershipReport(
                ['ready' => true],
                [
                    'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                    'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_REQUEST_NAME_STATE' => 'owned',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_REGISTER_OBJECT_STATE' => 'registered',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_WAIT_LOOP_STATE' => 'running',
                ]
            );

            self::assertSame('system-api-dbus-native-runtime-ownership', $report['kind']);
            self::assertSame('native-runtime-bus-owned', $report['status']);
            self::assertTrue($report['attempted']);
            self::assertSame(0, $report['exit_code']);
            self::assertSame('phase-overrides', $report['execution_mode']);
            self::assertSame('bus-name-owned', $report['ownership_state']);
            self::assertNull($report['blocking_reason']);
            self::assertSame('runtime-service-active', $report['next_action']);
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testCreateNativeBusOwnershipReportAcceptsObservedOwnedRuntimeWithoutReplayingDriver(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-owned-observed-' . bin2hex(random_bytes(6));
        $readyMarkerPath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.ready';
        $statusFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime-status.json';
        $pidFilePath = $baseDirectory . DIRECTORY_SEPARATOR . 'runtime.pid';

        try {
            self::assertTrue(mkdir($baseDirectory, 0777, true) || is_dir($baseDirectory));
            file_put_contents($readyMarkerPath, "native-runtime-bus-owned\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'bus-name-owned',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "4242\n");

            $report = $toolkit->createNativeBusOwnershipReport(
                ['ready' => true],
                [
                    'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                    'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                ]
            );

            self::assertSame('system-api-dbus-native-runtime-ownership', $report['kind']);
            self::assertSame('native-runtime-bus-owned', $report['status']);
            self::assertTrue($report['attempted']);
            self::assertSame(0, $report['exit_code']);
            self::assertSame('observed-runtime', $report['execution_mode']);
            self::assertSame('bus-name-owned', $report['ownership_state']);
            self::assertNull($report['ownership_driver']);
            self::assertNull($report['blocking_reason']);
            self::assertSame('runtime-service-active', $report['next_action']);
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testCreateNativeRuntimeServiceObservationReportAcceptsActiveRuntimeProcessContext(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-active-context-' . bin2hex(random_bytes(6));
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

            $report = $toolkit->createNativeRuntimeServiceObservationReport(
                ['ready' => true],
                [
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
                ]
            );

            self::assertSame('system-api-dbus-native-runtime-observation', $report['kind']);
            self::assertTrue($report['observed']);
            self::assertTrue($report['startup_acknowledged']);
            self::assertSame('native-runtime-service-observed-active', $report['status']);
            self::assertSame(0, $report['exit_code']);
            self::assertSame('bus-name-owned', $report['ownership_state']);
            self::assertNull($report['blocking_reason']);
            self::assertSame('runtime-service-active', $report['next_action']);
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testCreateNativeBusOwnershipReportUsesServiceRecoveryPlanWhenBackendIsBlocked(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createNativeBusOwnershipReport(
            ['ready' => true],
            [
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '0',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '0',
            ]
        );

        self::assertSame('system-api-dbus-native-runtime-ownership', $report['kind']);
        self::assertSame('native-runtime-service-blocked', $report['status']);
        self::assertFalse($report['attempted']);
        self::assertSame(3, $report['exit_code']);
        self::assertSame('dbus-extension-service-backend-unavailable', $report['blocking_reason']);
        self::assertSame('install-native-dbus-extension', $report['next_action']);
        self::assertSame('install-native-dbus-extension', $report['service_definition']['next_action']);
    }

    public function testCreateNativeBusOwnershipReportUsesConfiguredDriverWhenAvailable(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-driver-' . bin2hex(random_bytes(6));
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

            $report = $toolkit->createNativeBusOwnershipReport(
                ['ready' => true],
                [
                    'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                    'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'Dbus',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeRuntimeOwnershipDriverStub.php',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\Tests\\Fixtures\\NativeRuntimeOwnershipDriverStub',
                ]
            );

            self::assertSame('system-api-dbus-native-runtime-ownership', $report['kind']);
            self::assertSame('native-runtime-bus-owned', $report['status']);
            self::assertTrue($report['attempted']);
            self::assertSame(0, $report['exit_code']);
            self::assertSame('native-driver', $report['execution_mode']);
            self::assertSame('bus-name-owned', $report['ownership_state']);
            self::assertSame('W4\\OS\\Tests\\Fixtures\\NativeRuntimeOwnershipDriverStub', $report['ownership_driver']['class_name']);
            self::assertNull($report['blocking_reason']);
            self::assertSame('runtime-service-active', $report['next_action']);
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testCreateNativeBusOwnershipReportSurfacesSystemBusPolicyDenial(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-policy-denied-' . bin2hex(random_bytes(6));
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

            $report = $toolkit->createNativeBusOwnershipReport(
                ['ready' => true],
                [
                    'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                    'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusServicePolicyDenied',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                    'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
                    'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
                    'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
                ]
            );

            self::assertSame('system-api-dbus-native-runtime-ownership', $report['kind']);
            self::assertSame('native-runtime-bus-ownership-failed', $report['status']);
            self::assertTrue($report['attempted']);
            self::assertSame(1, $report['exit_code']);
            self::assertSame('native-dbus-system-bus-policy-denied', $report['blocking_reason']);
            self::assertSame('install-native-dbus-system-bus-policy', $report['next_action']);
            self::assertSame('failed', $report['service_lifecycle']['request_name']['state']);
            self::assertSame(
                'Connection ":1.2" is not allowed to own the service "org.w4.System1" due to security policies in the configuration file',
                $report['ownership_driver']['error_message']
            );
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testCreateNativeBusOwnershipReportUsesNativeDbusExtensionDriverAgainstFakeRuntime(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-real-driver-' . bin2hex(random_bytes(6));
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

            $report = $toolkit->createNativeBusOwnershipReport(
                ['ready' => true],
                [
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
                ]
            );

            self::assertSame('system-api-dbus-native-runtime-ownership', $report['kind']);
            self::assertSame('native-runtime-bus-owned', $report['status']);
            self::assertTrue($report['attempted']);
            self::assertSame(0, $report['exit_code']);
            self::assertSame('native-driver', $report['execution_mode']);
            self::assertSame('bus-name-owned', $report['ownership_state']);
            self::assertSame('W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver', $report['ownership_driver']['class_name']);
            self::assertSame('W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService', $report['ownership_driver']['runtime_class_name']);
            self::assertSame('bus-system-constructor', $report['ownership_driver']['constructor_mode']);
            self::assertTrue($report['ownership_driver']['request_name_signature']['compatible']);
            self::assertSame('requestName', $report['ownership_driver']['request_name_invocation_plan']['method_name']);
            self::assertSame(
                'org.w4.System1',
                $report['ownership_driver']['request_name_invocation_plan']['parameter_bindings'][0]['value_preview']
            );
            self::assertSame(
                0,
                $report['ownership_driver']['request_name_invocation_plan']['parameter_bindings'][1]['value_preview']
            );
            self::assertTrue($report['ownership_driver']['register_object_signature']['compatible']);
            self::assertSame(
                ['object_path', 'exported_object', 'introspection_xml', 'definition'],
                $report['ownership_driver']['register_object_signature']['resolved_channels']
            );
            self::assertSame(
                ['object_path', 'introspection_xml', 'definition', 'exported_object'],
                $report['ownership_driver']['register_object_signature']['preferred_parameter_group_match']
            );
            self::assertSame('registerObject', $report['ownership_driver']['register_object_invocation_plan']['method_name']);
            self::assertSame('object', $report['ownership_driver']['register_object_invocation_plan']['parameter_bindings'][1]['value_kind']);
            self::assertSame(
                'W4\\OS\\SystemApi\\NativeDbusExtensionRuntimeObject',
                $report['ownership_driver']['register_object_invocation_plan']['parameter_bindings'][1]['value_preview']
            );
            self::assertSame(
                'dbus-introspection-xml',
                $report['ownership_driver']['register_object_invocation_plan']['parameter_bindings'][2]['value_preview']['kind']
            );
            self::assertSame(
                'export-definition',
                $report['ownership_driver']['register_object_invocation_plan']['parameter_bindings'][3]['value_preview']['kind']
            );
            self::assertTrue($report['ownership_driver']['wait_loop_signature']['compatible']);
            self::assertSame('waitLoop', $report['ownership_driver']['wait_loop_invocation_plan']['method_name']);
            self::assertSame([], $report['ownership_driver']['wait_loop_invocation_plan']['parameter_bindings']);
            self::assertNull($report['blocking_reason']);
            self::assertSame('runtime-service-active', $report['next_action']);
        } finally {
            @unlink($readyMarkerPath);
            @unlink($statusFilePath);
            @unlink($pidFilePath);
            @rmdir($baseDirectory);
        }
    }

    public function testCreateNativeRuntimeCouplingReportDescribesFakeNativeDriverLifecycle(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();

        $report = $toolkit->createNativeRuntimeCouplingReport(
            ['ready' => true],
            [
                'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
                'W4_SYSTEM_API_NATIVE_DBUS_CLASS' => 'W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT' => '1',
                'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP' => '1',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP' => dirname(__DIR__) . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'Fixtures' . DIRECTORY_SEPARATOR . 'NativeDbusExtensionDriverBootstrap.php',
                'W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS' => 'W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver',
            ]
        );

        self::assertSame('system-api-dbus-native-runtime-coupling', $report['kind']);
        self::assertSame('native-runtime-coupling-ready', $report['status']);
        self::assertTrue($report['described']);
        self::assertTrue($report['compatible']);
        self::assertSame(0, $report['exit_code']);
        self::assertSame('W4\\OS\\SystemApi\\NativeDbusExtensionOwnershipDriver', $report['coupling_driver']['class_name']);
        self::assertSame('W4\\OS\\Tests\\Fixtures\\FakeNativeDbusService', $report['coupling_driver']['runtime_class_name']);
        self::assertSame('org.w4.System1', $report['coupling_driver']['request_name_invocation_plan']['parameter_bindings'][0]['value_preview']);
        self::assertSame('registerObject', $report['coupling_driver']['register_object_invocation_plan']['method_name']);
        self::assertSame('waitLoop', $report['coupling_driver']['wait_loop_invocation_plan']['method_name']);
        self::assertTrue($report['service_lifecycle']['request_name']['compatible']);
        self::assertTrue($report['service_lifecycle']['register_object']['compatible']);
        self::assertTrue($report['service_lifecycle']['wait_loop']['compatible']);
        self::assertNull($report['blocking_reason']);
        self::assertSame('materialize-native-bus-ownership', $report['next_action']);
    }
}
