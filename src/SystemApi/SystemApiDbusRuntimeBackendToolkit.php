<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

final class SystemApiDbusRuntimeBackendToolkit
{
    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @return array<string, mixed>
     */
    public function createReport(array $runtimeDiagnosis, ?array $environment = null): array
    {
        $env = $environment ?? $this->captureEnvironment();
        $selectedBackend = $this->resolveSelectedBackend($env);
        $ready = ($runtimeDiagnosis['ready'] ?? false) === true;

        return match ($selectedBackend) {
            'experimental-embedded-runtime' => $this->createExperimentalEmbeddedRuntimeReport($ready),
            'native-dbus-extension' => $this->createNativeDbusExtensionReport($ready, $env),
            'contract-pending' => $this->createContractPendingReport($ready),
            default => $this->createUnsupportedBackendReport($selectedBackend, $ready),
        };
    }

    /**
     * @return array<string, string>
     */
    private function captureEnvironment(): array
    {
        $environment = getenv();
        if (!is_array($environment)) {
            return [];
        }

        $captured = [];
        foreach ($environment as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $captured[$key] = $value;
            }
        }

        return $captured;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveSelectedBackend(array $environment): string
    {
        $selectedBackend = $environment['W4_SYSTEM_API_RUNTIME_BACKEND'] ?? 'contract-pending';

        return is_string($selectedBackend) && trim($selectedBackend) !== ''
            ? trim($selectedBackend)
            : 'contract-pending';
    }

    /**
     * @return array<string, mixed>
     */
    private function createContractPendingReport(bool $ready): array
    {
        return [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'contract-pending',
            'status' => 'pending-runtime-adapter',
            'materialized' => false,
            'transport' => 'contract-only',
            'ownership_model' => 'not-materialized',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready ? 'runtime-backend-not-configured' : 'missing-runtime-capabilities',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createExperimentalEmbeddedRuntimeReport(bool $ready): array
    {
        return [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'experimental-embedded-runtime',
            'status' => $ready
                ? 'experimental-runtime-backend-materialized'
                : 'pending-runtime-adapter',
            'materialized' => $ready,
            'transport' => 'stdio-activation-contract',
            'ownership_model' => 'process-local-activation-contract',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready ? null : 'missing-runtime-capabilities',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createNativeDbusExtensionReport(bool $ready, array $environment): array
    {
        $className = $this->resolveNativeDbusClassName($environment);
        $hasServiceClass = $className !== null;
        $hasBusSystemConstant = $this->resolveNativeDbusFeatureAvailability(
            $environment,
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM',
            static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && defined($resolvedClassName . '::BUS_SYSTEM'),
            $className
        );
        $hasRequestNameMethod = $this->resolveNativeDbusFeatureAvailability(
            $environment,
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME',
            static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'requestName'),
            $className
        );
        $hasRegisterObjectMethod = $this->resolveNativeDbusFeatureAvailability(
            $environment,
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT',
            static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'registerObject'),
            $className
        );
        $hasWaitLoopMethod = $this->resolveNativeDbusFeatureAvailability(
            $environment,
            'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP',
            static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'waitLoop'),
            $className
        );
        $missingApi = [];
        if (!$hasServiceClass) {
            $missingApi[] = 'dbus-service-class';
        }
        if (!$hasBusSystemConstant) {
            $missingApi[] = 'Dbus::BUS_SYSTEM';
        }
        if (!$hasRequestNameMethod) {
            $missingApi[] = 'requestName';
        }
        if (!$hasRegisterObjectMethod) {
            $missingApi[] = 'registerObject';
        }
        if (!$hasWaitLoopMethod) {
            $missingApi[] = 'waitLoop';
        }

        $compatibleForService = $hasServiceClass
            && $hasBusSystemConstant
            && $hasRequestNameMethod
            && $hasRegisterObjectMethod
            && $hasWaitLoopMethod;
        $materialized = $ready && $compatibleForService;

        return [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'native-dbus-extension',
            'status' => $materialized
                ? 'native-runtime-backend-materialized'
                : 'pending-runtime-adapter',
            'materialized' => $materialized,
            'transport' => 'dbus-system-bus',
            'ownership_model' => 'native-php-dbus-extension',
            'supports_bus_ownership' => true,
            'native_runtime_api' => [
                'class_name' => $className,
                'class_available' => $hasServiceClass,
                'has_bus_system_constant' => $hasBusSystemConstant,
                'has_request_name_method' => $hasRequestNameMethod,
                'has_register_object_method' => $hasRegisterObjectMethod,
                'has_wait_loop_method' => $hasWaitLoopMethod,
                'compatible_for_service' => $compatibleForService,
                'missing_api' => $missingApi,
                'service_contract' => [
                    'bus_name' => 'org.w4.System1',
                    'object_path' => '/org/w4/System1',
                    'interface' => 'org.w4.System1.Manager',
                    'required_methods' => [
                        'requestName',
                        'registerObject',
                        'waitLoop',
                    ],
                ],
            ],
            'blocking_reason' => !$ready
                ? 'missing-runtime-capabilities'
                : ($compatibleForService
                    ? null
                    : ($hasServiceClass
                        ? 'dbus-extension-api-incomplete'
                        : 'dbus-extension-service-backend-unavailable')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createUnsupportedBackendReport(string $selectedBackend, bool $ready): array
    {
        return [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => $selectedBackend,
            'status' => $ready ? 'unsupported-runtime-backend' : 'pending-runtime-adapter',
            'materialized' => false,
            'transport' => 'unknown',
            'ownership_model' => 'unsupported',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready
                ? 'unsupported-runtime-backend'
                : 'missing-runtime-capabilities',
        ];
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveNativeDbusClassName(array $environment): ?string
    {
        $overrideClassName = $environment['W4_SYSTEM_API_NATIVE_DBUS_CLASS'] ?? null;
        if (is_string($overrideClassName) && trim($overrideClassName) !== '') {
            return trim($overrideClassName);
        }

        foreach (['Dbus', 'DBus'] as $candidate) {
            if (class_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param array<string, string> $environment
     * @param callable(?string): bool $fallback
     */
    private function resolveNativeDbusFeatureAvailability(
        array $environment,
        string $overrideKey,
        callable $fallback,
        ?string $className
    ): bool {
        $override = $this->resolveBoolOverride($environment, $overrideKey);

        return $override ?? $fallback($className);
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveBoolOverride(array $environment, string $key): ?bool
    {
        $value = $environment[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        return match (strtolower(trim($value))) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }
}
