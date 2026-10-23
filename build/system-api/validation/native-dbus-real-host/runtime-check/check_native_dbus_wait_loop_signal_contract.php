<?php

declare(strict_types=1);

$rootDir = resolveStringOverride(captureEnvironment(), 'W4_SYSTEM_API_REMOTE_ROOT_DIR')
    ?? '/home/w4live/w4-os-system';
$artifactsDir = resolveStringOverride(captureEnvironment(), 'W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR')
    ?? ($rootDir . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/wait-loop-signal-contract');
$reportFilePath = $artifactsDir . '/check-native-dbus-wait-loop-signal-contract.json';

@mkdir($artifactsDir, 0777, true);

$result = [
    'api_version' => '1.0',
    'kind' => 'native-dbus-wait-loop-signal-contract',
    'root_dir' => $rootDir,
    'artifacts_dir' => $artifactsDir,
    'php_version_id' => PHP_VERSION_ID,
    'expected_contract' => PHP_VERSION_ID >= 80000
        ? 'incoming-signal-normalized-to-true'
        : 'incoming-signal-materialized-as-dbus-signal',
    'ok' => false,
];

if (!class_exists('Dbus')) {
    $result['error'] = 'dbus-extension-missing';
    writeJsonFile($reportFilePath, $result);
    printJson($result);
    exit(1);
}

$signalInterface = 'org.w4.System1.RuntimeProbe';
$signalMember = 'WaitLoopSignalContract';
$signalPath = '/org/w4/System1/RuntimeProbe';

fwrite(STDERR, "phase:construct-receiver\n");
$receiver = new Dbus(Dbus::BUS_SYSTEM, 0);
$result['receiver_constructor'] = 'bus-system-constructor';

fwrite(STDERR, "phase:add-watch\n");
$result['add_watch'] = $receiver->addWatch($signalInterface, $signalMember);

fwrite(STDERR, "phase:construct-sender\n");
$sender = new Dbus(Dbus::BUS_SYSTEM, 0);
$result['sender_constructor'] = 'bus-system-constructor';

fwrite(STDERR, "phase:build-signal\n");
$outgoingSignal = new DbusSignal(
    $sender,
    $signalPath,
    $signalInterface,
    $signalMember
);

fwrite(STDERR, "phase:send-signal\n");
$result['send_signal'] = $outgoingSignal->send('probe-payload');

fwrite(STDERR, "phase:wait-loop\n");
$waitLoopResult = $receiver->waitLoop(250);
$waitLoopType = is_object($waitLoopResult) ? $waitLoopResult::class : gettype($waitLoopResult);
$waitLoopValue = normalizeValue($waitLoopResult);
$result['wait_loop_type'] = $waitLoopType;
$result['wait_loop_value'] = $waitLoopValue;

if (PHP_VERSION_ID >= 80000) {
    $result['contract_state'] = is_bool($waitLoopResult) && $waitLoopResult === true
        ? 'incoming-signal-normalized-to-true'
        : 'incoming-signal-contract-mismatch';
    $result['ok'] = $result['contract_state'] === 'incoming-signal-normalized-to-true';
} else {
    $result['contract_state'] = is_object($waitLoopResult) && $waitLoopResult instanceof DbusSignal
        ? 'incoming-signal-materialized-as-dbus-signal'
        : 'incoming-signal-contract-mismatch';
    $result['ok'] = $result['contract_state'] === 'incoming-signal-materialized-as-dbus-signal';
}

unset($waitLoopResult, $outgoingSignal, $sender, $receiver);
$result['gc_cycles'] = gc_collect_cycles();

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

function normalizeValue(mixed $value): mixed
{
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
