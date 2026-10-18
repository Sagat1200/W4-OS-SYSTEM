#!/usr/bin/env php
<?php

declare(strict_types=1);

const W4_SYSTEM_API_STATUS = 'pending-runtime-adapter';
const W4_SYSTEM_API_BUS_NAME = 'org.w4.System1';
const W4_SYSTEM_API_OBJECT_PATH = '/org/w4/System1';
const W4_SYSTEM_API_INTERFACE = 'org.w4.System1.Manager';

$command = $argv[1] ?? 'DescribeLauncher';

switch ($command) {
    case 'DescribeLauncher':
        emitJson(createLauncherDescription());
        exit(0);

    case 'DescribeLaunchPlan':
        emitJson(createLaunchPlan());
        exit(0);

    case 'DescribeActivationBootstrap':
        emitJson(createActivationBootstrap());
        exit(0);

    case 'DescribeActivationPreparation':
        emitJson(createActivationPreparation());
        exit(0);

    case 'AttemptActivation':
        emitJson(createActivationAttempt());
        exit(0);

    case 'ExecuteActivation':
        $activationExecution = performActivationExecution();
        emitJson($activationExecution);
        exit($activationExecution['exit_code']);

    case 'DescribeHost':
        emitJson(createHostDescription());
        exit(0);

    case 'DescribeSpecification':
        emitJson(createAdapterSpecification());
        exit(0);

    case 'DescribeRuntime':
        emitJson(createAdapterRuntime());
        exit(0);

    case 'DiagnoseRuntime':
        emitJson(buildRuntimeDiagnosis());
        exit(buildRuntimeDiagnosis()['ready'] === true ? 0 : 2);

    case 'DescribeNativeRuntimeService':
        emitJson(createPendingResponse(
            requestId: 'pending-native-runtime-service',
            method: 'system.dbus-native-service',
            resourceKind: 'system-api-dbus-native-runtime-service',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ));
        exit(0);

    case 'StartNativeRuntimeService':
        emitJson(createPendingResponse(
            requestId: 'pending-native-runtime-startup',
            method: 'system.dbus-native-startup',
            resourceKind: 'system-api-dbus-native-runtime-startup',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ));
        exit(3);

    case 'DescribeNativeRuntimeCoupling':
        emitJson(createPendingResponse(
            requestId: 'pending-native-runtime-coupling',
            method: 'system.dbus-native-coupling',
            resourceKind: 'system-api-dbus-native-runtime-coupling',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ));
        exit(3);

    case 'ObserveNativeRuntimeServiceState':
        emitJson(createPendingResponse(
            requestId: 'pending-native-runtime-observation',
            method: 'system.dbus-native-observation',
            resourceKind: 'system-api-dbus-native-runtime-observation',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ));
        exit(3);

    case 'MaterializeNativeBusOwnership':
        emitJson(createPendingResponse(
            requestId: 'pending-native-runtime-ownership',
            method: 'system.dbus-native-ownership',
            resourceKind: 'system-api-dbus-native-runtime-ownership',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ));
        exit(3);

    case 'GetCapabilities':
        emitJson(createPendingResponse(
            requestId: $argv[2] ?? 'pending-get-capabilities',
            method: 'system.capabilities',
            resourceKind: 'system-api-capabilities',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ));
        exit(0);

    case 'Call':
        emitJson(createPendingCallResponse(resolvePendingCallRequest($argv)));
        exit(0);

    case 'serve-stdio-launcher':
        serveStdioLauncher();
        exit(0);

    case 'serve-dbus':
        $serveDbus = serveDbus();
        emitJson($serveDbus);
        exit($serveDbus['exit_code']);

    default:
        fwrite(STDERR, sprintf("ERROR: Comando no soportado: %s\n", $command));
        exit(1);
}

function emitJson(array $payload): void
{
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        fwrite(STDERR, "ERROR: No se pudo serializar el payload del runtime launcher.\n");
        exit(1);
    }

    echo $encoded . PHP_EOL;
}

function createLauncherDescription(): array
{
    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-launcher',
        'status' => 'launcher-ready',
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'transport' => 'stdio-ndjson-launcher',
        'commands' => [
            'DescribeLauncher',
            'DescribeLaunchPlan',
            'DescribeActivationBootstrap',
            'DescribeActivationPreparation',
            'AttemptActivation',
            'ExecuteActivation',
            'DescribeHost',
            'DescribeSpecification',
            'DescribeRuntime',
            'DiagnoseRuntime',
            'DescribeNativeRuntimeService',
            'StartNativeRuntimeService',
            'DescribeNativeRuntimeCoupling',
            'ObserveNativeRuntimeServiceState',
            'MaterializeNativeBusOwnership',
            'GetCapabilities',
            'Call',
            'serve-stdio-launcher',
            'serve-dbus',
        ],
        'handoff_mode' => 'child-process-stdio-relay',
        'delegates_to' => [
            'runtime_host' => '/usr/local/bin/w4-system-api-dbus-runtime-host',
            'call' => 'packaged-runtime-host',
            'get_capabilities' => 'packaged-runtime-host',
            'describe_native_runtime_service' => 'packaged-runtime-host',
            'start_native_runtime_service' => 'packaged-runtime-host',
            'describe_native_runtime_coupling' => 'packaged-runtime-host',
            'observe_native_runtime_service_state' => 'packaged-runtime-host',
            'materialize_native_bus_ownership' => 'packaged-runtime-host',
        ],
        'launch_plan' => createLaunchPlan(),
    ];
}

function createLaunchPlan(): array
{
    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-launch-plan',
        'status' => W4_SYSTEM_API_STATUS,
        'launcher_transport' => 'stdio-ndjson-launcher',
        'target_runtime' => [
            'entrypoint' => '/usr/local/bin/w4-system-api-dbus-runtime-host',
            'command' => 'serve-stdio-host',
            'bus_name' => W4_SYSTEM_API_BUS_NAME,
            'object_path' => W4_SYSTEM_API_OBJECT_PATH,
            'interface' => W4_SYSTEM_API_INTERFACE,
        ],
        'activation_command' => 'serve-dbus',
        'handoff' => [
            'kind' => 'runtime-host',
            'strategy' => 'launch-host-process-and-own-bus',
            'mode' => 'child-process-stdio-relay',
        ],
        'activation_bootstrap' => createActivationBootstrap(),
        'adapter_specification' => createAdapterSpecification(),
    ];
}

function createActivationBootstrap(): array
{
    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-bootstrap',
        'status' => W4_SYSTEM_API_STATUS,
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'launcher' => [
            'entrypoint' => '/usr/local/bin/w4-system-api-dbus-runtime-launcher',
            'command' => 'serve-dbus',
            'transport' => 'stdio-ndjson-launcher',
        ],
        'host_handoff' => [
            'entrypoint' => '/usr/local/bin/w4-system-api-dbus-runtime-host',
            'command' => 'serve-stdio-host',
            'mode' => 'child-process-stdio-relay',
        ],
        'runtime_adapter' => [
            'entrypoint' => '/usr/local/bin/w4-system-api-dbus-runtime-adapter',
            'command' => 'serve-dbus',
            'status' => W4_SYSTEM_API_STATUS,
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

function createActivationPreparation(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();
    $missingCapabilities = is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
        ? $runtimeDiagnosis['missing_capabilities']
        : [];
    $ready = ($runtimeDiagnosis['ready'] ?? false) === true;
    $runtimeBackend = createRuntimeBackend($runtimeDiagnosis);
    $nativeRuntimeService = createNativeRuntimeServiceContext($runtimeDiagnosis, $runtimeBackend);
    $resolvedContract = [
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'runtime_launcher_entry' => getenv('W4_SYSTEM_API_RUNTIME_LAUNCHER_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-launcher',
        'runtime_host_entry' => getenv('W4_SYSTEM_API_RUNTIME_HOST_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-host',
        'runtime_adapter_entry' => getenv('W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-adapter',
        'runtime_bootstrap_spec' => getenv('W4_SYSTEM_API_RUNTIME_BOOTSTRAP_SPEC') ?: '/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json',
    ];
    $nextAction = resolvePreparationNextAction($ready, $runtimeBackend, $nativeRuntimeService);

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-activation-context',
        'status' => $ready ? 'ready-for-runtime-adapter' : 'pending-runtime-adapter',
        'ready' => $ready,
        'binding_status' => W4_SYSTEM_API_STATUS,
        'resolved_contract' => $resolvedContract,
        'activation_bootstrap' => createActivationBootstrap(),
        'runtime_diagnosis' => $runtimeDiagnosis,
        'runtime_backend' => $runtimeBackend,
        'native_runtime_service' => $nativeRuntimeService,
        'execution_plan' => [
            'launcher' => [
                'entrypoint' => getenv('W4_SYSTEM_API_RUNTIME_LAUNCHER_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-launcher',
                'command' => 'serve-dbus',
                'transport' => 'stdio-ndjson-launcher',
            ],
            'host_handoff' => [
                'entrypoint' => getenv('W4_SYSTEM_API_RUNTIME_HOST_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-host',
                'command' => 'serve-stdio-host',
                'mode' => 'child-process-stdio-relay',
            ],
            'runtime_adapter' => [
                'entrypoint' => getenv('W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-adapter',
                'command' => 'serve-dbus',
                'ownership_goal' => W4_SYSTEM_API_BUS_NAME,
            ],
            'runtime_backend' => createRuntimeBackendExecutionPlan($runtimeBackend, $resolvedContract),
        ],
        'unresolved_requirements' => $missingCapabilities,
        'next_action' => $nextAction,
        'recovery_plan' => createRecoveryPlan($nextAction, $nativeRuntimeService),
    ];
}

function createActivationAttempt(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();
    $preparation = createActivationPreparation();
    $ready = ($preparation['ready'] ?? false) === true;
    $runtimeBackend = is_array($preparation['runtime_backend'] ?? null) ? $preparation['runtime_backend'] : [];
    $nativeRuntimeService = is_array($preparation['native_runtime_service'] ?? null)
        ? $preparation['native_runtime_service']
        : null;
    $selectedBackend = is_string($runtimeBackend['selected_backend'] ?? null) ? $runtimeBackend['selected_backend'] : 'contract-pending';
    $nativeBackend = $selectedBackend === 'native-dbus-extension';
    $nativeReady = $nativeBackend && (($runtimeBackend['materialized'] ?? false) === true);
    $nextAction = resolveAttemptNextAction($ready, $nativeReady, $nativeBackend, $nativeRuntimeService);

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-activation-attempt',
        'status' => !$ready
            ? 'blocked-missing-capabilities'
            : ($nativeReady ? 'ready-to-launch-native-runtime' : ($nativeBackend ? 'blocked-native-runtime-backend' : 'ready-to-attempt-runtime-adapter')),
        'attempted' => false,
        'bridge_kind' => 'packaged-runtime-launcher',
        'requested_command' => 'AttemptActivation',
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'blocking_reason' => !$ready
            ? 'missing-runtime-capabilities'
            : ($nativeReady
                ? 'native-runtime-service-not-launched'
                : ($nativeBackend
                    ? (is_string($nativeRuntimeService['blocking_reason'] ?? null)
                        ? $nativeRuntimeService['blocking_reason']
                        : (is_string($runtimeBackend['blocking_reason'] ?? null) ? $runtimeBackend['blocking_reason'] : 'dbus-extension-api-incomplete'))
                    : 'runtime-adapter-not-materialized')),
        'activation_preparation' => $preparation,
        'runtime_diagnosis' => $runtimeDiagnosis,
        'runtime_backend' => $runtimeBackend,
        'native_runtime_service' => $nativeRuntimeService,
        'missing_capabilities' => is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? $runtimeDiagnosis['missing_capabilities']
            : [],
        'next_action' => $nextAction,
        'recovery_plan' => createRecoveryPlan($nextAction, $nativeRuntimeService),
    ];
}

function createActivationExecution(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();
    $attempt = createActivationAttempt();
    $preparation = is_array($attempt['activation_preparation'] ?? null) ? $attempt['activation_preparation'] : [];
    $runtimeAdapterPlan = is_array($preparation['execution_plan']['runtime_adapter'] ?? null)
        ? $preparation['execution_plan']['runtime_adapter']
        : [];
    $runtimeBackendPlan = is_array($preparation['execution_plan']['runtime_backend'] ?? null)
        ? $preparation['execution_plan']['runtime_backend']
        : [];
    $readyForAdapter = ($attempt['status'] ?? null) === 'ready-to-attempt-runtime-adapter';
    $readyForNative = ($attempt['status'] ?? null) === 'ready-to-launch-native-runtime';
    $nextAction = $attempt['next_action'] ?? 'materialize-runtime-adapter';

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-activation-result',
        'status' => $readyForNative
            ? 'execution-pending-native-runtime-service'
            : ($readyForAdapter ? 'execution-pending-runtime-adapter' : 'execution-blocked'),
        'executed' => false,
        'exit_code' => 3,
        'bridge_kind' => 'packaged-runtime-launcher',
        'requested_command' => 'ExecuteActivation',
        'target_command' => $readyForNative
            ? [
                'entrypoint' => $runtimeBackendPlan['entrypoint'] ?? null,
                'command' => $runtimeBackendPlan['command'] ?? null,
                'command_role' => $runtimeBackendPlan['command_role'] ?? null,
                'activation_mode' => $runtimeBackendPlan['activation_mode'] ?? null,
            ]
            : [
                'entrypoint' => $runtimeAdapterPlan['entrypoint'] ?? null,
                'command' => $runtimeAdapterPlan['command'] ?? null,
                'command_role' => 'runtime-adapter-activation',
                'activation_mode' => 'activation-contract',
            ],
        'blocking_reason' => $attempt['blocking_reason'] ?? 'runtime-adapter-not-materialized',
        'activation_attempt' => $attempt,
        'runtime_diagnosis' => $runtimeDiagnosis,
        'runtime_backend' => $attempt['runtime_backend'] ?? [],
        'next_action' => $nextAction,
        'recovery_plan' => is_array($attempt['recovery_plan'] ?? null)
            ? $attempt['recovery_plan']
            : createRecoveryPlan($nextAction),
    ];
}

function performActivationExecution(): array
{
    $activationExecution = createActivationExecution();
    if (!in_array($activationExecution['status'] ?? null, ['execution-pending-runtime-adapter', 'execution-pending-native-runtime-service'], true)) {
        return $activationExecution;
    }

    return finalizeActivationExecution(
        $activationExecution,
        executeTargetCommand(is_array($activationExecution['target_command'] ?? null) ? $activationExecution['target_command'] : null)
    );
}

/**
 * @param array<string, mixed> $runtimeDiagnosis
 * @return array<string, mixed>
 */
function createRuntimeBackend(array $runtimeDiagnosis, ?array $environment = null): array
{
    $environment = $environment ?? captureEnvironment();
    $selectedBackend = $environment['W4_SYSTEM_API_RUNTIME_BACKEND'] ?? 'contract-pending';
    $selectedBackend = is_string($selectedBackend) && trim($selectedBackend) !== ''
        ? trim($selectedBackend)
        : 'contract-pending';
    $ready = ($runtimeDiagnosis['ready'] ?? false) === true;
    $nativeDbusClass = resolveNativeDbusClassName($environment);
    $nativeDbusHasServiceClass = $nativeDbusClass !== null;
    $nativeDbusHasBusSystemConstant = resolveNativeDbusFeatureAvailability(
        $environment,
        'W4_SYSTEM_API_NATIVE_DBUS_HAS_BUS_SYSTEM',
        static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && defined($resolvedClassName . '::BUS_SYSTEM'),
        $nativeDbusClass
    );
    $nativeDbusHasRequestNameMethod = resolveNativeDbusFeatureAvailability(
        $environment,
        'W4_SYSTEM_API_NATIVE_DBUS_HAS_REQUEST_NAME',
        static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'requestName'),
        $nativeDbusClass
    );
    $nativeDbusHasRegisterObjectMethod = resolveNativeDbusFeatureAvailability(
        $environment,
        'W4_SYSTEM_API_NATIVE_DBUS_HAS_REGISTER_OBJECT',
        static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'registerObject'),
        $nativeDbusClass
    );
    $nativeDbusHasWaitLoopMethod = resolveNativeDbusFeatureAvailability(
        $environment,
        'W4_SYSTEM_API_NATIVE_DBUS_HAS_WAIT_LOOP',
        static fn (?string $resolvedClassName): bool => is_string($resolvedClassName) && method_exists($resolvedClassName, 'waitLoop'),
        $nativeDbusClass
    );
    $nativeDbusMissingApi = [];
    if (!$nativeDbusHasServiceClass) {
        $nativeDbusMissingApi[] = 'dbus-service-class';
    }
    if (!$nativeDbusHasBusSystemConstant) {
        $nativeDbusMissingApi[] = 'Dbus::BUS_SYSTEM';
    }
    if (!$nativeDbusHasRequestNameMethod) {
        $nativeDbusMissingApi[] = 'requestName';
    }
    if (!$nativeDbusHasRegisterObjectMethod) {
        $nativeDbusMissingApi[] = 'registerObject';
    }
    if (!$nativeDbusHasWaitLoopMethod) {
        $nativeDbusMissingApi[] = 'waitLoop';
    }
    $nativeDbusCompatibleForService = $nativeDbusHasServiceClass
        && $nativeDbusHasBusSystemConstant
        && $nativeDbusHasRequestNameMethod
        && $nativeDbusHasRegisterObjectMethod
        && $nativeDbusHasWaitLoopMethod;
    $nativeDbusClassResolutionSource = resolveNativeDbusClassResolutionSource($environment, $nativeDbusClass);
    $nativeDbusExtensionLoaded = resolveNativeDbusExtensionLoaded($environment);
    $nativeDbusDiagnosticBlockingReason = $nativeDbusCompatibleForService
        ? null
        : ($nativeDbusHasServiceClass
            ? 'dbus-extension-api-incomplete'
            : 'dbus-extension-service-backend-unavailable');
    $nativeDbusDiagnosticNextAction = $nativeDbusCompatibleForService
        ? 'start-native-runtime-service'
        : ($nativeDbusHasServiceClass
            ? 'inspect-native-dbus-extension-api'
            : ($nativeDbusExtensionLoaded ? 'inspect-native-dbus-service-class' : 'install-native-dbus-extension'));

    return match ($selectedBackend) {
        'experimental-embedded-runtime' => [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'experimental-embedded-runtime',
            'status' => $ready ? 'experimental-runtime-backend-materialized' : W4_SYSTEM_API_STATUS,
            'materialized' => $ready,
            'transport' => 'stdio-activation-contract',
            'ownership_model' => 'process-local-activation-contract',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready ? null : 'missing-runtime-capabilities',
        ],
        'native-dbus-extension' => [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'native-dbus-extension',
            'status' => ($ready && $nativeDbusCompatibleForService)
                ? 'native-runtime-backend-materialized'
                : W4_SYSTEM_API_STATUS,
            'materialized' => $ready && $nativeDbusCompatibleForService,
            'transport' => 'dbus-system-bus',
            'ownership_model' => 'native-php-dbus-extension',
            'supports_bus_ownership' => true,
            'native_runtime_api' => [
                'extension_name' => 'dbus',
                'extension_loaded' => $nativeDbusExtensionLoaded,
                'class_name' => $nativeDbusClass,
                'class_candidates' => ['Dbus', 'DBus'],
                'class_resolution_source' => $nativeDbusClassResolutionSource,
                'class_available' => $nativeDbusHasServiceClass,
                'has_bus_system_constant' => $nativeDbusHasBusSystemConstant,
                'has_request_name_method' => $nativeDbusHasRequestNameMethod,
                'has_register_object_method' => $nativeDbusHasRegisterObjectMethod,
                'has_wait_loop_method' => $nativeDbusHasWaitLoopMethod,
                'compatible_for_service' => $nativeDbusCompatibleForService,
                'missing_api' => $nativeDbusMissingApi,
                'diagnostic_blocking_reason' => $nativeDbusDiagnosticBlockingReason,
                'diagnostic_next_action' => $nativeDbusDiagnosticNextAction,
                'service_contract' => [
                    'bus_name' => W4_SYSTEM_API_BUS_NAME,
                    'object_path' => W4_SYSTEM_API_OBJECT_PATH,
                    'interface' => W4_SYSTEM_API_INTERFACE,
                    'required_methods' => [
                        'requestName',
                        'registerObject',
                        'waitLoop',
                    ],
                ],
            ],
            'blocking_reason' => !$ready
                ? 'missing-runtime-capabilities'
                : ($nativeDbusCompatibleForService
                    ? null
                    : ($nativeDbusHasServiceClass
                        ? 'dbus-extension-api-incomplete'
                        : 'dbus-extension-service-backend-unavailable')),
        ],
        'contract-pending' => [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => 'contract-pending',
            'status' => W4_SYSTEM_API_STATUS,
            'materialized' => false,
            'transport' => 'contract-only',
            'ownership_model' => 'not-materialized',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready ? 'runtime-backend-not-configured' : 'missing-runtime-capabilities',
        ],
        default => [
            'kind' => 'system-api-dbus-runtime-backend',
            'selected_backend' => $selectedBackend,
            'status' => $ready ? 'unsupported-runtime-backend' : W4_SYSTEM_API_STATUS,
            'materialized' => false,
            'transport' => 'unknown',
            'ownership_model' => 'unsupported',
            'supports_bus_ownership' => false,
            'blocking_reason' => $ready ? 'unsupported-runtime-backend' : 'missing-runtime-capabilities',
        ],
    };
}

/**
 * @param array<string, string> $environment
 */
function resolveNativeDbusClassName(array $environment): ?string
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
 */
function resolveNativeDbusClassResolutionSource(array $environment, ?string $className): string
{
    $overrideClassName = $environment['W4_SYSTEM_API_NATIVE_DBUS_CLASS'] ?? null;
    if (is_string($overrideClassName) && trim($overrideClassName) !== '') {
        return 'env-override';
    }

    return $className !== null ? 'runtime-detection' : 'unresolved';
}

/**
 * @param array<string, string> $environment
 */
function resolveNativeDbusExtensionLoaded(array $environment): bool
{
    $override = resolveBoolOverride($environment, 'W4_SYSTEM_API_NATIVE_DBUS_EXTENSION_LOADED');
    if ($override !== null) {
        return $override;
    }

    return extension_loaded('dbus');
}

/**
 * @param array<string, string> $environment
 * @param callable(?string): bool $fallback
 */
function resolveNativeDbusFeatureAvailability(
    array $environment,
    string $overrideKey,
    callable $fallback,
    ?string $className
): bool {
    $override = resolveBoolOverride($environment, $overrideKey);

    return $override ?? $fallback($className);
}

/**
 * @param array<string, mixed> $runtimeBackend
 * @param array<string, string> $resolvedContract
 * @return array<string, mixed>
 */
function createRuntimeBackendExecutionPlan(array $runtimeBackend, array $resolvedContract): array
{
    $selectedBackend = is_string($runtimeBackend['selected_backend'] ?? null)
        ? $runtimeBackend['selected_backend']
        : 'contract-pending';

    if ($selectedBackend === 'native-dbus-extension') {
        return [
            'selected_backend' => 'native-dbus-extension',
            'entrypoint' => $resolvedContract['runtime_adapter_entry'],
            'command' => 'StartNativeRuntimeService',
            'command_role' => 'native-runtime-service-bootstrap',
            'activation_mode' => 'long-running-dbus-service',
            'ownership_goal' => $resolvedContract['bus_name'],
            'service_lifecycle' => 'request-name-and-wait-loop',
        ];
    }

    return [
        'selected_backend' => $selectedBackend,
        'entrypoint' => $resolvedContract['runtime_adapter_entry'],
        'command' => 'serve-dbus',
        'command_role' => 'runtime-adapter-activation',
        'activation_mode' => 'activation-contract',
        'ownership_goal' => $resolvedContract['bus_name'],
    ];
}

/**
 * @param array<string, mixed> $runtimeBackend
 */
function resolvePreparationNextAction(bool $ready, array $runtimeBackend, ?array $nativeRuntimeService = null): string
{
    if (!$ready) {
        return 'materialize-runtime-adapter';
    }

    $selectedBackend = is_string($runtimeBackend['selected_backend'] ?? null)
        ? $runtimeBackend['selected_backend']
        : 'contract-pending';

    if ($selectedBackend !== 'native-dbus-extension') {
        return 'attempt-runtime-adapter';
    }

    return (($runtimeBackend['materialized'] ?? false) === true)
        ? 'launch-native-runtime-service'
        : resolveNativeRuntimeServiceNextAction($nativeRuntimeService, 'complete-native-runtime-backend');
}

/**
 * @param array<string, mixed>|null $nativeRuntimeService
 */
function resolveAttemptNextAction(
    bool $ready,
    bool $nativeReady,
    bool $nativeBackend,
    ?array $nativeRuntimeService = null
): string {
    if (!$ready) {
        return 'materialize-runtime-adapter';
    }

    if ($nativeReady) {
        return 'execute-native-runtime-service';
    }

    if ($nativeBackend) {
        return resolveNativeRuntimeServiceNextAction($nativeRuntimeService, 'complete-native-runtime-backend');
    }

    return 'execute-runtime-adapter';
}

/**
 * @param array<string, mixed> $runtimeDiagnosis
 * @param array<string, mixed> $runtimeBackend
 * @return array<string, mixed>|null
 */
function createNativeRuntimeServiceContext(array $runtimeDiagnosis, array $runtimeBackend): ?array
{
    $selectedBackend = is_string($runtimeBackend['selected_backend'] ?? null)
        ? $runtimeBackend['selected_backend']
        : 'contract-pending';
    if ($selectedBackend !== 'native-dbus-extension') {
        return null;
    }

    $ready = ($runtimeDiagnosis['ready'] ?? false) === true;
    $materialized = (($runtimeBackend['materialized'] ?? false) === true);
    $diagnosticNextAction = $runtimeBackend['native_runtime_api']['diagnostic_next_action'] ?? null;
    $blockingReason = $materialized
        ? 'native-runtime-service-not-started'
        : (is_string($runtimeBackend['blocking_reason'] ?? null)
            ? $runtimeBackend['blocking_reason']
            : ($ready ? 'native-runtime-service-unavailable' : 'missing-runtime-capabilities'));
    $nextAction = $materialized
        ? 'start-native-runtime-service'
        : (is_string($diagnosticNextAction) && $diagnosticNextAction !== ''
            ? $diagnosticNextAction
            : ($ready ? 'complete-native-runtime-backend' : 'materialize-runtime-adapter'));

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-native-runtime-service',
        'status' => $materialized ? 'native-runtime-service-ready-to-start' : 'native-runtime-service-blocked',
        'eligible' => $materialized,
        'selected_backend' => 'native-dbus-extension',
        'runtime_backend' => $runtimeBackend,
        'blocking_reason' => $blockingReason,
        'next_action' => $nextAction,
    ];
}

/**
 * @param array<string, mixed>|null $nativeRuntimeService
 */
function resolveNativeRuntimeServiceNextAction(?array $nativeRuntimeService, string $default): string
{
    $nextAction = $nativeRuntimeService['next_action'] ?? null;

    return is_string($nextAction) && $nextAction !== '' ? $nextAction : $default;
}

/**
 * @param array<string, mixed>|null $nativeRuntimeService
 * @return list<string>
 */
function createRecoveryPlan(string $nextAction, ?array $nativeRuntimeService = null): array
{
    $plan = [];
    foreach ([$nextAction, resolveNativeRuntimeServiceNextAction($nativeRuntimeService, '')] as $candidate) {
        if (!is_string($candidate) || $candidate === '' || in_array($candidate, $plan, true)) {
            continue;
        }

        $plan[] = $candidate;
    }

    return $plan;
}

/**
 * @return array<string, mixed>
 */
function serveDbus(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();

    if (($runtimeDiagnosis['ready'] ?? false) === true) {
        $report = performActivationExecution();
        $report['bridge_kind'] = 'packaged-runtime-launcher';
        $report['requested_command'] = 'serve-dbus';
        $report['launch_plan'] = createLaunchPlan();

        return $report;
    }

    $report = createPendingActivationReport($runtimeDiagnosis, 'packaged-runtime-launcher');
    $report['launch_plan'] = createLaunchPlan();
    $report['exit_code'] = 3;

    return $report;
}

/**
 * @param array<string, mixed>|null $targetCommand
 * @return array<string, mixed>
 */
function executeTargetCommand(?array $targetCommand): array
{
    if (!is_array($targetCommand)) {
        fwrite(STDERR, "ERROR: target_command debe ser un objeto para ejecutar la activacion del runtime launcher.\n");
        exit(1);
    }

    $entrypoint = $targetCommand['entrypoint'] ?? null;
    $command = $targetCommand['command'] ?? null;
    if (!is_string($entrypoint) || trim($entrypoint) === '' || !is_string($command) || trim($command) === '') {
        fwrite(STDERR, "ERROR: target_command.entrypoint y target_command.command deben ser strings no vacios.\n");
        exit(1);
    }

    $resolvedEntrypoint = trim($entrypoint);
    $processCommand = str_ends_with(strtolower($resolvedEntrypoint), '.php')
        ? [PHP_BINARY, $resolvedEntrypoint, trim($command)]
        : [$resolvedEntrypoint, trim($command)];

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($processCommand, $descriptorSpec, $pipes, null, captureEnvironment());
    if (!is_resource($process)) {
        fwrite(STDERR, sprintf("ERROR: No se pudo ejecutar el target command %s %s\n", $resolvedEntrypoint, $command));
        exit(1);
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    $payload = null;

    if (is_string($stdout) && trim($stdout) !== '') {
        try {
            $decoded = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        } catch (\JsonException) {
            $payload = null;
        }
    }

    return [
        'invoked' => true,
        'entrypoint' => $resolvedEntrypoint,
        'command' => trim($command),
        'exit_code' => $exitCode,
        'stdout' => is_string($stdout) ? $stdout : '',
        'stderr' => is_string($stderr) ? $stderr : '',
        'payload' => $payload,
    ];
}

/**
 * @param array<string, mixed> $activationExecution
 * @param array<string, mixed> $commandResult
 * @return array<string, mixed>
 */
function finalizeActivationExecution(array $activationExecution, array $commandResult): array
{
    $exitCode = is_int($commandResult['exit_code'] ?? null) ? $commandResult['exit_code'] : 1;
    $commandRole = is_string($activationExecution['target_command']['command_role'] ?? null)
        ? $activationExecution['target_command']['command_role']
        : 'runtime-adapter-activation';

    $activationExecution['executed'] = true;
    $activationExecution['exit_code'] = $exitCode;
    $activationExecution['status'] = match ($exitCode) {
        0 => 'execution-completed',
        3 => 'execution-attempted-pending-runtime-adapter',
        default => 'execution-attempted-failed',
    };
    $activationExecution['blocking_reason'] = match ($exitCode) {
        0 => null,
        3 => $commandRole === 'native-runtime-service-bootstrap'
            ? 'native-runtime-service-returned-pending'
            : 'runtime-adapter-returned-pending',
        default => 'runtime-adapter-command-failed',
    };
    $activationExecution['command_result'] = $commandResult;
    $activationExecution['next_action'] = match ($exitCode) {
        0 => $commandRole === 'native-runtime-service-bootstrap'
            ? 'observe-native-runtime-service-state'
            : 'observe-runtime-adapter-state',
        3 => $commandRole === 'native-runtime-service-bootstrap'
            ? 'inspect-native-runtime-service-report'
            : 'inspect-runtime-adapter-report',
        default => 'inspect-runtime-adapter-error',
    };

    return $activationExecution;
}

function createHostDescription(): array
{
    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-host',
        'status' => 'local-runtime-host-ready',
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'transport' => 'stdio-ndjson-host',
        'commands' => [
            'DescribeHost',
            'DescribeSpecification',
            'DescribeRuntime',
            'DiagnoseRuntime',
            'GetCapabilities',
            'Call',
            'serve-stdio-host',
            'serve-dbus',
        ],
        'delegates_to' => [
            'runtime_adapter' => '/usr/local/bin/w4-system-api-dbus-runtime-adapter',
            'call' => 'packaged-runtime-adapter',
            'get_capabilities' => 'packaged-runtime-adapter',
        ],
        'adapter_specification' => createAdapterSpecification(),
    ];
}

function createAdapterRuntime(): array
{
    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-adapter',
        'status' => W4_SYSTEM_API_STATUS,
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'commands' => [
            'DescribeSpecification',
            'DescribeRuntime',
            'DiagnoseRuntime',
            'GetCapabilities',
            'Call',
            'serve-stdio-adapter',
            'serve-dbus',
        ],
        'adapter_specification' => createAdapterSpecification(),
    ];
}

function buildRuntimeDiagnosis(): array
{
    $environment = captureEnvironment();
    $hasDbusExtension = resolveBoolOverride($environment, 'W4_SYSTEM_API_PROBE_HAS_DBUS_EXTENSION')
        ?? (extension_loaded('dbus') || extension_loaded('php_dbus') || extension_loaded('php-dbus'));
    $hasBusctl = resolveBoolOverride($environment, 'W4_SYSTEM_API_PROBE_HAS_BUSCTL')
        ?? commandLooksAvailable('busctl');
    $hasGdbus = resolveBoolOverride($environment, 'W4_SYSTEM_API_PROBE_HAS_GDBUS')
        ?? commandLooksAvailable('gdbus');
    $hasDbusDaemon = resolveBoolOverride($environment, 'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON')
        ?? commandLooksAvailable('dbus-daemon');
    $hasSystemBusSocket = resolveBoolOverride($environment, 'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET')
        ?? (PHP_OS_FAMILY !== 'Windows' && file_exists($environment['W4_SYSTEM_API_PROBE_SYSTEM_BUS_SOCKET'] ?? '/run/dbus/system_bus_socket'));
    $phpBinary = $environment['W4_SYSTEM_API_PROBE_PHP_BINARY'] ?? PHP_BINARY;
    $phpVersion = $environment['W4_SYSTEM_API_PROBE_PHP_VERSION'] ?? PHP_VERSION;
    $osFamily = $environment['W4_SYSTEM_API_PROBE_OS_FAMILY'] ?? PHP_OS_FAMILY;

    $missingCapabilities = [];
    if (!$hasDbusExtension) {
        $missingCapabilities[] = 'php-dbus-runtime-adapter';
    }

    if (!$hasDbusDaemon) {
        $missingCapabilities[] = 'dbus-daemon';
    }

    if (!$hasSystemBusSocket) {
        $missingCapabilities[] = 'system-bus-socket';
    }

    if (!$hasBusctl && !$hasGdbus) {
        $missingCapabilities[] = 'dbus-client-tooling';
    }

    $ready = $missingCapabilities === [];

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-diagnosis',
        'ready' => $ready,
        'status' => $ready ? 'ready-for-runtime-adapter' : 'pending-runtime-adapter',
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'environment' => [
            'os_family' => $osFamily,
            'php_binary' => $phpBinary,
            'php_version' => $phpVersion,
        ],
        'probes' => [
            'php_dbus_extension' => $hasDbusExtension,
            'busctl' => $hasBusctl,
            'gdbus' => $hasGdbus,
            'dbus_daemon' => $hasDbusDaemon,
            'system_bus_socket' => $hasSystemBusSocket,
        ],
        'missing_capabilities' => $missingCapabilities,
    ];
}

/**
 * @param array<int, string> $argv
 * @return array<string, mixed>
 */
function resolvePendingCallRequest(array $argv): array
{
    $requestJson = $argv[2] ?? null;
    $requestFile = null;

    for ($index = 2, $count = count($argv); $index < $count; $index++) {
        $argument = $argv[$index];

        if ($argument === '--request-json' && isset($argv[$index + 1])) {
            $requestJson = $argv[++$index];
            continue;
        }

        if ($argument === '--request-file' && isset($argv[$index + 1])) {
            $requestFile = $argv[++$index];
        }
    }

    if (is_string($requestFile) && $requestFile !== '' && is_file($requestFile)) {
        $raw = file_get_contents($requestFile);
        if (is_string($raw) && trim($raw) !== '') {
            $requestJson = $raw;
        }
    }

    if (!is_string($requestJson) || trim($requestJson) === '') {
        return [
            'request_id' => 'pending-call',
            'method' => 'unknown',
        ];
    }

    try {
        $decoded = json_decode($requestJson, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException) {
        return [
            'request_id' => 'pending-call',
            'method' => 'unknown',
        ];
    }

    if (!is_array($decoded)) {
        return [
            'request_id' => 'pending-call',
            'method' => 'unknown',
        ];
    }

    return [
        'request_id' => is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-call',
        'method' => is_string($decoded['method'] ?? null) ? $decoded['method'] : 'unknown',
    ];
}

/**
 * @param array<string, mixed> $request
 * @return array<string, mixed>
 */
function createPendingCallResponse(array $request): array
{
    return createPendingResponse(
        requestId: is_string($request['request_id'] ?? null) ? $request['request_id'] : 'pending-call',
        method: is_string($request['method'] ?? null) ? $request['method'] : 'unknown',
        resourceKind: 'system-api-binding-pending',
        resourceId: W4_SYSTEM_API_BUS_NAME
    );
}

function serveStdioLauncher(): void
{
    while (($line = fgets(STDIN)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $response = handleLauncherEnvelope($line);
        $encoded = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            fwrite(STDERR, "ERROR: No se pudo serializar la respuesta NDJSON del runtime launcher.\n");
            exit(1);
        }

        echo $encoded . PHP_EOL;
        flush();
    }
}

/**
 * @return array<string, mixed>
 */
function handleLauncherEnvelope(string $line): array
{
    try {
        $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        return createProtocolError(sprintf('Envelope JSON invalido: %s', $exception->getMessage()));
    }

    if (!is_array($decoded)) {
        return createProtocolError('El envelope del runtime launcher debe decodificar a un objeto');
    }

    $method = $decoded['method'] ?? null;
    if (!is_string($method) || trim($method) === '') {
        return createProtocolError('method debe ser un string no vacio');
    }

    return match (trim($method)) {
        'DescribeLauncher' => createLauncherDescription(),
        'DescribeLaunchPlan' => createLaunchPlan(),
        'DescribeActivationPreparation' => createActivationPreparation(),
        'AttemptActivation' => createActivationAttempt(),
        'ExecuteActivation' => performActivationExecution(),
        'DescribeHost' => createHostDescription(),
        'DescribeSpecification' => createAdapterSpecification(),
        'DescribeRuntime' => createAdapterRuntime(),
        'DiagnoseRuntime' => buildRuntimeDiagnosis(),
        'DescribeNativeRuntimeService' => createPendingResponse(
            requestId: is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-native-runtime-service',
            method: 'system.dbus-native-service',
            resourceKind: 'system-api-dbus-native-runtime-service',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ),
        'StartNativeRuntimeService' => createPendingResponse(
            requestId: is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-native-runtime-startup',
            method: 'system.dbus-native-startup',
            resourceKind: 'system-api-dbus-native-runtime-startup',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ),
        'DescribeNativeRuntimeCoupling' => createPendingResponse(
            requestId: is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-native-runtime-coupling',
            method: 'system.dbus-native-coupling',
            resourceKind: 'system-api-dbus-native-runtime-coupling',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ),
        'ObserveNativeRuntimeServiceState' => createPendingResponse(
            requestId: is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-native-runtime-observation',
            method: 'system.dbus-native-observation',
            resourceKind: 'system-api-dbus-native-runtime-observation',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ),
        'MaterializeNativeBusOwnership' => createPendingResponse(
            requestId: is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-native-runtime-ownership',
            method: 'system.dbus-native-ownership',
            resourceKind: 'system-api-dbus-native-runtime-ownership',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ),
        'GetCapabilities' => createPendingResponse(
            requestId: is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-get-capabilities',
            method: 'system.capabilities',
            resourceKind: 'system-api-capabilities',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ),
        'Call' => createPendingCallResponse(is_array($decoded['request'] ?? null) ? $decoded['request'] : []),
        'DescribeActivationBootstrap' => createActivationBootstrap(),
        'ServeDbus' => createPendingActivationReport(buildRuntimeDiagnosis(), 'packaged-runtime-launcher'),
        default => createProtocolError(sprintf('Metodo de runtime launcher no soportado: %s', trim($method))),
    };
}

/**
 * @return array<string, mixed>
 */
function createPendingResponse(string $requestId, string $method, string $resourceKind, string $resourceId): array
{
    return [
        'api_version' => '1.0',
        'request_id' => $requestId,
        'result' => null,
        'error' => [
            'code' => 'BINDING_PENDING',
            'message' => sprintf('El runtime D-Bus para %s sigue pendiente; el runtime launcher empaquetado solo congela el handoff publico.', $method),
            'retryable' => false,
            'resource' => [
                'id' => $resourceId,
                'kind' => $resourceKind,
                'binding_status' => W4_SYSTEM_API_STATUS,
            ],
        ],
    ];
}

/**
 * @return array<string, mixed>
 */
function createProtocolError(string $message): array
{
    return [
        'api_version' => '1.0',
        'request_id' => 'runtime-launcher-invalid-request',
        'result' => null,
        'error' => [
            'code' => 'INVALID_ARGUMENT',
            'message' => $message,
            'retryable' => false,
        ],
    ];
}

/**
 * @param array<string, mixed> $runtimeDiagnosis
 * @return array<string, mixed>
 */
function createPendingActivationReport(array $runtimeDiagnosis, string $bridgeKind): array
{
    $activationPreparation = createActivationPreparation();
    $activationAttempt = createActivationAttempt();
    $activationExecution = createActivationExecution();
    $nextAction = is_string($activationPreparation['next_action'] ?? null)
        ? $activationPreparation['next_action']
        : ((($runtimeDiagnosis['ready'] ?? false) === true)
            ? 'attempt-runtime-adapter'
            : 'materialize-runtime-adapter');
    $recoveryPlan = is_array($activationPreparation['recovery_plan'] ?? null)
        ? $activationPreparation['recovery_plan']
        : createRecoveryPlan($nextAction);

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-activation',
        'status' => 'pending-runtime-adapter',
        'bridge_kind' => $bridgeKind,
        'requested_command' => 'serve-dbus',
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'adapter_specification' => createAdapterSpecification(),
        'activation_bootstrap' => createActivationBootstrap(),
        'activation_preparation' => $activationPreparation,
        'activation_attempt' => $activationAttempt,
        'activation_execution' => $activationExecution,
        'launch_plan' => createLaunchPlan(),
        'runtime_diagnosis' => $runtimeDiagnosis,
        'missing_capabilities' => is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? $runtimeDiagnosis['missing_capabilities']
            : [],
        'next_action' => $nextAction,
        'recovery_plan' => $recoveryPlan,
    ];
}

/**
 * @return array<string, mixed>
 */
function createAdapterSpecification(): array
{
    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-adapter-spec',
        'status' => W4_SYSTEM_API_STATUS,
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
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
            'DescribeNativeRuntimeService',
            'StartNativeRuntimeService',
            'DescribeNativeRuntimeCoupling',
            'ObserveNativeRuntimeServiceState',
            'MaterializeNativeBusOwnership',
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

function commandLooksAvailable(string $command): bool
{
    if (PHP_OS_FAMILY === 'Windows') {
        $resolved = shell_exec(sprintf('where %s 2>NUL', escapeshellarg($command)));
    } else {
        $resolved = shell_exec(sprintf('command -v %s 2>/dev/null', escapeshellarg($command)));
    }

    return is_string($resolved) && trim($resolved) !== '';
}

/**
 * @return array<string, string>
 */
function captureEnvironment(): array
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
function resolveBoolOverride(array $environment, string $key): ?bool
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
