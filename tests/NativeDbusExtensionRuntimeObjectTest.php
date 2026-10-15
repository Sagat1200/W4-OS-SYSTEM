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

        $payload = $object->describeSpecification();

        self::assertSame('system-api-dbus-native-runtime-object', $payload['kind']);
        self::assertSame('org.w4.System1.Manager', $payload['interface']);
        self::assertSame('system-api-toolkit', $payload['dispatch_mode']);
        self::assertContains('GetCapabilities', $payload['supported_methods']);
        self::assertContains('Call', $payload['supported_methods']);
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

        $payload = $object->getCapabilities('native-runtime-capabilities');

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

        $payload = $object->call([
            'api_version' => '1.0',
            'request_id' => 'native-runtime-call',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ]);

        self::assertSame('native-runtime-call', $payload['request_id']);
        self::assertSame('system.dbus-runtime', $payload['result']['method']);
        self::assertSame('local-system-api-runtime-probe', $payload['result']['resource']['provider']);
        self::assertNull($payload['error']);
    }
}
