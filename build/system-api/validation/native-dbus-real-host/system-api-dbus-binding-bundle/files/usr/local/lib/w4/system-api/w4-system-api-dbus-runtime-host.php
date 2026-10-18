#!/usr/bin/env php
<?php

declare(strict_types=1);

const W4_SYSTEM_API_STATUS = 'pending-runtime-adapter';
const W4_SYSTEM_API_BUS_NAME = 'org.w4.System1';
const W4_SYSTEM_API_OBJECT_PATH = '/org/w4/System1';
const W4_SYSTEM_API_INTERFACE = 'org.w4.System1.Manager';

$command = $argv[1] ?? 'DescribeHost';

switch ($command) {
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

    case 'serve-stdio-host':
        serveStdioHost();
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
        fwrite(STDERR, "ERROR: No se pudo serializar el payload del runtime host.\n");
        exit(1);
    }

    echo $encoded . PHP_EOL;
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
            'DescribeNativeRuntimeService',
            'StartNativeRuntimeService',
            'DescribeNativeRuntimeCoupling',
            'ObserveNativeRuntimeServiceState',
            'MaterializeNativeBusOwnership',
            'GetCapabilities',
            'Call',
            'serve-stdio-host',
            'serve-dbus',
        ],
        'delegates_to' => [
            'runtime_adapter' => '/usr/local/bin/w4-system-api-dbus-runtime-adapter',
            'call' => 'packaged-runtime-adapter',
            'get_capabilities' => 'packaged-runtime-adapter',
            'describe_native_runtime_service' => 'packaged-runtime-adapter',
            'start_native_runtime_service' => 'packaged-runtime-adapter',
            'describe_native_runtime_coupling' => 'packaged-runtime-adapter',
            'observe_native_runtime_service_state' => 'packaged-runtime-adapter',
            'materialize_native_bus_ownership' => 'packaged-runtime-adapter',
        ],
        'adapter_specification' => createAdapterSpecification(),
    ];
}

function createAdapterRuntime(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();
    $runtimeBackend = createRuntimeBackend($runtimeDiagnosis);

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-adapter',
        'status' => $runtimeBackend['status'] ?? W4_SYSTEM_API_STATUS,
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
        'runtime_diagnosis' => $runtimeDiagnosis,
        'runtime_backend' => $runtimeBackend,
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
 * @return array<string, mixed>
 */
function serveDbus(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();
    $runtimeBackend = createRuntimeBackend($runtimeDiagnosis);

    if (($runtimeBackend['materialized'] ?? false) === true) {
        return [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-runtime-activation',
            'status' => $runtimeBackend['status'] ?? 'runtime-backend-materialized',
            'bridge_kind' => 'packaged-runtime-host',
            'requested_command' => 'serve-dbus',
            'bus_name' => W4_SYSTEM_API_BUS_NAME,
            'object_path' => W4_SYSTEM_API_OBJECT_PATH,
            'interface' => W4_SYSTEM_API_INTERFACE,
            'materialized' => true,
            'exit_code' => 0,
            'adapter_specification' => createAdapterSpecification(),
            'activation_bootstrap' => createActivationBootstrap(),
            'runtime_diagnosis' => $runtimeDiagnosis,
            'runtime_backend' => $runtimeBackend,
            'next_action' => 'observe-runtime-backend',
        ];
    }

    $report = createPendingActivationReport($runtimeDiagnosis, 'packaged-runtime-host');
    $report['runtime_backend'] = $runtimeBackend;
    $report['exit_code'] = 3;

    return $report;
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

function serveStdioHost(): void
{
    while (($line = fgets(STDIN)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $response = handleHostEnvelope($line);
        $encoded = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            fwrite(STDERR, "ERROR: No se pudo serializar la respuesta NDJSON del runtime host.\n");
            exit(1);
        }

        echo $encoded . PHP_EOL;
        flush();
    }
}

/**
 * @return array<string, mixed>
 */
function handleHostEnvelope(string $line): array
{
    try {
        $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        return createProtocolError(sprintf('Envelope JSON invalido: %s', $exception->getMessage()));
    }

    if (!is_array($decoded)) {
        return createProtocolError('El envelope del runtime host debe decodificar a un objeto');
    }

    $method = $decoded['method'] ?? null;
    if (!is_string($method) || trim($method) === '') {
        return createProtocolError('method debe ser un string no vacio');
    }

    return match (trim($method)) {
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
        'ServeDbus' => createPendingActivationReport(buildRuntimeDiagnosis(), 'packaged-runtime-host'),
        default => createProtocolError(sprintf('Metodo de runtime host no soportado: %s', trim($method))),
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
            'message' => sprintf('El runtime D-Bus para %s sigue pendiente; el runtime host empaquetado solo congela la superficie publica.', $method),
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
        'request_id' => 'runtime-host-invalid-request',
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
    $nextAction = 'materialize-runtime-adapter';

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
        'runtime_diagnosis' => $runtimeDiagnosis,
        'missing_capabilities' => is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? $runtimeDiagnosis['missing_capabilities']
            : [],
        'next_action' => $nextAction,
        'recovery_plan' => [$nextAction],
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
