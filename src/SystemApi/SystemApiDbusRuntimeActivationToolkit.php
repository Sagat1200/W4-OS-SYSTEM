<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

final class SystemApiDbusRuntimeActivationToolkit
{
    /**
     * @return array<string, mixed>
     */
    public function createActivationBootstrap(): array
    {
        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-bootstrap',
            'status' => 'pending-runtime-adapter',
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'launcher' => [
                'entrypoint' => 'scripts/system_api_dbus_runtime_launcher.php',
                'command' => 'serve-dbus',
                'transport' => 'stdio-ndjson-launcher',
            ],
            'host_handoff' => [
                'entrypoint' => 'scripts/system_api_dbus_runtime_host.php',
                'command' => 'serve-stdio-host',
                'mode' => 'child-process-stdio-relay',
            ],
            'runtime_adapter' => [
                'entrypoint' => 'scripts/system_api_dbus_runtime_adapter.php',
                'command' => 'serve-dbus',
                'status' => 'pending-runtime-adapter',
                'required_capabilities' => [
                    'php-dbus-runtime-adapter',
                    'bus-name-ownership',
                    'dbus-method-dispatch',
                ],
            ],
            'environment_contract' => [
                'W4_SYSTEM_API_BUS_NAME',
                'W4_SYSTEM_API_OBJECT_PATH',
                'W4_SYSTEM_API_INTERFACE',
                'W4_SYSTEM_API_RUNTIME_LAUNCHER_ENTRY',
                'W4_SYSTEM_API_RUNTIME_HOST_ENTRY',
                'W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY',
            ],
            'phases' => [
                'launch-runtime-launcher',
                'handoff-to-runtime-host',
                'materialize-runtime-adapter',
                'own-org.w4.System1',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createAdapterSpecification(): array
    {
        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-adapter-spec',
            'status' => 'pending-runtime-adapter',
            'bus_name' => 'org.w4.System1',
            'object_path' => '/org/w4/System1',
            'interface' => 'org.w4.System1.Manager',
            'activation' => [
                'entry_command' => 'serve-dbus',
                'service_file' => '/usr/share/dbus-1/system-services/org.w4.System1.service',
                'systemd_unit' => '/etc/systemd/system/w4-system-api.service',
            ],
            'required_capabilities' => [
                'php-dbus-runtime-adapter',
                'bus-name-ownership',
                'dbus-method-dispatch',
            ],
            'required_methods' => [
                'Call',
                'GetCapabilities',
            ],
            'optional_methods' => [
                'DiagnoseRuntime',
                'DescribeRuntime',
            ],
            'protocol' => [
                'input' => 'string-json',
                'output' => 'string-json',
                'transport' => 'dbus-system-bus',
            ],
            'exit_codes' => [
                '0' => 'runtime-active',
                '1' => 'configuration-error',
                '3' => 'runtime-pending',
            ],
        ];
    }

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
            'adapter_specification' => $this->createAdapterSpecification(),
            'activation_bootstrap' => $this->createActivationBootstrap(),
            'runtime_diagnosis' => $runtimeDiagnosis,
            'missing_capabilities' => $missingCapabilities,
            'next_action' => 'materialize-runtime-adapter',
        ];
    }
}
