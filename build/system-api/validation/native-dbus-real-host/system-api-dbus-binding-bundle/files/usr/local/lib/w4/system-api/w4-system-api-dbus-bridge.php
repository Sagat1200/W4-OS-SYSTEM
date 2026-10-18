#!/usr/bin/env php
<?php

declare(strict_types=1);

const W4_SYSTEM_API_STATUS = 'pending-runtime-adapter';
const W4_SYSTEM_API_BUS_NAME = 'org.w4.System1';
const W4_SYSTEM_API_OBJECT_PATH = '/org/w4/System1';
const W4_SYSTEM_API_INTERFACE = 'org.w4.System1.Manager';

$command = $argv[1] ?? 'DescribeRuntime';

$runtime = [
    'api_version' => '1.0',
    'kind' => 'system-api-dbus-bridge-runtime',
    'status' => W4_SYSTEM_API_STATUS,
    'binding_status' => W4_SYSTEM_API_STATUS,
    'bus_name' => W4_SYSTEM_API_BUS_NAME,
    'object_path' => W4_SYSTEM_API_OBJECT_PATH,
    'interface' => W4_SYSTEM_API_INTERFACE,
    'commands' => [
        'Call',
        'GetCapabilities',
        'DiagnoseRuntime',
        'DescribeRuntime',
        'serve-stdio-bridge',
        'serve-dbus',
        'check-readiness',
    ],
    'delegates_to' => [
        'call' => 'pending-runtime-adapter',
        'get_capabilities' => 'pending-runtime-adapter',
        'diagnose_runtime' => '/usr/local/bin/w4-system-api-dbus-runtime-diagnose',
    ],
    'adapter_specification' => createAdapterSpecification(),
    'notes' => [
        'Este bridge empaquetado fija la semantica del binding D-Bus final.',
        'El adaptador D-Bus PHP real todavia no ha sido materializado en esta edicion.',
    ],
];

switch ($command) {
    case 'DescribeRuntime':
    case 'describe-runtime':
        emitJson($runtime);
        exit(0);

    case 'DiagnoseRuntime':
    case 'diagnose-runtime':
        emitJson(buildRuntimeDiagnosis());
        exit(buildRuntimeDiagnosis()['ready'] === true ? 0 : 2);

    case 'GetCapabilities':
    case 'get-capabilities':
        emitJson(createPendingResponse(
            requestId: $argv[2] ?? 'pending-get-capabilities',
            method: 'system.capabilities',
            resourceKind: 'system-api-capabilities',
            resourceId: 'org.w4.System1'
        ));
        exit(0);

    case 'Call':
    case 'call':
        emitJson(createPendingCallResponse(resolvePendingCallRequest($argv)));
        exit(0);

    case 'serve-stdio-bridge':
        serveStdioBridge();
        exit(0);

    case 'check-readiness':
        emitJson(array_merge($runtime, ['ready' => false]));
        exit(2);

    case 'serve-dbus':
        emitJson(createPendingActivationReport(buildRuntimeDiagnosis()));
        exit(3);

    default:
        fwrite(STDERR, sprintf("ERROR: Comando no soportado: %s\n", $command));
        exit(1);
}

function emitJson(array $payload): void
{
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        fwrite(STDERR, "ERROR: No se pudo serializar el payload del bridge.\n");
        exit(1);
    }

    echo $encoded . PHP_EOL;
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

function serveStdioBridge(): void
{
    while (($line = fgets(STDIN)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $response = handleBridgeEnvelope($line);
        $encoded = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            fwrite(STDERR, "ERROR: No se pudo serializar la respuesta NDJSON del bridge empaquetado.\n");
            exit(1);
        }

        echo $encoded . PHP_EOL;
        flush();
    }
}

/**
 * @return array<string, mixed>
 */
function handleBridgeEnvelope(string $line): array
{
    try {
        $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        return createProtocolError(sprintf('Envelope JSON invalido: %s', $exception->getMessage()));
    }

    if (!is_array($decoded)) {
        return createProtocolError('El envelope del bridge empaquetado debe decodificar a un objeto');
    }

    $method = $decoded['method'] ?? null;
    if (!is_string($method) || trim($method) === '') {
        return createProtocolError('method debe ser un string no vacio');
    }

    return match (trim($method)) {
        'DescribeRuntime' => $runtime = [
            'api_version' => '1.0',
            'kind' => 'system-api-dbus-bridge-runtime',
            'status' => W4_SYSTEM_API_STATUS,
            'binding_status' => W4_SYSTEM_API_STATUS,
            'bus_name' => W4_SYSTEM_API_BUS_NAME,
            'object_path' => W4_SYSTEM_API_OBJECT_PATH,
            'interface' => W4_SYSTEM_API_INTERFACE,
            'commands' => ['Call', 'GetCapabilities', 'DiagnoseRuntime', 'DescribeRuntime', 'serve-stdio-bridge', 'serve-dbus', 'check-readiness'],
        ],
        'DiagnoseRuntime' => buildRuntimeDiagnosis(),
        'GetCapabilities' => createPendingResponse(
            requestId: is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-get-capabilities',
            method: 'system.capabilities',
            resourceKind: 'system-api-capabilities',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ),
        'Call' => createPendingCallResponse(is_array($decoded['request'] ?? null) ? $decoded['request'] : []),
        default => createProtocolError(sprintf('Metodo de bridge empaquetado no soportado: %s', trim($method))),
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
            'message' => sprintf('El runtime D-Bus para %s sigue pendiente; el bridge empaquetado solo congela la superficie publica.', $method),
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
        'request_id' => 'bridge-invalid-request',
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
function createPendingActivationReport(array $runtimeDiagnosis): array
{
    $nextAction = 'materialize-runtime-adapter';

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-activation',
        'status' => 'pending-runtime-adapter',
        'bridge_kind' => 'packaged-dbus-bridge',
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
