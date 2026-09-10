<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live';
$defaultOutputRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output';

/**
 * @return string
 */
function runWindowsCommand(string $command): string
{
    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        throw new ValidationError(trim(implode(PHP_EOL, $output)) ?: sprintf('Fallo al ejecutar: %s', $command));
    }

    return trim(implode(PHP_EOL, $output));
}

/**
 * @return array<int, string>
 */
function listWslDistros(): array
{
    $output = runWindowsCommand('wsl -l -q');
    $lines = preg_split('/\r?\n/', $output) ?: [];
    $distros = [];

    foreach ($lines as $line) {
        $line = trim(str_replace("\0", '', $line));
        if ($line !== '') {
            $distros[] = $line;
        }
    }

    return $distros;
}

function quoteForWindowsCommand(string $value): string
{
    return '"' . str_replace('"', '\"', $value) . '"';
}

function quoteForBash(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

function quoteWslDistribution(string $value): string
{
    if (preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1) {
        return $value;
    }

    return quoteForWindowsCommand($value);
}

function isWslNativePath(string $path): bool
{
    return str_starts_with($path, '/');
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $bundlePath = null;
    $rootfsDir = null;
    $outputDir = null;
    $distribution = 'Ubuntu';
    $checkOnly = false;

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
            case '--profile':
                $profileId = $value;
                break;

            case '--bundle':
                $bundlePath = $value;
                break;

            case '--rootfs-dir':
                $rootfsDir = $value;
                break;

            case '--output-dir':
                $outputDir = $value;
                break;

            case '--distribution':
                $distribution = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null && $bundlePath === null) {
        throw new ValidationError('Debe indicar --profile o --bundle');
    }

    $availableDistros = listWslDistros();
    if (!in_array($distribution, $availableDistros, true)) {
        throw new ValidationError(sprintf('La distribucion WSL indicada no esta disponible: %s', $distribution));
    }

    if ($bundlePath === null) {
        $bundlePath = $defaultBundleRoot . DIRECTORY_SEPARATOR . $profileId;
    }

    if (!is_dir($bundlePath)) {
        throw new ValidationError(sprintf('No existe el bundle live indicado: %s', $bundlePath));
    }

    $composeScriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'compose-live.sh';
    if (!is_file($composeScriptPath)) {
        throw new ValidationError(sprintf('No existe compose-live.sh en el bundle: %s', $composeScriptPath));
    }

    if ($rootfsDir === null) {
        $rootfsDir = sprintf('/var/tmp/w4-os-system/%s/assembled-rootfs', $profileId ?? basename($bundlePath));
    }

    if ($outputDir === null) {
        $outputDir = $defaultOutputRoot . DIRECTORY_SEPARATOR . ($profileId ?? basename($bundlePath));
    }

    $wslRootfsDir = isWslNativePath($rootfsDir)
        ? $rootfsDir
        : runWindowsCommand(sprintf(
            'wsl -d %s -- wslpath -a %s',
            quoteWslDistribution($distribution),
            quoteForWindowsCommand($rootfsDir)
        ));

    $wslBundlePath = runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($bundlePath)
    ));

    $wslComposeScriptPath = runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($composeScriptPath)
    ));

    $wslOutputDir = isWslNativePath($outputDir)
        ? $outputDir
        : runWindowsCommand(sprintf(
            'wsl -d %s -- wslpath -a %s',
            quoteWslDistribution($distribution),
            quoteForWindowsCommand($outputDir)
        ));

    $runCommand = sprintf(
        'wsl -d %s -u root -- bash -lc %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand(sprintf(
            'test -d %s && mkdir -p %s && chmod +x %s && bash %s %s %s',
            quoteForBash($wslRootfsDir),
            quoteForBash($wslOutputDir),
            quoteForBash($wslComposeScriptPath),
            quoteForBash($wslComposeScriptPath),
            quoteForBash($wslRootfsDir),
            quoteForBash($wslOutputDir)
        ))
    );

    if ($checkOnly) {
        printJson([
            'status' => 'ready',
            'distribution' => $distribution,
            'bundle_path_windows' => $bundlePath,
            'bundle_path_wsl' => $wslBundlePath,
            'rootfs_dir_windows' => isWslNativePath($rootfsDir) ? null : $rootfsDir,
            'rootfs_dir_wsl' => $wslRootfsDir,
            'output_dir_windows' => isWslNativePath($outputDir) ? null : $outputDir,
            'output_dir_wsl' => $wslOutputDir,
            'run_command' => $runCommand,
        ]);
        exit(0);
    }

    $executionOutput = runWindowsCommand($runCommand);

    printJson([
        'status' => 'ok',
        'distribution' => $distribution,
        'bundle_path_windows' => $bundlePath,
        'rootfs_dir_windows' => isWslNativePath($rootfsDir) ? null : $rootfsDir,
        'rootfs_dir_wsl' => $wslRootfsDir,
        'output_dir_windows' => isWslNativePath($outputDir) ? null : $outputDir,
        'output_dir_wsl' => $wslOutputDir,
        'execution_output' => $executionOutput,
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
