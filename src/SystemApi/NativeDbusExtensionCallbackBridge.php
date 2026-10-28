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

    // Validate before entering typed application methods: native callbacks must not throw TypeError.
    public static function GetCapabilities(mixed $requestId = '', mixed ...$extra): string
    {
        if (func_num_args() !== 1 || !is_string($requestId)) {
            return self::invalidArguments('GetCapabilities');
        }

        return self::runtimeObject()->GetCapabilities($requestId);
    }

    public static function Call(mixed $requestJson = '{}', mixed ...$extra): string
    {
        if (func_num_args() !== 1 || !is_string($requestJson)) {
            return self::invalidArguments('Call');
        }

        return self::runtimeObject()->Call($requestJson);
    }

    private static function invalidArguments(string $method): string
    {
        return json_encode([
            'api_version' => '1.0',
            'request_id' => 'invalid-request',
            'result' => null,
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => $method . ' requiere exactamente un argumento string',
                'retryable' => false,
            ],
        ], JSON_THROW_ON_ERROR) . "\n";
    }

    private static function runtimeObject(): NativeDbusExtensionRuntimeObject
    {
        if (self::$runtimeObject === null) {
            throw new RuntimeException('El bridge D-Bus nativo no tiene un runtime object primed');
        }

        return self::$runtimeObject;
    }
}
