<?php

declare(strict_types=1);

/**
 * @param list<string> $command
 */
function runProcess(array $command): string
{
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new RuntimeException(sprintf('No se pudo iniciar el proceso: %s', implode(' ', $command)));
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        $message = trim((string) $stderr);
        if ($message === '') {
            $message = trim((string) $stdout);
        }
        if ($message === '') {
            $message = sprintf('Fallo al ejecutar: %s', implode(' ', $command));
        }

        throw new RuntimeException($message);
    }

    return trim((string) $stdout);
}

function findPhpBinary(): string
{
    $binary = PHP_BINARY;
    if (is_string($binary) && $binary !== '' && is_file($binary)) {
        return $binary;
    }

    return 'php';
}

function findVBoxManagePath(): string
{
    $override = getenv('W4_VBOXMANAGE_PATH');
    if (is_string($override) && trim($override) !== '') {
        return trim($override);
    }

    $candidates = [
        'C:\\Program Files\\Oracle\\VirtualBox\\VBoxManage.exe',
        'C:\\Program Files (x86)\\Oracle\\VirtualBox\\VBoxManage.exe',
    ];

    foreach ($candidates as $candidate) {
        if (is_file($candidate)) {
            return $candidate;
        }
    }

    $resolved = runProcess(['where', 'VBoxManage.exe']);
    $lines = preg_split('/\r?\n/', $resolved) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            return $line;
        }
    }

    throw new RuntimeException('No se encontro VBoxManage.exe en este entorno Windows');
}

/**
 * @param list<string> $arguments
 */
function sendVBoxCommand(array $arguments): string
{
    return runProcess($arguments);
}

function sendVBoxText(string $vboxManage, string $vmName, string $text): void
{
    sendVBoxCommand([$vboxManage, 'controlvm', $vmName, 'keyboardputstring', $text]);
    sendVBoxCommand([$vboxManage, 'controlvm', $vmName, 'keyboardputscancode', 'e0', '1c', 'e0', '9c']);
}

/**
 * @param array<string, mixed> $payload
 */
function printJson(array $payload): void
{
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

/**
 * @return array<string, mixed>|null
 */
function readJsonFile(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

function waitForArtifactAfter(string $path, int $timeoutSeconds, int $previousTimestamp): ?array
{
    $elapsed = 0;
    while ($elapsed <= $timeoutSeconds) {
        clearstatcache(true, $path);
        if (is_file($path)) {
            $mtime = filemtime($path);
            if (is_int($mtime) && $mtime >= $previousTimestamp) {
                $decoded = readJsonFile($path);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        sleep(1);
        $elapsed++;
    }

    return null;
}

/**
 * @return array<string, mixed>
 */
function decodeJsonObject(string $raw, string $errorMessage): array
{
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new RuntimeException($errorMessage);
    }

    return $decoded;
}

/**
 * @param array<string, mixed> $artifact
 * @return array<string, mixed>
 */
function summarizeLaunchProcess(array $artifact): array
{
    return [
        'ok' => $artifact['ok'] ?? null,
        'launch_ok' => $artifact['launch_ok'] ?? null,
        'service_reachable_ok' => $artifact['service_reachable_ok'] ?? null,
    ];
}

/**
 * @param array<string, mixed> $artifact
 * @return array<string, mixed>
 */
function summarizeOrdering(array $artifact): array
{
    $summary = [
        'ok' => $artifact['ok'] ?? null,
        'modes' => [],
    ];

    $modes = $artifact['modes'] ?? null;
    if (!is_array($modes)) {
        return $summary;
    }

    foreach ($modes as $label => $modeResult) {
        if (!is_array($modeResult)) {
            continue;
        }

        $summary['modes'][$label] = [
            'expectations_met' => $modeResult['expectations_met'] ?? null,
        ];
    }

    return $summary;
}

/**
 * @param array<string, mixed> $artifact
 * @return array<string, mixed>
 */
function summarizeHeapProbe(array $artifact): array
{
    return [
        'ok' => $artifact['ok'] ?? null,
        'scenario_count' => is_array($artifact['scenarios'] ?? null) ? count($artifact['scenarios']) : null,
    ];
}

try {
    $arguments = $argv ?? [];

    $vmName = null;
    $repoHostPath = dirname(__DIR__);
    $sharedFolderName = 'w4repo';
    $mountPoint = '/mnt/w4repo';
    $bindingTarget = '/';
    $prepareWaitMs = 1800;
    $launchWaitSeconds = 25;
    $orderingWaitSeconds = 40;
    $heapWaitSeconds = 40;
    $vmRecoveryEnabled = true;
    $recoveryWaitSeconds = 90;
    $recoverySettleSeconds = 25;
    $checkOnly = false;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--check-only') {
            $checkOnly = true;
            continue;
        }

        if ($argument === '--skip-vm-recovery') {
            $vmRecoveryEnabled = false;
            continue;
        }

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new RuntimeException(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--vm-name':
                $vmName = $value;
                break;

            case '--repo-host-path':
                $repoHostPath = $value;
                break;

            case '--shared-folder-name':
                $sharedFolderName = $value;
                break;

            case '--mount-point':
                $mountPoint = $value;
                break;

            case '--binding-target':
                $bindingTarget = $value;
                break;

            case '--prepare-wait-ms':
                $prepareWaitMs = (int) $value;
                break;

            case '--launch-wait-seconds':
                $launchWaitSeconds = (int) $value;
                break;

            case '--ordering-wait-seconds':
                $orderingWaitSeconds = (int) $value;
                break;

            case '--heap-wait-seconds':
                $heapWaitSeconds = (int) $value;
                break;

            case '--recovery-wait-seconds':
                $recoveryWaitSeconds = (int) $value;
                break;

            case '--recovery-settle-seconds':
                $recoverySettleSeconds = (int) $value;
                break;

            default:
                throw new RuntimeException(sprintf('Argumento no reconocido: %s', $argument));
        }
    }

    if ($vmName === null || trim($vmName) === '') {
        throw new RuntimeException('Debe indicar --vm-name');
    }

    $repoHostPath = rtrim($repoHostPath, "\\/\t\n\r\0\x0B");
    if ($repoHostPath === '' || !is_dir($repoHostPath)) {
        throw new RuntimeException(sprintf('Ruta de repo invalida: %s', $repoHostPath));
    }

    if ($recoveryWaitSeconds < 1) {
        throw new RuntimeException('--recovery-wait-seconds debe ser mayor que cero');
    }

    if ($recoverySettleSeconds < 0) {
        throw new RuntimeException('--recovery-settle-seconds no puede ser negativo');
    }

    $phpBinary = findPhpBinary();
    $vboxManage = findVBoxManagePath();
    $prepareScriptPath = $repoHostPath . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'prepare_native_dbus_live_vm.php';
    $recoveryScriptPath = $repoHostPath . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'recover_virtualbox_live_vm.php';
    if (!is_file($prepareScriptPath)) {
        throw new RuntimeException(sprintf('No se encontro el helper de preparacion: %s', $prepareScriptPath));
    }
    if (!is_file($recoveryScriptPath)) {
        throw new RuntimeException(sprintf('No se encontro el helper de recuperacion: %s', $recoveryScriptPath));
    }

    $dbusModuleGuestPath = rtrim($mountPoint, '/') . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so';
    $runtimeCheckGuestRoot = rtrim($mountPoint, '/') . '/build/system-api/validation/native-dbus-real-host/runtime-check';

    $launchScriptGuestPath = $runtimeCheckGuestRoot . '/check_native_dbus_launch_process.php';
    $orderingScriptGuestPath = $runtimeCheckGuestRoot . '/check_native_dbus_method_probe_ordering.php';
    $heapScriptGuestPath = $runtimeCheckGuestRoot . '/probe_native_dbus_heap_after_request_name.php';

    $prepareCommand = [
        $phpBinary,
        $prepareScriptPath,
        '--vm-name',
        $vmName,
        '--repo-host-path',
        $repoHostPath,
        '--shared-folder-name',
        $sharedFolderName,
        '--mount-point',
        $mountPoint,
        '--binding-target',
        $bindingTarget,
        '--wait-ms',
        (string) $prepareWaitMs,
    ];

    $recoveryCommand = [
        $phpBinary,
        $recoveryScriptPath,
        '--vm-name',
        $vmName,
        '--wait-seconds',
        (string) $recoveryWaitSeconds,
        '--settle-seconds',
        (string) $recoverySettleSeconds,
    ];

    $probes = [
        'launch_process' => [
            'command' => sprintf(
                'sudo env W4_SYSTEM_API_REMOTE_ROOT_DIR=%1$s W4_SYSTEM_API_REMOTE_DBUS_MODULE=%2$s php -d extension=%2$s %3$s',
                $mountPoint,
                $dbusModuleGuestPath,
                $launchScriptGuestPath
            ),
            'artifact_host_path' => $repoHostPath . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api'
                . DIRECTORY_SEPARATOR . 'validation' . DIRECTORY_SEPARATOR . 'native-dbus-real-host'
                . DIRECTORY_SEPARATOR . 'runtime-check' . DIRECTORY_SEPARATOR . 'guest-artifacts'
                . DIRECTORY_SEPARATOR . 'launch-process' . DIRECTORY_SEPARATOR . 'check-native-dbus-launch-process.json',
            'wait_seconds' => $launchWaitSeconds,
            'summary_callback' => 'summarizeLaunchProcess',
        ],
        'method_probe_ordering' => [
            'command' => sprintf(
                'sudo env W4_SYSTEM_API_REMOTE_ROOT_DIR=%1$s W4_SYSTEM_API_REMOTE_DBUS_MODULE=%2$s php -d extension=%2$s %3$s',
                $mountPoint,
                $dbusModuleGuestPath,
                $orderingScriptGuestPath
            ),
            'artifact_host_path' => $repoHostPath . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api'
                . DIRECTORY_SEPARATOR . 'validation' . DIRECTORY_SEPARATOR . 'native-dbus-real-host'
                . DIRECTORY_SEPARATOR . 'runtime-check' . DIRECTORY_SEPARATOR . 'guest-artifacts'
                . DIRECTORY_SEPARATOR . 'method-probe-ordering' . DIRECTORY_SEPARATOR . 'check-native-dbus-method-probe-ordering.json',
            'wait_seconds' => $orderingWaitSeconds,
            'summary_callback' => 'summarizeOrdering',
        ],
        'heap_after_request_name' => [
            'command' => sprintf(
                'sudo env W4_SYSTEM_API_REMOTE_ROOT_DIR=%1$s W4_SYSTEM_API_REMOTE_DBUS_MODULE=%2$s php -d extension=%2$s %3$s',
                $mountPoint,
                $dbusModuleGuestPath,
                $heapScriptGuestPath
            ),
            'artifact_host_path' => $repoHostPath . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api'
                . DIRECTORY_SEPARATOR . 'validation' . DIRECTORY_SEPARATOR . 'native-dbus-real-host'
                . DIRECTORY_SEPARATOR . 'runtime-check' . DIRECTORY_SEPARATOR . 'guest-artifacts'
                . DIRECTORY_SEPARATOR . 'heap-after-request-name' . DIRECTORY_SEPARATOR . 'probe-native-dbus-heap-after-request-name.json',
            'wait_seconds' => $heapWaitSeconds,
            'summary_callback' => 'summarizeHeapProbe',
        ],
    ];

    $payload = [
        'vm_name' => $vmName,
        'repo_host_path' => $repoHostPath,
        'mount_point' => $mountPoint,
        'shared_folder_name' => $sharedFolderName,
        'binding_target' => $bindingTarget,
        'prepare_wait_ms' => $prepareWaitMs,
        'vm_recovery_enabled' => $vmRecoveryEnabled,
        'recovery_wait_seconds' => $recoveryWaitSeconds,
        'recovery_settle_seconds' => $recoverySettleSeconds,
        'prepare_command' => $prepareCommand,
        'recovery_command' => $recoveryCommand,
        'probes' => array_map(
            static fn (array $probe): array => [
                'command' => $probe['command'],
                'artifact_host_path' => $probe['artifact_host_path'],
                'wait_seconds' => $probe['wait_seconds'],
            ],
            $probes
        ),
        'vboxmanage_path' => $vboxManage,
    ];

    if ($checkOnly) {
        $payload['status'] = 'ready';
        printJson($payload);
        exit(0);
    }

    $prepareOutput = runProcess($prepareCommand);
    $prepareResult = decodeJsonObject($prepareOutput, 'El helper de preparacion no devolvio JSON valido');

    $payload['prepare_result'] = $prepareResult;
    $payload['results'] = [];
    $recoveryAttempted = false;

    foreach ($probes as $label => $probe) {
        $artifactPath = $probe['artifact_host_path'];
        clearstatcache(true, $artifactPath);
        $previousTimestamp = is_file($artifactPath) && is_int(filemtime($artifactPath))
            ? (int) filemtime($artifactPath) + 1
            : time();

        sendVBoxText($vboxManage, $vmName, $probe['command']);
        $artifact = waitForArtifactAfter($artifactPath, $probe['wait_seconds'], $previousTimestamp);
        if (!is_array($artifact) && $vmRecoveryEnabled && !$recoveryAttempted) {
            $recoveryAttempted = true;
            $recoveryOutput = runProcess($recoveryCommand);
            $recoveryResult = decodeJsonObject($recoveryOutput, 'El helper de recuperacion no devolvio JSON valido');
            $payload['recovery_attempted'] = true;
            $payload['recovery_result'] = $recoveryResult;

            $prepareOutput = runProcess($prepareCommand);
            $prepareResult = decodeJsonObject($prepareOutput, 'El helper de preparacion no devolvio JSON valido tras la recuperacion');
            $payload['prepare_result_after_recovery'] = $prepareResult;

            clearstatcache(true, $artifactPath);
            $previousTimestamp = is_file($artifactPath) && is_int(filemtime($artifactPath))
                ? (int) filemtime($artifactPath) + 1
                : time();

            sendVBoxText($vboxManage, $vmName, $probe['command']);
            $artifact = waitForArtifactAfter($artifactPath, $probe['wait_seconds'], $previousTimestamp);
        }

        if (!is_array($artifact)) {
            throw new RuntimeException(sprintf('No se pudo leer el artefacto del probe %s: %s', $label, $artifactPath));
        }

        $summaryCallback = $probe['summary_callback'];
        $payload['results'][$label] = $summaryCallback($artifact);
    }

    $payload['status'] = 'completed';
    printJson($payload);
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
