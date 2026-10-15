<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\SystemApi\NativeDbusExtensionRuntimeObject;

final class NativeDbusExtensionRuntimeObjectTest extends TestCase
{
    public function testDescribeSpecificationExposesRealDispatchMetadata(): void
    {
        $object = new NativeDbusExtensionRuntimeObject(
            [
                'bus_name' => 'org.w4.System1',
                'object_path' => '/org/w4/System1',
                'interface' => 'org.w4.System1.Manager',
                'service_lifecycle' => 'request-name-register-object-wait-loop',
            ],
            dirname(__DIR__)
        );

        $payload = json_decode($object->DescribeSpecification(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('system-api-dbus-native-runtime-object', $payload['kind']);
        self::assertSame('org.w4.System1.Manager', $payload['interface']);
        self::assertSame('system-api-toolkit', $payload['dispatch_mode']);
        self::assertSame('1.0', $payload['api_version']);
        self::assertSame('native-runtime-object-exported', $payload['binding_status']);
        self::assertContains('GetCapabilities', $payload['supported_methods']);
        self::assertContains('Call', $payload['supported_methods']);
        self::assertContains('ApiVersion', $payload['binding_properties']);
        self::assertContains('BindingStatus', $payload['binding_properties']);
    }

    public function testGetCapabilitiesDelegatesToSystemApiToolkit(): void
    {
        $object = new NativeDbusExtensionRuntimeObject(
            [
                'bus_name' => 'org.w4.System1',
                'object_path' => '/org/w4/System1',
                'interface' => 'org.w4.System1.Manager',
                'service_lifecycle' => 'request-name-register-object-wait-loop',
            ],
            dirname(__DIR__)
        );

        $payload = json_decode($object->GetCapabilities('native-runtime-capabilities'), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('native-runtime-capabilities', $payload['request_id']);
        self::assertSame('system.capabilities', $payload['result']['method']);
        self::assertSame('system-api-capabilities', $payload['result']['resource']['kind']);
        self::assertNull($payload['error']);
    }

    public function testCallDelegatesToSystemApiToolkit(): void
    {
        $object = new NativeDbusExtensionRuntimeObject(
            [
                'bus_name' => 'org.w4.System1',
                'object_path' => '/org/w4/System1',
                'interface' => 'org.w4.System1.Manager',
                'service_lifecycle' => 'request-name-register-object-wait-loop',
            ],
            dirname(__DIR__)
        );

        $payload = json_decode($object->Call(json_encode([
            'api_version' => '1.0',
            'request_id' => 'native-runtime-call',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ], JSON_THROW_ON_ERROR)), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('native-runtime-call', $payload['request_id']);
        self::assertSame('system.dbus-runtime', $payload['result']['method']);
        self::assertSame('local-system-api-runtime-probe', $payload['result']['resource']['provider']);
        self::assertNull($payload['error']);
    }

    public function testObjectPublishesDbusReadableProperties(): void
    {
        $object = new NativeDbusExtensionRuntimeObject(
            [
                'bus_name' => 'org.w4.System1',
                'object_path' => '/org/w4/System1',
                'interface' => 'org.w4.System1.Manager',
                'service_lifecycle' => 'request-name-register-object-wait-loop',
            ],
            dirname(__DIR__)
        );

        self::assertSame('1.0', $object->ApiVersion);
        self::assertSame('native-runtime-object-exported', $object->BindingStatus);
    }

    public function testObjectBuildsExportDefinitionAlignedWithBindingSurface(): void
    {
        $object = new NativeDbusExtensionRuntimeObject(
            [
                'bus_name' => 'org.w4.System1',
                'object_path' => '/org/w4/System1',
                'interface' => 'org.w4.System1.Manager',
                'service_lifecycle' => 'request-name-register-object-wait-loop',
            ],
            dirname(__DIR__)
        );

        $payload = $object->createExportDefinitionPayload();

        self::assertSame('org.w4.System1.Manager', $payload['interface']);
        self::assertSame('/org/w4/System1', $payload['object_path']);
        self::assertSame('Call', $payload['methods'][0]['name']);
        self::assertSame('request_json', $payload['methods'][0]['input'][0]['name']);
        self::assertSame('GetCapabilities', $payload['methods'][1]['name']);
        self::assertSame('ApiVersion', $payload['properties'][0]['name']);
        self::assertSame('BindingStatus', $payload['properties'][1]['name']);
    }
}
