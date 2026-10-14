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
            'native-dbus-extension' => $this->createNativeDbusExtensionReport($ready),
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
    private function createNativeDbusExtensionReport(bool $ready): array
    {
        $hasServiceClass = class_exists('Dbus') || class_exists('DBus');
        $materialized = $ready && $hasServiceClass;

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
            'blocking_reason' => !$ready
                ? 'missing-runtime-capabilities'
                : ($hasServiceClass ? null : 'dbus-extension-service-backend-unavailable'),
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
}
