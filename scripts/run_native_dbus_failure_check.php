<?php

declare(strict_types=1);

/**
 * These rejection/cooldown probes use a private bus, never the host system bus.
 * No synthetic driver or runtime class override is used.
 */
// Retained to validate historical recovery reports from before the argument boundary was hardened.
function validateNativeDbusFailureEvidence(array $evidence): array
{
    $parent = $evidence['parent'] ?? [];
    $reactivation = $parent['auto_reactivation'] ?? [];
    $terminal = $parent['service_observation']['startup_report'] ?? [];
    $state = $evidence['supervisor'] ?? [];
    $checks = [
        'native_extension' => ($evidence['native_extension'] ?? false) === true,
        'initial_capabilities' => nativeDbusFailureResponseValid($evidence['before'] ?? [], 'failure-before', 'system.capabilities'),
        'terminal_failure' => ($terminal['status'] ?? null) === 'native-runtime-wait-loop-failed'
            && str_contains((string) ($terminal['ownership_driver']['error_message'] ?? ''), 'must be of type string'),
        'penalty' => ($reactivation['termination_class'] ?? null) === 'wait-loop-failed'
            && ($reactivation['attempt'] ?? null) === 1
            && ($reactivation['launched'] ?? false) === true
            && ($reactivation['cooldown_backoff_base_ms'] ?? null) === 1000
            && ($reactivation['failure_backoff_multiplier'] ?? null) === 3
            && ($reactivation['cooldown_backoff_ms'] ?? null) === 3000,
        'cooldown_recorded' => ($evidence['admission']['cooldown_until_epoch_ms'] ?? 0)
            - ($evidence['admission']['last_attempt_epoch_ms'] ?? 0) === 3000
            && ($evidence['admission']['attempt_count'] ?? null) === 1,
        'owner_replaced' => is_string($evidence['owner_before'] ?? null)
            && preg_match("/^\\(':\\d+\\.\\d+',\\)$/", trim($evidence['owner_before'])) === 1
            && is_string($evidence['owner_after'] ?? null)
            && preg_match("/^\\(':\\d+\\.\\d+',\\)$/", trim($evidence['owner_after'])) === 1
            && $evidence['owner_before'] !== $evidence['owner_after'],
        'capabilities_recovered' => nativeDbusFailureResponseValid($evidence['after'] ?? [], 'failure-after', 'system.capabilities'),
        'runtime_recovered' => nativeDbusFailureResponseValid($evidence['runtime'] ?? [], 'failure-runtime', 'system.dbus-runtime'),
        'supervisor_reset' => ($state['attempt_count'] ?? null) === 0
            && ($state['previous_attempt_count'] ?? null) === 1
            && ($state['cooldown_until_epoch_ms'] ?? null) === 0
            && ($state['last_reset_trigger'] ?? null) === 'reactivation-stability-achieved'
            && ($state['termination_class'] ?? null) === 'wait-loop-failed'
            && ($state['failure_backoff_multiplier'] ?? null) === 3,
    ];

    return ['validated' => !in_array(false, $checks, true), 'checks' => $checks];
}

function nativeDbusFailureResponseValid(array $response, string $id, string $method): bool
{
    return ($response['request_id'] ?? null) === $id
        && array_key_exists('error', $response) && $response['error'] === null
        && ($response['result']['method'] ?? null) === $method;
}

function nativeDbusInvalidArgumentCases(): array
{
    $cases = [];
    foreach (['Call', 'GetCapabilities'] as $method) {
        foreach ([
            'array' => ["['invalid-type']"],
            'integer' => ['int32 7'],
            'boolean' => ['true'],
            'missing' => [],
            'extra' => ['valid-string', 'extra-string'],
        ] as $label => $arguments) {
            $cases[$method . '-' . $label] = ['method' => $method, 'arguments' => $arguments];
        }
    }
    return $cases;
}

function validateNativeDbusRejectionEvidence(array $evidence): array
{
    $checks = [
        'native_extension' => ($evidence['native_extension'] ?? false) === true,
        'initial_capabilities' => nativeDbusFailureResponseValid($evidence['before'] ?? [], 'failure-before', 'system.capabilities'),
        'owner_unchanged' => is_string($evidence['owner_before'] ?? null)
            && preg_match("/^\\(':\\d+\\.\\d+',\\)$/", $evidence['owner_before']) === 1
            && $evidence['owner_before'] === ($evidence['owner_after'] ?? null),
        'parent_running' => ($evidence['parent_running'] ?? false) === true,
        'no_reactivation' => ($evidence['supervisor_absent'] ?? false) === true,
        'capabilities_available' => nativeDbusFailureResponseValid($evidence['after'] ?? [], 'failure-after', 'system.capabilities'),
        'runtime_available' => nativeDbusFailureResponseValid($evidence['runtime'] ?? [], 'failure-runtime', 'system.dbus-runtime'),
    ];
    foreach (nativeDbusInvalidArgumentCases() as $label => $case) {
        $response = $evidence['rejections'][$label] ?? [];
        $checks['rejected_' . $label] = ($response['api_version'] ?? null) === '1.0'
            && ($response['request_id'] ?? null) === 'invalid-request'
            && array_key_exists('result', $response) && $response['result'] === null
            && ($response['error']['code'] ?? null) === 'VALIDATION_ERROR'
            && ($response['error']['retryable'] ?? null) === false;
    }
    return ['validated' => !in_array(false, $checks, true), 'checks' => $checks];
}

function nativeDbusFailureReadJson(string $path): array
{
    $raw = is_file($path) ? file_get_contents($path) : false;
    $value = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($value) ? $value : [];
}

function validateNativeDbusCooldownEvidence(array $evidence): array
{
    $first = $evidence['first']['auto_reactivation'] ?? [];
    $denied = $evidence['probe']['auto_reactivation'] ?? [];
    $state = $evidence['state_before'] ?? [];
    $remaining = $denied['cooldown_remaining_ms'] ?? null;
    $checks = [
        'native_extension' => ($evidence['native_extension'] ?? false) === true,
        'initial_call' => nativeDbusFailureResponseValid($evidence['before'] ?? [], 'failure-before', 'system.capabilities'),
        'first_launch' => ($first['attempted'] ?? null) === true
            && ($first['launched'] ?? null) === true && ($first['attempt'] ?? null) === 1,
        'real_replacement' => is_string($evidence['owner_before'] ?? null)
            && preg_match("/^\\(':\\d+\\.\\d+',\\)$/", $evidence['owner_before']) === 1
            && is_string($evidence['owner_replacement'] ?? null)
            && preg_match("/^\\(':\\d+\\.\\d+',\\)$/", $evidence['owner_replacement']) === 1
            && $evidence['owner_before'] !== $evidence['owner_replacement'],
        'budget_available' => ($state['attempt_count'] ?? null) === 1
            && ($state['max_attempts'] ?? null) === 3,
        'cooldown_denied' => ($denied['reason'] ?? null) === 'native-runtime-auto-reactivation-cooldown-active'
            && ($denied['attempted'] ?? null) === false && ($denied['attempt'] ?? null) === 1
            && ($denied['max_attempts'] ?? null) === 3,
        'deadline_preserved' => ($state['cooldown_until_epoch_ms'] ?? 0) > 0
            && ($state['cooldown_until_epoch_ms'] ?? 0) - ($state['last_attempt_epoch_ms'] ?? 0) === 10000
            && ($denied['cooldown_until_epoch_ms'] ?? null) === $state['cooldown_until_epoch_ms']
            && is_int($remaining) && $remaining > 0 && $remaining <= 10000,
        'state_unchanged' => $state !== [] && $state === ($evidence['state_after'] ?? null),
        'terminal_report' => ($evidence['probe']['status'] ?? null) === 'native-runtime-service-not-running'
            && ($evidence['probe']['startup_report']['status'] ?? null) === 'native-runtime-service-loop-exited'
            && ($evidence['probe_command']['exit_code'] ?? null) === 3,
        'no_owner' => ($evidence['owner_check']['exit_code'] ?? null) === 0
            && ($evidence['owner_check']['stdout'] ?? null) === '(false,)',
        'no_error' => !isset($evidence['error']),
    ];
    return ['validated' => !in_array(false, $checks, true), 'checks' => $checks];
}

function nativeDbusCooldownProbe(string $directory, string $runtime, array $evidence): array
{
    // Let the first replacement exit before starting an independent service cycle
    // against its actual durable budget. Do not manufacture supervisor state.
    $deadline = microtime(true) + 15;
    $replacementPid = 0;
    do {
        $owner = nativeDbusFailureOwner($directory, 'owner-replacement');
        if ($owner !== null && $owner !== $evidence['owner_before']) {
            $evidence['owner_replacement'] = $owner;
        }
        $evidence['first'] = nativeDbusFailureReadJson("$directory/parent.json");
        $replacementPid = (int) ($evidence['first']['pid'] ?? 0);
        if ($replacementPid > 0 && !posix_kill($replacementPid, 0)) {
            break;
        }
        usleep(100000);
    } while (microtime(true) < $deadline);
    if ($replacementPid <= 0 || posix_kill($replacementPid, 0)) {
        throw new RuntimeException('No se confirmo la salida del primer proceso reactivado');
    }
    $statePath = "$runtime/w4-system-api-native-runtime-supervisor.json";
    $evidence['state_before'] = nativeDbusFailureReadJson($statePath);
    $evidence['probe_command'] = nativeDbusFailureCommand([
        PHP_BINARY, __DIR__ . '/system_api_dbus_runtime_adapter.php', 'ServeNativeRuntimeService',
    ], $directory, 'cooldown-probe');
    $evidence['probe'] = json_decode($evidence['probe_command']['stdout'], true) ?? [];
    $evidence['state_after'] = nativeDbusFailureReadJson($statePath);
    $evidence['owner_check'] = nativeDbusFailureCommand([
        'gdbus', 'call', '--address', (string) getenv('DBUS_SESSION_BUS_ADDRESS'),
        '--timeout', '2', '--dest', 'org.freedesktop.DBus', '--object-path', '/org/freedesktop/DBus',
        '--method', 'org.freedesktop.DBus.NameHasOwner', 'org.w4.System1',
    ], $directory, 'owner-absent');
    return $evidence;
}

function nativeDbusFailureWriteJson(string $path, array $value): void
{
    if (file_put_contents($path, json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n") === false) {
        throw new RuntimeException('No se pudo escribir ' . $path);
    }
}

function nativeDbusFailureCommand(array $command, string $directory, string $name): array
{
    $process = proc_open($command, [
        0 => ['file', '/dev/null', 'r'],
        1 => ['file', "$directory/$name.stdout", 'w'],
        2 => ['file', "$directory/$name.stderr", 'w'],
    ], $pipes);
    if (!is_resource($process)) {
        throw new RuntimeException('No se pudo ejecutar ' . $command[0]);
    }
    return [
        'exit_code' => proc_close($process),
        'stdout' => trim((string) file_get_contents("$directory/$name.stdout")),
        'stderr' => trim((string) file_get_contents("$directory/$name.stderr")),
    ];
}

function nativeDbusFailureCall(string $directory, string $label, string $method, array $arguments = []): array
{
    return nativeDbusFailureCommand([
        'gdbus', 'call', '--address', (string) getenv('DBUS_SESSION_BUS_ADDRESS'),
        '--timeout', '2', '--dest', 'org.w4.System1', '--object-path', '/org/w4/System1',
        '--method', 'org.w4.System1.Manager.' . $method, ...$arguments,
    ], $directory, $label);
}

function nativeDbusFailureDecode(array $command): array
{
    $output = $command['stdout'] ?? '';
    if (($command['exit_code'] ?? 1) !== 0
        || !str_starts_with($output, "('") || !str_ends_with($output, "',)")
    ) {
        return [];
    }
    $json = strtr(substr($output, 2, -3), ["\\\\" => "\\", "\\'" => "'", "\\n" => "\n", "\\r" => "\r", "\\t" => "\t"]);
    $value = json_decode($json, true);
    return is_array($value) ? $value : [];
}

function nativeDbusFailureOwner(string $directory, string $label): ?string
{
    $result = nativeDbusFailureCommand([
        'gdbus', 'call', '--address', (string) getenv('DBUS_SESSION_BUS_ADDRESS'),
        '--timeout', '2', '--dest', 'org.freedesktop.DBus', '--object-path', '/org/freedesktop/DBus',
        '--method', 'org.freedesktop.DBus.GetNameOwner', 'org.w4.System1',
    ], $directory, $label);
    return $result['exit_code'] === 0 ? $result['stdout'] : null;
}

function nativeDbusFailureWorker(string $directory, string $scenario = 'rejection'): int
{
    if (getenv('W4_FAILURE_CHECK_WORKER') !== $directory || !getenv('DBUS_SESSION_BUS_ADDRESS')) {
        throw new RuntimeException('El worker requiere el bus privado creado por el runner');
    }
    if (preg_match('/^unix:path=([^,;]+)/', (string) getenv('DBUS_SESSION_BUS_ADDRESS'), $socket) !== 1) {
        throw new RuntimeException('Se requiere un socket privado con ruta observable');
    }
    $runtime = "$directory/runtime-artifacts";
    if (!mkdir($runtime, 0700)) {
        throw new RuntimeException('No se pudo crear el directorio de runtime');
    }
    foreach (array_keys(getenv()) as $key) {
        if (str_starts_with($key, 'W4_SYSTEM_API_')) {
            putenv($key);
        }
    }
    $environment = [
        'DBUS_SYSTEM_BUS_ADDRESS' => (string) getenv('DBUS_SESSION_BUS_ADDRESS'),
        'W4_SYSTEM_API_PROBE_SYSTEM_BUS_SOCKET' => rawurldecode($socket[1]),
        'W4_SYSTEM_API_RUNTIME_BACKEND' => 'native-dbus-extension',
        'W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR' => $runtime,
        'W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS' => '60',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE' => '1',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_MAX_ATTEMPTS' => '1',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_BACKOFF_MS' => '1000',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_FAILURE_BACKOFF_MULTIPLIER' => '3',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_RESET_STABILITY_MS' => '1000',
        'W4_SYSTEM_API_NATIVE_RUNTIME_LAUNCH_OBSERVATION_TIMEOUT_MS' => '5000',
    ];
    if ($scenario === 'cooldown') {
        $environment = array_replace($environment, [
            'W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS' => '1',
            'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_MAX_ATTEMPTS' => '3',
            'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_BACKOFF_MS' => '10000',
            'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_RESET_STABILITY_MS' => '10000',
            'W4_SYSTEM_API_NATIVE_RUNTIME_LAUNCH_OBSERVATION_TIMEOUT_MS' => '500',
        ]);
    }
    foreach ($environment as $key => $value) {
        putenv("$key=$value");
    }
    $parent = proc_open([PHP_BINARY, __DIR__ . '/system_api_dbus_runtime_adapter.php', 'ServeNativeRuntimeService'], [
        0 => ['file', '/dev/null', 'r'],
        1 => ['file', "$directory/parent.json", 'w'],
        2 => ['file', "$directory/parent.stderr", 'w'],
    ], $pipes, dirname(__DIR__));
    if (!is_resource($parent)) {
        throw new RuntimeException('No se pudo iniciar el runtime');
    }
    $evidence = ['native_extension' => extension_loaded('dbus') && (new ReflectionClass('Dbus'))->isInternal()];
    try {
        $deadline = microtime(true) + 8;
        do {
            $evidence['owner_before'] = nativeDbusFailureOwner($directory, 'owner-before');
            if ($evidence['owner_before'] !== null) {
                break;
            }
            usleep(100000);
        } while (microtime(true) < $deadline && proc_get_status($parent)['running']);
        if ($evidence['owner_before'] === null) {
            throw new RuntimeException('No se observo owner inicial en el bus privado');
        }
        $evidence['before'] = nativeDbusFailureDecode(nativeDbusFailureCall($directory, 'before', 'GetCapabilities', ['failure-before']));
        if ($scenario === 'cooldown') {
            $evidence = nativeDbusCooldownProbe($directory, $runtime, $evidence);
        } else {
            foreach (nativeDbusInvalidArgumentCases() as $label => $case) {
                $evidence['rejections'][$label] = nativeDbusFailureDecode(
                    nativeDbusFailureCall($directory, $label, $case['method'], $case['arguments'])
                );
            }
            $evidence['owner_after'] = nativeDbusFailureOwner($directory, 'owner-after');
            $evidence['after'] = nativeDbusFailureDecode(nativeDbusFailureCall($directory, 'after', 'GetCapabilities', ['failure-after']));
            $request = json_encode(['api_version' => '1.0', 'request_id' => 'failure-runtime', 'method' => 'system.dbus-runtime', 'params' => []], JSON_THROW_ON_ERROR);
            $evidence['runtime'] = nativeDbusFailureDecode(nativeDbusFailureCall($directory, 'runtime', 'Call', [$request]));
            $evidence['parent_running'] = proc_get_status($parent)['running'];
            clearstatcache();
            $evidence['supervisor_absent'] = !file_exists("$runtime/w4-system-api-native-runtime-supervisor.json");
        }
    } catch (Throwable $exception) {
        $evidence['error'] = $exception->getMessage();
    } finally {
        // Only terminate a PID whose environment belongs to this unique run.
        $pid = (int) @file_get_contents("$runtime/w4-system-api-native-runtime.pid");
        $processEnvironment = $pid > 0 ? @file_get_contents("/proc/$pid/environ") : false;
        if (is_string($processEnvironment)
            && in_array("W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR=$runtime", explode("\0", $processEnvironment), true)
        ) {
            posix_kill($pid, SIGTERM);
        }
        if (proc_get_status($parent)['running']) {
            proc_terminate($parent);
        }
        proc_close($parent);
    }
    $result = $scenario === 'cooldown'
        ? validateNativeDbusCooldownEvidence($evidence)
        : validateNativeDbusRejectionEvidence($evidence);
    $report = $result + ['kind' => 'native-dbus-private-bus-' . $scenario . '-check', 'evidence' => $evidence];
    nativeDbusFailureWriteJson("$directory/report.json", $report);
    return $result['validated'] ? 0 : 3;
}

function runNativeDbusFailureCheck(array $arguments): int
{
    if (PHP_OS_FAMILY !== 'Linux' || PHP_VERSION_ID < 80400 || !extension_loaded('dbus') || !function_exists('posix_kill')) {
        throw new RuntimeException('Requiere Linux, PHP 8.4+, dbus y posix');
    }
    if (($arguments[1] ?? null) === '--private-worker' && count($arguments) === 4
        && in_array($arguments[3], ['rejection', 'cooldown'], true)
    ) {
        return nativeDbusFailureWorker($arguments[2], $arguments[3]);
    }
    $scenario = 'rejection';
    if (($arguments[1] ?? null) === '--scenario' && in_array($arguments[2] ?? null, ['rejection', 'cooldown'], true)) {
        $scenario = $arguments[2];
        array_splice($arguments, 1, 2);
    }
    if (count($arguments) !== 1 && !(count($arguments) === 3 && $arguments[1] === '--evidence-dir')) {
        throw new RuntimeException('Uso: php run_native_dbus_failure_check.php [--scenario rejection|cooldown] [--evidence-dir PATH]');
    }
    $base = $arguments[2] ?? dirname(__DIR__) . '/build/system-api/validation/native-dbus-failure-check';
    $directory = $base . '/run-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
    if (!mkdir($directory, 0700, true)) {
        throw new RuntimeException('No se pudo crear ' . $directory);
    }
    putenv("W4_FAILURE_CHECK_WORKER=$directory");
    $busDirectory = sys_get_temp_dir() . '/w4-bus-' . bin2hex(random_bytes(6));
    if (!mkdir($busDirectory, 0700)) {
        throw new RuntimeException('No se pudo crear el directorio del bus privado');
    }
    $configuration = '<busconfig><type>session</type><listen>unix:path='
        . htmlspecialchars("$busDirectory/bus", ENT_XML1) . '</listen><auth>EXTERNAL</auth>'
        . '<policy context="default"><allow send_destination="*"/><allow receive_sender="*"/>'
        . '<allow own="*"/></policy></busconfig>';
    $previousRuntimeDirectory = getenv('XDG_RUNTIME_DIR');
    try {
        if (file_put_contents("$busDirectory/bus.conf", $configuration) === false) {
            throw new RuntimeException('No se pudo escribir la configuracion del bus');
        }
        putenv("XDG_RUNTIME_DIR=$busDirectory");
        $command = nativeDbusFailureCommand([
            'timeout', '45s', 'dbus-run-session', '--config-file', "$busDirectory/bus.conf",
            '--', PHP_BINARY, __FILE__, '--private-worker', $directory, $scenario,
        ], $directory, 'worker');
    } finally {
        putenv($previousRuntimeDirectory === false ? 'XDG_RUNTIME_DIR' : "XDG_RUNTIME_DIR=$previousRuntimeDirectory");
        @unlink("$busDirectory/bus.conf");
        @unlink("$busDirectory/bus");
        @rmdir($busDirectory);
    }
    $report = nativeDbusFailureReadJson("$directory/report.json");
    $validated = $command['exit_code'] === 0 && ($report['validated'] ?? false) === true;
    echo json_encode([
        'validated' => $validated, 'worker_exit_code' => $command['exit_code'],
        'report_path' => "$directory/report.json", 'checks' => $report['checks'] ?? [],
        'error' => $report['evidence']['error'] ?? $command['stderr'],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    return $validated ? 0 : 3;
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try {
        exit(runNativeDbusFailureCheck($argv));
    } catch (Throwable $exception) {
        fwrite(STDERR, $exception->getMessage() . "\n");
        exit(3);
    }
}
