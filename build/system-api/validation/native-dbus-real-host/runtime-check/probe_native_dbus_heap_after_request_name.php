<?php

declare(strict_types=1);

if (resolveScenarioName($argv) !== null) {
    executeScenario(resolveScenarioName($argv), captureEnvironment());
    exit(0);
}

$environment = captureEnvironment();
$rootDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ROOT_DIR')
    ?? '/home/w4live/w4-os-system';
$phpBinary = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_PHP_BINARY')
    ?? '/usr/bin/php';
$dbusModule = resolveDbusModulePath($environment, $rootDir);
$artifactsDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR')
    ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/heap-after-request-name');
$reportFilePath = $artifactsDir . '/probe-native-dbus-heap-after-request-name.json';

@mkdir($artifactsDir, 0777, true);

$result = [
    'api_version' => '1.0',
    'kind' => 'native-dbus-heap-after-request-name-probe',
    'root_dir' => $rootDir,
    'php_binary' => $phpBinary,
    'dbus_module' => $dbusModule,
    'artifacts_dir' => $artifactsDir,
    'ok' => false,
];

if (!is_file($dbusModule)) {
    $result['error'] = 'dbus-module-missing';
    writeJsonFile($reportFilePath, $result);
    printJson($result);
    exit(1);
}

$scenarios = [
    'request_name_only',
    'exported_object_only',
    'request_name_then_scalar_allocation',
    'request_name_then_gc_collect',
    'request_name_then_require_noop',
    'request_name_then_require_autoload',
    'request_name_then_exported_object',
];

$result['scenarios'] = [];
foreach ($scenarios as $scenario) {
    $result['scenarios'][$scenario] = runScenarioSubprocess(
        $phpBinary,
        $dbusModule,
        __FILE__,
        $scenario,
        $rootDir,
        $environment
    );
}

$result['ok'] = true;

writeJsonFile($reportFilePath, $result);
printJson($result);

/**
 * @return array<string, string>
 */
function captureEnvironment(): array
{
    $environment = getenv();

    return is_array($environment) ? $environment : [];
}

function resolveScenarioName(array $argv): ?string
{
    foreach ($argv as $argument) {
        if (str_starts_with($argument, '--scenario=')) {
            $value = substr($argument, strlen('--scenario='));

            return $value !== '' ? $value : null;
        }
    }

    return null;
}

/**
 * @param array<string, string> $environment
 */
function executeScenario(?string $scenario, array $environment): void
{
    if ($scenario === null) {
        throw new RuntimeException('Scenario missing');
    }

    $rootDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ROOT_DIR')
        ?? '/home/w4live/w4-os-system';
    writeScenarioPhaseMarker($environment, $scenario, 'start');

    switch ($scenario) {
        case 'request_name_only':
            $connection = instantiateSystemBusConnection();
            writeScenarioPhaseMarker($environment, $scenario, 'before-request-name');
            $result = $connection->requestName('org.w4.System1');
            writeScenarioPhaseMarker($environment, $scenario, 'after-request-name');
            printJson([
                'scenario' => $scenario,
                'request_name_result' => normalizeValue($result),
            ]);
            return;

        case 'exported_object_only':
            writeScenarioPhaseMarker($environment, $scenario, 'before-require-autoload');
            requireAutoload($rootDir);
            writeScenarioPhaseMarker($environment, $scenario, 'after-require-autoload');
            writeScenarioPhaseMarker($environment, $scenario, 'before-exported-object');
            $exportedObject = new \W4\OS\SystemApi\NativeDbusExtensionRuntimeObject(
                defaultServiceDefinition(),
                $rootDir
            );
            writeScenarioPhaseMarker($environment, $scenario, 'after-exported-object');
            printJson([
                'scenario' => $scenario,
                'exported_object_class' => $exportedObject::class,
                'binding_status' => $exportedObject->BindingStatus,
            ]);
            return;

        case 'request_name_then_scalar_allocation':
            $connection = instantiateSystemBusConnection();
            writeScenarioPhaseMarker($environment, $scenario, 'before-request-name');
            $requestNameResult = $connection->requestName('org.w4.System1');
            writeScenarioPhaseMarker($environment, $scenario, 'after-request-name');
            $probeArray = [
                'alpha' => 'beta',
                'gamma' => 42,
                'delta' => true,
            ];
            writeScenarioPhaseMarker($environment, $scenario, 'after-scalar-allocation');
            printJson([
                'scenario' => $scenario,
                'request_name_result' => normalizeValue($requestNameResult),
                'probe_array' => $probeArray,
            ]);
            return;

        case 'request_name_then_gc_collect':
            $connection = instantiateSystemBusConnection();
            writeScenarioPhaseMarker($environment, $scenario, 'before-request-name');
            $requestNameResult = $connection->requestName('org.w4.System1');
            writeScenarioPhaseMarker($environment, $scenario, 'after-request-name');
            $cycles = gc_collect_cycles();
            writeScenarioPhaseMarker($environment, $scenario, 'after-gc-collect');
            printJson([
                'scenario' => $scenario,
                'request_name_result' => normalizeValue($requestNameResult),
                'collected_cycles' => $cycles,
            ]);
            return;

        case 'request_name_then_require_noop':
            $connection = instantiateSystemBusConnection();
            writeScenarioPhaseMarker($environment, $scenario, 'before-request-name');
            $requestNameResult = $connection->requestName('org.w4.System1');
            writeScenarioPhaseMarker($environment, $scenario, 'after-request-name');
            writeScenarioPhaseMarker($environment, $scenario, 'before-require-noop');
            require_once $rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/noop_require_after_request_name.php';
            writeScenarioPhaseMarker($environment, $scenario, 'after-require-noop');
            printJson([
                'scenario' => $scenario,
                'request_name_result' => normalizeValue($requestNameResult),
                'noop_required' => true,
            ]);
            return;

        case 'request_name_then_require_autoload':
            $connection = instantiateSystemBusConnection();
            writeScenarioPhaseMarker($environment, $scenario, 'before-request-name');
            $requestNameResult = $connection->requestName('org.w4.System1');
            writeScenarioPhaseMarker($environment, $scenario, 'after-request-name');
            writeScenarioPhaseMarker($environment, $scenario, 'before-require-autoload');
            requireAutoload($rootDir);
            writeScenarioPhaseMarker($environment, $scenario, 'after-require-autoload');
            printJson([
                'scenario' => $scenario,
                'request_name_result' => normalizeValue($requestNameResult),
                'autoload_loaded' => class_exists(\W4\OS\SystemApi\NativeDbusExtensionRuntimeObject::class),
            ]);
            return;

        case 'request_name_then_exported_object':
            $connection = instantiateSystemBusConnection();
            writeScenarioPhaseMarker($environment, $scenario, 'before-request-name');
            $requestNameResult = $connection->requestName('org.w4.System1');
            writeScenarioPhaseMarker($environment, $scenario, 'after-request-name');
            writeScenarioPhaseMarker($environment, $scenario, 'before-require-autoload');
            requireAutoload($rootDir);
            writeScenarioPhaseMarker($environment, $scenario, 'after-require-autoload');
            writeScenarioPhaseMarker($environment, $scenario, 'before-exported-object');
            $exportedObject = new \W4\OS\SystemApi\NativeDbusExtensionRuntimeObject(
                defaultServiceDefinition(),
                $rootDir
            );
            writeScenarioPhaseMarker($environment, $scenario, 'after-exported-object');
            printJson([
                'scenario' => $scenario,
                'request_name_result' => normalizeValue($requestNameResult),
                'exported_object_class' => $exportedObject::class,
                'binding_status' => $exportedObject->BindingStatus,
            ]);
            return;
    }

    throw new RuntimeException(sprintf('Unsupported scenario: %s', $scenario));
}

/**
 * @param array<string, string> $environment
 * @return array<string, mixed>
 */
function runScenarioSubprocess(
    string $phpBinary,
    string $dbusModule,
    string $scriptPath,
    string $scenario,
    string $rootDir,
    array $environment
): array {
    $process = runProcess(
        [
            $phpBinary,
            '-d',
            'extension=' . $dbusModule,
            $scriptPath,
            '--scenario=' . $scenario,
        ],
        $rootDir,
        array_merge($environment, [
            'W4_SYSTEM_API_SCENARIO_ARTIFACTS_DIR' => $environment['W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR']
                ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/heap-after-request-name'),
        ])
    );

    return [
        'exit_code' => $process['exit_code'],
        'stdout' => trim((string) ($process['stdout'] ?? '')),
        'stderr' => trim((string) ($process['stderr'] ?? '')),
    ];
}

/**
 * @param array<string, string> $environment
 */
function writeScenarioPhaseMarker(array $environment, string $scenario, string $phase): void
{
    $baseDir = resolveStringOverride($environment, 'W4_SYSTEM_API_SCENARIO_ARTIFACTS_DIR');
    if ($baseDir === null) {
        return;
    }

    $phaseDir = $baseDir . '/scenario-phases';
    @mkdir($phaseDir, 0777, true);
    @file_put_contents($phaseDir . '/' . $scenario . '.phase', $phase . PHP_EOL);
}

/**
 * @param list<string> $command
 * @param array<string, string> $environment
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function runProcess(array $command, string $cwd, array $environment): array
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $cwd, $environment);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start subprocess');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'exit_code' => is_int($exitCode) ? $exitCode : 1,
        'stdout' => is_string($stdout) ? $stdout : '',
        'stderr' => is_string($stderr) ? $stderr : '',
    ];
}

function instantiateSystemBusConnection(): object
{
    foreach (['Dbus', 'DBus'] as $className) {
        if (!class_exists($className)) {
            continue;
        }

        $busSystem = defined($className . '::BUS_SYSTEM')
            ? constant($className . '::BUS_SYSTEM')
            : null;

        return $busSystem !== null ? new $className($busSystem) : new $className();
    }

    throw new RuntimeException('Dbus runtime class is not available');
}

function requireAutoload(string $rootDir): void
{
    require_once $rootDir . '/vendor/autoload.php';
}

/**
 * @return array<string, mixed>
 */
function defaultServiceDefinition(): array
{
    return [
        'bus_name' => 'org.w4.System1',
        'object_path' => '/org/w4/System1',
        'interface' => 'org.w4.System1.Manager',
        'service_lifecycle' => 'request-name-register-object-wait-loop',
    ];
}

function normalizeValue(mixed $value): mixed
{
    if (is_bool($value) || is_int($value) || is_float($value) || is_string($value) || $value === null) {
        return $value;
    }

    return get_debug_type($value);
}

function resolveStringOverride(array $environment, string $key): ?string
{
    $value = $environment[$key] ?? null;

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}

function resolveDbusModulePath(array $environment, string $rootDir): string
{
    $override = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_DBUS_MODULE');
    if ($override !== null) {
        return $override;
    }

    $sharedArtifactPath = $rootDir
        . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so';
    if (is_file($sharedArtifactPath)) {
        return $sharedArtifactPath;
    }

    return '/home/w4live/dbus-0.1.1/modules/dbus.so';
}

/**
 * @param array<string, mixed> $data
 */
function writeJsonFile(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

/**
 * @param array<string, mixed> $data
 */
function printJson(array $data): void
{
    fwrite(STDOUT, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}
