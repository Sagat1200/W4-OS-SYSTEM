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
    } catch (\JsonException $exception) {
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
function runProcess(array $command, ?string $cwd = null): array
{
    $fixtureMap = readJsonFixtureFromEnv('W4_SERVER_VBOX_FLOW_COMMAND_MAP_JSON');
    $fixtureKey = json_encode(array_values($command), JSON_THROW_ON_ERROR);

    if ($fixtureMap !== null && array_key_exists($fixtureKey, $fixtureMap)) {
        $fixture = $fixtureMap[$fixtureKey];
        if (!is_array($fixture)) {
            $fixture = [
                'stdout' => $fixture,
                'stderr' => '',
                'exit_code' => 0,
            ];
        }

        return [
            'stdout' => trim((string) ($fixture['stdout'] ?? '')),
            'stderr' => trim((string) ($fixture['stderr'] ?? '')),
            'exit_code' => (int) ($fixture['exit_code'] ?? 0),
        ];
    }

    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, $cwd, null, ['bypass_shell' => true]);
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
        'stdout' => trim($stdout === false ? '' : $stdout),
        'stderr' => trim($stderr === false ? '' : $stderr),
        'exit_code' => $exitCode,
    ];
}

/**
 * @param list<string> $command
 */
function runChecked(array $command, ?string $cwd = null): string
{
    $result = runProcess($command, $cwd);
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

function slugify(string $value): string
{
    $slug = strtolower(preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '');
    $slug = trim($slug, '-');

    return $slug !== '' ? $slug : 'server';
}

function ensureDirectory(string $path): void
{
    if (is_dir($path)) {
        return;
    }

    if (!mkdir($path, 0777, true) && !is_dir($path)) {
        throw new ValidationError(sprintf('No se pudo crear el directorio: %s', $path));
    }
}

function readAsciiSecret(string $path): string
{
    if (!is_file($path)) {
        throw new ValidationError(sprintf('No existe el secreto requerido: %s', $path));
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer el secreto: %s', $path));
    }

    $value = trim($raw);
    if ($value === '') {
        throw new ValidationError(sprintf('El secreto esta vacio: %s', $path));
    }

    return $value;
}

/**
 * @param array<string, mixed> $payload
 */
function printJson(array $payload): void
{
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
}

/**
 * @return array<string, mixed>
 */
function buildInventory(string $inventoryId, int $diskSizeMib, int $isoSizeBytes): array
{
    return [
        'inventory_schema_version' => 1,
        'kind' => 'disk-inventory',
        'id' => $inventoryId,
        'generated_by' => 'run_server_virtualbox_install_flow.php',
        'disks' => [
            [
                'device' => '/dev/sda',
                'serial' => null,
                'wwid' => null,
                'by_path' => 'pci-0000:00:0d.0-ata-1.0',
                'size_bytes' => $diskSizeMib * 1024 * 1024,
                'is_installation_media' => false,
                'has_partitions' => false,
                'has_filesystem_signatures' => false,
                'read_only' => false,
            ],
            [
                'device' => '/dev/sr0',
                'serial' => null,
                'wwid' => null,
                'by_path' => 'pci-0000:00:0d.0-ata-2.0',
                'size_bytes' => $isoSizeBytes,
                'is_installation_media' => true,
                'has_partitions' => true,
                'has_filesystem_signatures' => true,
                'read_only' => true,
            ],
        ],
    ];
}

/**
 * @param array<string, mixed> $inventory
 */
function writeJsonFile(string $path, array $inventory): void
{
    ensureDirectory(dirname($path));
    $encoded = json_encode($inventory, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new ValidationError(sprintf('No se pudo serializar JSON para %s', $path));
    }

    if (file_put_contents($path, $encoded . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo JSON: %s', $path));
    }
}

/**
 * @param list<string> $arguments
 */
function runPhpScript(string $scriptPath, array $arguments, ?string $cwd = null): string
{
    $command = array_merge([PHP_BINARY, $scriptPath], $arguments);
    return runChecked($command, $cwd);
}

/**
 * @return array<string, mixed>
 */
function decodeJsonObject(string $json, string $label): array
{
    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    } catch (\JsonException $exception) {
        throw new ValidationError(sprintf('No se pudo decodificar JSON de %s: %s', $label, $exception->getMessage()));
    }

    if (!is_array($data)) {
        throw new ValidationError(sprintf('La salida JSON de %s no es un objeto/array valido', $label));
    }

    return $data;
}

/**
 * @return array<string, mixed>
 */
function decodeLastJsonObject(string $output, string $label): array
{
    $trimmed = trim($output);
    $offset = strrpos($trimmed, '{');

    while ($offset !== false) {
        $candidate = substr($trimmed, $offset);
        try {
            return decodeJsonObject($candidate, $label);
        } catch (ValidationError $exception) {
            $nextSearch = substr($trimmed, 0, $offset);
            $offset = strrpos($nextSearch, '{');
        }
    }

    throw new ValidationError(sprintf('No se encontro un JSON final valido en la salida de %s', $label));
}

function buildEvidenceLabel(string $vmSlug): string
{
    $label = preg_replace('/^w4-os-server-/', '', $vmSlug) ?? $vmSlug;
    return $label !== '' ? $label : $vmSlug;
}

function waitForVmState(string $vboxManage, string $vmName, string $expectedState, int $timeoutSeconds): void
{
    $deadline = time() + max(1, $timeoutSeconds);

    do {
        $output = runChecked([$vboxManage, 'showvminfo', $vmName, '--machinereadable']);
        if (preg_match('/^VMState="([^"]+)"/m', $output, $matches) === 1 && $matches[1] === $expectedState) {
            return;
        }

        sleep(5);
    } while (time() < $deadline);

    throw new ValidationError(sprintf('La VM %s no llego al estado %s dentro del tiempo esperado', $vmName, $expectedState));
}

/**
 * @param array<int, string> $runtimeLines
 * @return array{hostname: string, ssh_status: string, firstboot_status: string}
 */
function parseRuntimeState(string $runtimeOutput): array
{
    $runtimeLines = array_values(array_filter(
        array_map('trim', preg_split('/\r?\n/', $runtimeOutput) ?: []),
        static fn (string $line): bool => $line !== ''
    ));

    return [
        'hostname' => $runtimeLines[0] ?? '',
        'ssh_status' => $runtimeLines[1] ?? '',
        'firstboot_status' => $runtimeLines[2] ?? '',
    ];
}

/**
 * @param array<string, mixed> $baselinePayload
 * @param array{hostname: string, ssh_status: string, firstboot_status: string} $runtimeState
 */
function buildValidationSummary(
    string $vmName,
    string $isoPath,
    string $bundleDir,
    string $inventoryPath,
    string $beforeLuksScreenshotPath,
    string $postloginScreenshotPath,
    string $baselineReportPath,
    array $baselinePayload,
    array $runtimeState
): string {
    $summary = $baselinePayload['report_summary'] ?? [];

    return implode(PHP_EOL, [
        'Server VirtualBox install flow validation',
        '=====================================',
        sprintf('VM_NAME=%s', $vmName),
        sprintf('ISO=%s', $isoPath),
        sprintf('BUNDLE_DIR=%s', $bundleDir),
        sprintf('INVENTORY=%s', $inventoryPath),
        'RUNNER_STATUS=ok',
        'INSTALLATION_EXIT=0',
        sprintf('POST_BOOT_HOSTNAME=%s', $runtimeState['hostname']),
        sprintf('SSH_STATUS=%s', $runtimeState['ssh_status']),
        sprintf('FIRSTBOOT_STATUS=%s', $runtimeState['firstboot_status']),
        sprintf('BASELINE_REPORT=%s', $baselineReportPath),
        sprintf(
            'BASELINE_SUMMARY=%d passed / %d failed / %d skipped',
            (int) ($summary['passed'] ?? 0),
            (int) ($summary['failed'] ?? 0),
            (int) ($summary['skipped'] ?? 0)
        ),
        sprintf('SCREENSHOT_BEFORE_LUKS=%s', $beforeLuksScreenshotPath),
        sprintf('SCREENSHOT_POSTLOGIN=%s', $postloginScreenshotPath),
        '',
    ]);
}

try {
    $rootDir = dirname(__DIR__);
    $metadataToolkit = new ArtifactMetadataToolkit();

    $arguments = $argv ?? [];
    $profileId = 'w4-os-server';
    $vmName = 'W4-OS-Server-Auto';
    $sshHost = '127.0.0.1';
    $sshPort = 2238;
    $memoryMib = 2048;
    $cpus = 2;
    $diskSizeMib = 32768;
    $bootWait = 55;
    $waitMs = 1800;
    $baseDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'vbox-server-smoke';
    $isoPath = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'iso-output' . DIRECTORY_SEPARATOR . 'w4-os-server' . DIRECTORY_SEPARATOR . 'w4-os-server-live-amd64.iso';
    $secretsDir = $baseDir . DIRECTORY_SEPARATOR . 'secrets';
    $bundleDir = null;
    $inventoryPath = null;
    $livePassword = null;
    $baselineBundleDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'security' . DIRECTORY_SEPARATOR . 'w4-os-server';
    $baselineEvidenceDir = null;
    $baselineRemoteRoot = null;
    $runtimeUsername = 'w4admin';
    $connectWait = 300;
    $retryInterval = 5;
    $unlockWait = 20;
    $unlockRetryInterval = 10;
    $unlockRetries = 12;
    $unlockWindow = 180;
    $runtimeValidation = true;
    $checkOnly = false;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--check-only') {
            $checkOnly = true;
            continue;
        }

        if ($argument === '--skip-runtime-validation') {
            $runtimeValidation = false;
            continue;
        }

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--profile':
                $profileId = $value;
                break;

            case '--vm-name':
                $vmName = $value;
                break;

            case '--ssh-host':
                $sshHost = $value;
                break;

            case '--ssh-port':
                $sshPort = (int) $value;
                break;

            case '--memory-mib':
                $memoryMib = (int) $value;
                break;

            case '--cpus':
                $cpus = (int) $value;
                break;

            case '--disk-size-mib':
                $diskSizeMib = (int) $value;
                break;

            case '--boot-wait':
                $bootWait = (int) $value;
                break;

            case '--wait-ms':
                $waitMs = (int) $value;
                break;

            case '--base-dir':
                $baseDir = $value;
                break;

            case '--iso-path':
                $isoPath = $value;
                break;

            case '--secrets-dir':
                $secretsDir = $value;
                break;

            case '--bundle-dir':
                $bundleDir = $value;
                break;

            case '--inventory-path':
                $inventoryPath = $value;
                break;

            case '--live-password':
                $livePassword = $value;
                break;

            case '--baseline-bundle-dir':
                $baselineBundleDir = $value;
                break;

            case '--baseline-evidence-dir':
                $baselineEvidenceDir = $value;
                break;

            case '--baseline-remote-root':
                $baselineRemoteRoot = $value;
                break;

            case '--runtime-username':
                $runtimeUsername = $value;
                break;

            case '--connect-wait':
                $connectWait = (int) $value;
                break;

            case '--retry-interval':
                $retryInterval = (int) $value;
                break;

            case '--unlock-wait':
                $unlockWait = (int) $value;
                break;

            case '--unlock-retry-interval':
                $unlockRetryInterval = (int) $value;
                break;

            case '--unlock-retries':
                $unlockRetries = (int) $value;
                break;

            case '--unlock-window':
                $unlockWindow = (int) $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no reconocido: %s', $argument));
        }
    }

    if ($sshPort <= 0 || $memoryMib <= 0 || $cpus <= 0 || $diskSizeMib <= 0) {
        throw new ValidationError('ssh-port, memory-mib, cpus y disk-size-mib deben ser mayores que cero');
    }

    if ($bootWait < 0 || $waitMs < 0 || $connectWait < 0 || $retryInterval < 0 || $unlockWait < 0 || $unlockRetryInterval < 0 || $unlockRetries < 0 || $unlockWindow < 0) {
        throw new ValidationError('Los tiempos y contadores del flujo no pueden ser negativos');
    }

    $vmSlug = slugify($vmName);
    $evidenceLabel = buildEvidenceLabel($vmSlug);
    $vmDir = $baseDir . DIRECTORY_SEPARATOR . $vmName;
    $diskPath = $vmDir . DIRECTORY_SEPARATOR . $vmName . '.vdi';
    $inventoryPath ??= $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install-inventory' . DIRECTORY_SEPARATOR . 'virtualbox-server-' . $vmSlug . '.json';
    $bundleDir ??= $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . 'w4-os-server-' . $vmSlug;
    $baselineEvidenceDir ??= $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'security' . DIRECTORY_SEPARATOR . 'validation' . DIRECTORY_SEPARATOR . 'w4-os-server-' . $vmSlug;
    $baselineRemoteRoot ??= '/home/' . $runtimeUsername . '/w4-security-baseline-' . $vmSlug;
    $livePasswordPath = $secretsDir . DIRECTORY_SEPARATOR . 'live-password.txt';
    $localUserPasswordPath = $secretsDir . DIRECTORY_SEPARATOR . 'local-user-password.txt';
    $diskPassphrasePath = $secretsDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt';
    $beforeLuksScreenshotPath = $baseDir . DIRECTORY_SEPARATOR . 'server-' . $evidenceLabel . '-before-luks.png';
    $postloginScreenshotPath = $baseDir . DIRECTORY_SEPARATOR . 'server-' . $evidenceLabel . '-postlogin.png';
    $validationSummaryPath = $baseDir . DIRECTORY_SEPARATOR . 'server-' . $evidenceLabel . '-install-validation.txt';
    $baselineReportPath = $baselineEvidenceDir . DIRECTORY_SEPARATOR . 'security-baseline-report.json';

    if ($livePassword === null || trim($livePassword) === '') {
        $livePassword = readAsciiSecret($livePasswordPath);
    }

    $vboxManage = findVBoxManagePath();
    $enableLiveSshScript = __DIR__ . DIRECTORY_SEPARATOR . 'enable_live_ssh_in_virtualbox.php';
    $prepareBundleScript = __DIR__ . DIRECTORY_SEPARATOR . 'prepare_installation_bundle.php';
    $uploadTreeScript = __DIR__ . DIRECTORY_SEPARATOR . 'upload_tree_via_paramiko.php';
    $runRemoteScript = __DIR__ . DIRECTORY_SEPARATOR . 'run_remote_command_via_paramiko.php';
    $runSecurityBaselineScript = __DIR__ . DIRECTORY_SEPARATOR . 'run_security_baseline_via_paramiko.php';

    $payloadBase = [
        'profile_id' => $profileId,
        'vm_name' => $vmName,
        'vm_dir' => $vmDir,
        'disk_path' => $diskPath,
        'disk_size_mib' => $diskSizeMib,
        'ssh_host' => $sshHost,
        'ssh_port' => $sshPort,
        'memory_mib' => $memoryMib,
        'cpus' => $cpus,
        'boot_wait' => $bootWait,
        'wait_ms' => $waitMs,
        'iso_path' => $isoPath,
        'bundle_dir' => $bundleDir,
        'inventory_path' => $inventoryPath,
        'secrets_dir' => $secretsDir,
        'live_password_path' => $livePasswordPath,
        'local_user_password_path' => $localUserPasswordPath,
        'disk_passphrase_path' => $diskPassphrasePath,
        'vboxmanage_path' => $vboxManage,
        'enable_live_ssh_script' => $enableLiveSshScript,
        'prepare_bundle_script' => $prepareBundleScript,
        'upload_tree_script' => $uploadTreeScript,
        'run_remote_script' => $runRemoteScript,
        'run_security_baseline_script' => $runSecurityBaselineScript,
        'remote_bundle_dir' => '/home/w4live/w4-install-server',
        'remote_secrets_dir' => '/home/w4live/w4-install-secrets',
        'nat_rule' => sprintf('guestssh,tcp,%s,%d,,22', $sshHost, $sshPort),
        'expected_disk_by_path' => 'pci-0000:00:0d.0-ata-1.0',
        'runtime_validation_enabled' => $runtimeValidation,
        'runtime_username' => $runtimeUsername,
        'baseline_bundle_dir' => $baselineBundleDir,
        'baseline_evidence_dir' => $baselineEvidenceDir,
        'baseline_remote_root' => $baselineRemoteRoot,
        'baseline_report_path' => $baselineReportPath,
        'before_luks_screenshot_path' => $beforeLuksScreenshotPath,
        'postlogin_screenshot_path' => $postloginScreenshotPath,
        'validation_summary_path' => $validationSummaryPath,
        'connect_wait' => $connectWait,
        'retry_interval' => $retryInterval,
        'unlock_wait' => $unlockWait,
        'unlock_retry_interval' => $unlockRetryInterval,
        'unlock_retries' => $unlockRetries,
        'unlock_window' => $unlockWindow,
    ];

    if ($checkOnly) {
        printJson($metadataToolkit->createStatusPayload($payloadBase, 'ready'));
        exit(0);
    }

    if (!is_file($isoPath)) {
        throw new ValidationError(sprintf('No existe la ISO indicada: %s', $isoPath));
    }
    if (!is_file($prepareBundleScript) || !is_file($enableLiveSshScript) || !is_file($uploadTreeScript) || !is_file($runRemoteScript) || !is_file($runSecurityBaselineScript)) {
        throw new ValidationError('No se encontraron todos los scripts requeridos para el flujo host->VM');
    }
    if (is_dir($vmDir) || is_file($diskPath)) {
        throw new ValidationError(sprintf('Ya existe un directorio/VM para %s; use otro --vm-name o limpie primero el estado previo', $vmName));
    }
    if (!is_dir($secretsDir)) {
        throw new ValidationError(sprintf('No existe el directorio de secretos: %s', $secretsDir));
    }
    if (!is_file($localUserPasswordPath) || !is_file($diskPassphrasePath)) {
        throw new ValidationError('Faltan secretos requeridos para la instalacion Server');
    }

    ensureDirectory($baseDir);
    $isoSizeBytes = filesize($isoPath);
    if ($isoSizeBytes === false) {
        throw new ValidationError(sprintf('No se pudo determinar el tamano de la ISO: %s', $isoPath));
    }

    writeJsonFile(
        $inventoryPath,
        buildInventory('virtualbox-server-' . $vmSlug, $diskSizeMib, (int) $isoSizeBytes)
    );

    runPhpScript($prepareBundleScript, [
        '--profile',
        $profileId,
        '--disk-inventory',
        $inventoryPath,
        '--bundle-dir',
        $bundleDir,
    ], $rootDir);

    runChecked([$vboxManage, 'createvm', '--name', $vmName, '--ostype', 'Debian_64', '--basefolder', $baseDir, '--register']);
    runChecked([$vboxManage, 'modifyvm', $vmName, '--memory', (string) $memoryMib, '--cpus', (string) $cpus, '--firmware', 'efi', '--graphicscontroller', 'vmsvga', '--vram', '32', '--audio-enabled', 'off', '--boot1', 'dvd', '--boot2', 'disk', '--boot3', 'none', '--boot4', 'none', '--nic1', 'nat']);
    runChecked([$vboxManage, 'modifyvm', $vmName, '--natpf1', sprintf('guestssh,tcp,%s,%d,,22', $sshHost, $sshPort)]);
    runChecked([$vboxManage, 'storagectl', $vmName, '--name', 'SATA', '--add', 'sata', '--controller', 'IntelAhci']);
    runChecked([$vboxManage, 'createmedium', 'disk', '--filename', $diskPath, '--size', (string) $diskSizeMib, '--format', 'VDI']);
    runChecked([$vboxManage, 'storageattach', $vmName, '--storagectl', 'SATA', '--port', '0', '--device', '0', '--type', 'hdd', '--medium', $diskPath]);
    runChecked([$vboxManage, 'storageattach', $vmName, '--storagectl', 'SATA', '--port', '1', '--device', '0', '--type', 'dvddrive', '--medium', $isoPath]);
    runChecked([$vboxManage, 'startvm', $vmName, '--type', 'headless']);

    if ($bootWait > 0) {
        sleep($bootWait);
    }

    $enableLiveSshOutput = runPhpScript($enableLiveSshScript, [
        '--vm-name',
        $vmName,
        '--live-password',
        $livePassword,
        '--ssh-host',
        $sshHost,
        '--ssh-port',
        (string) $sshPort,
        '--wait-ms',
        (string) $waitMs,
    ], $rootDir);

    runPhpScript($uploadTreeScript, [
        '--host',
        $sshHost,
        '--port',
        (string) $sshPort,
        '--username',
        'w4live',
        '--password',
        $livePassword,
        '--local-dir',
        $bundleDir,
        '--remote-dir',
        '/home/w4live/w4-install-server',
        '--replace',
    ], $rootDir);

    runPhpScript($uploadTreeScript, [
        '--host',
        $sshHost,
        '--port',
        (string) $sshPort,
        '--username',
        'w4live',
        '--password',
        $livePassword,
        '--local-dir',
        $secretsDir,
        '--remote-dir',
        '/home/w4live/w4-install-secrets',
        '--replace',
    ], $rootDir);

    $remoteInstallCommand = "sudo bash -lc 'cd /home/w4live/w4-install-server && rm -f install.log install.exit && "
        . "W4_INSTALL_EXECUTE=1 "
        . "W4_INSTALL_SOURCE_ROOTFS=/run/live/rootfs/filesystem.squashfs "
        . "W4_INSTALL_SOURCE_SQUASHFS=/run/live/medium/live/filesystem.squashfs "
        . "W4_DISK_PASSPHRASE_FILE=/home/w4live/w4-install-secrets/disk-passphrase.txt "
        . "W4_LOCAL_USER_PASSWORD_FILE=/home/w4live/w4-install-secrets/local-user-password.txt "
        . "bash ./apply-installation.sh > install.log 2>&1; "
        . "code=$?; echo \"\$code\" > install.exit; echo __W4_INSTALL_EXIT__:\$code'";

    $installExit = runPhpScript($runRemoteScript, [
        '--host',
        $sshHost,
        '--port',
        (string) $sshPort,
        '--username',
        'w4live',
        '--password',
        $livePassword,
        '--command',
        $remoteInstallCommand,
        '--pty',
    ], $rootDir);

    $installOutput = trim($installExit);
    if (!preg_match('/__W4_INSTALL_EXIT__:(\d+)/', $installOutput, $matches)) {
        throw new ValidationError(sprintf('No se pudo extraer install.exit de la salida remota: %s', $installOutput));
    }
    $installExit = $matches[1];
    if ($installExit !== '0') {
        throw new ValidationError(sprintf('La instalacion remota devolvio install.exit=%s', $installExit));
    }

    if (!$runtimeValidation) {
        printJson($metadataToolkit->createSuccessPayload($profileId, array_merge(
            $payloadBase,
            [
                'live_ssh_setup_output' => $enableLiveSshOutput,
                'installation_exit' => $installExit,
                'next_step' => 'reboot_and_validate_encrypted_boot',
            ]
        )));
        exit(0);
    }

    runChecked([$vboxManage, 'controlvm', $vmName, 'acpipowerbutton']);
    waitForVmState($vboxManage, $vmName, 'poweroff', 180);
    runChecked([$vboxManage, 'storageattach', $vmName, '--storagectl', 'SATA', '--port', '1', '--device', '0', '--medium', 'none']);
    runChecked([$vboxManage, 'modifyvm', $vmName, '--boot1', 'disk', '--boot2', 'none', '--boot3', 'none', '--boot4', 'none']);
    runChecked([$vboxManage, 'startvm', $vmName, '--type', 'headless']);

    if ($unlockWait > 0) {
        sleep($unlockWait);
    }

    runChecked([$vboxManage, 'controlvm', $vmName, 'screenshotpng', $beforeLuksScreenshotPath]);

    $baselineOutput = runPhpScript($runSecurityBaselineScript, [
        '--host',
        $sshHost,
        '--port',
        (string) $sshPort,
        '--username',
        $runtimeUsername,
        '--password',
        readAsciiSecret($localUserPasswordPath),
        '--bundle-dir',
        $baselineBundleDir,
        '--remote-root',
        $baselineRemoteRoot,
        '--evidence-dir',
        $baselineEvidenceDir,
        '--sudo',
        '--vm-name',
        $vmName,
        '--luks-passphrase',
        readAsciiSecret($diskPassphrasePath),
        '--unlock-wait',
        (string) $unlockWait,
        '--unlock-retry-interval',
        (string) $unlockRetryInterval,
        '--unlock-retries',
        (string) $unlockRetries,
        '--unlock-window',
        (string) $unlockWindow,
        '--connect-wait',
        (string) $connectWait,
        '--retry-interval',
        (string) $retryInterval,
    ], $rootDir);
    $baselinePayload = decodeLastJsonObject($baselineOutput, 'run_security_baseline_via_paramiko.php');

    runChecked([$vboxManage, 'controlvm', $vmName, 'screenshotpng', $postloginScreenshotPath]);

    $runtimeOutput = runPhpScript($runRemoteScript, [
        '--host',
        $sshHost,
        '--port',
        (string) $sshPort,
        '--username',
        $runtimeUsername,
        '--password',
        readAsciiSecret($localUserPasswordPath),
        '--command',
        'hostname && systemctl is-active ssh && systemctl is-active w4-firstboot',
    ], $rootDir);
    $runtimeState = parseRuntimeState($runtimeOutput);

    $validationSummary = buildValidationSummary(
        $vmName,
        $isoPath,
        $bundleDir,
        $inventoryPath,
        $beforeLuksScreenshotPath,
        $postloginScreenshotPath,
        $baselineReportPath,
        $baselinePayload,
        $runtimeState
    );
    if (file_put_contents($validationSummaryPath, $validationSummary) === false) {
        throw new ValidationError(sprintf('No se pudo escribir el resumen de validacion: %s', $validationSummaryPath));
    }

    printJson($metadataToolkit->createSuccessPayload($profileId, array_merge(
        $payloadBase,
        [
            'live_ssh_setup_output' => $enableLiveSshOutput,
            'installation_exit' => $installExit,
            'baseline_validation_output' => $baselinePayload,
            'runtime_state' => $runtimeState,
            'next_step' => 'complete',
        ]
    )));
} catch (ValidationError $exception) {
    fwrite(STDERR, 'ERROR: ' . $exception->getMessage() . PHP_EOL);
    exit(1);
}
