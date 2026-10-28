<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class NativeDbusServeDbusCheckWslRunnerCliTest extends TestCase
{
    private string $repoRoot;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-dbus-serve-dbus-wsl-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->tempDir, 0777, true) || is_dir($this->tempDir));
    }

    protected function tearDown(): void
    {
        $this->clearFixtureEnv();
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testRunnerMaterializesValidatedServeDbusCheckFromFixtures(): void
    {
        $distribution = 'Ubuntu-Fixture';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'serve-dbus-check';
        $wslRepoRoot = '/mnt/c/W4/Packages/W4-OS SYSTEM';
        $wslEvidenceDir = $this->toWslPath($evidenceDir);
        $wslUser = 'sagat1200';

        $stdout = implode("\n", [
            '---LAUNCHER-OUTPUT---',
            "{\n    \"requested_command\": \"serve-dbus\",\n    \"target_command\": {\n        \"command\": \"StartNativeRuntimeService\"\n    }\n}",
            '---BUS-LIST---',
            'org.w4.System1                 34 php     sagat1200 :1.14         -    -       -',
            '---GDBUS-INTROSPECT---',
            'node /org/w4/System1 { interface org.w4.System1.Manager { methods: Call, GetCapabilities; }; };',
            '---GETCAPABILITIES---',
            "('{\n    \"request_id\": \"dbus-launcher-capabilities\",\n    \"result\": {\n        \"method\": \"system.capabilities\"\n    },\n    \"error\": null\n}\n',)",
            '---CALL-SYSTEM-DBUS-RUNTIME---',
            "('{\n    \"request_id\": \"dbus-launcher-call\",\n    \"result\": {\n        \"method\": \"system.dbus-runtime\"\n    },\n    \"error\": null\n}\n',)",
        ]);

        $commandMap = [
            $this->buildWslPathCommand($distribution, $this->repoRoot) => $wslRepoRoot,
            $this->buildWslPathCommand($distribution, $evidenceDir) => $wslEvidenceDir,
            $this->buildWslBashCommand($distribution, 'id -un') => $wslUser,
            $this->buildWslRootBashCommand(
                $distribution,
                $this->buildServeDbusCheckCommand($wslUser, $wslRepoRoot, $wslEvidenceDir, 20)
            ) => [
                'stdout' => $stdout,
                'stderr' => '',
                'exit_code' => 0,
            ],
        ];

        putenv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_DISTROS_JSON=' . json_encode([$distribution], JSON_THROW_ON_ERROR));
        putenv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_COMMAND_MAP_JSON=' . json_encode($commandMap, JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--distribution',
            $distribution,
            '--evidence-dir',
            $evidenceDir,
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('validated', $payload['status']);
        self::assertTrue($payload['validated']);
        self::assertNull($payload['blocking_reason']);
        self::assertSame('capture-serve-dbus-evidence', $payload['next_action']);
        self::assertTrue($payload['checks']['bus_owner_observed']);
        self::assertTrue($payload['checks']['launcher_command_validated']);
        self::assertTrue($payload['checks']['get_capabilities_validated']);
        self::assertTrue($payload['checks']['call_validated']);
        self::assertFileExists($payload['report_path']);
        self::assertFileExists($payload['summary_path']);
    }

    public function testRunnerReportsBlockedStateWhenPackagedOwnerIsNotMaterialized(): void
    {
        $distribution = 'Ubuntu-Fixture';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'serve-dbus-check-blocked';
        $wslRepoRoot = '/mnt/c/W4/Packages/W4-OS SYSTEM';
        $wslEvidenceDir = $this->toWslPath($evidenceDir);
        $wslUser = 'sagat1200';

        $stdout = implode("\n", [
            '---LAUNCHER-OUTPUT---',
            "{\n    \"requested_command\": \"serve-dbus\",\n    \"target_command\": {\n        \"command\": \"StartNativeRuntimeService\"\n    }\n}",
            '---BUS-LIST---',
            '---GDBUS-INTROSPECT---',
            '---GETCAPABILITIES---',
            'Error: GDBus.Error:org.freedesktop.DBus.Error.ServiceUnknown: The name org.w4.System1 was not provided by any .service files',
        ]);

        $commandMap = [
            $this->buildWslPathCommand($distribution, $this->repoRoot) => $wslRepoRoot,
            $this->buildWslPathCommand($distribution, $evidenceDir) => $wslEvidenceDir,
            $this->buildWslBashCommand($distribution, 'id -un') => $wslUser,
            $this->buildWslRootBashCommand(
                $distribution,
                $this->buildServeDbusCheckCommand($wslUser, $wslRepoRoot, $wslEvidenceDir, 20)
            ) => [
                'stdout' => $stdout,
                'stderr' => '',
                'exit_code' => 1,
            ],
        ];

        putenv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_DISTROS_JSON=' . json_encode([$distribution], JSON_THROW_ON_ERROR));
        putenv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_COMMAND_MAP_JSON=' . json_encode($commandMap, JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--distribution',
            $distribution,
            '--evidence-dir',
            $evidenceDir,
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertFalse($payload['validated']);
        self::assertSame('packaged-serve-dbus-owner-not-materialized', $payload['blocking_reason']);
        self::assertSame('inspect-packaged-serve-dbus-activation', $payload['next_action']);
        self::assertFalse($payload['checks']['bus_owner_observed']);
        self::assertTrue($payload['checks']['launcher_command_validated']);
        self::assertFalse($payload['checks']['get_capabilities_validated']);
        self::assertFalse($payload['checks']['call_validated']);
    }

    public function testRunnerValidatesAutoReactivationSupervisorResetFromArtifacts(): void
    {
        $distribution = 'Ubuntu-Fixture';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'serve-dbus-check-auto-reactivation';
        $wslRepoRoot = '/mnt/c/W4/Packages/W4-OS SYSTEM';
        $wslEvidenceDir = $this->toWslPath($evidenceDir);
        $wslUser = 'sagat1200';
        $artifactsDir = $evidenceDir . DIRECTORY_SEPARATOR . 'runtime-artifacts';

        self::assertTrue(mkdir($artifactsDir, 0777, true) || is_dir($artifactsDir));
        file_put_contents($artifactsDir . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.ready', "native-runtime-bus-owned\n");
        file_put_contents(
            $artifactsDir . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime-status.json',
            json_encode(['status' => 'native-runtime-bus-owned'], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );
        file_put_contents($artifactsDir . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime.pid', "88\n");
        file_put_contents(
            $artifactsDir . DIRECTORY_SEPARATOR . 'w4-system-api-native-runtime-supervisor.json',
            json_encode([
                'api_version' => '1.0',
                'kind' => 'system-api-dbus-native-runtime-supervisor-state',
                'attempt_count' => 0,
                'previous_attempt_count' => 1,
                'last_reset_reason' => 'native-runtime-stability-achieved',
                'last_reset_trigger' => 'reactivation-stability-achieved',
            ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
        );

        $stdout = implode("\n", [
            '---LAUNCHER-OUTPUT---',
            "{\n    \"requested_command\": \"serve-dbus\",\n    \"target_command\": {\n        \"command\": \"StartNativeRuntimeService\"\n    }\n}",
            '---BUS-LIST---',
            'org.w4.System1                 34 php     sagat1200 :1.14         -    -       -',
            '---GDBUS-INTROSPECT---',
            'node /org/w4/System1 { interface org.w4.System1.Manager { methods: Call, GetCapabilities; }; };',
            '---GETCAPABILITIES---',
            "('{\n    \"request_id\": \"dbus-launcher-capabilities\",\n    \"result\": {\n        \"method\": \"system.capabilities\"\n    },\n    \"error\": null\n}\n',)",
            '---CALL-SYSTEM-DBUS-RUNTIME---',
            "('{\n    \"request_id\": \"dbus-launcher-call\",\n    \"result\": {\n        \"method\": \"system.dbus-runtime\"\n    },\n    \"error\": null\n}\n',)",
        ]);

        $commandMap = [
            $this->buildWslPathCommand($distribution, $this->repoRoot) => $wslRepoRoot,
            $this->buildWslPathCommand($distribution, $evidenceDir) => $wslEvidenceDir,
            $this->buildWslBashCommand($distribution, 'id -un') => $wslUser,
            $this->buildWslRootBashCommand(
                $distribution,
                $this->buildServeDbusCheckCommand($wslUser, $wslRepoRoot, $wslEvidenceDir, 12, true, 2)
            ) => [
                'stdout' => $stdout,
                'stderr' => '',
                'exit_code' => 0,
            ],
        ];

        putenv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_DISTROS_JSON=' . json_encode([$distribution], JSON_THROW_ON_ERROR));
        putenv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_COMMAND_MAP_JSON=' . json_encode($commandMap, JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--distribution',
            $distribution,
            '--evidence-dir',
            $evidenceDir,
            '--validate-auto-reactivation',
            '1',
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('validated', $payload['status']);
        self::assertTrue($payload['validated']);
        self::assertTrue($payload['checks']['auto_reactivation_supervisor_state_observed']);
        self::assertTrue($payload['checks']['auto_reactivation_budget_reset_validated']);
        self::assertTrue($payload['checks']['auto_reactivation_runtime_active_validated']);
        self::assertTrue($payload['auto_reactivation_validation']['enabled']);
        self::assertSame(
            'reactivation-stability-achieved',
            $payload['auto_reactivation_validation']['supervisor_state']['last_reset_trigger']
        );
        self::assertSame(88, $payload['auto_reactivation_validation']['pid']);
    }

    private function scriptPath(): string
    {
        return $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_native_dbus_serve_dbus_check_in_wsl.php';
    }

    private function clearFixtureEnv(): void
    {
        putenv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_DISTROS_JSON');
        putenv('W4_RUN_NATIVE_DBUS_SERVE_DBUS_WSL_COMMAND_MAP_JSON');
    }

    /**
     * @param array<int, string> $command
     * @return array{stdout: string, stderr: string, exitCode: int}
     */
    private function runPhpScript(array $command): array
    {
        array_unshift($command, PHP_BINARY);

        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptor, $pipes, $this->repoRoot);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        return [
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
            'exitCode' => $exitCode,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }

    private function buildWslPathCommand(string $distribution, string $path): string
    {
        return sprintf(
            'wsl -d %s -- wslpath -a %s',
            $this->quoteWslDistribution($distribution),
            $this->quoteForWindowsCommand($path)
        );
    }

    private function buildWslBashCommand(string $distribution, string $bashCommand): string
    {
        return sprintf(
            'wsl -d %s -- bash -lc %s',
            $this->quoteWslDistribution($distribution),
            $this->quoteForWindowsCommand($bashCommand)
        );
    }

    private function buildWslRootBashCommand(string $distribution, string $bashCommand): string
    {
        return sprintf(
            'wsl -u root -d %s -- bash -lc %s',
            $this->quoteWslDistribution($distribution),
            $this->quoteForWindowsCommand($bashCommand)
        );
    }

    private function quoteForWindowsCommand(string $value): string
    {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    private function quoteWslDistribution(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1) {
            return $value;
        }

        return $this->quoteForWindowsCommand($value);
    }

    private function quoteForSingleQuotedBash(string $value): string
    {
        return "'" . str_replace("'", "'\"'\"'", $value) . "'";
    }

    private function buildServeDbusCheckCommand(
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
            'cd ' . $this->quoteForSingleQuotedBash($wslRepoRoot),
            'BASE=' . $this->quoteForSingleQuotedBash($wslEvidenceDir),
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

        return implode("\n", [
            'set -euo pipefail',
            '(service dbus start >/dev/null 2>&1 || service dbus restart >/dev/null 2>&1 || true)',
            "cat >/tmp/w4-serve-dbus-user-check.sh <<'USERCHECK'",
            $userScript,
            'USERCHECK',
            'chmod +x /tmp/w4-serve-dbus-user-check.sh',
            'su - ' . $wslUser . ' -c ' . $this->quoteForSingleQuotedBash('bash /tmp/w4-serve-dbus-user-check.sh'),
        ]);
    }

    private function toWslPath(string $path): string
    {
        return '/mnt/' . strtolower($path[0]) . str_replace('\\', '/', substr($path, 2));
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $child = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($child)) {
                $this->removeDirectory($child);
            } elseif (file_exists($child)) {
                @unlink($child);
            }
        }

        @rmdir($path);
    }
}
