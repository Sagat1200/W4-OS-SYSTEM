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
$keepAliveExplicitlyProvided = false;
$validateAutoReactivation = false;
$postReactivationWaitSeconds = null;

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
                $keepAliveExplicitlyProvided = true;
                break;

            case '--validate-auto-reactivation':
                $normalized = strtolower(trim($value));
                if (!in_array($normalized, ['0', '1', 'false', 'true', 'no', 'yes'], true)) {
                    throw new ValidationError('--validate-auto-reactivation debe ser 0/1 o true/false');
                }

                $validateAutoReactivation = in_array($normalized, ['1', 'true', 'yes'], true);
                break;

            case '--post-reactivation-wait-seconds':
                if (preg_match('/^\d+$/', trim($value)) !== 1) {
                    throw new ValidationError('--post-reactivation-wait-seconds debe ser un entero positivo');
                }

                $postReactivationWaitSeconds = max(0, min(45, (int) trim($value)));
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($validateAutoReactivation && !$keepAliveExplicitlyProvided) {
        // The post-reactivation probe needs enough overlap with the relaunched runtime
        // to distinguish real dispatch failures from keepalive expiry races.
        $keepAliveSeconds = 12;
    }

    if ($validateAutoReactivation && $postReactivationWaitSeconds === null) {
        $postReactivationWaitSeconds = 2;
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

    $rootBashCommand = buildServeDbusCheckCommand(
        $wslUser,
        $wslRepoRoot,
        $wslEvidenceDir,
        $keepAliveSeconds,
        $validateAutoReactivation,
        $postReactivationWaitSeconds ?? 0
    );
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
    $postReactivationBusOwnerObserved = preg_match('/---POST-REACTIVATION-BUS-LIST---.*org\.w4\.System1/s', $stdout) === 1;
    $postReactivationGetCapabilitiesValidated = str_contains($stdout, 'dbus-launcher-capabilities-reactivation');
    $postReactivationCallValidated = str_contains($stdout, 'dbus-launcher-call-reactivation');
    $postReactivationObservabilityValidated = $postReactivationBusOwnerObserved
        && $postReactivationGetCapabilitiesValidated
        && $postReactivationCallValidated;
    $autoReactivationValidation = createAutoReactivationValidationReport(
        $evidenceDir,
        $validateAutoReactivation,
        $postReactivationObservabilityValidated
    );
    $autoReactivationResetValidated = ($autoReactivationValidation['budget_reset_validated'] ?? false) === true;
    $autoReactivationRuntimeActive = ($autoReactivationValidation['runtime_active_validated'] ?? false) === true;
    $validated = $rootCommandResult['exit_code'] === 0
        && $busOwnerObserved
        && $launcherCommandValidated
        && $getCapabilitiesValidated
        && $callValidated
        && (!$validateAutoReactivation || ($autoReactivationResetValidated && $autoReactivationRuntimeActive));

    $combinedOutput = $stdout . "\n" . $stderr;
    $blockingReason = null;
    $nextAction = 'capture-serve-dbus-evidence';
    if (!$validated) {
        if (!$busOwnerObserved && str_contains($combinedOutput, 'ServiceUnknown')) {
            $blockingReason = 'packaged-serve-dbus-owner-not-materialized';
            $nextAction = 'inspect-packaged-serve-dbus-activation';
        } elseif ($validateAutoReactivation && !$autoReactivationResetValidated) {
            $blockingReason = 'native-dbus-auto-reactivation-reset-not-observed';
            $nextAction = 'inspect-native-runtime-auto-reactivation';
        } elseif ($validateAutoReactivation && !$autoReactivationRuntimeActive) {
            $blockingReason = 'native-dbus-auto-reactivation-runtime-not-active';
            $nextAction = 'inspect-native-runtime-auto-reactivation';
        } else {
            $blockingReason = 'native-dbus-serve-dbus-check-failed';
            $nextAction = 'inspect-packaged-serve-dbus-activation';
        }
    }

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
        'auto_reactivation_validation_enabled' => $validateAutoReactivation,
        'post_reactivation_wait_seconds' => $validateAutoReactivation ? ($postReactivationWaitSeconds ?? 0) : 0,
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
            'post_reactivation_bus_owner_observed' => $postReactivationBusOwnerObserved,
            'post_reactivation_get_capabilities_validated' => $postReactivationGetCapabilitiesValidated,
            'post_reactivation_call_validated' => $postReactivationCallValidated,
            'auto_reactivation_supervisor_state_observed' => ($autoReactivationValidation['supervisor_state_observed'] ?? false) === true,
            'auto_reactivation_budget_reset_validated' => $autoReactivationResetValidated,
            'auto_reactivation_runtime_active_validated' => $autoReactivationRuntimeActive,
        ],
        'auto_reactivation_validation' => $autoReactivationValidation,
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

function buildServeDbusCheckCommand(
    string $wslUser,
    string $wslRepoRoot,
    string $wslEvidenceDir,
    int $keepAliveSeconds,
    bool $validateAutoReactivation = false,
    int $postReactivationWaitSeconds = 0
): string
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
        ...($validateAutoReactivation ? [
            'export W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE=1',
            'export W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_MAX_ATTEMPTS=1',
            'export W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_BACKOFF_MS=10',
            'export W4_SYSTEM_API_NATIVE_RUNTIME_AUTO_REACTIVATE_RESET_STABILITY_MS=100',
        ] : []),
        'OUT="${BASE}/serve-dbus-launcher-output.json"',
        'ERR="${BASE}/serve-dbus-launcher-error.txt"',
        'INTROSPECT_TXT="${BASE}/gdbus-introspect.txt"',
        'CALL_TXT="${BASE}/serve-dbus-call-output.txt"',
        'POST_INTROSPECT_TXT="${BASE}/gdbus-post-reactivation-introspect.txt"',
        'POST_CALL_TXT="${BASE}/serve-dbus-post-reactivation-call-output.txt"',
        'rm -f "${OUT}" "${ERR}" "${INTROSPECT_TXT}" "${CALL_TXT}" "${POST_INTROSPECT_TXT}" "${POST_CALL_TXT}"',
        '/opt/php/8.4.3/bin/php scripts/system_api_dbus_runtime_launcher.php serve-dbus >"${OUT}" 2>"${ERR}" || true',
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
        ...($validateAutoReactivation
            ? ["  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.GetCapabilities 'dbus-launcher-capabilities' || true"]
            : ["  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.GetCapabilities 'dbus-launcher-capabilities'"]),
        "  echo '---CALL-SYSTEM-DBUS-RUNTIME---'",
        ...($validateAutoReactivation
            ? ["  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.Call '{\"api_version\":\"1.0\",\"request_id\":\"dbus-launcher-call\",\"method\":\"system.dbus-runtime\",\"params\":[]}' || true"]
            : ["  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.Call '{\"api_version\":\"1.0\",\"request_id\":\"dbus-launcher-call\",\"method\":\"system.dbus-runtime\",\"params\":[]}'"]),
        '} | tee "${CALL_TXT}"',
        ...($validateAutoReactivation && $postReactivationWaitSeconds > 0 ? [
            'SUPERVISOR="${ART_DIR}/w4-system-api-native-runtime-supervisor.json"',
            sprintf('for i in $(seq 1 %d); do', $postReactivationWaitSeconds),
            '  if [ -f "${SUPERVISOR}" ] && grep -q \'"last_reset_trigger": "reactivation-stability-achieved"\' "${SUPERVISOR}"; then',
            '    break',
            '  fi',
            '  sleep 1',
            'done',
            'gdbus introspect --system --dest org.w4.System1 --object-path /org/w4/System1 >"${POST_INTROSPECT_TXT}" 2>/dev/null || true',
            '{',
            "  echo '---POST-REACTIVATION-BUS-LIST---'",
            '  busctl --system list | grep org.w4.System1 || true',
            "  echo '---POST-REACTIVATION-GDBUS-INTROSPECT---'",
            '  cat "${POST_INTROSPECT_TXT}"',
            "  echo '---POST-REACTIVATION-GETCAPABILITIES---'",
            "  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.GetCapabilities 'dbus-launcher-capabilities-reactivation' || true",
            "  echo '---POST-REACTIVATION-CALL-SYSTEM-DBUS-RUNTIME---'",
            "  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.Call '{\"api_version\":\"1.0\",\"request_id\":\"dbus-launcher-call-reactivation\",\"method\":\"system.dbus-runtime\",\"params\":[]}' || true",
            '} | tee "${POST_CALL_TXT}"',
        ] : []),
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
 * @return array<string, mixed>
 */
function createAutoReactivationValidationReport(
    string $evidenceDir,
    bool $enabled,
    bool $postReactivationObservabilityValidated = false
): array
{
    $report = [
        'enabled' => $enabled,
    ];

    if (!$enabled) {
        return $report;
    }

    $artifactsDir = $evidenceDir . DIRECTORY_SEPARATOR . 'runtime-artifacts';
    $supervisorStatePath = $artifactsDir . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime-supervisor.json';
    $readyMarkerPath = $artifactsDir . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.ready';
    $statusFilePath = $artifactsDir . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime-status.json';
    $pidFilePath = $artifactsDir . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.pid';

    $supervisorState = null;
    $readyMarker = null;
    $statusPayload = null;
    $pidRaw = null;
    $pid = null;
    $supervisorStateObserved = false;
    $budgetResetValidated = false;
    $runtimeActiveValidated = false;

    for ($attempt = 0; $attempt < 20; $attempt++) {
        $supervisorState = readJsonArtifactOrNull($supervisorStatePath);
        $readyMarker = readTextArtifactOrNull($readyMarkerPath);
        $statusPayload = readJsonArtifactOrNull($statusFilePath);
        $pidRaw = readTextArtifactOrNull($pidFilePath);
        $pid = is_string($pidRaw) && preg_match('/^\d+$/', $pidRaw) === 1 ? (int) $pidRaw : null;

        $supervisorStateObserved = is_array($supervisorState);
        $budgetResetValidated = $supervisorStateObserved
            && ($supervisorState['attempt_count'] ?? null) === 0
            && is_int($supervisorState['previous_attempt_count'] ?? null)
            && (int) $supervisorState['previous_attempt_count'] > 0
            && ($supervisorState['last_reset_reason'] ?? null) === 'native-runtime-stability-achieved'
            && ($supervisorState['last_reset_trigger'] ?? null) === 'reactivation-stability-achieved';
        $runtimeActiveValidated = ($readyMarker === 'native-runtime-bus-owned'
            && is_array($statusPayload)
            && ($statusPayload['status'] ?? null) === 'native-runtime-bus-owned'
            && is_int($pid)
            && $pid > 0)
            || ($budgetResetValidated && $postReactivationObservabilityValidated);

        if ($budgetResetValidated || $runtimeActiveValidated) {
            break;
        }

        usleep(100000);
    }

    return $report + [
        'artifacts_dir' => $artifactsDir,
        'supervisor_state_path' => $supervisorStatePath,
        'supervisor_state_observed' => $supervisorStateObserved,
        'supervisor_state' => $supervisorState,
        'budget_reset_validated' => $budgetResetValidated,
        'post_reactivation_observability_validated' => $postReactivationObservabilityValidated,
        'ready_marker_path' => $readyMarkerPath,
        'ready_marker' => $readyMarker,
        'status_file_path' => $statusFilePath,
        'status_payload' => $statusPayload,
        'pid_file_path' => $pidFilePath,
        'pid' => $pid,
        'pid_raw' => $pidRaw,
        'runtime_active_validated' => $runtimeActiveValidated,
    ];
}

/**
 * @return array<string, mixed>|null
 */
function readJsonArtifactOrNull(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if (!is_string($raw) || trim($raw) === '') {
        return null;
    }

    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        return null;
    }

    return is_array($decoded) ? $decoded : null;
}

function readTextArtifactOrNull(string $path): ?string
{
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if (!is_string($raw)) {
        return null;
    }

    $trimmed = trim($raw);

    return $trimmed === '' ? null : $trimmed;
}

/**
 * @param array<string, mixed> $report
 */
function renderSummaryMarkdown(array $report): string
{
    $status = (string) ($report['status'] ?? 'blocked');
    $blockingReason = $report['blocking_reason'] ?? 'n/a';
    $checks = is_array($report['checks'] ?? null) ? $report['checks'] : [];
    $autoReactivation = is_array($report['auto_reactivation_validation'] ?? null)
        ? $report['auto_reactivation_validation']
        : ['enabled' => false];

    $lines = [
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
        '- Auto-reactivation validation enabled: `' . (($autoReactivation['enabled'] ?? false) ? 'yes' : 'no') . '`',
        '- Auto-reactivation supervisor observed: `' . (($checks['auto_reactivation_supervisor_state_observed'] ?? false) ? 'yes' : 'no') . '`',
        '- Auto-reactivation budget reset validated: `' . (($checks['auto_reactivation_budget_reset_validated'] ?? false) ? 'yes' : 'no') . '`',
        '- Auto-reactivation runtime active validated: `' . (($checks['auto_reactivation_runtime_active_validated'] ?? false) ? 'yes' : 'no') . '`',
        '- Call output path: `' . (string) ($report['call_output_path'] ?? '') . '`',
        '- Introspection path: `' . (string) ($report['introspection_path'] ?? '') . '`',
        '- Launcher stdout path: `' . (string) ($report['launcher_stdout_path'] ?? '') . '`',
        '- Launcher stderr path: `' . (string) ($report['launcher_stderr_path'] ?? '') . '`',
        '',
    ];

    if (($autoReactivation['enabled'] ?? false) === true) {
        $lines[] = '- Supervisor state path: `' . (string) ($autoReactivation['supervisor_state_path'] ?? '') . '`';
        $lines[] = '- PID file path: `' . (string) ($autoReactivation['pid_file_path'] ?? '') . '`';
        $lines[] = '';
    }

    return implode(PHP_EOL, $lines) . PHP_EOL;
}
