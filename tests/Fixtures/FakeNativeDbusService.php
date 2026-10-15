<?php

declare(strict_types=1);

namespace W4\OS\Tests\Fixtures;

use RuntimeException;
use W4\OS\SystemApi\NativeDbusExtensionRuntimeObject;

final class FakeNativeDbusService
{
    public const BUS_SYSTEM = 1;

    public function __construct(
        private readonly int $busType
    ) {
        if ($this->busType !== self::BUS_SYSTEM) {
            throw new RuntimeException('El driver nativo debe solicitar BUS_SYSTEM');
        }
    }

    public function requestName(string $busName, int $flags = 0): bool
    {
        if ($busName !== 'org.w4.System1') {
            throw new RuntimeException('El driver nativo debe solicitar org.w4.System1');
        }

        if ($flags !== 0) {
            throw new RuntimeException('El driver nativo no debe enviar flags inesperados');
        }

        return true;
    }

    public function registerObject(string $objectPath, NativeDbusExtensionRuntimeObject $object): bool
    {
        if ($objectPath !== '/org/w4/System1') {
            throw new RuntimeException('El driver nativo debe registrar /org/w4/System1');
        }

        $specification = $object->describeSpecification();
        if (($specification['interface'] ?? null) !== 'org.w4.System1.Manager') {
            throw new RuntimeException('El objeto exportado no conserva la interfaz esperada');
        }

        $capabilities = $object->getCapabilities('fake-dbus-capabilities');
        if (($capabilities['request_id'] ?? null) !== 'fake-dbus-capabilities') {
            throw new RuntimeException('El objeto exportado no delega GetCapabilities al manager real');
        }

        if (($capabilities['result']['method'] ?? null) !== 'system.capabilities') {
            throw new RuntimeException('GetCapabilities no devuelve el contrato real de system.capabilities');
        }

        $callResponse = $object->call([
            'api_version' => '1.0',
            'request_id' => 'fake-dbus-call',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ]);
        if (($callResponse['request_id'] ?? null) !== 'fake-dbus-call') {
            throw new RuntimeException('El objeto exportado no conserva el request_id de Call');
        }

        if (($callResponse['result']['method'] ?? null) !== 'system.dbus-runtime') {
            throw new RuntimeException('Call no delega al toolkit real para system.dbus-runtime');
        }

        return true;
    }

    public function waitLoop(): bool
    {
        return true;
    }
}
