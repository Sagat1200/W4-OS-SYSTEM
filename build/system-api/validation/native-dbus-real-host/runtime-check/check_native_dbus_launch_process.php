<?php

declare(strict_types=1);

$environment = getenv();

$rootDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ROOT_DIR')
    ?? '/home/w4live/w4-os-system';
$phpBinary = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_PHP_BINARY')
    ?? '/usr/bin/php';
$dbusModule = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_DBUS_MODULE')
    ?? '/home/w4live/dbus-0.1.1/modules/dbus.so';
$adapterScript = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ADAPTER_SCRIPT')
    ?? ($rootDir . '/scripts/system_api_dbus_runtime_adapter.php');
$artifactsDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR')
    ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/launch-process');
$reportFilePath = $artifactsDir . '/check-native-dbus-launch-process.json';
$readyMarkerPath = $artifactsDir . '/w4-system-api-native-runtime.ready';
$statusFilePath = $artifactsDir . '/w4-system-api-native-runtime-status.json';
$pidFilePath = $artifactsDir . '/w4-system-api-native-runtime.pid';

@mkdir($artifactsDir, 0777, true);
@unlink($reportFilePath);
@unlink($readyMarkerPath);
@unlink($statusFilePath);
@unlink($pidFilePath);

$environment = array_merge($environment, [
    'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
    'W4_SYSTEM_API_NATIVE_RUNTIME_STARTUP_MODE' => 'launch-process',
    'W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS' => '12',
    'W4_SYSTEM_API_NATIVE_RUNTIME_EXTENSION_PATH' => $dbusModule,
    'W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER' => $readyMarkerPath,
    'W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE' => $statusFilePath,
    'W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE' => $pidFilePath,
]);

$result = [
    'api_version' => '1.0',
    'kind' => 'native-dbus-launch-process-check',
    'root_dir' => $rootDir,
    'adapter_script' => $adapterScript,
    'dbus_module' => $dbusModule,
    'artifacts_dir' => $artifactsDir,
    'ok' => false,
];

if (!is_file($adapterScript)) {
    $result['error'] = 'adapter-script-missing';
    printJson($result);
    exit(1);
}

if (!is_file($dbusModule)) {
    $result['error'] = 'dbus-module-missing';
    printJson($result);
    exit(1);
}

$start = runProcess(
    [$phpBinary, '-d', 'extension=' . $dbusModule, $adapterScript, 'StartNativeRuntimeService'],
    $rootDir,
    $environment
);
$result['start'] = decodeJsonPayload($start);
$result['start_process'] = summarizeProcess($start);

usleep(500000);

$observe = runProcess(
    [$phpBinary, '-d', 'extension=' . $dbusModule, $adapterScript, 'ObserveNativeRuntimeServiceState'],
    $rootDir,
    $environment
);
$result['observation'] = decodeJsonPayload($observe);
$result['observation_process'] = summarizeProcess($observe);

$result['artifacts'] = [
    'ready_marker' => readOptionalFile($readyMarkerPath),
    'status_file' => readOptionalJsonFile($statusFilePath),
    'pid_file' => readOptionalFile($pidFilePath),
];

$result['busctl_status'] = runShellCommand('busctl --system status org.w4.System1');
$result['busctl_capabilities'] = runShellCommand(
    "busctl --system call org.w4.System1 /org/w4/System1 org.w4.System1.Manager GetCapabilities s launch-process-probe"
);

$startPayload = is_array($result['start']) ? $result['start'] : [];
$observationPayload = is_array($result['observation']) ? $result['observation'] : [];
$pidRaw = is_string($result['artifacts']['pid_file'] ?? null) ? trim($result['artifacts']['pid_file']) : '';
$pid = preg_match('/^\d+$/', $pidRaw) === 1 ? (int) $pidRaw : 0;

$result['process_status'] = $pid > 0
    ? runShellCommand(sprintf('ps -p %d -o pid=,ppid=,stat=,cmd=', $pid))
    : null;

usleep(1500000);

$delayedObserve = runProcess(
    [$phpBinary, '-d', 'extension=' . $dbusModule, $adapterScript, 'ObserveNativeRuntimeServiceState'],
    $rootDir,
    $environment
);
$result['delayed_observation'] = decodeJsonPayload($delayedObserve);
$result['delayed_observation_process'] = summarizeProcess($delayedObserve);

$result['launch_ok'] = ($start['exit_code'] ?? 1) === 0
    && ($startPayload['status'] ?? null) === 'native-runtime-service-launch-recorded'
    && $pid > 0
    && in_array($observationPayload['status'] ?? null, [
        'native-runtime-service-observed-active',
        'native-runtime-service-observed-pending-ownership',
    ], true);
$result['service_reachable_ok'] = (($result['busctl_status']['exit_code'] ?? 1) === 0)
    && (($result['busctl_capabilities']['exit_code'] ?? 1) === 0);
$result['ok'] = $result['launch_ok'] && $result['service_reachable_ok'];

writeJsonFile($reportFilePath, $result);
printJson($result);
exit($result['ok'] ? 0 : 1);

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

    return [
        'exit_code' => proc_close($process),
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
 * @param array{exit_code:int,stdout:string,stderr:string} $processResult
 * @return array<string, mixed>|null
 */
function decodeJsonPayload(array $processResult): ?array
{
    $stdout = trim($processResult['stdout'] ?? '');
    if ($stdout === '') {
        return null;
    }

    try {
        $decoded = json_decode($stdout, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

/**
 * @param array{exit_code:int,stdout:string,stderr:string} $processResult
 * @return array<string, mixed>
 */
function summarizeProcess(array $processResult): array
{
    return [
        'exit_code' => $processResult['exit_code'],
        'stdout_preview' => trim($processResult['stdout']) === ''
            ? null
            : trim(substr(trim($processResult['stdout']), 0, 800)),
        'stderr_preview' => trim($processResult['stderr']) === ''
            ? null
            : trim(substr(trim($processResult['stderr']), 0, 800)),
    ];
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
 * @return array<string, mixed>|null
 */
function readOptionalJsonFile(string $path): ?array
{
    $raw = readOptionalFile($path);
    if ($raw === null || trim($raw) === '') {
        return null;
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

/**
 * @param array<string, mixed> $data
 */
function printJson(array $data): void
{
    fwrite(STDOUT, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

/**
 * @param array<string, mixed> $data
 */
function writeJsonFile(string $path, array $data): void
{
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

/**
 * @param array<string, string> $environment
 */
function resolveStringOverride(array $environment, string $key): ?string
{
    $value = $environment[$key] ?? null;

    return is_string($value) && trim($value) !== '' ? trim($value) : null;
}
