<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use RuntimeException;

final class NativeDbusExtensionCallbackBridge
{
    private static ?NativeDbusExtensionRuntimeObject $runtimeObject = null;

    public static function prime(NativeDbusExtensionRuntimeObject $runtimeObject): string
    {
        self::$runtimeObject = $runtimeObject;

        return self::class;
    }

    public static function DescribeSpecification(): string
    {
        return self::runtimeObject()->DescribeSpecification();
    }

    public static function GetCapabilities(string $requestId = ''): string
    {
        return self::runtimeObject()->GetCapabilities($requestId);
    }

    public static function Call(string $requestJson = '{}'): string
    {
        return self::runtimeObject()->Call($requestJson);
    }

    private static function runtimeObject(): NativeDbusExtensionRuntimeObject
    {
        if (self::$runtimeObject === null) {
            throw new RuntimeException('El bridge D-Bus nativo no tiene un runtime object primed');
        }

        return self::$runtimeObject;
    }
}
