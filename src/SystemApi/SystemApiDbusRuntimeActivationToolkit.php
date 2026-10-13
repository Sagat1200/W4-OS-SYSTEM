<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

final class SystemApiDbusRuntimeActivationToolkit
{
    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @return array<string, mixed>
     */
    public function createPendingActivationReport(array $runtimeDiagnosis, string $bridgeKind): array
    {
        /** @var list<string> $missingCapabilities */
        $missingCapabilities = is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? array_values(array_filter(
                $runtimeDiagnosis['missing_capabilities'],
                static fn (mixed $value): bool => is_string($value) && trim($value) !== ''
            ))
            : [];

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-activation',
            'status' => 'pending-runtime-adapter',
            'bridge_kind' => $bridgeKind,
            'requested_command' => 'serve-dbus',
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'runtime_diagnosis' => $runtimeDiagnosis,
            'missing_capabilities' => $missingCapabilities,
            'next_action' => 'materialize-runtime-adapter',
        ];
    }
}
