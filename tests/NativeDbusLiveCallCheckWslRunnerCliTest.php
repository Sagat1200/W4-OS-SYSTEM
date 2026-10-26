<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class NativeDbusLiveCallCheckWslRunnerCliTest extends TestCase
{
    private string $repoRoot;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-dbus-live-call-wsl-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->tempDir, 0777, true) || is_dir($this->tempDir));
    }

    protected function tearDown(): void
    {
        $this->clearFixtureEnv();
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testRunnerMaterializesValidatedLiveCallCheckFromFixtures(): void
    {
        $distribution = 'Ubuntu-Fixture';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'live-check';
        $wslRepoRoot = '/mnt/c/W4/Packages/W4-OS SYSTEM';
        $wslEvidenceDir = $this->toWslPath($evidenceDir);
        $wslUser = 'sagat1200';

        $stdout = implode("\n", [
            '---BUS-LIST---',
            'org.w4.System1                 34 php     sagat1200 :1.14         -    -       -',
            '---GDBUS-INTROSPECT---',
            'node /org/w4/System1 { interface org.w4.System1.Manager { methods: Call, GetCapabilities; }; };',
            '---GETCAPABILITIES---',
            "('{\n    \"request_id\": \"dbus-live-capabilities\",\n    \"result\": {\n        \"method\": \"system.capabilities\"\n    },\n    \"error\": null\n}\n',)",
            '---CALL-SYSTEM-DBUS-RUNTIME---',
            "('{\n    \"request_id\": \"dbus-live-call\",\n    \"result\": {\n        \"method\": \"system.dbus-runtime\"\n    },\n    \"error\": null\n}\n',)",
        ]);

        $commandMap = [
            $this->buildWslPathCommand($distribution, $this->repoRoot) => $wslRepoRoot,
            $this->buildWslPathCommand($distribution, $evidenceDir) => $wslEvidenceDir,
            $this->buildWslBashCommand($distribution, 'id -un') => $wslUser,
            $this->buildWslRootBashCommand(
                $distribution,
                $this->buildLiveDbusCheckCommand($wslUser, $wslRepoRoot, $wslEvidenceDir, 20)
            ) => [
                'stdout' => $stdout,
                'stderr' => '',
                'exit_code' => 0,
            ],
        ];

        putenv('W4_RUN_NATIVE_DBUS_LIVE_CALL_WSL_DISTROS_JSON=' . json_encode([$distribution], JSON_THROW_ON_ERROR));
        putenv('W4_RUN_NATIVE_DBUS_LIVE_CALL_WSL_COMMAND_MAP_JSON=' . json_encode($commandMap, JSON_THROW_ON_ERROR));

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
        self::assertSame('capture-live-dbus-evidence', $payload['next_action']);
        self::assertSame($distribution, $payload['wsl_distribution']);
        self::assertSame($wslRepoRoot, $payload['wsl_repo_root']);
        self::assertSame($wslUser, $payload['wsl_user']);
        self::assertTrue($payload['checks']['bus_owner_observed']);
        self::assertTrue($payload['checks']['get_capabilities_validated']);
        self::assertTrue($payload['checks']['call_validated']);
        self::assertFileExists($payload['report_path']);
        self::assertFileExists($payload['summary_path']);
    }

    public function testRunnerReportsBlockedStateWhenOwnerIsNotMaterialized(): void
    {
        $distribution = 'Ubuntu-Fixture';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'live-check-blocked';
        $wslRepoRoot = '/mnt/c/W4/Packages/W4-OS SYSTEM';
        $wslEvidenceDir = $this->toWslPath($evidenceDir);
        $wslUser = 'sagat1200';

        $stdout = implode("\n", [
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
                $this->buildLiveDbusCheckCommand($wslUser, $wslRepoRoot, $wslEvidenceDir, 20)
            ) => [
                'stdout' => $stdout,
                'stderr' => '',
                'exit_code' => 1,
            ],
        ];

        putenv('W4_RUN_NATIVE_DBUS_LIVE_CALL_WSL_DISTROS_JSON=' . json_encode([$distribution], JSON_THROW_ON_ERROR));
        putenv('W4_RUN_NATIVE_DBUS_LIVE_CALL_WSL_COMMAND_MAP_JSON=' . json_encode($commandMap, JSON_THROW_ON_ERROR));

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
        self::assertSame('dbus-service-owner-not-materialized', $payload['blocking_reason']);
        self::assertSame('start-dbus-and-retry-live-check', $payload['next_action']);
        self::assertFalse($payload['checks']['bus_owner_observed']);
        self::assertFalse($payload['checks']['get_capabilities_validated']);
        self::assertFalse($payload['checks']['call_validated']);
    }

    private function scriptPath(): string
    {
        return $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_native_dbus_live_call_check_in_wsl.php';
    }

    private function clearFixtureEnv(): void
    {
        putenv('W4_RUN_NATIVE_DBUS_LIVE_CALL_WSL_DISTROS_JSON');
        putenv('W4_RUN_NATIVE_DBUS_LIVE_CALL_WSL_COMMAND_MAP_JSON');
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

    private function buildLiveDbusCheckCommand(string $wslUser, string $wslRepoRoot, string $wslEvidenceDir, int $keepAliveSeconds): string
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
            'OUT=/tmp/w4-system1-live.out',
            'ERR=/tmp/w4-system1-live.err',
            'INTROSPECT_TXT="${BASE}/gdbus-introspect.txt"',
            'CALL_TXT="${BASE}/live-dbus-call-output.txt"',
            'RUNTIME_STDOUT="${BASE}/runtime-stdout.json"',
            'RUNTIME_STDERR="${BASE}/runtime-stderr.txt"',
            'rm -f "${OUT}" "${ERR}" "${INTROSPECT_TXT}" "${CALL_TXT}" "${RUNTIME_STDOUT}" "${RUNTIME_STDERR}"',
            '/opt/php/8.4.3/bin/php scripts/system_api_dbus_runtime_adapter.php serve-native-runtime-service >"${OUT}" 2>"${ERR}" &',
            'pid=$!',
            'for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20; do',
            '  if busctl --system list | grep -q org.w4.System1; then',
            '    break',
            '  fi',
            '  sleep 1',
            'done',
            'gdbus introspect --system --dest org.w4.System1 --object-path /org/w4/System1 >"${INTROSPECT_TXT}" 2>/dev/null || true',
            '{',
            "  echo '---BUS-LIST---'",
            '  busctl --system list | grep org.w4.System1 || true',
            "  echo '---GDBUS-INTROSPECT---'",
            '  cat "${INTROSPECT_TXT}"',
            "  echo '---GETCAPABILITIES---'",
            "  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.GetCapabilities 'dbus-live-capabilities'",
            "  echo '---CALL-SYSTEM-DBUS-RUNTIME---'",
            "  gdbus call --system --dest org.w4.System1 --object-path /org/w4/System1 --method org.w4.System1.Manager.Call '{\"api_version\":\"1.0\",\"request_id\":\"dbus-live-call\",\"method\":\"system.dbus-runtime\",\"params\":[]}'",
            '} | tee "${CALL_TXT}"',
            'wait $pid || true',
            'cp -f "${OUT}" "${RUNTIME_STDOUT}"',
            'cp -f "${ERR}" "${RUNTIME_STDERR}"',
        ]);

        return implode("\n", [
            'set -euo pipefail',
            '(service dbus start >/dev/null 2>&1 || service dbus restart >/dev/null 2>&1 || true)',
            "cat >/tmp/w4-live-user-check.sh <<'USERCHECK'",
            $userScript,
            'USERCHECK',
            'chmod +x /tmp/w4-live-user-check.sh',
            'su - ' . $wslUser . ' -c ' . $this->quoteForSingleQuotedBash('bash /tmp/w4-live-user-check.sh'),
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
