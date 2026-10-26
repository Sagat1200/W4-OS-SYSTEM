<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$rootDir = dirname(__DIR__);
$defaultEvidenceDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'system-api' . DIRECTORY_SEPARATOR . 'validation' . DIRECTORY_SEPARATOR . 'native-dbus-real-host' . DIRECTORY_SEPARATOR . 'wsl-current-host-assessment';

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
 * @return array{stdout: string, stderr: string, exit_code: int}
 */
function runWindowsCommandCaptured(string $command): array
{
    $fixtureMap = readJsonFixtureFromEnv('W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_COMMAND_MAP_JSON');
    if ($fixtureMap !== null) {
        $fixture = $fixtureMap[$command] ?? null;
        if ($fixture !== null) {
            if (is_array($fixture)) {
                $stdout = (string) ($fixture['stdout'] ?? '');
                $stderr = (string) ($fixture['stderr'] ?? '');
                $exitCode = (int) ($fixture['exit_code'] ?? 0);
            } else {
                if (!is_scalar($fixture)) {
                    throw new ValidationError(sprintf(
                        'W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_COMMAND_MAP_JSON[%s] debe ser escalar o arreglo',
                        $command
                    ));
                }

                $stdout = (string) $fixture;
                $stderr = '';
                $exitCode = 0;
            }

            return [
                'stdout' => trim($stdout),
                'stderr' => trim($stderr),
                'exit_code' => $exitCode,
            ];
        }
    }

    $output = [];
    $exitCode = 0;
    exec($command . ' 2>&1', $output, $exitCode);

    return [
        'stdout' => trim(implode(PHP_EOL, $output)),
        'stderr' => '',
        'exit_code' => $exitCode,
    ];
}

function runWindowsCommand(string $command): string
{
    $result = runWindowsCommandCaptured($command);
    if ($result['exit_code'] !== 0) {
        $message = $result['stderr'] !== '' ? $result['stderr'] : $result['stdout'];
        if ($message === '') {
            $message = sprintf('Fallo al ejecutar: %s', $command);
        }

        throw new ValidationError($message);
    }

    return $result['stdout'];
}

/**
 * @return array<int, string>
 */
function listWslDistros(): array
{
    $fixture = readJsonFixtureFromEnv('W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_DISTROS_JSON');
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

function quoteWslDistribution(string $value): string
{
    if (preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1) {
        return $value;
    }

    return quoteForWindowsCommand($value);
}

function resolvePathForWsl(string $distribution, string $path): string
{
    return runWindowsCommand(sprintf(
        'wsl -d %s -- wslpath -a %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($path)
    ));
}

function runWslCommand(string $distribution, string $bashCommand): array
{
    $command = sprintf(
        'wsl -d %s -- bash -lc %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($bashCommand)
    );

    return runWindowsCommandCaptured($command);
}

function runWslCommandOrNull(string $distribution, string $bashCommand): ?string
{
    $result = runWslCommand($distribution, $bashCommand);

    return $result['exit_code'] === 0 ? $result['stdout'] : null;
}

function normalizeOptionalOutput(?string $value): ?string
{
    if ($value === null) {
        return null;
    }

    $trimmed = trim($value);

    return $trimmed === '' ? null : $trimmed;
}

/**
 * @param array<string, mixed> $data
 */
function writeJsonArtifact(string $path, array $data): void
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta del artefacto: %s', $directory));
    }

    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    $result = file_put_contents($path, $encoded . PHP_EOL);
    if ($result === false) {
        throw new ValidationError(sprintf('No se pudo escribir el artefacto JSON: %s', $path));
    }
}

function normalizePathForRunner(string $path): string
{
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function resolveExistingNativeDbusBuildEvidenceJsonPath(string $evidenceDir): ?string
{
    $path = normalizePathForRunner($evidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-extension-build-evidence.json');

    return is_file($path) ? $path : null;
}

function resolveExistingNativeDbusBuildLogPath(string $evidenceDir): ?string
{
    $patterns = [
        'native-dbus-extension-build-attempt*.log',
        'native-dbus-extension-build*.log',
        'wsl-ubuntu-operational-attempt*.md',
        '*.log',
        '*.md',
    ];

    $matches = [];
    foreach ($patterns as $pattern) {
        $paths = glob(normalizePathForRunner($evidenceDir . DIRECTORY_SEPARATOR . $pattern));
        if ($paths === false) {
            continue;
        }

        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            $basename = basename($path);
            if ($basename === 'native-dbus-extension-build-evidence.json') {
                continue;
            }

            $matches[$path] = filemtime($path) ?: 0;
        }

        if ($matches !== []) {
            break;
        }
    }

    if ($matches === []) {
        return null;
    }

    arsort($matches, SORT_NUMERIC);

    $selectedPath = array_key_first($matches);

    return is_string($selectedPath) ? $selectedPath : null;
}

/**
 * @return array<string, string>
 */
function collectWslEnvironment(string $distribution): array
{
    $phpVersion = normalizeOptionalOutput(runWslCommandOrNull($distribution, 'php -r \'echo PHP_VERSION, PHP_EOL;\''));
    $phpBinary = normalizeOptionalOutput(runWslCommandOrNull($distribution, 'command -v php'));
    $osFamily = 'Linux';
    $hasPecl = normalizeOptionalOutput(runWslCommandOrNull($distribution, 'command -v pecl')) !== null;
    $hasBusctl = normalizeOptionalOutput(runWslCommandOrNull($distribution, 'command -v busctl')) !== null;
    $hasGdbus = normalizeOptionalOutput(runWslCommandOrNull($distribution, 'command -v gdbus')) !== null;
    $hasDbusDaemon = normalizeOptionalOutput(runWslCommandOrNull($distribution, 'command -v dbus-daemon')) !== null;
    $hasSystemBusSocket = runWslCommand($distribution, 'test -S /run/dbus/system_bus_socket');
    $release = normalizeOptionalOutput(runWslCommandOrNull($distribution, 'cat /etc/os-release'));
    $kernel = normalizeOptionalOutput(runWslCommandOrNull($distribution, 'uname -a'));

    $environment = [
        'W4_SYSTEM_API_PROBE_OS_FAMILY' => $osFamily,
        'W4_SYSTEM_API_PROBE_HAS_PECL' => $hasPecl ? '1' : '0',
        'W4_SYSTEM_API_PROBE_HAS_BUSCTL' => $hasBusctl ? '1' : '0',
        'W4_SYSTEM_API_PROBE_HAS_GDBUS' => $hasGdbus ? '1' : '0',
        'W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON' => $hasDbusDaemon ? '1' : '0',
        'W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET' => $hasSystemBusSocket['exit_code'] === 0 ? '1' : '0',
    ];

    if ($phpVersion !== null) {
        $environment['W4_SYSTEM_API_PROBE_PHP_VERSION'] = $phpVersion;
    }

    if ($phpBinary !== null) {
        $environment['W4_SYSTEM_API_PROBE_PHP_BINARY'] = $phpBinary;
    }

    if ($release !== null) {
        $environment['W4_SYSTEM_API_WSL_RELEASE'] = $release;
    }

    if ($kernel !== null) {
        $environment['W4_SYSTEM_API_WSL_KERNEL'] = $kernel;
    }

    return $environment;
}

/**
 * @param list<string> $arguments
 * @param array<string, string> $environment
 * @return array{exit_code: int, stdout: string, stderr: string}
 */
function runLocalPhpScript(array $arguments, array $environment): array
{
    $command = array_merge([PHP_BINARY], $arguments);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptors, $pipes, dirname(__DIR__), array_merge($_ENV, $environment));
    if (!is_resource($process)) {
        throw new ValidationError('No se pudo ejecutar el runner local de validacion native-dbus');
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);

    return [
        'exit_code' => $exitCode,
        'stdout' => is_string($stdout) ? $stdout : '',
        'stderr' => is_string($stderr) ? $stderr : '',
    ];
}

try {
    $arguments = $argv ?? [];
    $distribution = 'Ubuntu';
    $evidenceDir = $defaultEvidenceDir;
    $format = 'json';
    $attemptOwnership = true;
    $targetRepoRoot = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--distribution':
                $distribution = $value;
                break;

            case '--evidence-dir':
                $evidenceDir = $value;
                break;

            case '--format':
                $format = strtolower($value);
                break;

            case '--attempt-ownership':
                $attemptOwnership = match (strtolower($value)) {
                    'yes', 'true', '1', 'on' => true,
                    'no', 'false', '0', 'off' => false,
                    default => throw new ValidationError(sprintf('Valor no soportado para --attempt-ownership: %s', $value)),
                };
                break;

            case '--target-repo-root':
                $targetRepoRoot = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if (!in_array($format, ['json', 'text', 'markdown'], true)) {
        throw new ValidationError(sprintf('Formato no soportado: %s', $format));
    }

    $availableDistros = listWslDistros();
    if (!in_array($distribution, $availableDistros, true)) {
        throw new ValidationError(sprintf('La distribucion WSL indicada no esta disponible: %s', $distribution));
    }

    $wslRepoRoot = resolvePathForWsl($distribution, $rootDir);
    $effectiveTargetRepoRoot = $targetRepoRoot ?? $wslRepoRoot;
    $collectedEnvironment = collectWslEnvironment($distribution);
    $wslEnvironmentPayload = [
        'distribution' => $distribution,
        'wsl_repo_root' => $wslRepoRoot,
        'preparation_target_repo_root' => $effectiveTargetRepoRoot,
        'php_version' => $collectedEnvironment['W4_SYSTEM_API_PROBE_PHP_VERSION'] ?? null,
        'php_binary' => $collectedEnvironment['W4_SYSTEM_API_PROBE_PHP_BINARY'] ?? null,
        'has_pecl' => ($collectedEnvironment['W4_SYSTEM_API_PROBE_HAS_PECL'] ?? '0') === '1',
        'has_busctl' => ($collectedEnvironment['W4_SYSTEM_API_PROBE_HAS_BUSCTL'] ?? '0') === '1',
        'has_gdbus' => ($collectedEnvironment['W4_SYSTEM_API_PROBE_HAS_GDBUS'] ?? '0') === '1',
        'has_dbus_daemon' => ($collectedEnvironment['W4_SYSTEM_API_PROBE_HAS_DBUS_DAEMON'] ?? '0') === '1',
        'has_system_bus_socket' => ($collectedEnvironment['W4_SYSTEM_API_PROBE_HAS_SYSTEM_BUS_SOCKET'] ?? '0') === '1',
        'release' => $collectedEnvironment['W4_SYSTEM_API_WSL_RELEASE'] ?? null,
        'kernel' => $collectedEnvironment['W4_SYSTEM_API_WSL_KERNEL'] ?? null,
    ];
    $wslEnvironmentReportPath = $evidenceDir . DIRECTORY_SEPARATOR . 'wsl-host-environment.json';
    writeJsonArtifact($wslEnvironmentReportPath, [
        'api_version' => '1.0',
        'kind' => 'system-api-native-dbus-wsl-host-environment',
        'generated_at' => gmdate(DATE_ATOM),
        'environment' => $wslEnvironmentPayload,
    ]);

    $localRunnerArguments = [
        $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_native_dbus_real_host_validation.php',
        '--evidence-dir',
        $evidenceDir,
        '--format',
        $format,
        '--attempt-ownership',
        $attemptOwnership ? 'true' : 'false',
        '--target-os-family',
        'Linux',
        '--target-repo-root',
        $effectiveTargetRepoRoot,
    ];

    $nativeDbusBuildEvidenceJsonPath = resolveExistingNativeDbusBuildEvidenceJsonPath($evidenceDir);
    if ($nativeDbusBuildEvidenceJsonPath !== null) {
        $localRunnerArguments[] = '--native-dbus-build-evidence-json-path';
        $localRunnerArguments[] = $nativeDbusBuildEvidenceJsonPath;
    } else {
        $nativeDbusBuildLogPath = resolveExistingNativeDbusBuildLogPath($evidenceDir);
        if ($nativeDbusBuildLogPath !== null) {
            $localRunnerArguments[] = '--native-dbus-build-log-path';
            $localRunnerArguments[] = $nativeDbusBuildLogPath;
        }
    }

    $runnerResult = runLocalPhpScript($localRunnerArguments, $collectedEnvironment);

    if ($format === 'json') {
        $payload = json_decode($runnerResult['stdout'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($payload)) {
            throw new ValidationError('El runner local no devolvio un objeto JSON valido');
        }

        $payload['wsl_distribution'] = $distribution;
        $payload['wsl_repo_root'] = $wslRepoRoot;
        $payload['wsl_environment'] = $wslEnvironmentPayload;
        $payload['wsl_environment_report_path'] = $wslEnvironmentReportPath;

        printJson($payload);
    } else {
        fwrite(STDOUT, $runnerResult['stdout']);
    }

    exit($runnerResult['exit_code']);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'ERROR: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
