<?php

declare(strict_types=1);

namespace W4\OS\Tests\Fixtures;

use RuntimeException;
use W4\OS\SystemApi\NativeDbusExtensionRuntimeObject;

final class FakeNativeDbusServiceOptionalBus
{
    public const BUS_SYSTEM = 1;

    public function __construct(private readonly int $busType = 0)
    {
        if ($this->busType !== self::BUS_SYSTEM) {
            throw new RuntimeException('El driver nativo debe preferir BUS_SYSTEM aun cuando el constructor lo declare como opcional');
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

    public function registerObject(
        string $objectPath,
        NativeDbusExtensionRuntimeObject $object,
        string $introspectionXml = '',
        array $definition = []
    ): bool {
        if ($objectPath !== '/org/w4/System1') {
            throw new RuntimeException('El driver nativo debe registrar /org/w4/System1');
        }

        $specification = json_decode($object->DescribeSpecification(), true, 512, JSON_THROW_ON_ERROR);
        if (($specification['interface'] ?? null) !== 'org.w4.System1.Manager') {
            throw new RuntimeException('El objeto exportado no conserva la interfaz esperada');
        }

        if (($definition['interface'] ?? null) !== 'org.w4.System1.Manager') {
            throw new RuntimeException('El driver nativo no entrega la definicion exportada del interface esperado');
        }

        if (($definition['object_path'] ?? null) !== '/org/w4/System1') {
            throw new RuntimeException('La definicion exportada no conserva el object path esperado');
        }

        if (($definition['methods'][0]['name'] ?? null) !== 'Call') {
            throw new RuntimeException('La definicion exportada no publica el metodo Call');
        }

        if (($definition['properties'][1]['name'] ?? null) !== 'BindingStatus') {
            throw new RuntimeException('La definicion exportada no publica la propiedad BindingStatus');
        }

        if (($definition['register_object_handoff']['strategy'] ?? null) !== 'parameter-name-driven-handoff') {
            throw new RuntimeException('La definicion exportada no publica la estrategia de handoff esperada');
        }

        if (($definition['register_object_handoff']['interface'] ?? null) !== 'org.w4.System1.Manager') {
            throw new RuntimeException('La definicion exportada no publica el canal interface esperado');
        }

        if (!str_contains($introspectionXml, '<interface name="org.w4.System1.Manager">')) {
            throw new RuntimeException('La introspeccion exportada no publica el interface esperado');
        }

        if (!str_contains($introspectionXml, '<method name="Call">')) {
            throw new RuntimeException('La introspeccion exportada no publica el metodo Call');
        }

        if (!str_contains($introspectionXml, '<property name="BindingStatus" type="s" access="read"/>')) {
            throw new RuntimeException('La introspeccion exportada no publica la propiedad BindingStatus');
        }

        if (($object->ApiVersion ?? null) !== '1.0') {
            throw new RuntimeException('El objeto exportado no publica ApiVersion');
        }

        if (($object->BindingStatus ?? null) !== 'native-runtime-object-exported') {
            throw new RuntimeException('El objeto exportado no publica BindingStatus');
        }

        $capabilities = json_decode($object->GetCapabilities('fake-dbus-capabilities'), true, 512, JSON_THROW_ON_ERROR);
        if (($capabilities['request_id'] ?? null) !== 'fake-dbus-capabilities') {
            throw new RuntimeException('El objeto exportado no delega GetCapabilities al manager real');
        }

        if (($capabilities['result']['method'] ?? null) !== 'system.capabilities') {
            throw new RuntimeException('GetCapabilities no devuelve el contrato real de system.capabilities');
        }

        $callResponse = json_decode($object->Call(json_encode([
            'api_version' => '1.0',
            'request_id' => 'fake-dbus-call',
            'method' => 'system.dbus-runtime',
            'params' => [],
        ], JSON_THROW_ON_ERROR)), true, 512, JSON_THROW_ON_ERROR);
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
