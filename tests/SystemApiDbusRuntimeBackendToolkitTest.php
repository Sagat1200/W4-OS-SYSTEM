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
        self::assertTrue($report['native_runtime_api']['class_available']);
        self::assertTrue($report['native_runtime_api']['has_bus_system_constant']);
        self::assertTrue($report['native_runtime_api']['has_request_name_method']);
        self::assertTrue($report['native_runtime_api']['has_register_object_method']);
        self::assertTrue($report['native_runtime_api']['has_wait_loop_method']);
        self::assertTrue($report['native_runtime_api']['compatible_for_service']);
        self::assertSame([], $report['native_runtime_api']['missing_api']);
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
        self::assertSame('native-runtime-service-started', $report['status']);
        self::assertTrue($report['startup_acknowledged']);
        self::assertSame(0, $report['exit_code']);
        self::assertSame('start-native-runtime-service', $report['service_definition']['next_action']);
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
            file_put_contents($readyMarkerPath, "native-runtime-service-started\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
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
            self::assertSame('native-runtime-service-observed-pending-ownership', $report['status']);
            self::assertTrue($report['observed']);
            self::assertTrue($report['startup_acknowledged']);
            self::assertSame('pending-bus-name-ownership', $report['ownership_state']);
            self::assertSame('native-runtime-bus-ownership-pending', $report['blocking_reason']);
            self::assertSame('materialize-native-bus-ownership', $report['next_action']);
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
            file_put_contents($readyMarkerPath, "native-runtime-service-started\n");
            file_put_contents($statusFilePath, json_encode([
                'startup_acknowledged' => true,
                'ownership_state' => 'pending-bus-name-ownership',
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT));
            file_put_contents($pidFilePath, "0\n");

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

    public function testCreateNativeBusOwnershipReportUsesConfiguredDriverWhenAvailable(): void
    {
        $toolkit = new SystemApiDbusRuntimeBackendToolkit();
        $baseDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-runtime-driver-' . bin2hex(random_bytes(6));
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
}
