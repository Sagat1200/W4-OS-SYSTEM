<?php

declare(strict_types=1);

$bundleDir = __DIR__;
$baselinePath = $bundleDir . DIRECTORY_SEPARATOR . 'security-baseline.json';
$reportPath = $argv[1] ?? ($bundleDir . DIRECTORY_SEPARATOR . 'security-baseline-report.json');
$raw = file_get_contents($baselinePath);
if ($raw === false) {
    fwrite(STDERR, "ERROR: No se pudo leer security-baseline.json\n");
    exit(1);
}

try {
    /** @var array<string, mixed> $baseline */
    $baseline = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $exception) {
    fwrite(STDERR, sprintf("ERROR: JSON invalido en security-baseline.json: %s\n", $exception->getMessage()));
    exit(1);
}

/**
 * @return array{exit_code:int,stdout:string,stderr:string}
 */
function runCommand(string $command): array
{
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open(['/bin/bash', '-lc', $command], $descriptorSpec, $pipes, __DIR__);
    if (!is_resource($process)) {
        return ['exit_code' => 1, 'stdout' => '', 'stderr' => 'No se pudo crear el proceso'];
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return [
        'exit_code' => proc_close($process),
        'stdout' => is_string($stdout) ? trim($stdout) : '',
        'stderr' => is_string($stderr) ? trim($stderr) : '',
    ];
}

function addResult(array &$results, string $id, string $status, string $detail): void
{
    $results[] = [
        'id' => $id,
        'status' => $status,
        'detail' => $detail,
    ];
}

function resolveBinary(array $candidates): string
{
    $searchDirectories = [
        '/usr/local/sbin',
        '/usr/local/bin',
        '/usr/sbin',
        '/usr/bin',
        '/sbin',
        '/bin',
    ];

    foreach ($candidates as $candidate) {
        $commandResult = runCommand('command -v ' . escapeshellarg($candidate));
        if ($commandResult['exit_code'] === 0 && $commandResult['stdout'] !== '') {
            return $commandResult['stdout'];
        }

        foreach ($searchDirectories as $directory) {
            $path = $directory . DIRECTORY_SEPARATOR . $candidate;
            if (is_file($path) && is_executable($path)) {
                return $path;
            }
        }
    }

    return '';
}

$results = [];
$username = 'w4admin';
$rootPrefix = '/dev/mapper/';
$acceptedFirewallCommands = array (
  0 => 'ufw',
  1 => 'nft',
);
$forbiddenEnabledStates = array (
  0 => 'enabled',
  1 => 'enabled-runtime',
  2 => 'linked',
  3 => 'linked-runtime',
  4 => 'alias',
);

$rootSource = runCommand("findmnt -n -o SOURCE /");
if ($rootSource['exit_code'] === 0 && str_starts_with($rootSource['stdout'], $rootPrefix)) {
    addResult($results, 'encrypted-root', 'passed', 'La raiz esta montada desde ' . $rootSource['stdout']);
} else {
    addResult($results, 'encrypted-root', 'failed', 'La raiz no esta montada sobre un mapper cifrado visible');
}

$account = runCommand('id -u ' . escapeshellarg($username));
if ($account['exit_code'] === 0 && $account['stdout'] !== '0') {
    addResult($results, 'standard-account', 'passed', 'La cuenta ' . $username . ' existe y no usa UID 0');
} else {
    addResult($results, 'standard-account', 'failed', 'La cuenta esperada no existe o usa UID 0');
}

$sudo = runCommand('command -v sudo');
if ($sudo['exit_code'] === 0 && $sudo['stdout'] !== '') {
    addResult($results, 'auditable-admin-path', 'passed', 'sudo esta disponible en ' . $sudo['stdout']);
} else {
    addResult($results, 'auditable-admin-path', 'failed', 'sudo no esta disponible');
}

$sshService = runCommand('systemctl is-enabled ssh 2>/dev/null || systemctl is-enabled ssh.service 2>/dev/null');
if ($sshService['exit_code'] !== 0) {
    addResult($results, 'remote-admin-disabled-by-default', 'passed', 'ssh no esta habilitado por defecto');
} elseif (in_array($sshService['stdout'], $forbiddenEnabledStates, true)) {
    addResult($results, 'remote-admin-disabled-by-default', 'failed', 'ssh aparece habilitado: ' . $sshService['stdout']);
} else {
    addResult($results, 'remote-admin-disabled-by-default', 'passed', 'ssh no esta habilitado por defecto (' . $sshService['stdout'] . ')');
}

$firewallCommand = resolveBinary($acceptedFirewallCommands);
if ($firewallCommand !== '') {
    addResult($results, 'firewall-control-plane', 'passed', 'Herramienta de firewall disponible: ' . $firewallCommand);
} else {
    addResult($results, 'firewall-control-plane', 'failed', 'No se encontro ufw ni nft en la imagen');
}

$apparmor = runCommand("test -r /sys/module/apparmor/parameters/enabled && grep -qx 'Y' /sys/module/apparmor/parameters/enabled");
if ($apparmor['exit_code'] === 0) {
    addResult($results, 'mac-enforcement', 'passed', 'AppArmor aparece activo en el kernel');
} else {
    addResult($results, 'mac-enforcement', 'failed', 'No se pudo confirmar AppArmor activo');
}

addResult($results, 'authenticated-updates', 'skipped', 'Control validado por pipeline firmado; revisar security-baseline.json y evidencia de MX-004');

$summary = [
    'passed' => 0,
    'failed' => 0,
    'skipped' => 0,
];

foreach ($results as $result) {
    $summary[$result['status']]++;
}

$report = [
    'security_baseline_report_schema_version' => 1,
    'kind' => 'security-baseline-report',
    'profile_id' => $baseline['profile_id'],
    'generated_at' => gmdate(DATE_ATOM),
    'results' => $results,
    'summary' => $summary,
];

$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($json === false || file_put_contents($reportPath, $json . PHP_EOL) === false) {
    fwrite(STDERR, "ERROR: No se pudo escribir el reporte de baseline\n");
    exit(1);
}

fwrite(STDOUT, $json . PHP_EOL);
exit($summary['failed'] === 0 ? 0 : 2);