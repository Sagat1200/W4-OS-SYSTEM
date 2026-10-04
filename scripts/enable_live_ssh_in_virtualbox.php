<?php

declare(strict_types=1);

use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

require_once __DIR__ . '/lib/ManifestToolkit.php';

/**
 * @return array<string, mixed>|null
 */
function readJsonFixtureFromEnv(string $variable): ?array
{
    $raw = getenv($variable);
    if ($raw === false || trim($raw) === '') {
        return null;
    }

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('Fixture JSON invalido en %s: %s', $variable, $exception->getMessage()));
    }

    if (!is_array($data)) {
        throw new ValidationError(sprintf('%s debe contener un objeto o arreglo JSON', $variable));
    }

    return $data;
}

/**
 * @param list<string> $command
 */
function runProcess(array $command): string
{
    $fixtureMap = readJsonFixtureFromEnv('W4_VBOX_LIVE_SSH_COMMAND_MAP_JSON');
    $fixtureKey = json_encode(array_values($command), JSON_THROW_ON_ERROR);

    if ($fixtureMap !== null && array_key_exists($fixtureKey, $fixtureMap)) {
        $fixture = $fixtureMap[$fixtureKey];
        if (is_array($fixture)) {
            $stdout = $fixture['stdout'] ?? '';
            $exitCode = (int) ($fixture['exit_code'] ?? 0);
            $stderr = (string) ($fixture['stderr'] ?? '');
        } else {
            $stdout = $fixture;
            $exitCode = 0;
            $stderr = '';
        }

        if (!is_scalar($stdout) || !is_scalar($stderr)) {
            throw new ValidationError(sprintf('Fixture invalido para %s', $fixtureKey));
        }

        if ($exitCode !== 0) {
            throw new ValidationError(trim((string) $stderr) !== '' ? (string) $stderr : sprintf('Fallo al ejecutar: %s', implode(' ', $command)));
        }

        return trim((string) $stdout);
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process)) {
        throw new ValidationError(sprintf('No se pudo iniciar el proceso: %s', implode(' ', $command)));
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

        throw new ValidationError($message);
    }

    return trim((string) $stdout);
}

function findVBoxManagePath(): string
{
    $override = getenv('W4_VBOXMANAGE_PATH');
    if ($override !== false && trim($override) !== '') {
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

    $resolved = trim(runProcess(['where', 'VBoxManage.exe']));
    if ($resolved !== '') {
        $lines = preg_split('/\r?\n/', $resolved) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '') {
                return $line;
            }
        }
    }

    throw new ValidationError('No se encontro VBoxManage.exe en este entorno Windows');
}

function quoteForBash(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

/**
 * @return list<array{label: string, command: string}>
 */
function buildLiveSetupCommands(string $liveUser, string $livePassword): array
{
    return [
        [
            'label' => 'ensure_sshd_runtime_dirs',
            'command' => 'sudo mkdir -p /run/sshd /etc/ssh/sshd_config.d',
        ],
        [
            'label' => 'set_live_password',
            'command' => sprintf(
                'printf %%s %s | sudo /usr/sbin/chpasswd',
                quoteForBash($liveUser . ':' . $livePassword)
            ),
        ],
        [
            'label' => 'write_password_auth_override',
            'command' => "printf 'PasswordAuthentication yes\nKbdInteractiveAuthentication yes\nPubkeyAuthentication yes\nAuthenticationMethods any\nUsePAM yes\nPermitEmptyPasswords no\n' | sudo tee /etc/ssh/sshd_config.d/99-w4-live-password.conf >/dev/null",
        ],
        [
            'label' => 'allow_ssh_in_ufw',
            'command' => 'sudo ufw allow 22/tcp',
        ],
        [
            'label' => 'restart_ssh_service',
            'command' => 'sudo sshd -t && sudo systemctl restart ssh',
        ],
    ];
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
    // En la tty de las imagenes Server el Enter extendido es el que ejecuta el comando de forma fiable.
    sendVBoxCommand([$vboxManage, 'controlvm', $vmName, 'keyboardputscancode', 'e0', '1c', 'e0', '9c']);
}

/**
 * @param array<string, mixed> $payload
 */
function printJson(array $payload): void
{
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

try {
    $arguments = $argv ?? [];
    $vmName = null;
    $liveUser = 'w4live';
    $livePassword = null;
    $sshHost = '127.0.0.1';
    $sshPort = 22;
    $checkOnly = false;
    $waitMs = 1500;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--check-only') {
            $checkOnly = true;
            continue;
        }

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--vm-name':
                $vmName = $value;
                break;

            case '--live-user':
                $liveUser = $value;
                break;

            case '--live-password':
                $livePassword = $value;
                break;

            case '--ssh-host':
                $sshHost = $value;
                break;

            case '--ssh-port':
                $sshPort = (int) $value;
                break;

            case '--wait-ms':
                $waitMs = (int) $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no reconocido: %s', $argument));
        }
    }

    if ($vmName === null || trim($vmName) === '') {
        throw new ValidationError('Debe indicar --vm-name');
    }

    if ($livePassword === null || trim($livePassword) === '') {
        throw new ValidationError('Debe indicar --live-password');
    }

    if ($sshPort <= 0) {
        throw new ValidationError('--ssh-port debe ser mayor que cero');
    }

    if ($waitMs < 0) {
        throw new ValidationError('--wait-ms no puede ser negativo');
    }

    $vboxManage = findVBoxManagePath();
    $steps = buildLiveSetupCommands($liveUser, $livePassword);
    $stepLabels = array_map(
        static fn (array $step): string => $step['label'],
        $steps
    );

    $metadataToolkit = new ArtifactMetadataToolkit();
    if ($checkOnly) {
        printJson($metadataToolkit->createStatusPayload(
            [
                'vm_name' => $vmName,
                'ssh_host' => $sshHost,
                'ssh_port' => $sshPort,
                'live_user' => $liveUser,
                'setup_steps' => $stepLabels,
                'setup_step_count' => count($stepLabels),
                'enter_scancode' => ['e0', '1c', 'e0', '9c'],
                'wait_ms' => $waitMs,
                'vboxmanage_path' => $vboxManage,
            ],
            'ready'
        ));
        exit(0);
    }

    foreach ($steps as $step) {
        sendVBoxText($vboxManage, $vmName, $step['command']);
        if ($waitMs > 0) {
            usleep($waitMs * 1000);
        }
    }

    printJson($metadataToolkit->createStatusPayload([
        'vm_name' => $vmName,
        'ssh_host' => $sshHost,
        'ssh_port' => $sshPort,
        'live_user' => $liveUser,
        'executed_steps' => $stepLabels,
        'setup_step_count' => count($stepLabels),
        'enter_scancode' => ['e0', '1c', 'e0', '9c'],
        'wait_ms' => $waitMs,
        'vboxmanage_path' => $vboxManage,
    ]));
} catch (ValidationError $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
