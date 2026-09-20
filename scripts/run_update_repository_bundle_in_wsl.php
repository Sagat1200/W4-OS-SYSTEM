<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'repositories';
$defaultOutputRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'update' . DIRECTORY_SEPARATOR . 'repository-output';

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
    $snapshotId = null;
    $bundlePath = null;
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
            case '--snapshot-id':
                $snapshotId = $value;
                break;

            case '--bundle':
                $bundlePath = $value;
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

    if ($snapshotId === null && $bundlePath === null) {
        throw new ValidationError('Debe indicar --snapshot-id o --bundle');
    }

    $availableDistros = listWslDistros();
    if (!in_array($distribution, $availableDistros, true)) {
        throw new ValidationError(sprintf('La distribucion WSL indicada no esta disponible: %s', $distribution));
    }

    if ($bundlePath === null) {
        $bundlePath = $defaultBundleRoot . DIRECTORY_SEPARATOR . $snapshotId;
    }

    if (!is_dir($bundlePath)) {
        throw new ValidationError(sprintf('No existe el bundle de repositorio indicado: %s', $bundlePath));
    }

    $buildScriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'build-repo.sh';
    if (!is_file($buildScriptPath)) {
        throw new ValidationError(sprintf('No existe build-repo.sh en el bundle: %s', $buildScriptPath));
    }

    if ($outputDir === null) {
        $outputDir = $defaultOutputRoot . DIRECTORY_SEPARATOR . ($snapshotId ?? basename($bundlePath));
    }

    $wslBuildScriptPath = runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($buildScriptPath)
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
            'mkdir -p %s && chmod +x %s && bash %s %s',
            quoteForBash($wslOutputDir),
            quoteForBash($wslBuildScriptPath),
            quoteForBash($wslBuildScriptPath),
            quoteForBash($wslOutputDir)
        ))
    );

    if ($checkOnly) {
        printJson([
            'status' => 'ready',
            'distribution' => $distribution,
            'bundle_path_windows' => $bundlePath,
            'build_script_path_wsl' => $wslBuildScriptPath,
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
        'output_dir_windows' => isWslNativePath($outputDir) ? null : $outputDir,
        'output_dir_wsl' => $wslOutputDir,
        'execution_output' => $executionOutput,
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
