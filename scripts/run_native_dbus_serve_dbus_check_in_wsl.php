<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use W4\OS\Support\ValidationError;

$rootDir = dirname(__DIR__);
$defaultEvidenceDir = $rootDir
    . DIRECTORY_SEPARATOR . 'build'
    . DIRECTORY_SEPARATOR . 'system-api'
    . DIRECTORY_SEPARATOR . 'validation'
    . DIRECTORY_SEPARATOR . 'native-dbus-real-host'
    . DIRECTORY_SEPARATOR . 'wsl-serve-dbus-check';
$distribution = null;
$evidenceDir = $defaultEvidenceDir;
$keepAliveSeconds = 20;

try {
    $arguments = $argv ?? [];

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--distribution':
                $distribution = trim($value);
                break;

            case '--evidence-dir':
                $evidenceDir = $value;
                break;

            case '--keepalive-seconds':
                if (preg_match('/^\d+$/', trim($value)) !== 1) {
                    throw new ValidationError('--keepalive-seconds debe ser un entero positivo');
                }

                $keepAliveSeconds = max(1, min(30, (int) trim($value)));
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    $resolvedDistribution = $distribution;
    if ($resolvedDistribution === null || $resolvedDistribution === '') {
        $distros = listWslDistros();
        if ($distros === []) {
            throw new ValidationError('No se encontraron distribuciones WSL disponibles');
        }

        $resolvedDistribution = $distros[0];
    }

    $evidenceDir = normalizePathForRunner($evidenceDir);
    ensureDirectory($evidenceDir);

    $wslRepoRoot = resolvePathForWsl($resolvedDistribution, $rootDir);
    $wslEvidenceDir = resolvePathForWsl($resolvedDistribution, $evidenceDir);
    $wslUser = normalizeOptionalOutput(runWslCommandOrNull($resolvedDistribution, 'id -un'));
    if ($wslUser === null) {
        throw new ValidationError('No se pudo determinar el usuario por defecto de la distribucion WSL');
    }

    $callOutputPath = $evidenceDir . DIRECTORY_SEPARATOR . 'serve-dbus-call-output.txt';
    $introspectionPath = $evidenceDir . DIRECTORY_SEPARATOR . 'gdbus-introspect.txt';
    $launcherStdoutPath = $evidenceDir . DIRECTORY_SEPARATOR . 'serve-dbus-launcher-output.json';
    $launcherStderrPath = $evidenceDir . DIRECTORY_SEPARATOR . 'serve-dbus-launcher-error.txt';
    $reportPath = $evidenceDir . DIRECTORY_SEPARATOR . 'serve-dbus-check-report.json';
    $summaryPath = $evidenceDir . DIRECTORY_SEPARATOR . 'serve-dbus-check-summary.md';

    $rootBashCommand = buildServeDbusCheckCommand($wslUser, $wslRepoRoot, $wslEvidenceDir, $keepAliveSeconds);
    $rootCommandResult = hasCommandFixturesConfigured()
        ? runWslRootCommand($resolvedDistribution, $rootBashCommand)
        : runWslRootScript($resolvedDistribution, $rootBashCommand);

    $stdout = trim($rootCommandResult['stdout']);
    $stderr = trim($rootCommandResult['stderr']);
    $busOwnerObserved = preg_match('/^org\.w4\.System1\b/m', $stdout) === 1;
    $launcherCommandValidated = str_contains($stdout, '"requested_command": "serve-dbus"')
        && str_contains($stdout, '"command": "StartNativeRuntimeService"');
    $getCapabilitiesValidated = str_contains($stdout, 'dbus-launcher-capabilities')
        && str_contains($stdout, '"method": "system.capabilities"');
    $callValidated = str_contains($stdout, 'dbus-launcher-call')
        && str_contains($stdout, '"method": "system.dbus-runtime"');
    $validated = $rootCommandResult['exit_code'] === 0
        && $busOwnerObserved
        && $launcherCommandValidated
        && $getCapabilitiesValidated
        && $callValidated;

    $blockingReason = $validated
        ? null
        : (str_contains($stdout . "\n" . $stderr, 'ServiceUnknown')
            ? 'packaged-serve-dbus-owner-not-materialized'
            : 'native-dbus-serve-dbus-check-failed');
    $nextAction = $validated ? 'capture-serve-dbus-evidence' : 'inspect-packaged-serve-dbus-activation';

    $report = [
        'api_version' => '1.0',
        'kind' => 'system-api-native-dbus-serve-dbus-check',
        'status' => $validated ? 'validated' : 'blocked',
        'validated' => $validated,
        'exit_code' => $validated ? 0 : 3,
        'blocking_reason' => $blockingReason,
        'next_action' => $nextAction,
        'wsl_distribution' => $resolvedDistribution,
        'wsl_repo_root' => $wslRepoRoot,
        'wsl_user' => $wslUser,
        'keepalive_seconds' => $keepAliveSeconds,
        'report_path' => $reportPath,
        'summary_path' => $summaryPath,
        'call_output_path' => $callOutputPath,
        'introspection_path' => $introspectionPath,
        'launcher_stdout_path' => $launcherStdoutPath,
        'launcher_stderr_path' => $launcherStderrPath,
        'checks' => [
            'bus_owner_observed' => $busOwnerObserved,
            'launcher_command_validated' => $launcherCommandValidated,
            'get_capabilities_validated' => $getCapabilitiesValidated,
            'call_validated' => $callValidated,
        ],
        'command_result' => $rootCommandResult,
    ];

    writeJsonArtifact($reportPath, $report);
    writeTextArtifact($summaryPath, renderSummaryMarkdown($report));

    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . PHP_EOL;
    exit((int) $report['exit_code']);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}

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
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('Fixture JSON invalido en %s: %s', $variable, $exception->getMessage()));
    }

    return is_array($data) ? $data : null;
}

/**
 * @return array{stdout: string, stderr: string, exit_code: int}
 */
function runWindowsCommandCaptured(string $command): array
{
    $fixtureMap = readJsonFixtureFromEnv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_COMMAND_MAP_JSON');
    if ($fixtureMap !== null) {
        $fixture = $fixtureMap[$command] ?? null;
        if ($fixture !== null) {
            if (is_array($fixture)) {
                return [
                    'stdout' => trim((string) ($fixture['stdout'] ?? '')),
                    'stderr' => trim((string) ($fixture['stderr'] ?? '')),
                    'exit_code' => (int) ($fixture['exit_code'] ?? 0),
                ];
            }

            return [
                'stdout' => trim((string) $fixture),
                'stderr' => '',
                'exit_code' => 0,
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

function hasCommandFixturesConfigured(): bool
{
    return readJsonFixtureFromEnv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_COMMAND_MAP_JSON') !== null;
}

function runWindowsCommand(string $command): string
{
    $result = runWindowsCommandCaptured($command);
    if ($result['exit_code'] !== 0) {
        $message = $result['stderr'] !== '' ? $result['stderr'] : $result['stdout'];
        throw new ValidationError($message !== '' ? $message : sprintf('Fallo al ejecutar: %s', $command));
    }

    return $result['stdout'];
}

/**
 * @return array<int, string>
 */
function listWslDistros(): array
{
    $fixture = readJsonFixtureFromEnv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_DISTROS_JSON');
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

/**
 * @return array{stdout: string, stderr: string, exit_code: int}
 */
function runWslCommand(string $distribution, string $bashCommand): array
{
    return runWindowsCommandCaptured(sprintf(
        'wsl -d %s -- bash -lc %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($bashCommand)
    ));
}

/**
 * @return array{stdout: string, stderr: string, exit_code: int}
 */
function runWslRootCommand(string $distribution, string $bashCommand): array
{
    return runWindowsCommandCaptured(sprintf(
        'wsl -u root -d %s -- bash -lc %s',
        quoteWslDistribution($distribution),
        quoteForWindowsCommand($bashCommand)
    ));
}

/**
 * @return array{stdout: string, stderr: string, exit_code: int}
 */
function runWslRootScript(string $distribution, string $scriptContents): array
{
    $tempPath = tempnam(sys_get_temp_dir(), 'w4-native-dbus-serve-dbus-');
    if ($tempPath === false) {
        throw new ValidationError('No se pudo crear un script temporal para WSL');
    }

    $scriptPath = $tempPath . '.sh';
    @unlink($tempPath);

    try {
        if (file_put_contents($scriptPath, $scriptContents) === false) {
            throw new ValidationError('No se pudo escribir el script temporal para WSL');
        }

        $wslScriptPath = resolvePathForWsl($distribution, $scriptPath);

        return runWindowsCommandCaptured(sprintf(
            'wsl -u root -d %s -- bash %s',
            quoteWslDistribution($distribution),
            quoteForWindowsCommand($wslScriptPath)
        ));
    } finally {
        if (is_file($scriptPath)) {
            @unlink($scriptPath);
        }
    }
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

function normalizePathForRunner(string $path): string
{
    return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
}

function ensureDirectory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
        throw new ValidationError(sprintf('No se pudo crear el directorio: %s', $path));
    }
}

/**
 * @param array<string, mixed> $data
 */
function writeJsonArtifact(string $path, array $data): void
{
    ensureDirectory(dirname($path));
    $encoded = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    if (file_put_contents($path, $encoded . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir el artefacto JSON: %s', $path));
    }
}

function writeTextArtifact(string $path, string $contents): void
{
    ensureDirectory(dirname($path));
    if (file_put_contents($path, $contents) === false) {
        throw new ValidationError(sprintf('No se pudo escribir el artefacto de texto: %s', $path));
    }
}

function quoteForSingleQuotedBash(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

function buildServeDbusCheckCommand(string $wslUser, string $wslRepoRoot, string $wslEvidenceDir, int $keepAliveSeconds): string
{
    $userScript = implode("\n", [
        'set -euo pipefail',
        'cd ' . quoteForSingleQuotedBash($wslRepoRoot),
        'BASE=' . quoteForSingleQuotedBash($wslEvidenceDir),
        'ART_DIR="${BASE}/runtime-artifacts"',
        'mkdir -p "${ART_DIR}"',
        'export W4_SYSTEM_API_RUNTIME_BACKEND=native-dbus-extension',
        'export W4_SYSTEM_API_NATIVE_RUNTIME_ARTIFACTS_DIR="${ART_DIR}"',
        'export W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS=' . $keepAliveSeconds,
        'OUT="${BASE}/serve-dbus-launcher-output.json"',
        'ERR="${BASE}/serve-dbus-launcher-error.txt"',
        'INTROSPECT_TXT="${BASE}/gdbus-introspect.txt"',
        'CALL_TXT="${BASE}/serve-dbus-call-output.txt"',
        'rm -f "${OUT}" "${ERR}" "${INTROSPECT_TXT}" "${CALL_TXT}"',
        '/opt/php/8.4.3/bin/php scripts/system_api_dbus_runtime_launcher.php serve-dbus >"${OUT}" 2>"${ERR}"',
        'for i in 1 2 3 4 5 6 7 8 9 10; do',
        '  if busctl --system list | grep -q org.w4.System1; then',
        '    break',
        '  fi',
        '  sleep 1',
        'done',
        'gdbus introspect --system --dest org.w4.System1 --object-path /org/w4/System1 >"${INTROSPECT_TXT}" 2>/dev/null || true',
        '{',
        "  echo '---LAUNCHER-OUTPUT---'",
        '  cat "${OUT}"',
        "  echo '---BUS-LIST---'",
        '  busctl --system list | grep org.w4.System1 || true',
        "  echo '---GDBUS-INTROSPECT---'",
        '  cat "${INTROSPECT_TXT}"',
        "  echo '---GETCAPABILITIES---'",
        "  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.GetCapabilities 'dbus-launcher-capabilities'",
        "  echo '---CALL-SYSTEM-DBUS-RUNTIME---'",
        "  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.Call '{\"api_version\":\"1.0\",\"request_id\":\"dbus-launcher-call\",\"method\":\"system.dbus-runtime\",\"params\":[]}'",
        '} | tee "${CALL_TXT}"',
    ]);

    $lines = [
        'set -euo pipefail',
        '(service dbus start >/dev/null 2>&1 || service dbus restart >/dev/null 2>&1 || true)',
        "cat >/tmp/w4-serve-dbus-user-check.sh <<'USERCHECK'",
        $userScript,
        'USERCHECK',
        'chmod +x /tmp/w4-serve-dbus-user-check.sh',
        'su - ' . $wslUser . ' -c ' . quoteForSingleQuotedBash('bash /tmp/w4-serve-dbus-user-check.sh'),
    ];

    return implode("\n", $lines);
}

/**
 * @param array<string, mixed> $report
 */
function renderSummaryMarkdown(array $report): string
{
    $status = (string) ($report['status'] ?? 'blocked');
    $blockingReason = $report['blocking_reason'] ?? 'n/a';
    $checks = is_array($report['checks'] ?? null) ? $report['checks'] : [];

    return implode(PHP_EOL, [
        '# Native D-Bus Packaged serve-dbus Check in WSL',
        '',
        '- Status: `' . $status . '`',
        '- Exit code: `' . (string) ($report['exit_code'] ?? 3) . '`',
        '- Blocking reason: `' . (is_string($blockingReason) ? $blockingReason : 'n/a') . '`',
        '- Next action: `' . (string) ($report['next_action'] ?? 'inspect-packaged-serve-dbus-activation') . '`',
        '- WSL distribution: `' . (string) ($report['wsl_distribution'] ?? '') . '`',
        '- WSL repo root: `' . (string) ($report['wsl_repo_root'] ?? '') . '`',
        '- WSL user: `' . (string) ($report['wsl_user'] ?? '') . '`',
        '- Bus owner observed: `' . (($checks['bus_owner_observed'] ?? false) ? 'yes' : 'no') . '`',
        '- Launcher command validated: `' . (($checks['launcher_command_validated'] ?? false) ? 'yes' : 'no') . '`',
        '- GetCapabilities validated: `' . (($checks['get_capabilities_validated'] ?? false) ? 'yes' : 'no') . '`',
        '- Call(system.dbus-runtime) validated: `' . (($checks['call_validated'] ?? false) ? 'yes' : 'no') . '`',
        '- Call output path: `' . (string) ($report['call_output_path'] ?? '') . '`',
        '- Introspection path: `' . (string) ($report['introspection_path'] ?? '') . '`',
        '- Launcher stdout path: `' . (string) ($report['launcher_stdout_path'] ?? '') . '`',
        '- Launcher stderr path: `' . (string) ($report['launcher_stderr_path'] ?? '') . '`',
        '',
    ]) . PHP_EOL;
}
