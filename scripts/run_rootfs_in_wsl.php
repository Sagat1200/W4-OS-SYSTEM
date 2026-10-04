<?php

declare(strict_types=1);

use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'rootfs';

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
 * @return string
 */
function runWindowsCommand(string $command): string
{
    $fixtureMap = readJsonFixtureFromEnv('W4_WSL_RUNNER_COMMAND_MAP_JSON');
    if ($fixtureMap !== null) {
        $fixture = $fixtureMap[$command] ?? null;
        if ($fixture !== null) {
            if (!is_scalar($fixture)) {
                throw new ValidationError(sprintf(
                    'W4_WSL_RUNNER_COMMAND_MAP_JSON[%s] debe ser escalar',
                    $command
                ));
            }

            return trim((string) $fixture);
        }
    }

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
    $fixture = readJsonFixtureFromEnv('W4_WSL_RUNNER_DISTROS_JSON');
    if ($fixture !== null) {
        /** @var array<int, string> $distros */
        $distros = [];
        foreach ($fixture as $value) {
            if (is_string($value) && trim($value) !== '') {
                $distros[] = trim($value);
            }
        }

        return $distros;
    }

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

function quoteForBash(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

function quoteForWindowsCommand(string $value): string
{
    return '"' . str_replace('"', '\"', $value) . '"';
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

function canUseSudoWithoutPassword(string $distribution): bool
{
    $override = getenv('W4_WSL_RUNNER_CAN_USE_SUDO');
    if ($override !== false && trim($override) !== '') {
        return in_array(strtolower(trim($override)), ['1', 'true', 'yes'], true);
    }

    $output = [];
    $exitCode = 0;
    $command = sprintf(
        'wsl -d %s -- bash -lc %s 2>&1',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand('sudo -n true')
    );
    exec($command, $output, $exitCode);

    return $exitCode === 0;
}

/**
 * @return array<string, string>
 */
function detectDependencies(string $distribution): array
{
    $commands = ['debootstrap', 'sudo', 'chroot'];
    $result = [];

    foreach ($commands as $command) {
        $wslCommand = sprintf(
            'wsl -d %s -- bash -lc %s',
            quoteWslDistribution($distribution),
            quoteForWindowsCommand(sprintf('command -v %s || true', $command))
        );
        $result[$command] = trim(runWindowsCommand($wslCommand));
    }

    return $result;
}

/**
 * @param array<string, string> $dependencies
 * @return list<string>
 */
function missingDependencies(array $dependencies): array
{
    $missing = [];
    foreach ($dependencies as $command => $path) {
        if ($path === '') {
            $missing[] = $command;
        }
    }

    return $missing;
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $bundlePath = null;
    $distribution = 'Ubuntu';
    $checkOnly = false;
    $installDeps = false;
    $rootfsDir = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if ($argument === '--check-only') {
            $checkOnly = true;
            continue;
        }

        if ($argument === '--install-deps') {
            $installDeps = true;
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

            case '--distribution':
                $distribution = $value;
                break;

            case '--rootfs-dir':
                $rootfsDir = $value;
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
        throw new ValidationError(sprintf(
            'La distribucion WSL indicada no esta disponible: %s',
            $distribution
        ));
    }

    if ($bundlePath === null) {
        $bundlePath = $defaultBundleRoot . DIRECTORY_SEPARATOR . $profileId;
    }

    if (!is_dir($bundlePath)) {
        throw new ValidationError(sprintf('No existe el bundle rootfs: %s', $bundlePath));
    }

    $scriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'build-rootfs.sh';
    if (!is_file($scriptPath)) {
        throw new ValidationError(sprintf('No existe build-rootfs.sh en el bundle: %s', $scriptPath));
    }

    $wslBundlePath = runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($bundlePath)
    ));
    $wslScriptPath = runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($scriptPath)
    ));

    if ($rootfsDir === null) {
        $rootfsDir = sprintf('/var/tmp/w4-os-system/%s/assembled-rootfs', $profileId ?? basename($bundlePath));
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

    $dependencies = detectDependencies($distribution);
    $missing = missingDependencies($dependencies);
    $canUseSudo = canUseSudoWithoutPassword($distribution);

    if ($installDeps && $missing !== []) {
        $packages = implode(' ', $missing);
        $installCommand = sprintf(
            'wsl -d %s -- bash -lc %s',
            quoteWslDistribution($distribution),
            quoteForWindowsCommand(sprintf('sudo apt-get update && sudo apt-get install -y %s', $packages))
        );
        runWindowsCommand($installCommand);
        $dependencies = detectDependencies($distribution);
        $missing = missingDependencies($dependencies);
    }

    if ($checkOnly) {
        $metadataToolkit = new ArtifactMetadataToolkit();
        $runCommand = $canUseSudo
            ? sprintf(
                'wsl -d %s -- bash -lc %s',
                $distribution,
                sprintf(
                    'sudo bash %s %s',
                    quoteForBash($wslScriptPath),
                    quoteForBash($wslRootfsDir)
                )
            )
            : sprintf(
                'wsl -d %s -u root -- bash -lc %s',
                $distribution,
                sprintf(
                    'bash %s %s',
                    quoteForBash($wslScriptPath),
                    quoteForBash($wslRootfsDir)
                )
            );

        printJson($metadataToolkit->createStatusPayload(
            [
                'distribution' => $distribution,
                'bundle_path_windows' => $bundlePath,
                'bundle_path_wsl' => $wslBundlePath,
                'rootfs_dir_windows' => isWslNativePath($rootfsDir) ? null : $rootfsDir,
                'rootfs_dir_wsl' => $wslRootfsDir,
                'dependencies' => $dependencies,
                'sudo_non_interactive' => $canUseSudo,
                'missing_dependencies' => $missing,
                'run_command' => $runCommand,
            ],
            $missing === [] ? 'ready' : 'missing_dependencies'
        ));
        exit(0);
    }

    if ($missing !== []) {
        throw new ValidationError(
            'Faltan dependencias en WSL: ' . implode(', ', $missing) . '. Ejecuta con --check-only o --install-deps.'
        );
    }

    $innerCommand = sprintf(
        'chmod +x %s && %s %s',
        quoteForBash($wslScriptPath),
        $canUseSudo ? 'sudo bash' : 'bash',
        implode(' ', [
            quoteForBash($wslScriptPath),
            quoteForBash($wslRootfsDir),
        ])
    );

    $runCommand = $canUseSudo
        ? sprintf(
            'wsl -d %s -- bash -lc %s',
            quoteWslDistribution($distribution),
            quoteForWindowsCommand($innerCommand)
        )
        : sprintf(
            'wsl -d %s -u root -- bash -lc %s',
            quoteWslDistribution($distribution),
            quoteForWindowsCommand($innerCommand)
        );

    $executionOutput = runWindowsCommand($runCommand);

    $metadataToolkit = new ArtifactMetadataToolkit();
    printJson($metadataToolkit->createStatusPayload([
        'distribution' => $distribution,
        'bundle_path_windows' => $bundlePath,
        'rootfs_dir_windows' => isWslNativePath($rootfsDir) ? null : $rootfsDir,
        'rootfs_dir_wsl' => $wslRootfsDir,
        'execution_output' => $executionOutput,
    ]));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
