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
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @param array<string, string> $entrypoints
     * @return array<string, mixed>
     */
    public function createActivationPreparation(
        array $runtimeDiagnosis,
        ?array $environment = null,
        array $entrypoints = []
    ): array {
        $env = $environment ?? $this->captureEnvironment();
        $missingCapabilities = $this->normalizeMissingCapabilities($runtimeDiagnosis);
        $ready = ($runtimeDiagnosis['ready'] ?? false) === true;

        $resolvedContract = [
            'bus_name' => $this->resolveStringValue($env, 'W4_SYSTEM_API_BUS_NAME', 'org.w4.System1'),
            'object_path' => $this->resolveStringValue($env, 'W4_SYSTEM_API_OBJECT_PATH', '/org/w4/System1'),
            'interface' => $this->resolveStringValue($env, 'W4_SYSTEM_API_INTERFACE', 'org.w4.System1.Manager'),
            'runtime_launcher_entry' => $this->resolveEntrypoint(
                $env,
                'W4_SYSTEM_API_RUNTIME_LAUNCHER_ENTRY',
                $entrypoints['runtime_launcher_entry'] ?? 'scripts/system_api_dbus_runtime_launcher.php'
            ),
            'runtime_host_entry' => $this->resolveEntrypoint(
                $env,
                'W4_SYSTEM_API_RUNTIME_HOST_ENTRY',
                $entrypoints['runtime_host_entry'] ?? 'scripts/system_api_dbus_runtime_host.php'
            ),
            'runtime_adapter_entry' => $this->resolveEntrypoint(
                $env,
                'W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY',
                $entrypoints['runtime_adapter_entry'] ?? 'scripts/system_api_dbus_runtime_adapter.php'
            ),
            'runtime_bootstrap_spec' => $this->resolveEntrypoint(
                $env,
                'W4_SYSTEM_API_RUNTIME_BOOTSTRAP_SPEC',
                $entrypoints['runtime_bootstrap_spec'] ?? 'embedded:DescribeActivationBootstrap'
            ),
        ];

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-activation-context',
            'status' => $ready ? 'ready-for-runtime-adapter' : 'pending-runtime-adapter',
            'ready' => $ready,
            'binding_status' => 'pending-runtime-adapter',
            'resolved_contract' => $resolvedContract,
            'activation_bootstrap' => $this->createActivationBootstrap(),
            'runtime_diagnosis' => $runtimeDiagnosis,
            'execution_plan' => [
                'launcher' => [
                    'entrypoint' => $resolvedContract['runtime_launcher_entry'],
                    'command' => 'serve-dbus',
                    'transport' => 'stdio-ndjson-launcher',
                ],
                'host_handoff' => [
                    'entrypoint' => $resolvedContract['runtime_host_entry'],
                    'command' => 'serve-stdio-host',
                    'mode' => 'child-process-stdio-relay',
                ],
                'runtime_adapter' => [
                    'entrypoint' => $resolvedContract['runtime_adapter_entry'],
                    'command' => 'serve-dbus',
                    'ownership_goal' => $resolvedContract['bus_name'],
                ],
            ],
            'unresolved_requirements' => $missingCapabilities,
            'next_action' => $ready ? 'attempt-runtime-adapter' : 'materialize-runtime-adapter',
        ];
    }

    /**
     * @param array<string, mixed> $runtimeDiagnosis
     * @param array<string, string>|null $environment
     * @param array<string, string> $entrypoints
     * @return array<string, mixed>
     */
    public function createActivationAttempt(
        array $runtimeDiagnosis,
        string $bridgeKind,
        ?array $environment = null,
        array $entrypoints = []
    ): array {
        $preparation = $this->createActivationPreparation($runtimeDiagnosis, $environment, $entrypoints);
        $ready = ($preparation['ready'] ?? false) === true;
        $blockingReason = $ready ? 'runtime-adapter-not-materialized' : 'missing-runtime-capabilities';

        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-activation-attempt',
            'status' => $ready ? 'ready-to-attempt-runtime-adapter' : 'blocked-missing-capabilities',
            'attempted' => false,
            'bridge_kind' => $bridgeKind,
            'requested_command' => 'AttemptActivation',
            'bus_name' => $preparation['resolved_contract']['bus_name'],
            'object_path' => $preparation['resolved_contract']['object_path'],
            'interface' => $preparation['resolved_contract']['interface'],
            'blocking_reason' => $blockingReason,
            'activation_preparation' => $preparation,
            'runtime_diagnosis' => $runtimeDiagnosis,
            'missing_capabilities' => $this->normalizeMissingCapabilities($runtimeDiagnosis),
            'next_action' => $ready ? 'execute-runtime-adapter' : 'materialize-runtime-adapter',
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
        $missingCapabilities = $this->normalizeMissingCapabilities($runtimeDiagnosis);

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
            'activation_preparation' => $this->createActivationPreparation($runtimeDiagnosis),
            'activation_attempt' => $this->createActivationAttempt($runtimeDiagnosis, $bridgeKind),
            'runtime_diagnosis' => $runtimeDiagnosis,
            'missing_capabilities' => $missingCapabilities,
            'next_action' => (($runtimeDiagnosis['ready'] ?? false) === true)
                ? 'attempt-runtime-adapter'
                : 'materialize-runtime-adapter',
        ];
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
     * @param array<string, mixed> $runtimeDiagnosis
     * @return list<string>
     */
    private function normalizeMissingCapabilities(array $runtimeDiagnosis): array
    {
        return is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? array_values(array_filter(
                $runtimeDiagnosis['missing_capabilities'],
                static fn (mixed $value): bool => is_string($value) && trim($value) !== ''
            ))
            : [];
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveStringValue(array $environment, string $key, string $default): string
    {
        $value = $environment[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    /**
     * @param array<string, string> $environment
     */
    private function resolveEntrypoint(array $environment, string $key, string $default): string
    {
        $value = $environment[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }
}
