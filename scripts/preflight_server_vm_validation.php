<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$profileId = 'w4-os-server';
$hypervisor = 'auto';
$expectedSha256 = null;
$isoPath = null;
$manifestPath = null;
$checksumPath = null;
$summaryPath = null;
$bundleDir = null;
$policyPath = null;

try {
    $arguments = $argv ?? [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--profile':
                $profileId = $value;
                break;

            case '--hypervisor':
                $hypervisor = strtolower($value);
                break;

            case '--expected-sha256':
                $expectedSha256 = strtolower($value);
                break;

            case '--iso-path':
                $isoPath = $value;
                break;

            case '--manifest-path':
                $manifestPath = $value;
                break;

            case '--checksum-path':
                $checksumPath = $value;
                break;

            case '--summary-path':
                $summaryPath = $value;
                break;

            case '--bundle-dir':
                $bundleDir = $value;
                break;

            case '--policy-path':
                $policyPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if (!in_array($hypervisor, ['auto', 'virtualbox', 'hyperv', 'none'], true)) {
        throw new ValidationError('--hypervisor debe ser auto, virtualbox, hyperv o none');
    }

    $defaultIsoOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'iso-output' . DIRECTORY_SEPARATOR . $profileId;
    $isoPath ??= $defaultIsoOutputDir . DIRECTORY_SEPARATOR . $profileId . '-live-amd64.iso';
    $manifestPath ??= $defaultIsoOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest';
    $checksumPath ??= $defaultIsoOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'SHA256SUMS';
    $summaryPath ??= $defaultIsoOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'iso-summary.env';
    $bundleDir ??= $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . $profileId;
    $policyPath ??= $rootDir
        . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR . 'editions'
        . DIRECTORY_SEPARATOR . str_replace('w4-os-', '', $profileId)
        . DIRECTORY_SEPARATOR . 'policy.json';

    $checks = [
        'iso_exists' => file_exists($isoPath) && is_file($isoPath),
        'manifest_exists' => file_exists($manifestPath) && is_file($manifestPath),
        'checksums_exists' => file_exists($checksumPath) && is_file($checksumPath),
        'summary_exists' => file_exists($summaryPath) && is_file($summaryPath),
        'edition_policy_exists' => is_file($policyPath),
        'installation_bundle_exists' => is_dir($bundleDir),
        'apply_script_exists' => is_file($bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh'),
        'verify_script_exists' => is_file($bundleDir . DIRECTORY_SEPARATOR . 'verify-installation.sh'),
    ];

    $actualSha256 = $checks['iso_exists'] ? strtolower((string) hash_file('sha256', $isoPath)) : null;
    if ($expectedSha256 !== null) {
        $checks['iso_sha256_matches'] = $actualSha256 === $expectedSha256;
    }

    $forbiddenPackages = ['w4-desktop-meta', 'os-prober', 'pipewire', 'xdg-desktop-portal'];
    $forbiddenFound = [];
    if ($checks['manifest_exists']) {
        $forbiddenFound = findForbiddenPackages($manifestPath, $forbiddenPackages);
    }
    $checks['manifest_headless'] = $checks['manifest_exists'] && $forbiddenFound === [];

    $summaryValues = $checks['summary_exists'] ? parseEnvFile($summaryPath) : [];
    $checks['summary_profile_matches'] = ($summaryValues['W4_PROFILE_ID'] ?? null) === $profileId;
    $policyValues = $checks['edition_policy_exists'] ? readEditionPolicy($policyPath, $profileId) : [];
    $checks['summary_default_target_matches_policy'] = $checks['summary_exists']
        && $checks['edition_policy_exists']
        && (($summaryValues['W4_DEFAULT_TARGET'] ?? null) === ($policyValues['boot']['default_target'] ?? null));

    $hypervisorChecks = detectHypervisors($hypervisor);
    $checks['hypervisor_ready'] = $hypervisor === 'none' || $hypervisorChecks['ready'];

    $ready = !in_array(false, $checks, true);

    printJson([
        'status' => $ready ? 'ready' : 'blocked',
        'profile' => $profileId,
        'iso_path' => $isoPath,
        'iso_sha256' => $actualSha256,
        'expected_sha256' => $expectedSha256,
        'manifest_path' => $manifestPath,
        'policy_path' => $policyPath,
        'forbidden_packages_found' => $forbiddenFound,
        'bundle_dir' => $bundleDir,
        'hypervisor' => $hypervisorChecks,
        'checks' => $checks,
        'next_step' => $ready
            ? 'Arrancar la ISO en una VM UEFI con disco virtual desechable y ejecutar el bundle de instalacion Server.'
            : 'Resolver los checks fallidos antes de intentar una instalacion Server en VM.',
    ]);

    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}

/**
 * @param list<string> $forbiddenPackages
 * @return list<string>
 */
function findForbiddenPackages(string $manifestPath, array $forbiddenPackages): array
{
    $contents = file_get_contents($manifestPath);
    if ($contents === false) {
        throw new ValidationError(sprintf('No se pudo leer el manifest: %s', $manifestPath));
    }

    $packages = [];
    foreach (preg_split('/\r?\n/', $contents) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }

        $parts = preg_split('/\s+/', $line);
        if ($parts !== false && isset($parts[0]) && $parts[0] !== '') {
            $packages[] = $parts[0];
        }
    }

    return array_values(array_intersect($forbiddenPackages, $packages));
}

/**
 * @return array<string, string>
 */
function parseEnvFile(string $path): array
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new ValidationError(sprintf('No se pudo leer metadata: %s', $path));
    }

    $values = [];
    foreach (preg_split('/\r?\n/', $contents) ?: [] as $line) {
        $line = trim($line);
        if ($line === '' || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $values[$key] = trim($value, "\"'");
    }

    return $values;
}

/**
 * @return array<string, mixed>
 */
function readEditionPolicy(string $path, string $expectedProfileId): array
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new ValidationError(sprintf('No se pudo leer la politica de edicion: %s', $path));
    }

    try {
        /** @var array<string, mixed> $policy */
        $policy = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('Politica de edicion invalida en %s: %s', $path, $exception->getMessage()));
    }

    if (($policy['profile_id'] ?? null) !== $expectedProfileId) {
        throw new ValidationError(sprintf('La politica %s no corresponde al profile_id %s', $path, $expectedProfileId));
    }

    return $policy;
}

/**
 * @return array{requested:string,ready:bool,virtualbox:array<string,mixed>,hyperv:array<string,mixed>}
 */
function detectHypervisors(string $requested): array
{
    $virtualbox = [
        'available' => false,
        'path' => null,
    ];
    $hyperv = [
        'available' => false,
        'source' => null,
    ];

    if ($requested === 'none') {
        return [
            'requested' => $requested,
            'ready' => true,
            'virtualbox' => $virtualbox,
            'hyperv' => $hyperv,
        ];
    }

    if ($requested === 'auto' || $requested === 'virtualbox') {
        $virtualbox['path'] = findVirtualBoxManage();
        $virtualbox['available'] = $virtualbox['path'] !== null;
    }

    if ($requested === 'auto' || $requested === 'hyperv') {
        $hyperv['source'] = findHyperVGetVm();
        $hyperv['available'] = $hyperv['source'] !== null;
    }

    return [
        'requested' => $requested,
        'ready' => match ($requested) {
            'virtualbox' => $virtualbox['available'],
            'hyperv' => $hyperv['available'],
            default => $virtualbox['available'] || $hyperv['available'],
        },
        'virtualbox' => $virtualbox,
        'hyperv' => $hyperv,
    ];
}

function findVirtualBoxManage(): ?string
{
    $override = getenv('W4_PREFLIGHT_VBOXMANAGE');
    if (is_string($override) && $override !== '') {
        return is_file($override) ? $override : null;
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

    return findCommand(PHP_OS_FAMILY === 'Windows' ? 'where VBoxManage.exe' : 'command -v VBoxManage');
}

function findHyperVGetVm(): ?string
{
    $override = getenv('W4_PREFLIGHT_HYPERV');
    if (is_string($override) && $override !== '') {
        return in_array(strtolower($override), ['1', 'true', 'yes'], true) ? 'env:W4_PREFLIGHT_HYPERV' : null;
    }

    if (PHP_OS_FAMILY !== 'Windows') {
        return null;
    }

    return findCommand('powershell -NoProfile -Command "Get-Command Hyper-V\\Get-VM -ErrorAction SilentlyContinue | Select-Object -First 1 -ExpandProperty Source"');
}

function findCommand(string $command): ?string
{
    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        return null;
    }

    foreach ($output as $line) {
        $line = trim($line);
        if ($line !== '') {
            return $line;
        }
    }

    return null;
}
