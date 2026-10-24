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

function quoteForBash(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

/**
 * @param array<string, mixed> $payload
 */
function printJson(array $payload): void
{
    fwrite(STDOUT, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
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

function ensureSharedFolder(
    string $vboxManage,
    string $vmName,
    string $sharedFolderName,
    string $repoHostPath,
    string $mountPoint
): void {
    try {
        sendVBoxCommand([
            $vboxManage,
            'sharedfolder',
            'add',
            $vmName,
            '--name=' . $sharedFolderName,
            '--hostpath=' . $repoHostPath,
            '--transient',
            '--automount',
            '--auto-mount-point=' . $mountPoint,
        ]);
    } catch (Throwable $exception) {
        $message = $exception->getMessage();
        if (!str_contains($message, "Shared folder named '{$sharedFolderName}' already exists")) {
            throw $exception;
        }
    }
}

function isVmRunning(string $vboxManage, string $vmName): bool
{
    $output = sendVBoxCommand([$vboxManage, 'list', 'runningvms']);

    return str_contains($output, '"' . $vmName . '"');
}

/**
 * @return list<array{label:string,command:string}>
 */
function buildPreparationSteps(
    string $sharedFolderName,
    string $mountPoint,
    string $bindingTarget,
    string $bundleScriptPath
): array {
    $quotedMountPoint = quoteForBash($mountPoint);
    $quotedSharedFolder = quoteForBash($sharedFolderName);
    $quotedBundleScript = quoteForBash($bundleScriptPath);
    $quotedBindingTarget = quoteForBash($bindingTarget);

    return [
        [
            'label' => 'mount_shared_repo',
            'command' => sprintf(
                'sudo mkdir -p %1$s && (mountpoint -q %1$s || sudo mount -t vboxsf -o uid=1000,gid=1000 %2$s %1$s)',
                $quotedMountPoint,
                $quotedSharedFolder
            ),
        ],
        [
            'label' => 'verify_binding_bundle',
            'command' => sprintf('test -f %s', $quotedBundleScript),
        ],
        [
            'label' => 'apply_binding_bundle',
            'command' => sprintf('sudo bash %s %s', $quotedBundleScript, $quotedBindingTarget),
        ],
        [
            'label' => 'restart_dbus',
            'command' => 'sudo systemctl restart dbus',
        ],
    ];
}

try {
    $arguments = $argv ?? [];

    $vmName = null;
    $sharedFolderName = 'w4repo';
    $mountPoint = '/mnt/w4repo';
    $bindingTarget = '/';
    $waitMs = 1800;
    $checkOnly = false;
    $repoHostPath = dirname(__DIR__);
    $bundleRelativePath = 'build/system-api/validation/native-dbus-real-host/system-api-dbus-binding-bundle/apply-binding.sh';

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

            case '--wait-ms':
                $waitMs = (int) $value;
                break;

            case '--bundle-relative-path':
                $bundleRelativePath = $value;
                break;

            default:
                throw new RuntimeException(sprintf('Argumento no reconocido: %s', $argument));
        }
    }

    if ($vmName === null || trim($vmName) === '') {
        throw new RuntimeException('Debe indicar --vm-name');
    }

    if ($waitMs < 0) {
        throw new RuntimeException('--wait-ms no puede ser negativo');
    }

    $repoHostPath = rtrim($repoHostPath, "\\/\t\n\r\0\x0B");
    if ($repoHostPath === '' || !is_dir($repoHostPath)) {
        throw new RuntimeException(sprintf('Ruta de repo invalida: %s', $repoHostPath));
    }

    $bundleScriptHostPath = $repoHostPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $bundleRelativePath);
    if (!is_file($bundleScriptHostPath)) {
        throw new RuntimeException(sprintf('No se encontro el bundle de binding: %s', $bundleScriptHostPath));
    }

    $bundleScriptGuestPath = rtrim($mountPoint, '/') . '/' . ltrim($bundleRelativePath, '/');

    $vboxManage = findVBoxManagePath();
    $steps = buildPreparationSteps($sharedFolderName, $mountPoint, $bindingTarget, $bundleScriptGuestPath);

    $payload = [
        'vm_name' => $vmName,
        'shared_folder_name' => $sharedFolderName,
        'repo_host_path' => $repoHostPath,
        'mount_point' => $mountPoint,
        'binding_target' => $bindingTarget,
        'bundle_relative_path' => $bundleRelativePath,
        'bundle_guest_path' => $bundleScriptGuestPath,
        'wait_ms' => $waitMs,
        'vboxmanage_path' => $vboxManage,
        'steps' => array_map(
            static fn (array $step): array => [
                'label' => $step['label'],
                'command' => $step['command'],
            ],
            $steps
        ),
    ];

    if ($checkOnly) {
        $payload['status'] = 'ready';
        printJson($payload);
        exit(0);
    }

    if (!isVmRunning($vboxManage, $vmName)) {
        throw new RuntimeException(sprintf('La VM %s no esta corriendo', $vmName));
    }

    ensureSharedFolder($vboxManage, $vmName, $sharedFolderName, $repoHostPath, $mountPoint);

    $executedSteps = [];
    foreach ($steps as $step) {
        sendVBoxText($vboxManage, $vmName, $step['command']);
        $executedSteps[] = $step['label'];
        if ($waitMs > 0) {
            usleep($waitMs * 1000);
        }
    }

    $payload['status'] = 'prepared';
    $payload['executed_steps'] = $executedSteps;
    printJson($payload);
} catch (Throwable $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
