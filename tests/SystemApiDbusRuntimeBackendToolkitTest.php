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
}
