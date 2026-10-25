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
 * @return array{stdout: string, stderr: string, exit_code: int}
 */
function runCaptured(array $command): array
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

        return [
            'stdout' => trim((string) $stdout),
            'stderr' => trim($stderr),
            'exit_code' => $exitCode,
        ];
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
    return [
        'stdout' => trim((string) $stdout),
        'stderr' => trim((string) $stderr),
        'exit_code' => $exitCode,
    ];
}

/**
 * @param list<string> $command
 */
function runChecked(array $command): string
{
    $result = runCaptured($command);
    if ($result['exit_code'] !== 0) {
        $message = $result['stderr'];
        if ($message === '') {
            $message = $result['stdout'];
        }
        if ($message === '') {
            $message = sprintf('Fallo al ejecutar: %s', implode(' ', $command));
        }

        throw new ValidationError($message);
    }

    return $result['stdout'];
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

function quoteForBashDouble(string $value): string
{
    return '"' . str_replace(
        ['\\', '"', '$', '`'],
        ['\\\\', '\\"', '\\$', '\\`'],
        $value
    ) . '"';
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
    $quoted = array_map(static fn (string $name): string => quoteForBashDouble('./' . $name), $scriptNames);

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
            ? './run-update-with-repo-env.sh --repo-dir ' . quoteForBashDouble($remoteRepositoryDir)
            : ($remoteRepositoryEnv !== null
                ? './run-update-with-repo-env.sh --repo-env ' . quoteForBashDouble($remoteRepositoryEnv)
                : './run-update-offline.sh'),
        'health' => './run-health-checks.sh',
        'reconcile' => './reconcile-after-reboot.sh',
        default => throw new ValidationError(sprintf('Stage no soportado: %s', $stage)),
    };

    return sprintf(
        'bash -lc %s',
        quoteForBash(sprintf(
            'cd %s && %s && source ./executor.env && %s',
            quoteForBashDouble($remoteDir),
            $chmodCommand,
            $stageCommand
        ))
    );
}

/**
 * @return array<string, mixed>
 */
function decodeJsonFile(string $path, string $label): array
{
    $raw = file_get_contents($path);
    if (!is_string($raw)) {
        throw new ValidationError(sprintf('No se pudo leer %s: %s', $label, $path));
    }

    try {
        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('%s contiene JSON invalido: %s', $label, $exception->getMessage()));
    }

    if (!is_array($decoded)) {
        throw new ValidationError(sprintf('%s debe contener un objeto JSON: %s', $label, $path));
    }

    return $decoded;
}

/**
 * @return array<string, mixed>
 */
function loadHealthCheckResults(string $storeDir): array
{
    $path = $storeDir . DIRECTORY_SEPARATOR . 'health-check-results.json';
    if (!is_file($path)) {
        throw new ValidationError(sprintf(
            'El stage remoto no sincronizo health-check-results.json en %s',
            $storeDir
        ));
    }

    $payload = decodeJsonFile($path, 'health-check-results.json');
    if (($payload['kind'] ?? null) !== 'health-check-results') {
        throw new ValidationError(sprintf('health-check-results.json invalido en %s: kind no soportado', $path));
    }

    $status = $payload['status'] ?? null;
    if (!is_string($status) || !in_array($status, ['ok', 'failed'], true)) {
        throw new ValidationError(sprintf('health-check-results.json invalido en %s: status no soportado', $path));
    }

    if (!is_array($payload['results'] ?? null)) {
        throw new ValidationError(sprintf('health-check-results.json invalido en %s: results debe ser un objeto', $path));
    }

    return $payload;
}

function formatFailedHealthChecks(array $healthCheckResults): string
{
    $results = is_array($healthCheckResults['results'] ?? null) ? $healthCheckResults['results'] : [];
    $failed = [];

    foreach ($results as $checkName => $status) {
        if (is_string($checkName) && $status === 'failed') {
            $failed[] = $checkName;
        }
    }

    if ($failed === []) {
        return 'sin detalle de checks fallidos';
    }

    sort($failed);

    return implode(', ', $failed);
}

function validateSyncedArtifacts(string $storeDir, string $stage): array
{
    if (!in_array($stage, ['health', 'reconcile'], true)) {
        return [
            'validated_stage' => $stage,
            'health_status' => null,
            'expected_stage' => null,
        ];
    }

    $healthCheckResults = loadHealthCheckResults($storeDir);
    $healthStatus = (string) $healthCheckResults['status'];
    $expectedStage = $healthStatus === 'ok' ? 'confirmed' : 'failed';

    if ($stage === 'health' && $healthStatus !== 'ok') {
        throw new ValidationError(sprintf(
            'La validacion post-arranque fallo en la VM: %s',
            formatFailedHealthChecks($healthCheckResults)
        ));
    }

    if ($stage === 'reconcile') {
        $operationPath = $storeDir . DIRECTORY_SEPARATOR . 'operation.json';
        $healthReportPath = $storeDir . DIRECTORY_SEPARATOR . 'health-report.json';

        if (!is_file($operationPath) || !is_file($healthReportPath)) {
            throw new ValidationError(sprintf(
                'El stage reconcile no sincronizo operation.json y health-report.json en %s',
                $storeDir
            ));
        }

        $operation = decodeJsonFile($operationPath, 'operation.json');
        $healthReport = decodeJsonFile($healthReportPath, 'health-report.json');
        $operationStage = is_string($operation['stage'] ?? null) ? trim((string) $operation['stage']) : null;
        $healthReportStage = is_string($healthReport['stage'] ?? null) ? trim((string) $healthReport['stage']) : null;
        $healthReportStatus = is_string($healthReport['status'] ?? null) ? trim((string) $healthReport['status']) : null;

        if ($operationStage !== $expectedStage || $healthReportStage !== $expectedStage || $healthReportStatus !== $healthStatus) {
            throw new ValidationError(sprintf(
                'El store sincronizado quedo inconsistente: health-check-results=%s, operation.stage=%s, health-report.stage=%s, health-report.status=%s',
                $expectedStage,
                $operationStage ?? 'null',
                $healthReportStage ?? 'null',
                $healthReportStatus ?? 'null'
            ));
        }
    }

    return [
        'validated_stage' => $stage,
        'health_status' => $healthStatus,
        'expected_stage' => $expectedStage,
    ];
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

    $executionResult = runCaptured([
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
        '--command-b64',
        base64_encode($remoteCommand),
        '--pty',
    ]);
    $executionOutput = $executionResult['stdout'];

    if (!in_array($stage, ['health', 'reconcile'], true) && $executionResult['exit_code'] !== 0) {
        $message = $executionResult['stderr'] !== '' ? $executionResult['stderr'] : $executionResult['stdout'];
        if ($message === '') {
            $message = sprintf('Fallo al ejecutar: %s', $remoteCommand);
        }

        throw new ValidationError($message);
    }

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
    $syncedArtifactValidation = validateSyncedArtifacts($storeDir, $stage);

    $payload = $readyPayload;
    $payload['status'] = 'ok';
    $payload['upload_output'] = $uploadOutput;
    $payload['execution_output'] = $executionOutput;
    $payload['execution_exit_code'] = $executionResult['exit_code'];
    $payload['execution_error_output'] = $executionResult['stderr'];
    $payload['download_output'] = $downloadOutput;
    $payload['synced_artifact_validation'] = $syncedArtifactValidation;
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
