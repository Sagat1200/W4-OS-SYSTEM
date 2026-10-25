<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$defaultStoreRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'update-operations';

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

function runWindowsCommand(string $command): string
{
    $fixtureMap = readJsonFixtureFromEnv('W4_RUN_SYSTEM_API_UPDATE_BUNDLE_WSL_COMMAND_MAP_JSON');
    if ($fixtureMap !== null) {
        $fixture = $fixtureMap[$command] ?? null;
        if ($fixture !== null) {
            if (!is_scalar($fixture)) {
                throw new ValidationError(sprintf(
                    'W4_RUN_SYSTEM_API_UPDATE_BUNDLE_WSL_COMMAND_MAP_JSON[%s] debe ser escalar',
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
    $fixture = readJsonFixtureFromEnv('W4_RUN_SYSTEM_API_UPDATE_BUNDLE_WSL_DISTROS_JSON');
    if ($fixture !== null) {
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

function resolvePathForWsl(string $distribution, string $path): string
{
    if (isWslNativePath($path)) {
        return $path;
    }

    return runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($path)
    ));
}

/**
 * @param array<int, string> $scriptNames
 */
function buildChmodCommand(array $scriptNames): string
{
    $quoted = array_map(static fn (string $name): string => quoteForBash('./' . $name), $scriptNames);

    return 'chmod +x ' . implode(' ', $quoted);
}

try {
    $arguments = $argv ?? [];
    $storeDir = null;
    $operationId = null;
    $distribution = 'Ubuntu';
    $stage = 'offline';
    $checkOnly = false;
    $repositoryDir = null;
    $repositoryEnv = null;

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
            case '--store-dir':
                $storeDir = $value;
                break;

            case '--operation-id':
                $operationId = $value;
                break;

            case '--distribution':
                $distribution = $value;
                break;

            case '--stage':
                $stage = $value;
                break;

            case '--repository-dir':
                $repositoryDir = $value;
                break;

            case '--repository-env':
                $repositoryEnv = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($storeDir === null && $operationId === null) {
        throw new ValidationError('Debe indicar --store-dir o --operation-id');
    }

    if ($storeDir !== null && $operationId !== null) {
        throw new ValidationError('Use --store-dir o --operation-id, no ambos a la vez');
    }

    if (!in_array($stage, ['offline', 'health', 'reconcile'], true)) {
        throw new ValidationError('stage debe ser offline, health o reconcile');
    }

    if ($repositoryDir !== null && $repositoryEnv !== null) {
        throw new ValidationError('Use --repository-dir o --repository-env, no ambos a la vez');
    }

    $availableDistros = listWslDistros();
    if (!in_array($distribution, $availableDistros, true)) {
        throw new ValidationError(sprintf('La distribucion WSL indicada no esta disponible: %s', $distribution));
    }

    if ($storeDir === null) {
        $storeDir = $defaultStoreRoot . DIRECTORY_SEPARATOR . preg_replace('/[^A-Za-z0-9._-]/', '-', trim((string) $operationId));
    }

    if (!is_dir($storeDir)) {
        throw new ValidationError(sprintf('No existe el store durable indicado: %s', $storeDir));
    }

    $requiredArtifacts = [
        'executor.env',
        'run-update-offline.sh',
        'run-update-with-repo-env.sh',
        'run-health-checks.sh',
        'reconcile-after-reboot.sh',
        'update-executor.json',
        'update-plan.json',
    ];

    foreach ($requiredArtifacts as $artifact) {
        $path = $storeDir . DIRECTORY_SEPARATOR . $artifact;
        if (!is_file($path)) {
            throw new ValidationError(sprintf('Falta un artefacto requerido del bundle: %s', $path));
        }
    }

    $wslStoreDir = resolvePathForWsl($distribution, $storeDir);
    $wslRepositoryDir = $repositoryDir !== null ? resolvePathForWsl($distribution, $repositoryDir) : null;
    $wslRepositoryEnv = $repositoryEnv !== null ? resolvePathForWsl($distribution, $repositoryEnv) : null;
    $healthResultPath = $storeDir . DIRECTORY_SEPARATOR . 'health-check-results.json';
    $wslHealthResultPath = resolvePathForWsl($distribution, $healthResultPath);
    $bundleScripts = [
        'run-update-offline.sh',
        'run-update-with-repo-env.sh',
        'run-health-checks.sh',
        'reconcile-after-reboot.sh',
    ];
    $chmodCommand = buildChmodCommand($bundleScripts);

    $offlineCommand = sprintf(
        'cd %s && %s && source ./executor.env && %s',
        quoteForBash($wslStoreDir),
        $chmodCommand,
        $wslRepositoryDir !== null
            ? './run-update-with-repo-env.sh --repo-dir ' . quoteForBash($wslRepositoryDir)
            : ($wslRepositoryEnv !== null
                ? './run-update-with-repo-env.sh --repo-env ' . quoteForBash($wslRepositoryEnv)
                : './run-update-offline.sh')
    );
    $healthCommand = sprintf(
        'cd %s && %s && source ./executor.env && ./run-health-checks.sh',
        quoteForBash($wslStoreDir),
        $chmodCommand
    );
    $reconcileCommand = sprintf(
        'cd %s && %s && source ./executor.env && ./reconcile-after-reboot.sh',
        quoteForBash($wslStoreDir),
        $chmodCommand
    );

    $selectedCommand = match ($stage) {
        'offline' => $offlineCommand,
        'health' => $healthCommand,
        'reconcile' => $reconcileCommand,
    };

    $runCommand = sprintf(
        'wsl -d %s -- bash -lc %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($selectedCommand)
    );

    if ($checkOnly) {
        printJson([
            'status' => 'ready',
            'distribution' => $distribution,
            'operation_id' => $operationId,
            'store_dir_windows' => isWslNativePath($storeDir) ? null : $storeDir,
            'store_dir_wsl' => $wslStoreDir,
            'health_result_path_windows' => isWslNativePath($healthResultPath) ? null : $healthResultPath,
            'health_result_path_wsl' => $wslHealthResultPath,
            'stage' => $stage,
            'repository_dir_windows' => ($repositoryDir !== null && !isWslNativePath($repositoryDir)) ? $repositoryDir : null,
            'repository_dir_wsl' => $wslRepositoryDir,
            'repository_env_windows' => ($repositoryEnv !== null && !isWslNativePath($repositoryEnv)) ? $repositoryEnv : null,
            'repository_env_wsl' => $wslRepositoryEnv,
            'offline_command' => $offlineCommand,
            'health_command' => $healthCommand,
            'reconcile_command' => $reconcileCommand,
            'run_command' => $runCommand,
            'required_artifacts' => $requiredArtifacts,
        ]);
        exit(0);
    }

    $executionOutput = runWindowsCommand($runCommand);

    printJson([
        'status' => 'ok',
        'distribution' => $distribution,
        'operation_id' => $operationId,
        'store_dir_windows' => isWslNativePath($storeDir) ? null : $storeDir,
        'store_dir_wsl' => $wslStoreDir,
        'health_result_path_windows' => isWslNativePath($healthResultPath) ? null : $healthResultPath,
        'health_result_path_wsl' => $wslHealthResultPath,
        'stage' => $stage,
        'repository_dir_windows' => ($repositoryDir !== null && !isWslNativePath($repositoryDir)) ? $repositoryDir : null,
        'repository_dir_wsl' => $wslRepositoryDir,
        'repository_env_windows' => ($repositoryEnv !== null && !isWslNativePath($repositoryEnv)) ? $repositoryEnv : null,
        'repository_env_wsl' => $wslRepositoryEnv,
        'offline_command' => $offlineCommand,
        'health_command' => $healthCommand,
        'reconcile_command' => $reconcileCommand,
        'run_command' => $runCommand,
        'execution_output' => $executionOutput,
    ]);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'ERROR: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
