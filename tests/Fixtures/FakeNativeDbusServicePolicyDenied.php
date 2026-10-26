<?php

declare(strict_types=1);

namespace W4\OS\Tests\Fixtures;

use RuntimeException;

final class FakeNativeDbusServicePolicyDenied
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
        throw new RuntimeException(
            sprintf(
                'Connection ":1.2" is not allowed to own the service "%s" due to security policies in the configuration file',
                $busName
            )
        );
    }

    public function registerObject(string $objectPath, object $object, string $introspectionXml = '', array $definition = []): bool
    {
        return true;
    }

    public function waitLoop(): bool
    {
        return true;
    }
}
