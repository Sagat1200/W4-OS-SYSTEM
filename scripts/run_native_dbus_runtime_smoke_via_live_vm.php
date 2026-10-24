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
 * @param array<string, mixed> $artifact
 * @return array<string, mixed>
 */
function buildArtifactSummary(array $artifact): array
{
    $summary = [
        'ok' => $artifact['ok'] ?? null,
        'checks' => [],
    ];

    $checks = $artifact['checks'] ?? null;
    if (!is_array($checks)) {
        return $summary;
    }

    foreach ($checks as $label => $probeResult) {
        if (!is_array($probeResult)) {
            continue;
        }

        $summary['checks'][$label] = [
            'exit_code' => $probeResult['exit_code'] ?? null,
        ];
    }

    return $summary;
}

try {
    $arguments = $argv ?? [];

    $vmName = null;
    $repoHostPath = dirname(__DIR__);
    $sharedFolderName = 'w4repo';
    $mountPoint = '/mnt/w4repo';
    $bindingTarget = '/';
    $prepareWaitMs = 1800;
    $smokeWaitSeconds = 45;
    $checkOnly = false;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--check-only') {
            $checkOnly = true;
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

            case '--smoke-wait-seconds':
                $smokeWaitSeconds = (int) $value;
                break;

            default:
                throw new RuntimeException(sprintf('Argumento no reconocido: %s', $argument));
        }
    }

    if ($vmName === null || trim($vmName) === '') {
        throw new RuntimeException('Debe indicar --vm-name');
    }

    if ($prepareWaitMs < 0) {
        throw new RuntimeException('--prepare-wait-ms no puede ser negativo');
    }

    if ($smokeWaitSeconds < 1) {
        throw new RuntimeException('--smoke-wait-seconds debe ser mayor que cero');
    }

    $repoHostPath = rtrim($repoHostPath, "\\/\t\n\r\0\x0B");
    if ($repoHostPath === '' || !is_dir($repoHostPath)) {
        throw new RuntimeException(sprintf('Ruta de repo invalida: %s', $repoHostPath));
    }

    $phpBinary = findPhpBinary();
    $vboxManage = findVBoxManagePath();
    $prepareScriptPath = $repoHostPath . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'prepare_native_dbus_live_vm.php';
    if (!is_file($prepareScriptPath)) {
        throw new RuntimeException(sprintf('No se encontro el helper de preparacion: %s', $prepareScriptPath));
    }

    $dbusModuleGuestPath = rtrim($mountPoint, '/') . '/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so';
    $runtimeSmokeScriptGuestPath = rtrim($mountPoint, '/') . '/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_runtime_smoke.php';
    $artifactHostPath = $repoHostPath . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'validation' . DIRECTORY_SEPARATOR . 'native-dbus-real-host' . DIRECTORY_SEPARATOR . 'runtime-check' . DIRECTORY_SEPARATOR . 'guest-artifacts' . DIRECTORY_SEPARATOR . 'runtime-smoke' . DIRECTORY_SEPARATOR . 'check-native-dbus-runtime-smoke.json';

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

    $smokeCommand = sprintf(
        'sudo W4_SYSTEM_API_REMOTE_ROOT_DIR=%1$s W4_SYSTEM_API_REMOTE_DBUS_MODULE=%2$s php -d extension=%2$s %3$s',
        $mountPoint,
        $dbusModuleGuestPath,
        $runtimeSmokeScriptGuestPath
    );

    $payload = [
        'vm_name' => $vmName,
        'repo_host_path' => $repoHostPath,
        'mount_point' => $mountPoint,
        'shared_folder_name' => $sharedFolderName,
        'binding_target' => $bindingTarget,
        'prepare_wait_ms' => $prepareWaitMs,
        'smoke_wait_seconds' => $smokeWaitSeconds,
        'prepare_command' => $prepareCommand,
        'smoke_command' => $smokeCommand,
        'artifact_host_path' => $artifactHostPath,
        'vboxmanage_path' => $vboxManage,
    ];

    if ($checkOnly) {
        $payload['status'] = 'ready';
        printJson($payload);
        exit(0);
    }

    $prepareOutput = runProcess($prepareCommand);
    $prepareResult = json_decode($prepareOutput, true);
    if (!is_array($prepareResult)) {
        throw new RuntimeException('El helper de preparacion no devolvio JSON valido');
    }

    $artifactTimestamp = time();
    sendVBoxText($vboxManage, $vmName, $smokeCommand);
    $artifact = waitForArtifactAfter($artifactHostPath, $smokeWaitSeconds, $artifactTimestamp);
    if (!is_array($artifact)) {
        throw new RuntimeException(sprintf('No se pudo leer el artefacto del runtime smoke: %s', $artifactHostPath));
    }

    $payload['status'] = 'completed';
    $payload['prepare_result'] = $prepareResult;
    $payload['runtime_smoke'] = buildArtifactSummary($artifact);
    printJson($payload);
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
