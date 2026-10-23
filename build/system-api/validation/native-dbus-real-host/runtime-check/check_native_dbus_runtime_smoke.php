<?php

declare(strict_types=1);

$environment = captureEnvironment();
$rootDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ROOT_DIR')
    ?? '/home/w4live/w4-os-system';
$phpBinary = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_PHP_BINARY')
    ?? '/usr/bin/php';
$dbusModule = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_DBUS_MODULE')
    ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so');
$artifactsDir = resolveStringOverride($environment, 'W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR')
    ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/runtime-smoke');
$reportFilePath = $artifactsDir . '/check-native-dbus-runtime-smoke.json';

@mkdir($artifactsDir, 0777, true);

$result = [
    'api_version' => '1.0',
    'kind' => 'native-dbus-runtime-smoke',
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

$checks = [
    'wrapper_smoke' => $rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_wrapper_smoke.php',
    'wait_loop_signal_contract' => $rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_wait_loop_signal_contract.php',
];

$result['checks'] = [];
foreach ($checks as $label => $scriptPath) {
    $result['checks'][$label] = runPhpProbe(
        $phpBinary,
        $dbusModule,
        $scriptPath,
        $rootDir,
        $environment
    );
}

$result['ok'] = true;
foreach ($result['checks'] as $probeResult) {
    if (($probeResult['exit_code'] ?? 1) !== 0) {
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
 * @return array<string, mixed>
 */
function runPhpProbe(
    string $phpBinary,
    string $dbusModule,
    string $scriptPath,
    string $rootDir,
    array $environment
): array {
    $command = implode(' ', [
        escapeshellarg($phpBinary),
        '-d',
        escapeshellarg('extension=' . $dbusModule),
        escapeshellarg($scriptPath),
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
            'script' => $scriptPath,
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
        'script' => $scriptPath,
        'exit_code' => $exitCode,
        'stdout' => trim((string) $stdout),
        'stderr' => trim((string) $stderr),
    ];
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
