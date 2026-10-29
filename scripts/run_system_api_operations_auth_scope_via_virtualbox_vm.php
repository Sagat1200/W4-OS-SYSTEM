<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Support\ValidationError;

function runCaptured(array $command): array
{
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

function runChecked(array $command): string
{
    $result = runCaptured($command);
    if ($result['exit_code'] !== 0) {
        $message = $result['stderr'] !== '' ? $result['stderr'] : $result['stdout'];
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

function waitForVmRunning(string $vboxManage, string $vmName, int $timeoutSeconds): array
{
    $elapsed = 0;
    while ($elapsed <= $timeoutSeconds) {
        $info = inspectVirtualMachine($vboxManage, $vmName);
        if (($info['state'] ?? null) === 'running') {
            return $info;
        }
        sleep(2);
        $elapsed += 2;
    }

    return inspectVirtualMachine($vboxManage, $vmName);
}

function resolveForwardedSshPort(string $vboxManage, string $vmName): ?int
{
    $output = runChecked([$vboxManage, 'showvminfo', $vmName, '--machinereadable']);
    $lines = preg_split('/\r?\n/', $output) ?: [];
    $forwardings = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || !str_starts_with($line, 'Forwarding(') || !str_contains($line, '=')) {
            continue;
        }

        [, $value] = explode('=', $line, 2);
        $value = trim($value);
        if ($value !== '' && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }

        $parts = array_map('trim', explode(',', $value));
        if (count($parts) < 6) {
            continue;
        }

        $name = $parts[0] ?? '';
        $hostPort = $parts[3] ?? '';
        $guestPort = $parts[5] ?? '';
        if (!is_string($name) || !is_string($hostPort) || !is_string($guestPort)) {
            continue;
        }
        if (trim($guestPort) !== '22' || preg_match('/^\d+$/', trim($hostPort)) !== 1) {
            continue;
        }

        $forwardings[] = [
            'name' => $name,
            'host_port' => (int) trim($hostPort),
        ];
    }

    foreach (['w4livessh', 'guestssh', 'ssh'] as $preferred) {
        foreach ($forwardings as $forwarding) {
            if (str_contains(strtolower((string) $forwarding['name']), $preferred)) {
                return (int) $forwarding['host_port'];
            }
        }
    }

    if ($forwardings !== []) {
        return (int) $forwardings[0]['host_port'];
    }

    return null;
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
    $slug = preg_replace('/[^A-Za-z0-9._-]+/', '-', trim($value)) ?? 'system-api-ops-auth-scope';
    $slug = trim($slug, '-');

    return $slug !== '' ? strtolower($slug) : 'system-api-ops-auth-scope';
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

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }
    if (!mkdir($path, 0700, true) && !is_dir($path)) {
        throw new ValidationError('No se pudo crear: ' . $path);
    }
}

function copyFile(string $source, string $destination): void
{
    ensureDirectory(dirname($destination));
    if (!copy($source, $destination)) {
        throw new ValidationError(sprintf('No se pudo copiar %s -> %s', $source, $destination));
    }
}

function copyTree(string $sourceDir, string $destinationDir): void
{
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        $sourcePath = $item->getPathname();
        $relative = substr($sourcePath, strlen(rtrim($sourceDir, "\\/")) + 1);
        $destPath = rtrim($destinationDir, "\\/") . DIRECTORY_SEPARATOR . $relative;
        if ($item->isDir()) {
            ensureDirectory($destPath);
            continue;
        }
        copyFile($sourcePath, $destPath);
    }
}

function decodeJsonFile(string $path, string $label): array
{
    $raw = file_get_contents($path);
    if (!is_string($raw)) {
        throw new ValidationError(sprintf('No se pudo leer %s: %s', $label, $path));
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('%s contiene JSON invalido: %s', $label, $exception->getMessage()));
    }

    if (!is_array($decoded)) {
        throw new ValidationError(sprintf('%s debe ser JSON objeto: %s', $label, $path));
    }

    return $decoded;
}

function resolvePasswordFromCredentialsFile(string $rootDir, string $vmName, string $username, int $sshPort): ?string
{
    $path = $rootDir . DIRECTORY_SEPARATOR . 'Docs' . DIRECTORY_SEPARATOR . 'DEVELOPMENT' . DIRECTORY_SEPARATOR . 'CONTRASEÑAS_OS.md';
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    $lines = preg_split('/\r?\n/', $raw) ?: [];
    $candidates = [
        '/^\s*VM_W4_OS_SERVER_DBUSMODERN_PASSWORD\s*[:=]\s*(.+?)\s*$/i',
        '/^\s*W4_OS_SERVER_DBUSMODERN_SSH_PASSWORD\s*[:=]\s*(.+?)\s*$/i',
        '/^\s*VIRTUALBOX_W4LIVE_PASSWORD\s*[:=]\s*(.+?)\s*$/i',
        '/^\s*W4LIVE_PASSWORD\s*[:=]\s*(.+?)\s*$/i',
        '/^\s*SSH_PASSWORD\s*[:=]\s*(.+?)\s*$/i',
    ];

    foreach ($lines as $line) {
        foreach ($candidates as $pattern) {
            if (preg_match($pattern, $line, $matches) === 1) {
                $value = trim((string) ($matches[1] ?? ''));
                $value = trim($value, "`'\" ");
                if ($value !== '') {
                    return $value;
                }
            }
        }
    }

    foreach ($lines as $line) {
        $lower = strtolower($line);
        if (!str_contains($lower, 'pass') && !str_contains($lower, 'clave')) {
            continue;
        }
        if (!str_contains($line, $vmName) && !str_contains($line, $username) && !str_contains($line, (string) $sshPort)) {
            continue;
        }

        if (preg_match('/(?:password|pass|clave)\s*[:=]\s*(.+?)\s*$/i', $line, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            $value = trim($value, "`'\" ");
            if ($value !== '') {
                return $value;
            }
        }

        if (str_contains($line, '|')) {
            $parts = array_values(array_filter(array_map('trim', explode('|', $line)), static fn (string $part): bool => $part !== ''));
            if (count($parts) >= 2) {
                $value = trim((string) $parts[count($parts) - 1]);
                $value = trim($value, "`'\" ");
                if ($value !== '' && !str_contains(strtolower($value), 'password')) {
                    return $value;
                }
            }
        }

        if (preg_match('/' . preg_quote($username, '/') . '\s*:\s*(\S+)\s*$/', $line, $matches) === 1) {
            $value = trim((string) ($matches[1] ?? ''));
            $value = trim($value, "`'\" ");
            if ($value !== '') {
                return $value;
            }
        }
    }

    return null;
}

function printJson(array $payload): void
{
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

try {
    $rootDir = dirname(__DIR__);
    $phpBinary = findPhpBinary();
    $vboxManage = findVBoxManagePath();

    $arguments = $argv ?? [];
    $vmName = 'W4-OS-Server-DbusModern';
    $remoteRoot = '/home/w4live/system-api-ops-auth-scope';
    $sshHost = '127.0.0.1';
    $sshPort = 2222;
    $sshPortExplicit = false;
    $username = 'w4live';
    $password = null;
    $liveUser = 'w4live';
    $livePassword = null;
    $waitMs = 1500;
    $checkOnly = false;
    $remotePhp = '/opt/php/8.4.24/bin/php';
    $autoLivePassword = false;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--check-only') {
            $checkOnly = true;
            continue;
        }
        if ($argument === '--auto-live-password') {
            $autoLivePassword = true;
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
            case '--remote-root':
                $remoteRoot = $value;
                break;
            case '--ssh-host':
                $sshHost = $value;
                break;
            case '--ssh-port':
                $sshPort = (int) $value;
                $sshPortExplicit = true;
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
            case '--remote-php':
                $remotePhp = $value;
                break;
            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if (trim($vmName) === '') {
        throw new ValidationError('--vm-name no puede ser vacio');
    }

    $vmInfo = inspectVirtualMachine($vboxManage, $vmName);

    $baseEvidenceDir = $rootDir
        . DIRECTORY_SEPARATOR . 'build'
        . DIRECTORY_SEPARATOR . 'system-api'
        . DIRECTORY_SEPARATOR . 'validation'
        . DIRECTORY_SEPARATOR . 'system-api-ops-auth-scope';
    ensureDirectory($baseEvidenceDir);
    $localRunDir = $baseEvidenceDir . DIRECTORY_SEPARATOR . 'vbox-run-' . gmdate('Ymd-His') . '-' . bin2hex(random_bytes(4));
    ensureDirectory($localRunDir);

    $bundleDir = $localRunDir . DIRECTORY_SEPARATOR . 'bundle';
    ensureDirectory($bundleDir);

    copyTree($rootDir . DIRECTORY_SEPARATOR . 'src', $bundleDir . DIRECTORY_SEPARATOR . 'src');
    copyFile($rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'bootstrap.php', $bundleDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'bootstrap.php');
    copyFile($rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_manager.php', $bundleDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'system_api_manager.php');
    copyFile($rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_system_api_operations_auth_scope_worker.php', $bundleDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_system_api_operations_auth_scope_worker.php');

    $remoteBase = rtrim($remoteRoot, '/');
    if ($remoteBase === '') {
        throw new ValidationError('--remote-root no puede ser vacio');
    }

    $remoteDir = $remoteBase . '/' . makeSlug(basename($localRunDir));
    $remoteEvidenceDir = $remoteDir . '/evidence';

    $enableLiveSshScript = __DIR__ . DIRECTORY_SEPARATOR . 'enable_live_ssh_in_virtualbox.php';
    $uploadTreeScript = __DIR__ . DIRECTORY_SEPARATOR . 'upload_tree_via_paramiko.php';
    $runRemoteScript = __DIR__ . DIRECTORY_SEPARATOR . 'run_remote_command_via_paramiko.php';
    $downloadFilesScript = __DIR__ . DIRECTORY_SEPARATOR . 'download_files_via_paramiko.php';

    $readyPayload = [
        'status' => 'ready',
        'vm_name' => $vmName,
        'vboxmanage_path' => $vboxManage,
        'vm_info' => $vmInfo,
        'ssh_host' => $sshHost,
        'ssh_port' => $sshPort,
        'username' => $username,
        'remote_dir' => $remoteDir,
        'remote_evidence_dir' => $remoteEvidenceDir,
        'local_run_dir' => $localRunDir,
        'bundle_dir' => $bundleDir,
        'remote_php' => $remotePhp,
    ];

    if ($checkOnly) {
        printJson($readyPayload);
        exit(0);
    }

    if (($vmInfo['state'] ?? null) !== 'running') {
        runChecked([$vboxManage, 'startvm', $vmName, '--type', 'headless']);
        $vmInfo = waitForVmRunning($vboxManage, $vmName, 90);
        if (($vmInfo['state'] ?? null) !== 'running') {
            throw new ValidationError(sprintf('La VM no quedo corriendo: %s', $vmName));
        }
    }

    if (!$sshPortExplicit) {
        $resolvedPort = resolveForwardedSshPort($vboxManage, $vmName);
        if (is_int($resolvedPort) && $resolvedPort > 0) {
            $sshPort = $resolvedPort;
        }
    }

    if ($password === null || trim($password) === '') {
        $password = $livePassword;
    }
    if ($password === null || trim($password) === '') {
        $password = resolvePasswordFromCredentialsFile($rootDir, $vmName, $username, $sshPort);
    }
    if ($password === null || trim($password) === '') {
        if ($autoLivePassword && ($livePassword === null || trim($livePassword) === '')) {
            $livePassword = bin2hex(random_bytes(12));
            $password = $livePassword;
        }
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
        $bundleDir,
        '--remote-dir',
        $remoteDir,
        '--replace',
    ]);

    $remoteCommand = sprintf(
        'bash -lc %s',
        quoteForBash(sprintf(
            'cd %s && %s %s --evidence-dir %s',
            quoteForBashDouble($remoteDir),
            quoteForBashDouble($remotePhp),
            quoteForBashDouble('scripts/run_system_api_operations_auth_scope_worker.php'),
            quoteForBashDouble($remoteEvidenceDir)
        ))
    );

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

    $downloadOutput = runChecked([
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
        $remoteEvidenceDir,
        '--local-dir',
        $localRunDir,
        '--artifact',
        'report.json',
    ]);

    if ($executionResult['exit_code'] !== 0) {
        $message = $executionResult['stderr'] !== '' ? $executionResult['stderr'] : $executionResult['stdout'];
        if ($message === '') {
            $message = 'Fallo al ejecutar el worker remoto';
        }
        throw new ValidationError($message);
    }

    $downloadSummary = null;
    try {
        $downloadSummary = json_decode($downloadOutput, true, 512, JSON_THROW_ON_ERROR);
    } catch (Throwable) {
        $downloadSummary = null;
    }
    $missing = is_array($downloadSummary) ? ($downloadSummary['missing'] ?? null) : null;
    if (is_array($missing) && in_array('report.json', $missing, true)) {
        throw new ValidationError('La VM no entrego report.json para esta ejecucion');
    }

    $report = decodeJsonFile($localRunDir . DIRECTORY_SEPARATOR . 'report.json', 'report.json');
    $validated = ($report['validated'] ?? false) === true;

    $payload = $readyPayload;
    $payload['status'] = $validated ? 'validated' : 'failed';
    $payload['validated'] = $validated;
    $payload['upload_output'] = $uploadOutput;
    $payload['execution_exit_code'] = $executionResult['exit_code'];
    $payload['execution_stdout'] = $executionResult['stdout'];
    $payload['execution_stderr'] = $executionResult['stderr'];
    $payload['download_output'] = $downloadOutput;
    $payload['report_path'] = $localRunDir . DIRECTORY_SEPARATOR . 'report.json';
    if ($liveSshOutput !== null) {
        $payload['live_ssh_setup_output'] = $liveSshOutput;
    }

    printJson($payload);
    exit($validated ? 0 : 3);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'ERROR: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}

