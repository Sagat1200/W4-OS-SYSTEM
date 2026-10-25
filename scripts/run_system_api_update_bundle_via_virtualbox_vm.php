<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

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
function runChecked(array $command): string
{
    $fixtureMap = readJsonFixtureFromEnv('W4_SYSTEM_API_UPDATE_VM_COMMAND_MAP_JSON');
    $fixtureKey = json_encode(array_values($command), JSON_THROW_ON_ERROR);

    if ($fixtureMap !== null && array_key_exists($fixtureKey, $fixtureMap)) {
        $fixture = $fixtureMap[$fixtureKey];
        if (is_array($fixture)) {
            $stdout = $fixture['stdout'] ?? '';
            $stderr = (string) ($fixture['stderr'] ?? '');
            $exitCode = (int) ($fixture['exit_code'] ?? 0);
        } else {
            $stdout = $fixture;
            $stderr = '';
            $exitCode = 0;
        }

        if (!is_scalar($stdout)) {
            throw new ValidationError(sprintf('Fixture invalido para %s', $fixtureKey));
        }

        if ($exitCode !== 0) {
            throw new ValidationError(trim($stderr) !== '' ? trim($stderr) : sprintf('Fallo al ejecutar: %s', implode(' ', $command)));
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

    $resolved = runChecked(['where', 'VBoxManage.exe']);
    $lines = preg_split('/\r?\n/', $resolved) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            return $line;
        }
    }

    throw new ValidationError('No se encontro VBoxManage.exe en este entorno Windows');
}

/**
 * @return array<string, string|null>
 */
function inspectVirtualMachine(string $vboxManage, string $vmName): array
{
    $output = runChecked([$vboxManage, 'showvminfo', $vmName, '--machinereadable']);
    $lines = preg_split('/\r?\n/', $output) ?: [];
    $values = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($value !== '' && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }

        $values[$key] = $value;
    }

    return [
        'name' => $values['name'] ?? $vmName,
        'uuid' => $values['UUID'] ?? null,
        'state' => $values['VMState'] ?? null,
        'ostype' => $values['ostype'] ?? null,
        'config_file' => $values['CfgFile'] ?? null,
    ];
}

function findPhpBinary(): string
{
    $binary = PHP_BINARY;
    if (is_string($binary) && $binary !== '' && is_file($binary)) {
        return $binary;
    }

    return 'php';
}

function makeSlug(string $value): string
{
    $slug = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($value)) ?? 'system-api-update';
    $slug = trim($slug, '-');

    return $slug !== '' ? strtolower($slug) : 'system-api-update';
}

function quoteForBash(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

function resolveStoreDir(string $rootDir, ?string $storeDir, ?string $operationId): string
{
    if ($storeDir !== null) {
        return $storeDir;
    }

    $sanitized = preg_replace('/[^A-Za-z0-9._-]/', '-', trim((string) $operationId));
    if (!is_string($sanitized) || $sanitized === '') {
        throw new ValidationError('No se pudo resolver el store durable para operation_id');
    }

    return $rootDir
        . DIRECTORY_SEPARATOR . 'build'
        . DIRECTORY_SEPARATOR . 'system-api'
        . DIRECTORY_SEPARATOR . 'update-operations'
        . DIRECTORY_SEPARATOR . $sanitized;
}

/**
 * @return list<string>
 */
function requiredArtifacts(): array
{
    return [
        'executor.env',
        'run-update-offline.sh',
        'run-update-with-repo-env.sh',
        'run-health-checks.sh',
        'reconcile-after-reboot.sh',
        'reconcile-update-operation.php',
        'update-executor.json',
        'update-plan.json',
    ];
}

function ensureBundleArtifacts(string $storeDir): void
{
    if (!is_dir($storeDir)) {
        throw new ValidationError(sprintf('No existe el store durable indicado: %s', $storeDir));
    }

    foreach (requiredArtifacts() as $artifact) {
        $path = $storeDir . DIRECTORY_SEPARATOR . $artifact;
        if (!is_file($path)) {
            throw new ValidationError(sprintf('Falta un artefacto requerido del bundle: %s', $path));
        }
    }
}

/**
 * @param list<string> $scriptNames
 */
function buildChmodCommand(array $scriptNames): string
{
    $quoted = array_map(static fn (string $name): string => quoteForBash('./' . $name), $scriptNames);

    return 'chmod +x ' . implode(' ', $quoted);
}

function buildRemoteStageCommand(
    string $remoteDir,
    string $stage,
    ?string $remoteRepositoryDir,
    ?string $remoteRepositoryEnv
): string {
    $chmodCommand = buildChmodCommand([
        'run-update-offline.sh',
        'run-update-with-repo-env.sh',
        'run-health-checks.sh',
        'reconcile-after-reboot.sh',
    ]);

    $stageCommand = match ($stage) {
        'offline' => $remoteRepositoryDir !== null
            ? './run-update-with-repo-env.sh --repo-dir ' . quoteForBash($remoteRepositoryDir)
            : ($remoteRepositoryEnv !== null
                ? './run-update-with-repo-env.sh --repo-env ' . quoteForBash($remoteRepositoryEnv)
                : './run-update-offline.sh'),
        'health' => './run-health-checks.sh',
        'reconcile' => './reconcile-after-reboot.sh',
        default => throw new ValidationError(sprintf('Stage no soportado: %s', $stage)),
    };

    return sprintf(
        'bash -lc %s',
        quoteForBash(sprintf(
            'cd %s && %s && source ./executor.env && %s',
            quoteForBash($remoteDir),
            $chmodCommand,
            $stageCommand
        ))
    );
}

/**
 * @return list<string>
 */
function artifactsToSyncBack(): array
{
    return [
        'operation.json',
        'health-report.json',
        'events.ndjson',
        'health-check-results.json',
        'offline-application.json',
        'snapshot-manifest.json',
        'staging-manifest.json',
    ];
}

try {
    $rootDir = dirname(__DIR__);
    $phpBinary = findPhpBinary();
    $vboxManage = findVBoxManagePath();

    $arguments = $argv ?? [];
    $vmName = null;
    $storeDir = null;
    $operationId = null;
    $remoteRoot = '/home/w4live/system-api-update-bundle';
    $sshHost = '127.0.0.1';
    $sshPort = 2242;
    $username = 'w4live';
    $password = null;
    $liveUser = 'w4live';
    $livePassword = null;
    $waitMs = 1500;
    $stage = 'health';
    $checkOnly = false;
    $remoteRepositoryDir = null;
    $remoteRepositoryEnv = null;

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
            case '--store-dir':
                $storeDir = $value;
                break;
            case '--operation-id':
                $operationId = $value;
                break;
            case '--remote-root':
                $remoteRoot = $value;
                break;
            case '--ssh-host':
                $sshHost = $value;
                break;
            case '--ssh-port':
                $sshPort = (int) $value;
                break;
            case '--username':
                $username = $value;
                break;
            case '--password':
                $password = $value;
                break;
            case '--live-user':
                $liveUser = $value;
                break;
            case '--live-password':
                $livePassword = $value;
                break;
            case '--wait-ms':
                $waitMs = (int) $value;
                break;
            case '--stage':
                $stage = $value;
                break;
            case '--remote-repository-dir':
                $remoteRepositoryDir = $value;
                break;
            case '--remote-repository-env':
                $remoteRepositoryEnv = $value;
                break;
            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($vmName === null || trim($vmName) === '') {
        throw new ValidationError('Debe indicar --vm-name');
    }

    if ($storeDir !== null && $operationId !== null) {
        throw new ValidationError('Use --store-dir o --operation-id, no ambos a la vez');
    }

    if ($storeDir === null && $operationId === null) {
        throw new ValidationError('Debe indicar --store-dir o --operation-id');
    }

    if (!in_array($stage, ['offline', 'health', 'reconcile'], true)) {
        throw new ValidationError('stage debe ser offline, health o reconcile');
    }

    if ($remoteRepositoryDir !== null && $remoteRepositoryEnv !== null) {
        throw new ValidationError('Use --remote-repository-dir o --remote-repository-env, no ambos a la vez');
    }

    $storeDir = resolveStoreDir($rootDir, $storeDir, $operationId);
    ensureBundleArtifacts($storeDir);

    $remoteBundleRoot = rtrim($remoteRoot, '/');
    if ($remoteBundleRoot === '') {
        throw new ValidationError('--remote-root no puede ser vacio');
    }

    $vmInfo = inspectVirtualMachine($vboxManage, $vmName);
    $remoteBundleDir = $remoteBundleRoot . '/' . makeSlug(basename($storeDir));
    $enableLiveSshScript = __DIR__ . DIRECTORY_SEPARATOR . 'enable_live_ssh_in_virtualbox.php';
    $uploadTreeScript = __DIR__ . DIRECTORY_SEPARATOR . 'upload_tree_via_paramiko.php';
    $runRemoteScript = __DIR__ . DIRECTORY_SEPARATOR . 'run_remote_command_via_paramiko.php';
    $downloadFilesScript = __DIR__ . DIRECTORY_SEPARATOR . 'download_files_via_paramiko.php';

    $remoteCommand = buildRemoteStageCommand(
        $remoteBundleDir,
        $stage,
        $remoteRepositoryDir,
        $remoteRepositoryEnv
    );

    $readyPayload = [
        'status' => 'ready',
        'vm_name' => $vmName,
        'vboxmanage_path' => $vboxManage,
        'vm_info' => $vmInfo,
        'store_dir' => $storeDir,
        'operation_id' => $operationId,
        'stage' => $stage,
        'ssh_host' => $sshHost,
        'ssh_port' => $sshPort,
        'username' => $username,
        'remote_bundle_dir' => $remoteBundleDir,
        'remote_repository_dir' => $remoteRepositoryDir,
        'remote_repository_env' => $remoteRepositoryEnv,
        'enable_live_ssh_script' => $enableLiveSshScript,
        'upload_tree_script' => $uploadTreeScript,
        'run_remote_command_script' => $runRemoteScript,
        'download_files_script' => $downloadFilesScript,
        'required_artifacts' => requiredArtifacts(),
        'sync_back_artifacts' => artifactsToSyncBack(),
        'remote_command' => $remoteCommand,
    ];

    if ($checkOnly) {
        printJson($readyPayload);
        exit(0);
    }

    if ($password === null || trim($password) === '') {
        $password = $livePassword;
    }

    if ($password === null || trim($password) === '') {
        throw new ValidationError('Debe indicar --password o --live-password');
    }

    $liveSshOutput = null;
    if ($livePassword !== null && trim($livePassword) !== '') {
        $liveSshCommand = [
            $phpBinary,
            $enableLiveSshScript,
            '--vm-name',
            $vmName,
            '--live-user',
            $liveUser,
            '--live-password',
            $livePassword,
            '--ssh-host',
            $sshHost,
            '--ssh-port',
            (string) $sshPort,
            '--wait-ms',
            (string) $waitMs,
        ];
        $liveSshOutput = runChecked($liveSshCommand);
    }

    $uploadOutput = runChecked([
        $phpBinary,
        $uploadTreeScript,
        '--host',
        $sshHost,
        '--port',
        (string) $sshPort,
        '--username',
        $username,
        '--password',
        $password,
        '--local-dir',
        $storeDir,
        '--remote-dir',
        $remoteBundleDir,
        '--replace',
    ]);

    $executionOutput = runChecked([
        $phpBinary,
        $runRemoteScript,
        '--host',
        $sshHost,
        '--port',
        (string) $sshPort,
        '--username',
        $username,
        '--password',
        $password,
        '--command',
        $remoteCommand,
        '--pty',
    ]);

    $downloadCommand = [
        $phpBinary,
        $downloadFilesScript,
        '--host',
        $sshHost,
        '--port',
        (string) $sshPort,
        '--username',
        $username,
        '--password',
        $password,
        '--remote-dir',
        $remoteBundleDir,
        '--local-dir',
        $storeDir,
    ];

    foreach (artifactsToSyncBack() as $artifact) {
        $downloadCommand[] = '--artifact';
        $downloadCommand[] = $artifact;
    }

    $downloadOutput = runChecked($downloadCommand);

    $payload = $readyPayload;
    $payload['status'] = 'ok';
    $payload['upload_output'] = $uploadOutput;
    $payload['execution_output'] = $executionOutput;
    $payload['download_output'] = $downloadOutput;
    $payload['next_step'] = match ($stage) {
        'offline' => 'reboot-and-run-health',
        'health' => 'reconcile',
        'reconcile' => 'inspect-operation',
    };
    if ($liveSshOutput !== null) {
        $payload['live_ssh_setup_output'] = $liveSshOutput;
    }

    printJson($payload);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'ERROR: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
