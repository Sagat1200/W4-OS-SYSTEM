<?php

declare(strict_types=1);

/**
 * This probe uses a private bus: never send the malformed call to the host bus.
 * No synthetic driver or runtime class override is used.
 */
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

function nativeDbusFailureReadJson(string $path): array
{
    $raw = is_file($path) ? file_get_contents($path) : false;
    $value = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($value) ? $value : [];
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

function nativeDbusFailureWorker(string $directory): int
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
        'W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS' => '12',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE' => '1',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_MAX_ATTEMPTS' => '1',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_BACKOFF_MS' => '1000',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_FAILURE_BACKOFF_MULTIPLIER' => '3',
        'W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_RESET_STABILITY_MS' => '1000',
        'W4_SYSTEM_API_NATIVE_RUNTIME_LAUNCH_OBSERVATION_TIMEOUT_MS' => '5000',
    ];
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
        // The native dispatcher passes an array to the string callback parameter.
        $evidence['injection'] = nativeDbusFailureCall($directory, 'injection', 'Call', ["['invalid-type']"]);
        $deadline = microtime(true) + 20;
        do {
            $state = nativeDbusFailureReadJson("$runtime/w4-system-api-native-runtime-supervisor.json");
            if (($state['attempt_count'] ?? null) === 1) {
                $evidence['admission'] = $state;
            }
            $evidence['parent'] = nativeDbusFailureReadJson("$directory/parent.json");
            if ($evidence['parent'] !== []) {
                break;
            }
            usleep(20000);
        } while (microtime(true) < $deadline);
        $evidence['supervisor'] = nativeDbusFailureReadJson("$runtime/w4-system-api-native-runtime-supervisor.json");
        $evidence['owner_after'] = nativeDbusFailureOwner($directory, 'owner-after');
        $evidence['after'] = nativeDbusFailureDecode(nativeDbusFailureCall($directory, 'after', 'GetCapabilities', ['failure-after']));
        $request = json_encode(['api_version' => '1.0', 'request_id' => 'failure-runtime', 'method' => 'system.dbus-runtime', 'params' => []], JSON_THROW_ON_ERROR);
        $evidence['runtime'] = nativeDbusFailureDecode(nativeDbusFailureCall($directory, 'runtime', 'Call', [$request]));
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
    $result = validateNativeDbusFailureEvidence($evidence);
    $report = $result + ['kind' => 'native-dbus-private-bus-failure-check', 'evidence' => $evidence];
    nativeDbusFailureWriteJson("$directory/report.json", $report);
    return $result['validated'] ? 0 : 3;
}

function runNativeDbusFailureCheck(array $arguments): int
{
    if (PHP_OS_FAMILY !== 'Linux' || PHP_VERSION_ID < 80400 || !extension_loaded('dbus') || !function_exists('posix_kill')) {
        throw new RuntimeException('Requiere Linux, PHP 8.4+, dbus y posix');
    }
    if (($arguments[1] ?? null) === '--private-worker' && count($arguments) === 3) {
        return nativeDbusFailureWorker($arguments[2]);
    }
    if (count($arguments) !== 1 && !(count($arguments) === 3 && $arguments[1] === '--evidence-dir')) {
        throw new RuntimeException('Uso: php run_native_dbus_failure_check.php [--evidence-dir PATH]');
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
            '--', PHP_BINARY, __FILE__, '--private-worker', $directory,
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
