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
    ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/wrapper-smoke');
$reportFilePath = $artifactsDir . '/check-native-dbus-wrapper-smoke.json';

@mkdir($artifactsDir, 0777, true);

$result = [
    'api_version' => '1.0',
    'kind' => 'native-dbus-wrapper-smoke',
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
    'proxy_direct_wrapper',
    'proxy_factory_wrapper',
    'signal_wrapper',
    'array_wrapper',
    'dict_wrapper',
    'variant_wrapper',
    'set_wrapper',
    'struct_wrapper',
    'object_path_wrapper',
    'scalar_wrappers',
    'nested_wrappers_bundle',
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
foreach ($result['scenarios'] as $scenarioResult) {
    if (($scenarioResult['exit_code'] ?? 1) !== 0) {
        $result['ok'] = false;
        break;
    }
}

writeJsonFile($reportFilePath, $result);
printJson($result);
exit($result['ok'] ? 0 : 1);

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

    switch ($scenario) {
        case 'proxy_direct_wrapper':
            $dbus = instantiateSystemBusConnection();
            $constructor = (new ReflectionClass(DbusObject::class))->getConstructor();
            $proxy = $dbus->createProxy(
                'org.freedesktop.DBus',
                '/org/freedesktop/DBus',
                'org.freedesktop.DBus'
            );

            printJson([
                'scenario' => $scenario,
                'dbus_class' => $dbus::class,
                'proxy_class' => $proxy::class,
                'proxy_id' => spl_object_id($proxy),
                'constructor_visibility' => $constructor?->isPrivate() ? 'private' : 'non-private',
                'proxy_vars' => normalizeValue(get_object_vars($proxy)),
            ]);
            return;

        case 'proxy_factory_wrapper':
            $dbus = instantiateSystemBusConnection();
            $proxy = $dbus->createProxy(
                'org.freedesktop.DBus',
                '/org/freedesktop/DBus',
                'org.freedesktop.DBus'
            );

            printJson([
                'scenario' => $scenario,
                'proxy_class' => $proxy::class,
                'proxy_id' => spl_object_id($proxy),
                'proxy_vars' => normalizeValue(get_object_vars($proxy)),
            ]);
            return;

        case 'signal_wrapper':
            $dbus = instantiateSystemBusConnection();
            $signal = new DbusSignal(
                $dbus,
                '/org/w4/System1',
                'org.w4.System1.Manager',
                'CapabilitiesChanged'
            );

            printJson([
                'scenario' => $scenario,
                'signal_class' => $signal::class,
                'signal_id' => spl_object_id($signal),
                'signal_vars' => normalizeValue(get_object_vars($signal)),
            ]);
            return;

        case 'array_wrapper':
            $wrapper = new DbusArray(Dbus::STRING, ['alpha', 'beta'], 's');

            printJson([
                'scenario' => $scenario,
                'wrapper_class' => $wrapper::class,
                'data' => normalizeValue($wrapper->getData()),
                'vars' => normalizeValue(get_object_vars($wrapper)),
            ]);
            return;

        case 'dict_wrapper':
            $wrapper = new DbusDict(Dbus::INT32, ['alpha' => 1, 'beta' => 2], 'i');

            printJson([
                'scenario' => $scenario,
                'wrapper_class' => $wrapper::class,
                'data' => normalizeValue($wrapper->getData()),
                'vars' => normalizeValue(get_object_vars($wrapper)),
            ]);
            return;

        case 'variant_wrapper':
            $wrappedScalar = new DbusUInt32(42);
            $wrapper = new DbusVariant($wrappedScalar);

            printJson([
                'scenario' => $scenario,
                'wrapper_class' => $wrapper::class,
                'data' => normalizeValue($wrapper->getData()),
                'vars' => normalizeValue(get_object_vars($wrapper)),
            ]);
            return;

        case 'set_wrapper':
            $wrapper = new DbusSet(
                new DbusInt32(7),
                new DbusBool(1),
                new DbusObjectPath('/org/w4/System1')
            );

            printJson([
                'scenario' => $scenario,
                'wrapper_class' => $wrapper::class,
                'data' => normalizeValue($wrapper->getData()),
                'vars' => normalizeValue(get_object_vars($wrapper)),
            ]);
            return;

        case 'struct_wrapper':
            $wrapper = new DbusStruct(
                '(is)',
                [new DbusInt32(7), 'seven']
            );

            printJson([
                'scenario' => $scenario,
                'wrapper_class' => $wrapper::class,
                'data' => normalizeValue($wrapper->getData()),
                'vars' => normalizeValue(get_object_vars($wrapper)),
            ]);
            return;

        case 'object_path_wrapper':
            $wrapper = new DbusObjectPath('/org/w4/System1');

            printJson([
                'scenario' => $scenario,
                'wrapper_class' => $wrapper::class,
                'data' => normalizeValue($wrapper->getData()),
                'vars' => normalizeValue(get_object_vars($wrapper)),
            ]);
            return;

        case 'scalar_wrappers':
            $wrappers = [
                'DbusByte' => new DbusByte(7),
                'DbusBool' => new DbusBool(1),
                'DbusInt16' => new DbusInt16(16),
                'DbusUInt16' => new DbusUInt16(160),
                'DbusInt32' => new DbusInt32(32),
                'DbusUInt32' => new DbusUInt32(320),
                'DbusInt64' => new DbusInt64(64),
                'DbusUInt64' => new DbusUInt64(640),
                'DbusDouble' => new DbusDouble(12),
            ];

            $summary = [];
            foreach ($wrappers as $label => $wrapper) {
                $summary[$label] = [
                    'class' => $wrapper::class,
                    'id' => spl_object_id($wrapper),
                    'vars' => normalizeValue(get_object_vars($wrapper)),
                ];
            }

            printJson([
                'scenario' => $scenario,
                'wrappers' => $summary,
            ]);
            return;

        case 'nested_wrappers_bundle':
            $summary = [];
            $factories = [
                'array' => static fn (): object => new DbusArray(Dbus::STRING, ['alpha', 'beta'], 's'),
                'dict' => static fn (): object => new DbusDict(Dbus::INT32, ['alpha' => 1, 'beta' => 2], 'i'),
                'variant' => static fn (): object => new DbusVariant(new DbusUInt32(7)),
                'set' => static fn (): object => new DbusSet(new DbusInt32(7), new DbusBool(1)),
                'struct' => static fn (): object => new DbusStruct('(is)', [new DbusInt32(7), 'seven']),
                'object_path' => static fn (): object => new DbusObjectPath('/org/w4/System1'),
            ];

            foreach ($factories as $label => $factory) {
                writeBundleStep($environment, 'before-' . $label);
                $wrapper = $factory();
                writeBundleStep($environment, 'after-create-' . $label);
                $summary[$label] = [
                    'class' => $wrapper::class,
                    'id' => spl_object_id($wrapper),
                ];
                writeBundleStep($environment, 'after-summary-' . $label);

                unset($wrapper);
                gc_collect_cycles();
                writeBundleStep($environment, 'after-destroy-' . $label);
            }

            $summary['scalars'] = [];
            foreach ([
                new DbusByte(7),
                new DbusBool(1),
                new DbusInt16(16),
                new DbusUInt16(160),
                new DbusInt32(32),
                new DbusUInt32(320),
                new DbusInt64(64),
                new DbusUInt64(640),
                new DbusDouble(12),
            ] as $wrapper) {
                $summary['scalars'][] = [
                    'class' => $wrapper::class,
                    'id' => spl_object_id($wrapper),
                ];
                writeBundleStep($environment, 'after-scalar-' . $wrapper::class);
                unset($wrapper);
                gc_collect_cycles();
            }

            printJson([
                'scenario' => $scenario,
                'bundle' => $summary,
                'gc_cycles' => gc_collect_cycles(),
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
    $command = implode(' ', [
        escapeshellarg($phpBinary),
        '-d',
        escapeshellarg('extension=' . $dbusModule),
        escapeshellarg($scriptPath),
        escapeshellarg('--scenario=' . $scenario),
    ]);

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $childEnvironment = array_merge($environment, [
        'W4_SYSTEM_API_REMOTE_ROOT_DIR' => $rootDir,
    ]);

    $process = proc_open(
        $command,
        $descriptorSpec,
        $pipes,
        $rootDir,
        $childEnvironment
    );

    if (!is_resource($process)) {
        return [
            'exit_code' => 1,
            'stdout' => '',
            'stderr' => 'failed-to-start-subprocess',
        ];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'stdout' => trim((string) $stdout),
        'stderr' => trim((string) $stderr),
    ];
}

function instantiateSystemBusConnection(): Dbus
{
    return new Dbus(Dbus::BUS_SYSTEM, 0);
}

/**
 */
function normalizeValue($value): mixed
{
    if ($value === null) {
        return null;
    }

    if (is_object($value)) {
        return [
            'class' => $value::class,
            'vars' => normalizeValue(get_object_vars($value)),
        ];
    }

    if (is_array($value)) {
        $normalized = [];
        foreach ($value as $key => $entry) {
            $normalized[$key] = normalizeValue($entry);
        }

        return $normalized;
    }

    return $value;
}

/**
 * @param array<string, mixed> $payload
 */
function writeJsonFile(string $path, array $payload): void
{
    file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

/**
 * @param array<string, mixed> $payload
 */
function printJson(array $payload): void
{
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

/**
 * @param array<string, string> $environment
 */
function resolveStringOverride(array $environment, string $key): ?string
{
    $value = $environment[$key] ?? null;

    return is_string($value) && $value !== '' ? $value : null;
}

/**
 * @param array<string, string> $environment
 */
function resolveDbusModulePath(array $environment, string $rootDir): string
{
    return resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_DBUS_MODULE')
        ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so');
}

/**
 * @param array<string, string> $environment
 */
function writeBundleStep(array $environment, string $step): void
{
    $path = resolveStringOverride($environment, 'W4_DBUS_WRAPPER_BUNDLE_STEP_FILE');
    if ($path === null) {
        return;
    }

    file_put_contents($path, $step . PHP_EOL, FILE_APPEND);
}
