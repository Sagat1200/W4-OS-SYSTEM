<?php

declare(strict_types=1);

if (resolveModeName($argv) !== null) {
    serveMode(resolveModeName($argv), captureEnvironment());
    exit(0);
}

$environment = captureEnvironment();
$rootDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ROOT_DIR')
    ?? '/home/w4live/w4-os-system';
$phpBinary = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_PHP_BINARY')
    ?? '/usr/bin/php';
$dbusModule = resolveDbusModulePath($environment, $rootDir);
$artifactsDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR')
    ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/method-probe-ordering');
$reportFilePath = $artifactsDir . '/check-native-dbus-method-probe-ordering.json';

@mkdir($artifactsDir, 0777, true);

$result = [
    'api_version' => '1.0',
    'kind' => 'native-dbus-method-probe-ordering-check',
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

$modes = [
    'preload-first',
    'preload-object-only',
    'preload-classes-only',
];

$result['modes'] = [];
foreach ($modes as $mode) {
    $result['modes'][$mode] = runOrderingMode(
        $mode,
        $phpBinary,
        $dbusModule,
        __FILE__,
        $rootDir,
        $artifactsDir,
        $environment
    );
}

$result['ok'] = array_reduce(
    $result['modes'],
    static fn (bool $carry, array $modeResult): bool => $carry && (($modeResult['expectations_met'] ?? false) === true),
    true
);

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

function resolveModeName(array $argv): ?string
{
    foreach ($argv as $argument) {
        if (str_starts_with($argument, '--mode=')) {
            $value = substr($argument, strlen('--mode='));

            return $value !== '' ? $value : null;
        }
    }

    return null;
}

/**
 * @param array<string, string> $environment
 */
function serveMode(?string $mode, array $environment): void
{
    if ($mode === null) {
        throw new RuntimeException('Ordering mode missing');
    }

    $rootDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ROOT_DIR')
        ?? '/home/w4live/w4-os-system';
    require $rootDir . '/scripts/bootstrap.php';

    $modeArtifactsDir = resolveModeArtifactsDir($environment, $rootDir, $mode);
    @mkdir($modeArtifactsDir, 0777, true);

    $readyFile = $modeArtifactsDir . '/service.ready.json';
    $stopFile = $modeArtifactsDir . '/service.stop';
    $logFile = $modeArtifactsDir . '/service.events.log';

    @unlink($readyFile);
    @unlink($stopFile);
    @unlink($logFile);

    $serviceDefinition = defaultServiceDefinition();

    switch ($mode) {
        case 'preload-first':
            $runtimeObject = createRuntimeObject($serviceDefinition, $rootDir);
            $callbackClass = \W4\OS\SystemApi\NativeDbusExtensionCallbackBridge::prime($runtimeObject);
            $connection = instantiateSystemBusConnection();
            $requestNameResult = $connection->requestName($serviceDefinition['bus_name']);
            $registerObjectResult = $connection->registerObject(
                $serviceDefinition['object_path'],
                $serviceDefinition['interface'],
                $callbackClass
            );
            break;

        case 'preload-object-only':
            $runtimeObject = createRuntimeObject($serviceDefinition, $rootDir);
            $connection = instantiateSystemBusConnection();
            $requestNameResult = $connection->requestName($serviceDefinition['bus_name']);
            $callbackClass = \W4\OS\SystemApi\NativeDbusExtensionCallbackBridge::prime($runtimeObject);
            $registerObjectResult = $connection->registerObject(
                $serviceDefinition['object_path'],
                $serviceDefinition['interface'],
                $callbackClass
            );
            break;

        case 'preload-classes-only':
            preloadPhpRuntimeClasses();
            $connection = instantiateSystemBusConnection();
            $requestNameResult = $connection->requestName($serviceDefinition['bus_name']);
            $runtimeObject = createRuntimeObject($serviceDefinition, $rootDir);
            $callbackClass = \W4\OS\SystemApi\NativeDbusExtensionCallbackBridge::prime($runtimeObject);
            $registerObjectResult = $connection->registerObject(
                $serviceDefinition['object_path'],
                $serviceDefinition['interface'],
                $callbackClass
            );
            break;

        default:
            throw new RuntimeException(sprintf('Unsupported ordering mode: %s', $mode));
    }

    file_put_contents(
        $readyFile,
        json_encode([
            'status' => 'ready',
            'ordering_mode' => $mode,
            'bus_name' => $serviceDefinition['bus_name'],
            'object_path' => $serviceDefinition['object_path'],
            'interface' => $serviceDefinition['interface'],
            'request_name_result' => normalizeValue($requestNameResult),
            'register_object_result' => normalizeValue($registerObjectResult),
            'callback_class' => $callbackClass,
            'pid' => getmypid(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
    );

    for ($iteration = 1; $iteration <= 160; $iteration++) {
        if (file_exists($stopFile)) {
            file_put_contents($logFile, "stop-file-detected\n", FILE_APPEND);
            break;
        }

        $result = $connection->waitLoop(250);
        file_put_contents(
            $logFile,
            json_encode([
                'iteration' => $iteration,
                'wait_loop_result' => normalizeValue($result),
                'timestamp' => date(DATE_ATOM),
            ], JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }

    file_put_contents($logFile, "service-loop-finished\n", FILE_APPEND);
}

/**
 * @param array<string, string> $environment
 * @return array<string, mixed>
 */
function runOrderingMode(
    string $mode,
    string $phpBinary,
    string $dbusModule,
    string $scriptPath,
    string $rootDir,
    string $artifactsDir,
    array $environment
): array {
    $modeArtifactsDir = $artifactsDir . '/' . $mode;
    $readyFile = $modeArtifactsDir . '/service.ready.json';
    $stopFile = $modeArtifactsDir . '/service.stop';
    $logFile = $modeArtifactsDir . '/service.events.log';

    ensureCleanDirectory($modeArtifactsDir);

    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $childEnvironment = array_merge($environment, [
        'W4_SYSTEM_API_REMOTE_ROOT_DIR' => $rootDir,
        'W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR' => $artifactsDir,
    ]);

    $process = proc_open(
        [
            $phpBinary,
            '-d',
            'extension=' . $dbusModule,
            $scriptPath,
            '--mode=' . $mode,
        ],
        $descriptorSpec,
        $pipes,
        $rootDir,
        $childEnvironment
    );

    if (!is_resource($process)) {
        return [
            'ordering_mode' => $mode,
            'expectation' => orderingExpectation($mode),
            'error' => 'unable-to-start-process',
            'expectations_met' => false,
        ];
    }

    fclose($pipes[0]);

    $ready = waitForReadyFileOrExit($process, $readyFile, 8000000);
    $readyPayload = readOptionalJsonFile($readyFile);

    $result = [
        'ordering_mode' => $mode,
        'expectation' => orderingExpectation($mode),
        'ready' => $ready,
        'ready_payload' => $readyPayload,
        'busctl_status' => $ready
            ? runShellCommand('busctl --system status org.w4.System1')
            : null,
        'busctl_capabilities' => $ready
            ? runShellCommand(
                sprintf(
                    'busctl --system call org.w4.System1 /org/w4/System1 org.w4.System1.Manager GetCapabilities s %s',
                    escapeshellarg('ordering-' . $mode)
                )
            )
            : null,
    ];

    if (is_resource($process)) {
        @file_put_contents($stopFile, "stop\n");
    }

    $processResult = closeManagedProcess($process, $pipes, 5000000);

    $result['process'] = [
        'exit_code' => $processResult['exit_code'],
        'stdout_preview' => preview($processResult['stdout']),
        'stderr_preview' => preview($processResult['stderr']),
    ];
    $result['log_tail'] = readLogTail($logFile);

    $serviceReachable = (($result['busctl_status']['exit_code'] ?? 1) === 0)
        && (($result['busctl_capabilities']['exit_code'] ?? 1) === 0);
    $result['service_reachable_ok'] = $serviceReachable;
    $result['expectations_met'] = expectationsMet(
        $mode,
        $ready,
        $serviceReachable
    );

    return $result;
}

function orderingExpectation(string $mode): array
{
    return match ($mode) {
        'preload-first' => [
            'ready' => true,
            'service_reachable_ok' => true,
        ],
        'preload-object-only' => [
            'ready' => true,
            'service_reachable_ok' => true,
        ],
        'preload-classes-only' => [
            'ready' => true,
            'service_reachable_ok' => true,
        ],
        default => [
            'ready' => false,
            'service_reachable_ok' => false,
        ],
    };
}

function expectationsMet(string $mode, bool $ready, bool $serviceReachable): bool
{
    $expectation = orderingExpectation($mode);

    return $ready === $expectation['ready']
        && $serviceReachable === $expectation['service_reachable_ok'];
}

/**
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function closeManagedProcess($process, array $pipes, int $timeoutMicros): array
{
    $elapsed = 0;
    while ($elapsed < $timeoutMicros) {
        $status = proc_get_status($process);
        if (($status['running'] ?? false) !== true) {
            break;
        }

        usleep(100000);
        $elapsed += 100000;
    }

    $status = proc_get_status($process);
    if (($status['running'] ?? false) === true) {
        proc_terminate($process);
    }

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

function waitForReadyFileOrExit($process, string $readyFile, int $timeoutMicros): bool
{
    $elapsed = 0;
    while ($elapsed < $timeoutMicros) {
        if (is_file($readyFile)) {
            return true;
        }

        $status = proc_get_status($process);
        if (($status['running'] ?? false) !== true) {
            return is_file($readyFile);
        }

        usleep(100000);
        $elapsed += 100000;
    }

    return is_file($readyFile);
}

function ensureCleanDirectory(string $path): void
{
    @mkdir($path, 0777, true);

    foreach ([
        $path . '/service.ready.json',
        $path . '/service.stop',
        $path . '/service.events.log',
    ] as $filePath) {
        @unlink($filePath);
    }
}

/**
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function runShellCommand(string $command): array
{
    return runProcess(['/bin/bash', '-lc', $command], '/home/w4live', getenv());
}

/**
 * @param list<string> $command
 * @param array<string, string>|array $environment
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
        return [
            'exit_code' => 1,
            'stdout' => '',
            'stderr' => 'proc_open failed',
        ];
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

/**
 * @param array<string, mixed> $serviceDefinition
 */
function createRuntimeObject(array $serviceDefinition, string $rootDir): object
{
    return new \W4\OS\SystemApi\NativeDbusExtensionRuntimeObject($serviceDefinition, $rootDir);
}

function preloadPhpRuntimeClasses(): void
{
    foreach ([
        \W4\OS\SystemApi\NativeDbusExtensionRuntimeObject::class,
        \W4\OS\SystemApi\NativeDbusExtensionCallbackBridge::class,
        \W4\OS\SystemApi\SystemApiManager::class,
        \W4\OS\SystemApi\SystemApiToolkit::class,
        \W4\OS\SystemApi\SystemApiDbusRuntimeProbeToolkit::class,
        \W4\OS\SystemApi\SystemApiDbusRuntimeActivationToolkit::class,
        \W4\OS\SystemApi\SystemApiDbusRuntimeBackendToolkit::class,
    ] as $className) {
        class_exists($className);
    }
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

function readLogTail(string $path): ?string
{
    $raw = readOptionalFile($path);
    if ($raw === null) {
        return null;
    }

    $lines = preg_split('/\r\n|\r|\n/', trim($raw));
    if (!is_array($lines)) {
        return null;
    }

    $tail = array_slice($lines, -5);

    return implode(PHP_EOL, $tail);
}

function preview(string $value): ?string
{
    $value = trim($value);

    return $value === '' ? null : substr($value, 0, 800);
}

function normalizeValue(mixed $value): mixed
{
    if (is_bool($value) || is_int($value) || is_float($value) || is_string($value) || $value === null) {
        return $value;
    }

    return get_debug_type($value);
}

function resolveModeArtifactsDir(array $environment, string $rootDir, string $mode): string
{
    $baseDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR')
        ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/method-probe-ordering');

    return $baseDir . '/' . $mode;
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
 * @return array<string, mixed>|null
 */
function readOptionalJsonFile(string $path): ?array
{
    $raw = readOptionalFile($path);
    if ($raw === null) {
        return null;
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

function readOptionalFile(string $path): ?string
{
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);

    return is_string($raw) ? $raw : null;
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
