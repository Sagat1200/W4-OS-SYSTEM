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
    ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/method-roundtrip-smoke');
$reportFilePath = $artifactsDir . '/check-native-dbus-method-roundtrip-smoke.json';

@mkdir($artifactsDir, 0777, true);

$result = [
    'api_version' => '1.0',
    'kind' => 'native-dbus-method-roundtrip-smoke',
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

$result += runMethodRoundtripCheck(
    $phpBinary,
    $dbusModule,
    __FILE__,
    $rootDir,
    $artifactsDir,
    $environment
);

$result['ok'] = ($result['ready'] ?? false) === true
    && (($result['busctl_status']['exit_code'] ?? 1) === 0)
    && (($result['busctl_capabilities']['exit_code'] ?? 1) === 0)
    && (($result['response_decoded_ok'] ?? false) === true)
    && (($result['request_id_matches'] ?? false) === true)
    && (($result['result_method_matches'] ?? false) === true)
    && (($result['capabilities_present'] ?? false) === true)
    && (($result['process_alive_after_call'] ?? false) === true)
    && (($result['shutdown_clean'] ?? false) === true);

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
    if ($mode !== 'serve') {
        throw new RuntimeException(sprintf('Unsupported mode: %s', (string) $mode));
    }

    $rootDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ROOT_DIR')
        ?? '/home/w4live/w4-os-system';
    require $rootDir . '/scripts/bootstrap.php';

    $artifactsDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR')
        ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/method-roundtrip-smoke');
    @mkdir($artifactsDir, 0777, true);

    $readyFile = $artifactsDir . '/service.ready.json';
    $stopFile = $artifactsDir . '/service.stop';
    $logFile = $artifactsDir . '/service.events.log';

    @unlink($readyFile);
    @unlink($stopFile);
    @unlink($logFile);

    preloadPhpRuntimeClasses();

    $serviceDefinition = defaultServiceDefinition();
    $connection = instantiateSystemBusConnection();
    $requestNameResult = $connection->requestName($serviceDefinition['bus_name']);
    $runtimeObject = createRuntimeObject($serviceDefinition, $rootDir);
    $callbackClass = \W4\OS\SystemApi\NativeDbusExtensionCallbackBridge::prime($runtimeObject);
    $registerObjectResult = $connection->registerObject(
        $serviceDefinition['object_path'],
        $serviceDefinition['interface'],
        $callbackClass
    );

    file_put_contents(
        $readyFile,
        json_encode([
            'status' => 'ready',
            'probe_mode' => 'preload-classes-before-connection',
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

        $waitLoopResult = $connection->waitLoop(250);
        file_put_contents(
            $logFile,
            json_encode([
                'iteration' => $iteration,
                'wait_loop_result' => normalizeValue($waitLoopResult),
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
function runMethodRoundtripCheck(
    string $phpBinary,
    string $dbusModule,
    string $scriptPath,
    string $rootDir,
    string $artifactsDir,
    array $environment
): array {
    ensureCleanDirectory($artifactsDir);

    $readyFile = $artifactsDir . '/service.ready.json';
    $stopFile = $artifactsDir . '/service.stop';
    $logFile = $artifactsDir . '/service.events.log';
    $requestId = 'method-roundtrip-smoke';

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
            '--mode=serve',
        ],
        $descriptorSpec,
        $pipes,
        $rootDir,
        $childEnvironment
    );

    if (!is_resource($process)) {
        return [
            'ready' => false,
            'error' => 'unable-to-start-process',
        ];
    }

    fclose($pipes[0]);

    $ready = waitForReadyFileOrExit($process, $readyFile, 8000000);
    $readyPayload = readOptionalJsonFile($readyFile);

    $busctlStatus = $ready
        ? runShellCommand('busctl --system status org.w4.System1')
        : null;
    $busctlCapabilities = $ready
        ? runShellCommand(
            sprintf(
                'busctl --system call org.w4.System1 /org/w4/System1 org.w4.System1.Manager GetCapabilities s %s',
                escapeshellarg($requestId)
            )
        )
        : null;

    $decodedResponse = decodeBusctlStringPayload($busctlCapabilities['stdout'] ?? '');
    $processStatusAfterCall = proc_get_status($process);
    $processAliveAfterCall = ($processStatusAfterCall['running'] ?? false) === true;

    @file_put_contents($stopFile, "stop\n");
    $processResult = closeManagedProcess($process, $pipes, 5000000);

    $resultMethod = $decodedResponse['result']['method'] ?? null;
    $capabilities = $decodedResponse['result']['capabilities'] ?? null;

    return [
        'request_id' => $requestId,
        'ready' => $ready,
        'ready_payload' => $readyPayload,
        'busctl_status' => $busctlStatus,
        'busctl_capabilities' => $busctlCapabilities,
        'response_payload' => $decodedResponse,
        'response_decoded_ok' => is_array($decodedResponse),
        'request_id_matches' => ($decodedResponse['request_id'] ?? null) === $requestId,
        'result_method_matches' => $resultMethod === 'system.capabilities',
        'capabilities_present' => is_array($capabilities) && $capabilities !== [],
        'process_alive_after_call' => $processAliveAfterCall,
        'process' => [
            'exit_code' => $processResult['exit_code'],
            'stdout_preview' => preview($processResult['stdout']),
            'stderr_preview' => preview($processResult['stderr']),
        ],
        'shutdown_clean' => ($processResult['exit_code'] ?? 1) === 0,
        'log_tail' => readLogTail($logFile),
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

/**
 * @return array<string, mixed>|null
 */
function decodeBusctlStringPayload(string $stdout): ?array
{
    $trimmed = trim($stdout);
    if ($trimmed === '') {
        return null;
    }

    if (!preg_match('/^s\s+"(?P<payload>.*)"$/s', $trimmed, $matches)) {
        return null;
    }

    $payload = stripcslashes($matches['payload']);

    try {
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
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

    return get_debug_type($value);
}

/**
 * @param array<string, string> $environment
 */
function resolveStringOverride(array $environment, string $key): ?string
{
    $value = $environment[$key] ?? null;

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}

/**
 * @param array<string, string> $environment
 */
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
