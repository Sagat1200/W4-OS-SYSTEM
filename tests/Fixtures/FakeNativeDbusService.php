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

        return true;
    }

    public function waitLoop(): bool
    {
        return true;
    }
}
