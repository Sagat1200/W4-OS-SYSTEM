<?php

declare(strict_types=1);

namespace W4\OS\SystemApi;

use W4\OS\Support\ArtifactMetadataToolkit;

final class SystemApiDbusBindingToolkit
{
    private const API_VERSION = '1.0';
    private const BUS_NAME = 'org.w4.System1';
    private const OBJECT_PATH = '/org/w4/System1';
    private const INTERFACE_NAME = 'org.w4.System1.Manager';
    private const BINDING_STATUS = 'pending-runtime-adapter';

    private ArtifactMetadataToolkit $metadataToolkit;

    public function __construct()
    {
        $this->metadataToolkit = new ArtifactMetadataToolkit();
    }

    /**
     * @return array{manifest: array<string, mixed>, files: array<string, string>}
     */
    public function buildBundle(): array
    {
        $files = [
            'files/usr/share/w4/system-api/org.w4.System1.Manager.xml' => $this->buildIntrospectionXml(),
            'files/usr/share/w4/system-api/org.w4.System1.runtime-adapter.json' => $this->buildRuntimeAdapterSpecificationJson(),
            'files/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json' => $this->buildRuntimeBootstrapJson(),
            'files/usr/share/dbus-1/system-services/org.w4.System1.service' => $this->buildDbusServiceFile(),
            'files/etc/systemd/system/w4-system-api.service' => $this->buildSystemdUnit(),
            'files/etc/w4/system-api-binding.env' => $this->buildBindingEnv(),
            'files/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php' => $this->buildBridgeRuntimeScript(),
            'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php' => $this->buildRuntimeAdapterScript(),
            'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php' => $this->buildRuntimeHostScript(),
            'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php' => $this->buildRuntimeLauncherScript(),
            'files/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php' => $this->buildBridgeReadinessScript(),
            'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php' => $this->buildRuntimeDiagnosisScript(),
            'files/usr/local/bin/w4-system-api-dbus-bridge' => $this->buildBridgeWrapperScript(),
            'files/usr/local/bin/w4-system-api-dbus-runtime-adapter' => $this->buildRuntimeAdapterWrapperScript(),
            'files/usr/local/bin/w4-system-api-dbus-runtime-host' => $this->buildRuntimeHostWrapperScript(),
            'files/usr/local/bin/w4-system-api-dbus-runtime-launcher' => $this->buildRuntimeLauncherWrapperScript(),
            'files/usr/local/bin/w4-system-api-dbus-bridge-readiness' => $this->buildBridgeReadinessWrapperScript(),
            'files/usr/local/bin/w4-system-api-dbus-runtime-diagnose' => $this->buildRuntimeDiagnosisWrapperScript(),
            'apply-binding.sh' => $this->buildApplyScript(),
        ];

        $manifest = $this->metadataToolkit->createManifestEnvelope(
            'binding_schema_version',
            'system-api-dbus-binding',
            [
                'api_version' => self::API_VERSION,
                'bus_name' => self::BUS_NAME,
                'object_path' => self::OBJECT_PATH,
                'interface' => self::INTERFACE_NAME,
            ],
            [
                'binding_status' => self::BINDING_STATUS,
                'enabled_by_default' => false,
                'transport' => [
                    'current_local_runtime' => 'stdio-ndjson-launcher',
                    'future_public_binding' => 'dbus-system-bus',
                ],
                'public_methods' => [
                    [
                        'name' => 'Call',
                        'input' => 'request_json',
                        'output' => 'response_json',
                    ],
                    [
                        'name' => 'GetCapabilities',
                        'input' => 'request_id',
                        'output' => 'response_json',
                    ],
                ],
                'activation' => [
                    'service_file' => 'files/usr/share/dbus-1/system-services/org.w4.System1.service',
                    'systemd_unit' => 'files/etc/systemd/system/w4-system-api.service',
                    'runtime_env' => 'files/etc/w4/system-api-binding.env',
                    'adapter_status' => self::BINDING_STATUS,
                    'adapter_specification' => 'files/usr/share/w4/system-api/org.w4.System1.runtime-adapter.json',
                    'bootstrap_specification' => 'files/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json',
                    'runtime_entrypoint' => 'files/usr/local/bin/w4-system-api-dbus-runtime-launcher',
                ],
                'runtime_launcher' => [
                    'entrypoint' => 'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php',
                    'wrapper' => 'files/usr/local/bin/w4-system-api-dbus-runtime-launcher',
                    'runtime_status' => 'launcher-ready',
                    'transport' => 'stdio-ndjson-launcher',
                    'handoff_mode' => 'child-process-stdio-relay',
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
                        'GetCapabilities',
                        'Call',
                        'serve-stdio-launcher',
                        'serve-dbus',
                    ],
                ],
                'runtime_host' => [
                    'entrypoint' => 'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php',
                    'wrapper' => 'files/usr/local/bin/w4-system-api-dbus-runtime-host',
                    'runtime_status' => self::BINDING_STATUS,
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
                ],
                'runtime_adapter' => [
                    'entrypoint' => 'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php',
                    'wrapper' => 'files/usr/local/bin/w4-system-api-dbus-runtime-adapter',
                    'runtime_status' => self::BINDING_STATUS,
                    'commands' => [
                        'DescribeSpecification',
                        'DescribeActivationPreparation',
                        'AttemptActivation',
                        'ExecuteActivation',
                        'DescribeNativeRuntimeService',
                        'StartNativeRuntimeService',
                        'ObserveNativeRuntimeServiceState',
                        'MaterializeNativeBusOwnership',
                        'DescribeRuntime',
                        'DiagnoseRuntime',
                        'GetCapabilities',
                        'Call',
                        'serve-stdio-adapter',
                        'serve-dbus',
                    ],
                ],
                'runtime_bridge' => [
                    'entrypoint' => 'files/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php',
                    'wrapper' => 'files/usr/local/bin/w4-system-api-dbus-bridge',
                    'readiness_entrypoint' => 'files/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php',
                    'readiness_wrapper' => 'files/usr/local/bin/w4-system-api-dbus-bridge-readiness',
                    'diagnostics_entrypoint' => 'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php',
                    'diagnostics_wrapper' => 'files/usr/local/bin/w4-system-api-dbus-runtime-diagnose',
                    'runtime_status' => self::BINDING_STATUS,
                    'commands' => [
                        'Call',
                        'GetCapabilities',
                        'DiagnoseRuntime',
                        'DescribeRuntime',
                        'serve-stdio-bridge',
                        'serve-dbus',
                        'check-readiness',
                    ],
                ],
                'generated_files' => $this->metadataToolkit->normalizeGeneratedFiles(
                    array_merge(['system-api-dbus-binding-manifest.json'], array_keys($files))
                ),
            ]
        );

        return [
            'manifest' => $manifest,
            'files' => $files,
        ];
    }

    private function buildIntrospectionXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE node PUBLIC "-//freedesktop//DTD D-BUS Object Introspection 1.0//EN"
 "http://www.freedesktop.org/standards/dbus/1.0/introspect.dtd">
<node name="/org/w4/System1">
  <interface name="org.w4.System1.Manager">
    <annotation name="org.freedesktop.DBus.DocString"
     value="W4 System API public manager interface. The runtime adapter is still pending; this contract freezes the binding surface."/>
    <method name="Call">
      <arg name="request_json" type="s" direction="in"/>
      <arg name="response_json" type="s" direction="out"/>
    </method>
    <method name="GetCapabilities">
      <arg name="request_id" type="s" direction="in"/>
      <arg name="response_json" type="s" direction="out"/>
    </method>
    <property name="ApiVersion" type="s" access="read"/>
    <property name="BindingStatus" type="s" access="read"/>
  </interface>
</node>
XML;
    }

    private function buildDbusServiceFile(): string
    {
        return <<<SERVICE
[D-BUS Service]
Name=org.w4.System1
Exec=/usr/local/bin/w4-system-api-dbus-runtime-launcher serve-dbus
User=root
SystemdService=w4-system-api.service
# Pending runtime adapter: the packaged runtime launcher orchestrates the host handoff and fails fast until a real D-Bus runtime lands.
SERVICE;
    }

    private function buildSystemdUnit(): string
    {
        return <<<UNIT
[Unit]
Description=W4 System API D-Bus bridge
Documentation=man:w4-system-api(7)
After=dbus.service
Requires=dbus.service

[Service]
Type=dbus
BusName=org.w4.System1
EnvironmentFile=-/etc/w4/system-api-binding.env
ExecStart=/usr/local/bin/w4-system-api-dbus-runtime-launcher serve-dbus
Restart=on-failure
RestartSec=2s
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
UNIT;
    }

    private function buildRuntimeAdapterSpecificationJson(): string
    {
        $payload = [
            'api_version' => self::API_VERSION,
            'kind' => 'system-api-dbus-runtime-adapter-spec',
            'status' => self::BINDING_STATUS,
            'bus_name' => self::BUS_NAME,
            'object_path' => self::OBJECT_PATH,
            'interface' => self::INTERFACE_NAME,
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

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new \RuntimeException('No se pudo serializar la especificacion del runtime adapter D-Bus');
        }

        return $encoded . PHP_EOL;
    }

    private function buildRuntimeBootstrapJson(): string
    {
        $payload = [
            'api_version' => self::API_VERSION,
            'kind' => 'system-api-dbus-runtime-bootstrap',
            'status' => self::BINDING_STATUS,
            'bus_name' => self::BUS_NAME,
            'object_path' => self::OBJECT_PATH,
            'interface' => self::INTERFACE_NAME,
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
                'status' => self::BINDING_STATUS,
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

        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            throw new \RuntimeException('No se pudo serializar la especificacion de bootstrap del runtime D-Bus');
        }

        return $encoded . PHP_EOL;
    }

    private function buildBindingEnv(): string
    {
        return <<<ENV
W4_SYSTEM_API_VERSION="1.0"
W4_SYSTEM_API_BUS_NAME="org.w4.System1"
W4_SYSTEM_API_OBJECT_PATH="/org/w4/System1"
W4_SYSTEM_API_INTERFACE="org.w4.System1.Manager"
W4_SYSTEM_API_BINDING_STATUS="pending-runtime-adapter"
W4_SYSTEM_API_LOCAL_TRANSPORT="stdio-ndjson-launcher"
W4_SYSTEM_API_BRIDGE_ENTRY="/usr/local/bin/w4-system-api-dbus-bridge"
W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY="/usr/local/bin/w4-system-api-dbus-runtime-adapter"
W4_SYSTEM_API_RUNTIME_HOST_ENTRY="/usr/local/bin/w4-system-api-dbus-runtime-host"
W4_SYSTEM_API_RUNTIME_LAUNCHER_ENTRY="/usr/local/bin/w4-system-api-dbus-runtime-launcher"
W4_SYSTEM_API_RUNTIME_BOOTSTRAP_SPEC="/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json"
W4_SYSTEM_API_READINESS_ENTRY="/usr/local/bin/w4-system-api-dbus-bridge-readiness"
W4_SYSTEM_API_DIAGNOSTICS_ENTRY="/usr/local/bin/w4-system-api-dbus-runtime-diagnose"
ENV;
    }

    private function buildBridgeRuntimeScript(): string
    {
        return <<<'PHP'
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
        'next_action' => 'materialize-runtime-adapter',
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
PHP;
    }

    private function buildBridgeReadinessScript(): string
    {
        return <<<'PHP'
#!/usr/bin/env php
<?php

declare(strict_types=1);

$payload = [
    'api_version' => '1.0',
    'kind' => 'system-api-dbus-bridge-readiness',
    'ready' => false,
    'status' => 'pending-runtime-adapter',
    'required_bus_name' => 'org.w4.System1',
    'required_object_path' => '/org/w4/System1',
    'required_interface' => 'org.w4.System1.Manager',
    'missing_capabilities' => [
        'php-dbus-runtime-adapter',
        'bus-name-ownership',
        'dbus-method-dispatch',
    ],
];

$encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($encoded === false) {
    fwrite(STDERR, "ERROR: No se pudo serializar el payload de readiness.\n");
    exit(1);
}

echo $encoded . PHP_EOL;
exit(2);
PHP;
    }

    private function buildRuntimeDiagnosisScript(): string
    {
        return <<<'PHP'
#!/usr/bin/env php
<?php

declare(strict_types=1);

$hasDbusExtension = extension_loaded('dbus') || extension_loaded('php_dbus') || extension_loaded('php-dbus');
$hasBusctl = commandLooksAvailable('busctl');
$hasGdbus = commandLooksAvailable('gdbus');
$hasDbusDaemon = commandLooksAvailable('dbus-daemon');
$hasSystemBusSocket = file_exists('/run/dbus/system_bus_socket');

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

$payload = [
    'api_version' => '1.0',
    'kind' => 'system-api-dbus-runtime-diagnosis',
    'ready' => $ready,
    'status' => $ready ? 'ready-for-runtime-adapter' : 'pending-runtime-adapter',
    'bus_name' => 'org.w4.System1',
    'object_path' => '/org/w4/System1',
    'interface' => 'org.w4.System1.Manager',
    'environment' => [
        'os_family' => PHP_OS_FAMILY,
        'php_binary' => PHP_BINARY,
        'php_version' => PHP_VERSION,
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

$encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($encoded === false) {
    fwrite(STDERR, "ERROR: No se pudo serializar el payload de diagnostico.\n");
    exit(1);
}

echo $encoded . PHP_EOL;
exit($ready ? 0 : 2);

function commandLooksAvailable(string $command): bool
{
    if (PHP_OS_FAMILY === 'Windows') {
        $resolved = shell_exec(sprintf('where %s 2>NUL', escapeshellarg($command)));
    } else {
        $resolved = shell_exec(sprintf('command -v %s 2>/dev/null', escapeshellarg($command)));
    }

    return is_string($resolved) && trim($resolved) !== '';
}
PHP;
    }

    private function buildRuntimeAdapterScript(): string
    {
        return <<<'PHP'
#!/usr/bin/env php
<?php

declare(strict_types=1);

const W4_SYSTEM_API_STATUS = 'pending-runtime-adapter';
const W4_SYSTEM_API_BUS_NAME = 'org.w4.System1';
const W4_SYSTEM_API_OBJECT_PATH = '/org/w4/System1';
const W4_SYSTEM_API_INTERFACE = 'org.w4.System1.Manager';

$command = $argv[1] ?? 'DescribeSpecification';

switch ($command) {
    case 'DescribeSpecification':
        emitJson(createAdapterSpecification());
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

    case 'DescribeNativeRuntimeService':
        emitJson(createNativeRuntimeServiceDefinition(buildRuntimeDiagnosis()));
        exit(0);

    case 'StartNativeRuntimeService':
        $nativeRuntimeStartup = createNativeRuntimeServiceStartupReport(buildRuntimeDiagnosis());
        persistNativeRuntimeArtifacts($nativeRuntimeStartup);
        emitJson($nativeRuntimeStartup);
        exit($nativeRuntimeStartup['exit_code']);

    case 'ObserveNativeRuntimeServiceState':
        $nativeRuntimeObservation = createNativeRuntimeServiceObservationReport(buildRuntimeDiagnosis());
        emitJson($nativeRuntimeObservation);
        exit($nativeRuntimeObservation['exit_code']);

    case 'MaterializeNativeBusOwnership':
        $nativeBusOwnership = createNativeBusOwnershipReport(buildRuntimeDiagnosis());
        persistNativeRuntimeOwnershipArtifacts($nativeBusOwnership);
        emitJson($nativeBusOwnership);
        exit($nativeBusOwnership['exit_code']);

    case 'DescribeRuntime':
        emitJson(createAdapterRuntime());
        exit(0);

    case 'DiagnoseRuntime':
        emitJson(buildRuntimeDiagnosis());
        exit(buildRuntimeDiagnosis()['ready'] === true ? 0 : 2);

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

    case 'serve-stdio-adapter':
        serveStdioAdapter();
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
        fwrite(STDERR, "ERROR: No se pudo serializar el payload del runtime adapter.\n");
        exit(1);
    }

    echo $encoded . PHP_EOL;
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
            'DescribeActivationPreparation',
            'AttemptActivation',
            'ExecuteActivation',
            'DescribeNativeRuntimeService',
            'StartNativeRuntimeService',
            'ObserveNativeRuntimeServiceState',
            'MaterializeNativeBusOwnership',
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
function createRuntimeBackend(array $runtimeDiagnosis): array
{
    $environment = captureEnvironment();
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
                'class_name' => $nativeDbusClass,
                'class_available' => $nativeDbusHasServiceClass,
                'has_bus_system_constant' => $nativeDbusHasBusSystemConstant,
                'has_request_name_method' => $nativeDbusHasRequestNameMethod,
                'has_register_object_method' => $nativeDbusHasRegisterObjectMethod,
                'has_wait_loop_method' => $nativeDbusHasWaitLoopMethod,
                'compatible_for_service' => $nativeDbusCompatibleForService,
                'missing_api' => $nativeDbusMissingApi,
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
 * @param array<string, mixed> $runtimeDiagnosis
 * @return array<string, mixed>
 */
function createNativeRuntimeServiceDefinition(array $runtimeDiagnosis): array
{
    $environment = captureEnvironment();
    $runtimeBackend = createRuntimeBackend($runtimeDiagnosis);
    $selectedBackend = is_string($runtimeBackend['selected_backend'] ?? null)
        ? $runtimeBackend['selected_backend']
        : 'contract-pending';
    $eligible = $selectedBackend === 'native-dbus-extension' && (($runtimeBackend['materialized'] ?? false) === true);
    $artifactsBasePath = resolveNativeRuntimeArtifactsBasePath($environment);

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-native-runtime-service',
        'status' => $eligible ? 'native-runtime-service-ready-to-start' : 'native-runtime-service-blocked',
        'eligible' => $eligible,
        'selected_backend' => $selectedBackend,
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'startup_command' => 'StartNativeRuntimeService',
        'startup_mode' => 'bootstrap-contract',
        'ownership_goal' => W4_SYSTEM_API_BUS_NAME,
        'service_lifecycle' => 'request-name-register-object-wait-loop',
        'artifacts' => [
            'ready_marker_path' => resolvePathValue(
                $environment,
                'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER',
                $artifactsBasePath . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.ready'
            ),
            'status_file_path' => resolvePathValue(
                $environment,
                'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE',
                $artifactsBasePath . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime-status.json'
            ),
            'pid_file_path' => resolvePathValue(
                $environment,
                'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE',
                $artifactsBasePath . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.pid'
            ),
        ],
        'runtime_backend' => $runtimeBackend,
        'blocking_reason' => $eligible
            ? 'native-runtime-service-not-started'
            : (is_string($runtimeBackend['blocking_reason'] ?? null)
                ? $runtimeBackend['blocking_reason']
                : 'native-runtime-service-unavailable'),
        'next_action' => $eligible ? 'start-native-runtime-service' : 'complete-native-runtime-backend',
    ];
}

/**
 * @param array<string, mixed> $runtimeDiagnosis
 * @return array<string, mixed>
 */
function createNativeRuntimeServiceStartupReport(array $runtimeDiagnosis): array
{
    $serviceDefinition = createNativeRuntimeServiceDefinition($runtimeDiagnosis);
    $eligible = ($serviceDefinition['eligible'] ?? false) === true;

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-native-runtime-startup',
        'status' => $eligible ? 'native-runtime-service-started' : 'native-runtime-service-blocked',
        'startup_acknowledged' => $eligible,
        'exit_code' => $eligible ? 0 : 3,
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'service_definition' => $serviceDefinition,
        'runtime_backend' => $serviceDefinition['runtime_backend'] ?? [],
        'ownership_state' => $eligible ? 'pending-bus-name-ownership' : 'blocked-before-startup',
        'blocking_reason' => $eligible
            ? null
            : (is_string($serviceDefinition['blocking_reason'] ?? null)
                ? $serviceDefinition['blocking_reason']
                : 'native-runtime-service-unavailable'),
        'next_action' => $eligible ? 'observe-native-runtime-service-state' : 'complete-native-runtime-backend',
    ];
}

/**
 * @param array<string, mixed> $runtimeDiagnosis
 * @return array<string, mixed>
 */
function createNativeRuntimeServiceObservationReport(array $runtimeDiagnosis): array
{
    $serviceDefinition = createNativeRuntimeServiceDefinition($runtimeDiagnosis);
    $eligible = ($serviceDefinition['eligible'] ?? false) === true;
    $artifacts = is_array($serviceDefinition['artifacts'] ?? null) ? $serviceDefinition['artifacts'] : [];
    $readyMarker = observeTextArtifact(optionalArtifactPath($artifacts, 'ready_marker_path'));
    $statusFile = observeJsonArtifact(optionalArtifactPath($artifacts, 'status_file_path'));
    $pidFile = observePidArtifact(optionalArtifactPath($artifacts, 'pid_file_path'));
    $startupReport = is_array($statusFile['payload'] ?? null) ? $statusFile['payload'] : null;
    $startupAcknowledged = ($startupReport['startup_acknowledged'] ?? false) === true;
    $artifactsObserved = ($readyMarker['exists'] ?? false) === true
        && ($statusFile['exists'] ?? false) === true
        && ($pidFile['exists'] ?? false) === true;
    $observed = $eligible && $startupAcknowledged && $artifactsObserved;
    $ownershipState = is_string($startupReport['ownership_state'] ?? null)
        ? $startupReport['ownership_state']
        : ($observed ? 'pending-bus-name-ownership' : 'native-runtime-service-not-started');
    $status = !$eligible
        ? 'native-runtime-service-blocked'
        : ($observed
            ? ($ownershipState === 'bus-name-owned'
                ? 'native-runtime-service-observed-active'
                : 'native-runtime-service-observed-pending-ownership')
            : (($readyMarker['exists'] ?? false) === true || ($statusFile['exists'] ?? false) === true || ($pidFile['exists'] ?? false) === true
                ? 'native-runtime-service-observation-incomplete'
                : 'native-runtime-service-not-observed'));
    $blockingReason = !$eligible
        ? (is_string($serviceDefinition['blocking_reason'] ?? null)
            ? $serviceDefinition['blocking_reason']
            : 'native-runtime-service-unavailable')
        : ($status === 'native-runtime-service-not-observed'
            ? 'native-runtime-service-not-started'
            : ($status === 'native-runtime-service-observation-incomplete'
                ? 'native-runtime-service-artifacts-incomplete'
                : ($ownershipState === 'bus-name-owned' ? null : 'native-runtime-bus-ownership-pending')));
    $nextAction = !$eligible
        ? 'complete-native-runtime-backend'
        : ($status === 'native-runtime-service-not-observed'
            ? 'start-native-runtime-service'
            : ($status === 'native-runtime-service-observation-incomplete'
                ? 'inspect-native-runtime-service-artifacts'
                : ($ownershipState === 'bus-name-owned'
                    ? 'runtime-service-active'
                    : 'materialize-native-bus-ownership')));

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-native-runtime-observation',
        'status' => $status,
        'observed' => $observed,
        'exit_code' => $ownershipState === 'bus-name-owned' ? 0 : 3,
        'startup_acknowledged' => $startupAcknowledged,
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'service_definition' => $serviceDefinition,
        'runtime_backend' => $serviceDefinition['runtime_backend'] ?? [],
        'ownership_state' => $ownershipState,
        'artifacts' => [
            'ready_marker' => $readyMarker,
            'status_file' => $statusFile,
            'pid_file' => $pidFile,
        ],
        'startup_report' => $startupReport,
        'pid' => $pidFile['pid'] ?? null,
        'blocking_reason' => $blockingReason,
        'next_action' => $nextAction,
    ];
}

/**
 * @param array<string, mixed> $runtimeDiagnosis
 * @return array<string, mixed>
 */
function createNativeBusOwnershipReport(array $runtimeDiagnosis): array
{
    $environment = captureEnvironment();
    $serviceDefinition = createNativeRuntimeServiceDefinition($runtimeDiagnosis);
    $observation = createNativeRuntimeServiceObservationReport($runtimeDiagnosis);
    $eligible = ($serviceDefinition['eligible'] ?? false) === true;
    $observed = ($observation['observed'] ?? false) === true;
    $attempted = $eligible && $observed;
    $phaseOverridesConfigured = hasExplicitNativeRuntimePhaseOverrides($environment);
    $driverExecution = $attempted && !$phaseOverridesConfigured
        ? executeNativeRuntimeOwnershipDriver($serviceDefinition, $environment)
        : null;
    $requestNameState = $phaseOverridesConfigured
        ? resolveNativeRuntimePhaseState(
            $environment,
            'W4_SYSTEM_API_NATIVE_RUNTIME_REQUEST_NAME_STATE',
            ['not-attempted', 'pending', 'owned', 'failed'],
            $attempted ? 'pending' : 'not-attempted'
        )
        : resolveDriverLifecycleState(
            $driverExecution,
            'request_name_state',
            ['not-attempted', 'pending', 'owned', 'failed'],
            $attempted ? 'pending' : 'not-attempted'
        );
    $registerObjectState = $phaseOverridesConfigured
        ? resolveNativeRuntimePhaseState(
            $environment,
            'W4_SYSTEM_API_NATIVE_RUNTIME_REGISTER_OBJECT_STATE',
            ['not-attempted', 'pending', 'registered', 'failed', 'skipped'],
            !$attempted ? 'not-attempted' : ($requestNameState === 'owned' ? 'pending' : 'skipped')
        )
        : resolveDriverLifecycleState(
            $driverExecution,
            'register_object_state',
            ['not-attempted', 'pending', 'registered', 'failed', 'skipped'],
            !$attempted ? 'not-attempted' : ($requestNameState === 'owned' ? 'pending' : 'skipped')
        );
    $waitLoopState = $phaseOverridesConfigured
        ? resolveNativeRuntimePhaseState(
            $environment,
            'W4_SYSTEM_API_NATIVE_RUNTIME_WAIT_LOOP_STATE',
            ['not-attempted', 'pending', 'running', 'failed', 'skipped'],
            !$attempted ? 'not-attempted' : ($requestNameState === 'owned' && $registerObjectState === 'registered' ? 'pending' : 'skipped')
        )
        : resolveDriverLifecycleState(
            $driverExecution,
            'wait_loop_state',
            ['not-attempted', 'pending', 'running', 'failed', 'skipped'],
            !$attempted ? 'not-attempted' : ($requestNameState === 'owned' && $registerObjectState === 'registered' ? 'pending' : 'skipped')
        );
    $hasFailure = in_array('failed', [$requestNameState, $registerObjectState, $waitLoopState], true);
    $busOwned = $requestNameState === 'owned'
        && $registerObjectState === 'registered'
        && $waitLoopState === 'running';
    $executionMode = !$attempted
        ? 'blocked-before-ownership'
        : ($phaseOverridesConfigured
            ? 'phase-overrides'
            : ($driverExecution !== null ? 'native-driver' : 'contract-default'));
    $ownershipState = !$attempted
        ? 'blocked-before-ownership'
        : ($hasFailure
            ? 'native-runtime-ownership-failed'
            : ($busOwned ? 'bus-name-owned' : 'pending-bus-name-ownership'));
    $status = !$eligible
        ? 'native-runtime-service-blocked'
        : (!$observed
            ? 'native-runtime-bus-ownership-blocked'
            : ($hasFailure
                ? 'native-runtime-bus-ownership-failed'
                : ($busOwned
                    ? 'native-runtime-bus-owned'
                    : 'native-runtime-bus-ownership-pending')));
    $driverBlockingReason = is_string($driverExecution['blocking_reason'] ?? null)
        && $driverExecution['blocking_reason'] !== ''
            ? $driverExecution['blocking_reason']
            : null;
    $blockingReason = !$eligible
        ? (is_string($serviceDefinition['blocking_reason'] ?? null)
            ? $serviceDefinition['blocking_reason']
            : 'native-runtime-service-unavailable')
        : (!$observed
            ? 'native-runtime-service-not-observed'
            : ($driverBlockingReason
                ?? ($requestNameState === 'failed'
                    ? 'native-runtime-request-name-failed'
                    : ($registerObjectState === 'failed'
                        ? 'native-runtime-register-object-failed'
                        : ($waitLoopState === 'failed'
                            ? 'native-runtime-wait-loop-failed'
                            : ($busOwned ? null : 'native-runtime-bus-ownership-pending'))))));
    $nextAction = !$eligible
        ? 'complete-native-runtime-backend'
        : (!$observed
            ? 'observe-native-runtime-service-state'
            : ($busOwned
                ? 'runtime-service-active'
                : ($hasFailure
                    ? 'inspect-native-runtime-ownership-report'
                    : 'observe-native-runtime-service-state')));

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-native-runtime-ownership',
        'status' => $status,
        'attempted' => $attempted,
        'exit_code' => !$attempted ? 3 : ($busOwned ? 0 : ($hasFailure ? 1 : 3)),
        'startup_acknowledged' => ($observation['startup_acknowledged'] ?? false) === true,
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'service_definition' => $serviceDefinition,
        'runtime_backend' => $serviceDefinition['runtime_backend'] ?? [],
        'service_observation' => $observation,
        'execution_mode' => $executionMode,
        'ownership_driver' => is_array($driverExecution['driver'] ?? null) ? $driverExecution['driver'] : null,
        'ownership_state' => $ownershipState,
        'service_lifecycle' => [
            'request_name' => [
                'required' => true,
                'state' => $requestNameState,
            ],
            'register_object' => [
                'required' => true,
                'state' => $registerObjectState,
            ],
            'wait_loop' => [
                'required' => true,
                'state' => $waitLoopState,
            ],
        ],
        'blocking_reason' => $blockingReason,
        'next_action' => $nextAction,
    ];
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
 * @param array<string, string> $environment
 */
function resolvePathValue(array $environment, string $key, string $default): string
{
    $value = $environment[$key] ?? null;

    return is_string($value) && trim($value) !== '' ? trim($value) : $default;
}

/**
 * @param array<string, string> $environment
 */
function hasExplicitNativeRuntimePhaseOverrides(array $environment): bool
{
    foreach ([
        'W4_SYSTEM_API_NATIVE_RUNTIME_REQUEST_NAME_STATE',
        'W4_SYSTEM_API_NATIVE_RUNTIME_REGISTER_OBJECT_STATE',
        'W4_SYSTEM_API_NATIVE_RUNTIME_WAIT_LOOP_STATE',
    ] as $key) {
        $value = $environment[$key] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return true;
        }
    }

    return false;
}

/**
 * @param array<string, string> $environment
 * @param list<string> $allowedStates
 */
function resolveNativeRuntimePhaseState(array $environment, string $key, array $allowedStates, string $default): string
{
    $value = $environment[$key] ?? null;
    if (!is_string($value) || trim($value) === '') {
        return $default;
    }

    $normalized = strtolower(trim($value));

    return in_array($normalized, $allowedStates, true) ? $normalized : $default;
}

/**
 * @param array<string, mixed>|null $driverExecution
 * @param list<string> $allowedStates
 */
function resolveDriverLifecycleState(?array $driverExecution, string $field, array $allowedStates, string $default): string
{
    $value = $driverExecution[$field] ?? null;
    if (!is_string($value) || trim($value) === '') {
        return $default;
    }

    $normalized = strtolower(trim($value));

    return in_array($normalized, $allowedStates, true) ? $normalized : $default;
}

/**
 * @param array<string, mixed> $serviceDefinition
 * @param array<string, string> $environment
 * @return array<string, mixed>|null
 */
function executeNativeRuntimeOwnershipDriver(array $serviceDefinition, array $environment): ?array
{
    $driverClassName = resolveNativeRuntimeDriverClassName($environment);
    $bootstrapPath = resolveNativeRuntimeDriverBootstrapPath($environment);
    if ($driverClassName === null && $bootstrapPath === null) {
        return null;
    }

    if ($bootstrapPath !== null && is_file($bootstrapPath)) {
        require_once $bootstrapPath;
    }

    if ($driverClassName === null) {
        return [
            'request_name_state' => 'failed',
            'register_object_state' => 'not-attempted',
            'wait_loop_state' => 'not-attempted',
            'blocking_reason' => 'native-runtime-driver-class-not-configured',
            'driver' => [
                'mode' => 'native-driver',
                'bootstrap_path' => $bootstrapPath,
                'class_name' => null,
            ],
        ];
    }

    if (!class_exists($driverClassName)) {
        return [
            'request_name_state' => 'failed',
            'register_object_state' => 'not-attempted',
            'wait_loop_state' => 'not-attempted',
            'blocking_reason' => 'native-runtime-driver-class-unavailable',
            'driver' => [
                'mode' => 'native-driver',
                'bootstrap_path' => $bootstrapPath,
                'class_name' => $driverClassName,
                'available' => false,
            ],
        ];
    }

    try {
        $method = new ReflectionMethod($driverClassName, 'materializeOwnership');
        $result = $method->isStatic()
            ? $driverClassName::materializeOwnership($serviceDefinition)
            : (new $driverClassName())->materializeOwnership($serviceDefinition);
    } catch (ReflectionException) {
        return [
            'request_name_state' => 'failed',
            'register_object_state' => 'not-attempted',
            'wait_loop_state' => 'not-attempted',
            'blocking_reason' => 'native-runtime-driver-method-unavailable',
            'driver' => [
                'mode' => 'native-driver',
                'bootstrap_path' => $bootstrapPath,
                'class_name' => $driverClassName,
                'available' => true,
            ],
        ];
    } catch (Throwable $exception) {
        return [
            'request_name_state' => 'failed',
            'register_object_state' => 'not-attempted',
            'wait_loop_state' => 'not-attempted',
            'blocking_reason' => 'native-runtime-driver-execution-failed',
            'driver' => [
                'mode' => 'native-driver',
                'bootstrap_path' => $bootstrapPath,
                'class_name' => $driverClassName,
                'available' => true,
                'error_message' => $exception->getMessage(),
            ],
        ];
    }

    if (!is_array($result)) {
        return [
            'request_name_state' => 'failed',
            'register_object_state' => 'not-attempted',
            'wait_loop_state' => 'not-attempted',
            'blocking_reason' => 'native-runtime-driver-invalid-result',
            'driver' => [
                'mode' => 'native-driver',
                'bootstrap_path' => $bootstrapPath,
                'class_name' => $driverClassName,
                'available' => true,
            ],
        ];
    }

    $driver = is_array($result['driver'] ?? null) ? $result['driver'] : [];
    $driver['mode'] = 'native-driver';
    $driver['bootstrap_path'] = $bootstrapPath;
    $driver['class_name'] = $driverClassName;
    $driver['available'] = true;
    $result['driver'] = $driver;

    return $result;
}

/**
 * @param array<string, string> $environment
 */
function resolveNativeRuntimeDriverClassName(array $environment): ?string
{
    $value = $environment['W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_CLASS'] ?? null;

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}

/**
 * @param array<string, string> $environment
 */
function resolveNativeRuntimeDriverBootstrapPath(array $environment): ?string
{
    $value = $environment['W4_SYSTEM_API_NATIVE_RUNTIME_DRIVER_BOOTSTRAP'] ?? null;

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}

/**
 * @param array<string, string> $environment
 */
function resolveNativeRuntimeArtifactsBasePath(array $environment): string
{
    foreach (['W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR', 'TEMP', 'TMP'] as $key) {
        $value = $environment[$key] ?? null;
        if (is_string($value) && trim($value) !== '') {
            return rtrim(trim($value), "\\/");
        }
    }

    $currentDirectory = getcwd();
    if (is_string($currentDirectory) && trim($currentDirectory) !== '') {
        return rtrim(trim($currentDirectory), "\\/") . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'native-runtime';
    }

    $tempDirectory = sys_get_temp_dir();
    if (is_string($tempDirectory) && trim($tempDirectory) !== '') {
        return rtrim(trim($tempDirectory), "\\/");
    }

    return 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'native-runtime';
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
            'bridge_kind' => 'packaged-runtime-adapter',
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

    $report = createPendingActivationReport($runtimeDiagnosis, 'packaged-runtime-adapter');
    $report['runtime_backend'] = $runtimeBackend;
    $report['exit_code'] = 3;

    return $report;
}

/**
 * @param array<string, mixed> $report
 */
function persistNativeRuntimeArtifacts(array $report): void
{
    if (($report['startup_acknowledged'] ?? false) !== true) {
        return;
    }

    $serviceDefinition = is_array($report['service_definition'] ?? null) ? $report['service_definition'] : [];
    $artifacts = is_array($serviceDefinition['artifacts'] ?? null) ? $serviceDefinition['artifacts'] : [];
    $readyMarkerPath = optionalArtifactPath($artifacts, 'ready_marker_path');
    $statusFilePath = optionalArtifactPath($artifacts, 'status_file_path');
    $pidFilePath = optionalArtifactPath($artifacts, 'pid_file_path');

    if ($readyMarkerPath !== null) {
        writeArtifactFile($readyMarkerPath, "native-runtime-service-started\n");
    }

    if ($statusFilePath !== null) {
        $statusPayload = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($statusPayload !== false) {
            writeArtifactFile($statusFilePath, $statusPayload . PHP_EOL);
        }
    }

    if ($pidFilePath !== null) {
        writeArtifactFile($pidFilePath, "0\n");
    }
}

/**
 * @param array<string, mixed> $report
 */
function persistNativeRuntimeOwnershipArtifacts(array $report): void
{
    if (($report['attempted'] ?? false) !== true) {
        return;
    }

    $serviceDefinition = is_array($report['service_definition'] ?? null) ? $report['service_definition'] : [];
    $artifacts = is_array($serviceDefinition['artifacts'] ?? null) ? $serviceDefinition['artifacts'] : [];
    $readyMarkerPath = optionalArtifactPath($artifacts, 'ready_marker_path');
    $statusFilePath = optionalArtifactPath($artifacts, 'status_file_path');
    $pidFilePath = optionalArtifactPath($artifacts, 'pid_file_path');

    if ($readyMarkerPath !== null) {
        $readyMarkerContents = ($report['ownership_state'] ?? null) === 'bus-name-owned'
            ? "native-runtime-bus-owned\n"
            : "native-runtime-service-started\n";
        writeArtifactFile($readyMarkerPath, $readyMarkerContents);
    }

    if ($statusFilePath !== null) {
        $statusPayload = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($statusPayload !== false) {
            writeArtifactFile($statusFilePath, $statusPayload . PHP_EOL);
        }
    }

    if ($pidFilePath !== null && !is_file($pidFilePath)) {
        writeArtifactFile($pidFilePath, "0\n");
    }
}

/**
 * @param array<string, mixed> $artifacts
 */
function optionalArtifactPath(array $artifacts, string $field): ?string
{
    $value = $artifacts[$field] ?? null;

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}

/**
 * @return array<string, mixed>
 */
function observeTextArtifact(?string $path): array
{
    $contents = readArtifactFile($path);

    return [
        'path' => $path,
        'exists' => $path !== null && is_file($path),
        'contents' => $contents === null ? null : trim($contents),
    ];
}

/**
 * @return array<string, mixed>
 */
function observeJsonArtifact(?string $path): array
{
    $contents = readArtifactFile($path);
    $payload = null;
    $parseError = null;

    if ($contents !== null && trim($contents) !== '') {
        try {
            $decoded = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        } catch (JsonException $exception) {
            $parseError = $exception->getMessage();
        }
    }

    return [
        'path' => $path,
        'exists' => $path !== null && is_file($path),
        'payload' => $payload,
        'parse_error' => $parseError,
    ];
}

/**
 * @return array<string, mixed>
 */
function observePidArtifact(?string $path): array
{
    $contents = readArtifactFile($path);
    $trimmed = $contents === null ? null : trim($contents);
    $pid = is_string($trimmed) && preg_match('/^-?\d+$/', $trimmed) === 1
        ? (int) $trimmed
        : null;

    return [
        'path' => $path,
        'exists' => $path !== null && is_file($path),
        'pid' => $pid,
        'raw_value' => $trimmed,
    ];
}

function readArtifactFile(?string $path): ?string
{
    if ($path === null || !is_file($path)) {
        return null;
    }

    $contents = @file_get_contents($path);

    return $contents === false ? null : $contents;
}

function writeArtifactFile(string $path, string $contents): void
{
    $directory = dirname($path);
    if ($directory !== '' && !is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        fwrite(STDERR, sprintf("ERROR: No se pudo crear el directorio de artefacto %s\n", $directory));
        exit(1);
    }

    if (file_put_contents($path, $contents) === false) {
        fwrite(STDERR, sprintf("ERROR: No se pudo escribir el artefacto %s\n", $path));
        exit(1);
    }
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

function serveStdioAdapter(): void
{
    while (($line = fgets(STDIN)) !== false) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $response = handleAdapterEnvelope($line);
        $encoded = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            fwrite(STDERR, "ERROR: No se pudo serializar la respuesta NDJSON del runtime adapter.\n");
            exit(1);
        }

        echo $encoded . PHP_EOL;
        flush();
    }
}

/**
 * @return array<string, mixed>
 */
function handleAdapterEnvelope(string $line): array
{
    try {
        $decoded = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        return createProtocolError(sprintf('Envelope JSON invalido: %s', $exception->getMessage()));
    }

    if (!is_array($decoded)) {
        return createProtocolError('El envelope del runtime adapter debe decodificar a un objeto');
    }

    $method = $decoded['method'] ?? null;
    if (!is_string($method) || trim($method) === '') {
        return createProtocolError('method debe ser un string no vacio');
    }

    return match (trim($method)) {
        'DescribeSpecification' => createAdapterSpecification(),
        'DescribeActivationPreparation' => createActivationPreparation(),
        'AttemptActivation' => createActivationAttempt(),
        'ExecuteActivation' => performActivationExecution(),
        'DescribeRuntime' => createAdapterRuntime(),
        'DiagnoseRuntime' => buildRuntimeDiagnosis(),
        'GetCapabilities' => createPendingResponse(
            requestId: is_string($decoded['request_id'] ?? null) ? $decoded['request_id'] : 'pending-get-capabilities',
            method: 'system.capabilities',
            resourceKind: 'system-api-capabilities',
            resourceId: W4_SYSTEM_API_BUS_NAME
        ),
        'Call' => createPendingCallResponse(is_array($decoded['request'] ?? null) ? $decoded['request'] : []),
        'ServeDbus' => createPendingActivationReport(buildRuntimeDiagnosis(), 'packaged-runtime-adapter'),
        default => createProtocolError(sprintf('Metodo de runtime adapter no soportado: %s', trim($method))),
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
            'message' => sprintf('El runtime D-Bus para %s sigue pendiente; el runtime adapter empaquetado solo congela la superficie publica.', $method),
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
        'request_id' => 'runtime-adapter-invalid-request',
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
        'activation_preparation' => createActivationPreparation(),
        'activation_attempt' => createActivationAttempt(),
        'activation_execution' => createActivationExecution(),
        'runtime_diagnosis' => $runtimeDiagnosis,
        'missing_capabilities' => is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? $runtimeDiagnosis['missing_capabilities']
            : [],
        'next_action' => 'materialize-runtime-adapter',
    ];
}

/**
 * @return array<string, mixed>
 */
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

/**
 * @return array<string, mixed>
 */
function createActivationPreparation(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();
    $missingCapabilities = is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
        ? $runtimeDiagnosis['missing_capabilities']
        : [];
    $ready = ($runtimeDiagnosis['ready'] ?? false) === true;
    $runtimeBackend = createRuntimeBackend($runtimeDiagnosis);
    $resolvedContract = [
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'runtime_launcher_entry' => getenv('W4_SYSTEM_API_RUNTIME_LAUNCHER_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-launcher',
        'runtime_host_entry' => getenv('W4_SYSTEM_API_RUNTIME_HOST_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-host',
        'runtime_adapter_entry' => getenv('W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-adapter',
        'runtime_bootstrap_spec' => getenv('W4_SYSTEM_API_RUNTIME_BOOTSTRAP_SPEC') ?: '/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json',
    ];

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
        'next_action' => resolvePreparationNextAction($ready, $runtimeBackend),
    ];
}

/**
 * @return array<string, mixed>
 */
function createActivationAttempt(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();
    $preparation = createActivationPreparation();
    $ready = ($preparation['ready'] ?? false) === true;
    $runtimeBackend = is_array($preparation['runtime_backend'] ?? null) ? $preparation['runtime_backend'] : [];
    $selectedBackend = is_string($runtimeBackend['selected_backend'] ?? null) ? $runtimeBackend['selected_backend'] : 'contract-pending';
    $nativeBackend = $selectedBackend === 'native-dbus-extension';
    $nativeReady = $nativeBackend && (($runtimeBackend['materialized'] ?? false) === true);

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-activation-attempt',
        'status' => !$ready
            ? 'blocked-missing-capabilities'
            : ($nativeReady ? 'ready-to-launch-native-runtime' : ($nativeBackend ? 'blocked-native-runtime-backend' : 'ready-to-attempt-runtime-adapter')),
        'attempted' => false,
        'bridge_kind' => 'packaged-runtime-adapter',
        'requested_command' => 'AttemptActivation',
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'blocking_reason' => !$ready
            ? 'missing-runtime-capabilities'
            : ($nativeReady
                ? 'native-runtime-service-not-launched'
                : ($nativeBackend
                    ? (is_string($runtimeBackend['blocking_reason'] ?? null) ? $runtimeBackend['blocking_reason'] : 'dbus-extension-api-incomplete')
                    : 'runtime-adapter-not-materialized')),
        'activation_preparation' => $preparation,
        'runtime_diagnosis' => $runtimeDiagnosis,
        'runtime_backend' => $runtimeBackend,
        'missing_capabilities' => is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? $runtimeDiagnosis['missing_capabilities']
            : [],
        'next_action' => !$ready
            ? 'materialize-runtime-adapter'
            : ($nativeReady
                ? 'execute-native-runtime-service'
                : ($nativeBackend ? 'complete-native-runtime-backend' : 'execute-runtime-adapter')),
    ];
}

/**
 * @return array<string, mixed>
 */
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

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-activation-result',
        'status' => $readyForNative
            ? 'execution-pending-native-runtime-service'
            : ($readyForAdapter ? 'execution-pending-runtime-adapter' : 'execution-blocked'),
        'executed' => false,
        'exit_code' => 3,
        'bridge_kind' => 'packaged-runtime-adapter',
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
        'next_action' => $attempt['next_action'] ?? 'materialize-runtime-adapter',
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
 * @param array<string, mixed>|null $targetCommand
 * @return array<string, mixed>
 */
function executeTargetCommand(?array $targetCommand): array
{
    if (!is_array($targetCommand)) {
        fwrite(STDERR, "ERROR: target_command debe ser un objeto para ejecutar la activacion del runtime adapter.\n");
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
function resolvePreparationNextAction(bool $ready, array $runtimeBackend): string
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
        : 'complete-native-runtime-backend';
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
PHP;
    }

    private function buildRuntimeHostScript(): string
    {
        return <<<'PHP'
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
function createRuntimeBackend(array $runtimeDiagnosis): array
{
    $environment = captureEnvironment();
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
                'class_name' => $nativeDbusClass,
                'class_available' => $nativeDbusHasServiceClass,
                'has_bus_system_constant' => $nativeDbusHasBusSystemConstant,
                'has_request_name_method' => $nativeDbusHasRequestNameMethod,
                'has_register_object_method' => $nativeDbusHasRegisterObjectMethod,
                'has_wait_loop_method' => $nativeDbusHasWaitLoopMethod,
                'compatible_for_service' => $nativeDbusCompatibleForService,
                'missing_api' => $nativeDbusMissingApi,
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
        'next_action' => 'materialize-runtime-adapter',
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
PHP;
    }

    private function buildRuntimeLauncherScript(): string
    {
        return <<<'PHP'
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
    $resolvedContract = [
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
        'runtime_launcher_entry' => getenv('W4_SYSTEM_API_RUNTIME_LAUNCHER_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-launcher',
        'runtime_host_entry' => getenv('W4_SYSTEM_API_RUNTIME_HOST_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-host',
        'runtime_adapter_entry' => getenv('W4_SYSTEM_API_RUNTIME_ADAPTER_ENTRY') ?: '/usr/local/bin/w4-system-api-dbus-runtime-adapter',
        'runtime_bootstrap_spec' => getenv('W4_SYSTEM_API_RUNTIME_BOOTSTRAP_SPEC') ?: '/usr/share/w4/system-api/org.w4.System1.runtime-bootstrap.json',
    ];

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
        'next_action' => resolvePreparationNextAction($ready, $runtimeBackend),
    ];
}

function createActivationAttempt(): array
{
    $runtimeDiagnosis = buildRuntimeDiagnosis();
    $preparation = createActivationPreparation();
    $ready = ($preparation['ready'] ?? false) === true;
    $runtimeBackend = is_array($preparation['runtime_backend'] ?? null) ? $preparation['runtime_backend'] : [];
    $selectedBackend = is_string($runtimeBackend['selected_backend'] ?? null) ? $runtimeBackend['selected_backend'] : 'contract-pending';
    $nativeBackend = $selectedBackend === 'native-dbus-extension';
    $nativeReady = $nativeBackend && (($runtimeBackend['materialized'] ?? false) === true);

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
                    ? (is_string($runtimeBackend['blocking_reason'] ?? null) ? $runtimeBackend['blocking_reason'] : 'dbus-extension-api-incomplete')
                    : 'runtime-adapter-not-materialized')),
        'activation_preparation' => $preparation,
        'runtime_diagnosis' => $runtimeDiagnosis,
        'runtime_backend' => $runtimeBackend,
        'missing_capabilities' => is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? $runtimeDiagnosis['missing_capabilities']
            : [],
        'next_action' => !$ready
            ? 'materialize-runtime-adapter'
            : ($nativeReady
                ? 'execute-native-runtime-service'
                : ($nativeBackend ? 'complete-native-runtime-backend' : 'execute-runtime-adapter')),
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
        'next_action' => $attempt['next_action'] ?? 'materialize-runtime-adapter',
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
function createRuntimeBackend(array $runtimeDiagnosis): array
{
    $environment = captureEnvironment();
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
                'class_name' => $nativeDbusClass,
                'class_available' => $nativeDbusHasServiceClass,
                'has_bus_system_constant' => $nativeDbusHasBusSystemConstant,
                'has_request_name_method' => $nativeDbusHasRequestNameMethod,
                'has_register_object_method' => $nativeDbusHasRegisterObjectMethod,
                'has_wait_loop_method' => $nativeDbusHasWaitLoopMethod,
                'compatible_for_service' => $nativeDbusCompatibleForService,
                'missing_api' => $nativeDbusMissingApi,
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
function resolvePreparationNextAction(bool $ready, array $runtimeBackend): string
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
        : 'complete-native-runtime-backend';
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
        'activation_preparation' => createActivationPreparation(),
        'activation_attempt' => createActivationAttempt(),
        'activation_execution' => createActivationExecution(),
        'launch_plan' => createLaunchPlan(),
        'runtime_diagnosis' => $runtimeDiagnosis,
        'missing_capabilities' => is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? $runtimeDiagnosis['missing_capabilities']
            : [],
        'next_action' => 'materialize-runtime-adapter',
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
PHP;
    }

    private function buildBridgeWrapperScript(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

exec /usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php "$@"
SCRIPT;
    }

    private function buildRuntimeAdapterWrapperScript(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

exec /usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php "$@"
SCRIPT;
    }

    private function buildRuntimeHostWrapperScript(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

exec /usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php "$@"
SCRIPT;
    }

    private function buildRuntimeLauncherWrapperScript(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

exec /usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php "$@"
SCRIPT;
    }

    private function buildBridgeReadinessWrapperScript(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

exec /usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php "$@"
SCRIPT;
    }

    private function buildRuntimeDiagnosisWrapperScript(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

exec /usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php "$@"
SCRIPT;
    }

    private function buildApplyScript(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Uso: $0 <rootfs-dir>" >&2
  exit 1
fi

ROOTFS_DIR="$1"
BUNDLE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILES_DIR="${BUNDLE_DIR}/files"

install -d "${ROOTFS_DIR}/usr/share/w4/system-api"
install -d "${ROOTFS_DIR}/usr/share/dbus-1/system-services"
install -d "${ROOTFS_DIR}/etc/systemd/system"
install -d "${ROOTFS_DIR}/etc/w4"
install -d "${ROOTFS_DIR}/usr/local/lib/w4/system-api"
install -d "${ROOTFS_DIR}/usr/local/bin"

cp -f "${FILES_DIR}/usr/share/w4/system-api/org.w4.System1.Manager.xml" "${ROOTFS_DIR}/usr/share/w4/system-api/org.w4.System1.Manager.xml"
cp -f "${FILES_DIR}/usr/share/dbus-1/system-services/org.w4.System1.service" "${ROOTFS_DIR}/usr/share/dbus-1/system-services/org.w4.System1.service"
cp -f "${FILES_DIR}/etc/systemd/system/w4-system-api.service" "${ROOTFS_DIR}/etc/systemd/system/w4-system-api.service"
cp -f "${FILES_DIR}/etc/w4/system-api-binding.env" "${ROOTFS_DIR}/etc/w4/system-api-binding.env"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-bridge" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-adapter" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-adapter"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-host" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-host"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-launcher" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-launcher"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-adapter"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-host"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-launcher"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose"

# The service remains disabled until the real D-Bus bridge exists in the target runtime.
SCRIPT;
    }
}
