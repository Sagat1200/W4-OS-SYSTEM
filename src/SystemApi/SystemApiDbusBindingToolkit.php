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
            'files/usr/share/dbus-1/system-services/org.w4.System1.service' => $this->buildDbusServiceFile(),
            'files/etc/systemd/system/w4-system-api.service' => $this->buildSystemdUnit(),
            'files/etc/w4/system-api-binding.env' => $this->buildBindingEnv(),
            'files/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php' => $this->buildBridgeRuntimeScript(),
            'files/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php' => $this->buildBridgeReadinessScript(),
            'files/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php' => $this->buildRuntimeDiagnosisScript(),
            'files/usr/local/bin/w4-system-api-dbus-bridge' => $this->buildBridgeWrapperScript(),
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
                    'current_local_runtime' => 'stdio-ndjson-daemon',
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
Exec=/usr/local/bin/w4-system-api-dbus-bridge serve-dbus
User=root
SystemdService=w4-system-api.service
# Pending runtime adapter: the packaged bridge exists and fails fast until a real D-Bus runtime lands.
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
ExecStart=/usr/local/bin/w4-system-api-dbus-bridge serve-dbus
Restart=on-failure
RestartSec=2s
StandardOutput=journal
StandardError=journal

[Install]
WantedBy=multi-user.target
UNIT;
    }

    private function buildBindingEnv(): string
    {
        return <<<ENV
W4_SYSTEM_API_VERSION="1.0"
W4_SYSTEM_API_BUS_NAME="org.w4.System1"
W4_SYSTEM_API_OBJECT_PATH="/org/w4/System1"
W4_SYSTEM_API_INTERFACE="org.w4.System1.Manager"
W4_SYSTEM_API_BINDING_STATUS="pending-runtime-adapter"
W4_SYSTEM_API_LOCAL_TRANSPORT="stdio-ndjson-daemon"
W4_SYSTEM_API_BRIDGE_ENTRY="/usr/local/bin/w4-system-api-dbus-bridge"
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

    return [
        'api_version' => '1.0',
        'kind' => 'system-api-dbus-runtime-diagnosis',
        'ready' => $ready,
        'status' => $ready ? 'ready-for-runtime-adapter' : 'pending-runtime-adapter',
        'bus_name' => W4_SYSTEM_API_BUS_NAME,
        'object_path' => W4_SYSTEM_API_OBJECT_PATH,
        'interface' => W4_SYSTEM_API_INTERFACE,
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
        'runtime_diagnosis' => $runtimeDiagnosis,
        'missing_capabilities' => is_array($runtimeDiagnosis['missing_capabilities'] ?? null)
            ? $runtimeDiagnosis['missing_capabilities']
            : [],
        'next_action' => 'materialize-runtime-adapter',
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

    private function buildBridgeWrapperScript(): string
    {
        return <<<'SCRIPT'
#!/usr/bin/env bash
set -euo pipefail

exec /usr/bin/php /usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php "$@"
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
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-bridge" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose"

# The service remains disabled until the real D-Bus bridge exists in the target runtime.
SCRIPT;
    }
}
