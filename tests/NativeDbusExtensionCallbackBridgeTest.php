<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use W4\OS\SystemApi\NativeDbusExtensionCallbackBridge;
use W4\OS\SystemApi\NativeDbusExtensionRuntimeObject;

final class NativeDbusExtensionCallbackBridgeTest extends TestCase
{
    protected function setUp(): void
    {
        $this->resetBridgeRuntimeObject();
    }

    public function testPrimeReturnsBridgeClassNameAndDelegatesDescribeSpecification(): void
    {
        $runtimeObject = $this->createRuntimeObject();

        $className = NativeDbusExtensionCallbackBridge::prime($runtimeObject);
        $payload = json_decode(NativeDbusExtensionCallbackBridge::DescribeSpecification(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(NativeDbusExtensionCallbackBridge::class, $className);
        self::assertSame('system-api-dbus-native-runtime-object', $payload['kind']);
        self::assertSame('org.w4.System1.Manager', $payload['interface']);
        self::assertSame('native-runtime-object-exported', $payload['binding_status']);
    }

    public function testBridgeDelegatesRuntimeMethodsAfterPrime(): void
    {
        NativeDbusExtensionCallbackBridge::prime($this->createRuntimeObject());

        $capabilities = json_decode(
            NativeDbusExtensionCallbackBridge::GetCapabilities('bridge-capabilities'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );
        $call = json_decode(
            NativeDbusExtensionCallbackBridge::Call(json_encode([
                'api_version' => '1.0',
                'request_id' => 'bridge-call',
                'method' => 'system.dbus-runtime',
                'params' => [],
            ], JSON_THROW_ON_ERROR)),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        self::assertSame('bridge-capabilities', $capabilities['request_id']);
        self::assertSame('system.capabilities', $capabilities['result']['method']);
        self::assertSame('bridge-call', $call['request_id']);
        self::assertSame('system.dbus-runtime', $call['result']['method']);
    }

    public function testBridgeFailsClearlyWhenRuntimeObjectWasNotPrimed(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('no tiene un runtime object primed');

        NativeDbusExtensionCallbackBridge::Call('{}');
    }

    private function createRuntimeObject(): NativeDbusExtensionRuntimeObject
    {
        return new NativeDbusExtensionRuntimeObject(
            [
                'bus_name' => 'org.w4.System1',
                'object_path' => '/org/w4/System1',
                'interface' => 'org.w4.System1.Manager',
                'service_lifecycle' => 'request-name-register-object-wait-loop',
            ],
            dirname(__DIR__)
        );
    }

    private function resetBridgeRuntimeObject(): void
    {
        $property = new \ReflectionProperty(NativeDbusExtensionCallbackBridge::class, 'runtimeObject');
        $property->setValue(null, null);
    }
}
