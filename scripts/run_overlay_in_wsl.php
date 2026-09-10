<?php

declare(strict_types=1);

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultOverlayRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays';

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
    $overlayPath = null;
    $rootfsDir = null;
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

            case '--overlay':
                $overlayPath = $value;
                break;

            case '--rootfs-dir':
                $rootfsDir = $value;
                break;

            case '--distribution':
                $distribution = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null && $overlayPath === null) {
        throw new ValidationError('Debe indicar --profile o --overlay');
    }

    $availableDistros = listWslDistros();
    if (!in_array($distribution, $availableDistros, true)) {
        throw new ValidationError(sprintf('La distribucion WSL indicada no esta disponible: %s', $distribution));
    }

    if ($overlayPath === null) {
        $overlayPath = $defaultOverlayRoot . DIRECTORY_SEPARATOR . $profileId;
    }

    if (!is_dir($overlayPath)) {
        throw new ValidationError(sprintf('No existe el overlay indicado: %s', $overlayPath));
    }

    $applyScriptPath = $overlayPath . DIRECTORY_SEPARATOR . 'apply-overlay.sh';
    if (!is_file($applyScriptPath)) {
        throw new ValidationError(sprintf('No existe apply-overlay.sh en el overlay: %s', $applyScriptPath));
    }

    if ($rootfsDir === null) {
        $rootfsDir = sprintf('/var/tmp/w4-os-system/%s/assembled-rootfs', $profileId ?? basename($overlayPath));
    }

    if (isWslNativePath($rootfsDir)) {
        $wslRootfsDir = $rootfsDir;
    } else {
        $wslRootfsDir = runWindowsCommand(sprintf(
            'wsl -d %s -- wslpath -a %s',
            quoteWslDistribution($distribution),
            quoteForWindowsCommand($rootfsDir)
        ));
    }

    if (!is_dir($overlayPath)) {
        throw new ValidationError(sprintf('No existe el overlay a aplicar: %s', $overlayPath));
    }

    $wslOverlayPath = runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($overlayPath)
    ));

    $wslApplyScriptPath = runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($applyScriptPath)
    ));

    $runCommand = sprintf(
        'wsl -d %s -u root -- bash -lc %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand(sprintf(
            'test -d %s && chmod +x %s && bash %s %s',
            quoteForBash($wslRootfsDir),
            quoteForBash($wslApplyScriptPath),
            quoteForBash($wslApplyScriptPath),
            quoteForBash($wslRootfsDir)
        ))
    );

    if ($checkOnly) {
        printJson([
            'status' => 'ready',
            'distribution' => $distribution,
            'overlay_path_windows' => $overlayPath,
            'overlay_path_wsl' => $wslOverlayPath,
            'rootfs_dir_windows' => isWslNativePath($rootfsDir) ? null : $rootfsDir,
            'rootfs_dir_wsl' => $wslRootfsDir,
            'run_command' => $runCommand,
        ]);
        exit(0);
    }

    $executionOutput = runWindowsCommand($runCommand);

    printJson([
        'status' => 'ok',
        'distribution' => $distribution,
        'overlay_path_windows' => $overlayPath,
        'rootfs_dir_windows' => isWslNativePath($rootfsDir) ? null : $rootfsDir,
        'rootfs_dir_wsl' => $wslRootfsDir,
        'execution_output' => $executionOutput,
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
