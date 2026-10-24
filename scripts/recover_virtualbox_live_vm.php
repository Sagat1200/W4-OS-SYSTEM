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

function isVmRunning(string $vboxManage, string $vmName): bool
{
    $output = sendVBoxCommand([$vboxManage, 'list', 'runningvms']);

    return str_contains($output, '"' . $vmName . '"');
}

/**
 * @return array<string, string>
 */
function readVmInfo(string $vboxManage, string $vmName): array
{
    $raw = sendVBoxCommand([$vboxManage, 'showvminfo', $vmName, '--machinereadable']);
    $lines = preg_split('/\r?\n/', $raw) ?: [];
    $info = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $info[$key] = trim($value, "\"");
    }

    return $info;
}

/**
 * @return array<string, string>
 */
function waitForGuestAdditionsRunLevel(
    string $vboxManage,
    string $vmName,
    int $timeoutSeconds
): array {
    $elapsed = 0;
    while ($elapsed <= $timeoutSeconds) {
        $info = readVmInfo($vboxManage, $vmName);
        if (($info['VMState'] ?? null) === 'running' && ($info['GuestAdditionsRunLevel'] ?? null) === '1') {
            return $info;
        }

        sleep(2);
        $elapsed += 2;
    }

    return readVmInfo($vboxManage, $vmName);
}

/**
 * @param array<string, mixed> $payload
 */
function printJson(array $payload): void
{
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
}

try {
    $arguments = $argv ?? [];

    $vmName = null;
    $waitSeconds = 90;
    $settleSeconds = 25;
    $screenshotPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api'
        . DIRECTORY_SEPARATOR . 'validation' . DIRECTORY_SEPARATOR . 'native-dbus-real-host'
        . DIRECTORY_SEPARATOR . 'runtime-check' . DIRECTORY_SEPARATOR . 'guest-artifacts'
        . DIRECTORY_SEPARATOR . 'vm-recovery' . DIRECTORY_SEPARATOR . 'post-reset-console.png';
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

            case '--wait-seconds':
                $waitSeconds = (int) $value;
                break;

            case '--settle-seconds':
                $settleSeconds = (int) $value;
                break;

            case '--screenshot-path':
                $screenshotPath = $value;
                break;

            default:
                throw new RuntimeException(sprintf('Argumento no reconocido: %s', $argument));
        }
    }

    if ($vmName === null || trim($vmName) === '') {
        throw new RuntimeException('Debe indicar --vm-name');
    }

    if ($waitSeconds < 1) {
        throw new RuntimeException('--wait-seconds debe ser mayor que cero');
    }

    if ($settleSeconds < 0) {
        throw new RuntimeException('--settle-seconds no puede ser negativo');
    }

    $vboxManage = findVBoxManagePath();
    $payload = [
        'vm_name' => $vmName,
        'wait_seconds' => $waitSeconds,
        'settle_seconds' => $settleSeconds,
        'screenshot_path' => $screenshotPath,
        'vboxmanage_path' => $vboxManage,
    ];

    if ($checkOnly) {
        $payload['status'] = 'ready';
        printJson($payload);
        exit(0);
    }

    if (!isVmRunning($vboxManage, $vmName)) {
        throw new RuntimeException(sprintf('La VM no esta corriendo: %s', $vmName));
    }

    $before = readVmInfo($vboxManage, $vmName);
    $payload['before'] = [
        'vm_state' => $before['VMState'] ?? null,
        'session_name' => $before['SessionName'] ?? null,
        'guest_additions_run_level' => $before['GuestAdditionsRunLevel'] ?? null,
    ];

    sendVBoxCommand([$vboxManage, 'controlvm', $vmName, 'reset']);
    $after = waitForGuestAdditionsRunLevel($vboxManage, $vmName, $waitSeconds);

    if ($settleSeconds > 0) {
        sleep($settleSeconds);
        $after = readVmInfo($vboxManage, $vmName);
    }

    $screenshotDir = dirname($screenshotPath);
    if ($screenshotDir !== '' && !is_dir($screenshotDir)) {
        if (!mkdir($screenshotDir, 0777, true) && !is_dir($screenshotDir)) {
            throw new RuntimeException(sprintf('No se pudo crear el directorio para screenshot: %s', $screenshotDir));
        }
    }

    sendVBoxCommand([$vboxManage, 'controlvm', $vmName, 'screenshotpng', $screenshotPath]);

    $payload['after'] = [
        'vm_state' => $after['VMState'] ?? null,
        'session_name' => $after['SessionName'] ?? null,
        'guest_additions_run_level' => $after['GuestAdditionsRunLevel'] ?? null,
    ];
    if (($after['VMState'] ?? null) !== 'running' || ($after['GuestAdditionsRunLevel'] ?? null) !== '1') {
        throw new RuntimeException(sprintf(
            'La VM no quedo lista tras el reset: VMState=%s GuestAdditionsRunLevel=%s',
            (string) ($after['VMState'] ?? 'unknown'),
            (string) ($after['GuestAdditionsRunLevel'] ?? 'unknown')
        ));
    }

    $payload['status'] = 'recovered';

    printJson($payload);
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
