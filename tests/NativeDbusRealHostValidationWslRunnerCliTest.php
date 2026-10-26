<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class NativeDbusRealHostValidationWslRunnerCliTest extends TestCase
{
    private string $repoRoot;
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repoRoot = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-native-dbus-wsl-runner-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->tempDir, 0777, true) || is_dir($this->tempDir));
    }

    protected function tearDown(): void
    {
        $this->clearFixtureEnv();
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    public function testRunnerMaterializesBlockedWslHostAssessmentFromFixtures(): void
    {
        $distribution = 'Ubuntu-Fixture';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'wsl-assessment';
        $wslRepoRoot = '/mnt/c/W4/Packages/W4-OS SYSTEM';

        $this->setFixtureEnv($distribution, $wslRepoRoot, [
            "php -r 'echo PHP_VERSION, PHP_EOL;'" => '7.4.3',
            'command -v php' => '/usr/bin/php',
            'command -v pecl' => '/usr/bin/pecl',
            'command -v busctl' => '/usr/bin/busctl',
            'command -v gdbus' => '/usr/bin/gdbus',
            'command -v dbus-daemon' => '/usr/bin/dbus-daemon',
            'test -S /run/dbus/system_bus_socket' => ['stdout' => '', 'stderr' => '', 'exit_code' => 1],
            'cat /etc/os-release' => "NAME=\"Ubuntu\"\nVERSION=\"20.04.6 LTS (Focal Fossa)\"\nVERSION_ID=\"20.04\"",
            'uname -a' => 'Linux fixture-host 6.8.12.3-microsoft-standard-WSL2 x86_64 GNU/Linux',
        ]);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--distribution',
            $distribution,
            '--evidence-dir',
            $evidenceDir,
            '--attempt-ownership',
            'false',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertSame('linux-php-runtime-too-old', $payload['blocking_reason']);
        self::assertSame('upgrade-runtime-php', $payload['next_action']);
        self::assertSame($distribution, $payload['wsl_distribution']);
        self::assertSame($wslRepoRoot, $payload['wsl_repo_root']);
        self::assertSame($wslRepoRoot, $payload['preparation_target_repo_root']);
        self::assertSame('7.4.3', $payload['wsl_environment']['php_version']);
        self::assertSame('/usr/bin/php', $payload['wsl_environment']['php_binary']);
        self::assertTrue($payload['wsl_environment']['has_pecl']);
        self::assertTrue($payload['wsl_environment']['has_busctl']);
        self::assertTrue($payload['wsl_environment']['has_gdbus']);
        self::assertTrue($payload['wsl_environment']['has_dbus_daemon']);
        self::assertFalse($payload['wsl_environment']['has_system_bus_socket']);
        self::assertStringContainsString('Ubuntu', (string) $payload['wsl_environment']['release']);
        self::assertStringContainsString('fixture-host', (string) $payload['wsl_environment']['kernel']);
        self::assertStringContainsString('php8.4-cli', implode("\n", $payload['preparation_plan'][0]['commands']));
        self::assertFileExists($payload['wsl_environment_report_path']);
        self::assertFileExists($payload['report_path']);
        self::assertFileExists($payload['summary_path']);
        self::assertFileExists($payload['handoff_path']);

        $environmentReport = $this->decodeJsonFile($payload['wsl_environment_report_path']);
        self::assertSame('system-api-native-dbus-wsl-host-environment', $environmentReport['kind']);
        self::assertSame($distribution, $environmentReport['environment']['distribution']);
        self::assertSame($wslRepoRoot, $environmentReport['environment']['wsl_repo_root']);
        self::assertSame('7.4.3', $environmentReport['environment']['php_version']);
        self::assertFalse($environmentReport['environment']['has_system_bus_socket']);
    }

    public function testRunnerCanOverrideLinuxTargetRepoRootWhileKeepingCollectedWslRoot(): void
    {
        $distribution = 'Ubuntu-Fixture';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'wsl-assessment-custom-root';
        $wslRepoRoot = '/mnt/c/W4/Packages/W4-OS SYSTEM';
        $targetRepoRoot = '/srv/w4/os-system';

        $this->setFixtureEnv($distribution, $wslRepoRoot, [
            "php -r 'echo PHP_VERSION, PHP_EOL;'" => '7.4.3',
            'command -v php' => '/usr/bin/php',
            'command -v pecl' => '/usr/bin/pecl',
            'command -v busctl' => '/usr/bin/busctl',
            'command -v gdbus' => '/usr/bin/gdbus',
            'command -v dbus-daemon' => '/usr/bin/dbus-daemon',
            'test -S /run/dbus/system_bus_socket' => ['stdout' => '', 'stderr' => '', 'exit_code' => 1],
            'cat /etc/os-release' => "NAME=\"Ubuntu\"\nVERSION=\"20.04.6 LTS (Focal Fossa)\"\nVERSION_ID=\"20.04\"",
            'uname -a' => 'Linux fixture-host 6.8.12.3-microsoft-standard-WSL2 x86_64 GNU/Linux',
        ]);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--distribution',
            $distribution,
            '--evidence-dir',
            $evidenceDir,
            '--attempt-ownership',
            'false',
            '--target-repo-root',
            $targetRepoRoot,
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame($distribution, $payload['wsl_distribution']);
        self::assertSame($wslRepoRoot, $payload['wsl_repo_root']);
        self::assertSame($targetRepoRoot, $payload['preparation_target_repo_root']);
        self::assertFileExists($payload['wsl_environment_report_path']);

        $environmentReport = $this->decodeJsonFile($payload['wsl_environment_report_path']);
        self::assertSame($targetRepoRoot, $environmentReport['environment']['preparation_target_repo_root']);

        $handoff = file_get_contents($payload['handoff_path']);
        self::assertNotFalse($handoff);
        self::assertStringContainsString('Target repo root: `' . $targetRepoRoot . '`', $handoff);
        self::assertStringContainsString(
            'cd "' . $targetRepoRoot . '" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux',
            $handoff
        );
    }

    public function testRunnerForwardsExistingNativeDbusBuildLogToLocalValidationRunner(): void
    {
        $distribution = 'Ubuntu-Fixture';
        $evidenceDir = $this->tempDir . DIRECTORY_SEPARATOR . 'wsl-assessment-build-log';
        $wslRepoRoot = '/mnt/c/W4/Packages/W4-OS SYSTEM';
        self::assertTrue(mkdir($evidenceDir, 0777, true) || is_dir($evidenceDir));

        $buildLogPath = $evidenceDir . DIRECTORY_SEPARATOR . 'native-dbus-extension-build-attempt-php-8.4.3.log';
        file_put_contents($buildLogPath, <<<TEXT
Se intento pecl install channel://pecl.php.net/dbus-0.1.1
referencias a zend_object_store_get_object(...)
make: *** [Makefile:192: dbus.lo] Error 1
TEXT);

        $this->setFixtureEnv($distribution, $wslRepoRoot, [
            "php -r 'echo PHP_VERSION, PHP_EOL;'" => '8.4.3',
            'command -v php' => '/opt/php/8.4.3/bin/php',
            'command -v pecl' => '/opt/php/8.4.3/bin/pecl',
            'command -v busctl' => '/usr/bin/busctl',
            'command -v gdbus' => '/usr/bin/gdbus',
            'command -v dbus-daemon' => '/usr/bin/dbus-daemon',
            'test -S /run/dbus/system_bus_socket' => ['stdout' => '', 'stderr' => '', 'exit_code' => 0],
            'cat /etc/os-release' => "NAME=\"Ubuntu\"\nVERSION=\"22.04.5 LTS (Jammy Jellyfish)\"\nVERSION_ID=\"22.04\"",
            'uname -a' => 'Linux fixture-host 6.18.40.1-microsoft-standard-WSL2 x86_64 GNU/Linux',
        ]);

        $result = $this->runPhpScript([
            $this->scriptPath(),
            '--distribution',
            $distribution,
            '--evidence-dir',
            $evidenceDir,
            '--attempt-ownership',
            'false',
        ]);

        self::assertSame(3, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertSame('native-dbus-extension-build-failed', $payload['blocking_reason']);
        self::assertSame('prepare-native-dbus-fork-workspace', $payload['next_action']);
        self::assertSame('build-failed', $payload['native_dbus_extension_installability']['state']);
        self::assertSame('make', $payload['native_dbus_extension_installability']['build_phase']);
        self::assertSame('legacy-tsrmls-api-incompatible-with-php84', $payload['native_dbus_extension_installability']['reason']);
        self::assertSame('make: *** [Makefile:192: dbus.lo] Error 1', $payload['native_dbus_extension_installability']['failure_signature']);
        self::assertSame(str_replace('\\', '/', $buildLogPath), str_replace('\\', '/', (string) $payload['native_dbus_extension_installability']['log_path']));
        self::assertFileExists($payload['native_dbus_extension_build_evidence_path']);

        $structuredEvidence = $this->decodeJsonFile($payload['native_dbus_extension_build_evidence_path']);
        self::assertSame('system-api-native-dbus-extension-build-evidence', $structuredEvidence['kind']);
        self::assertSame('build-failed', $structuredEvidence['state']);
        self::assertSame('make', $structuredEvidence['build_phase']);
        self::assertSame('legacy-tsrmls-api-incompatible-with-php84', $structuredEvidence['reason']);
        self::assertSame('make: *** [Makefile:192: dbus.lo] Error 1', $structuredEvidence['failure_signature']);
    }

    private function scriptPath(): string
    {
        return $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_native_dbus_real_host_validation_in_wsl.php';
    }

    /**
     * @param array<string, string|array{stdout?: string, stderr?: string, exit_code?: int}> $bashCommandMap
     */
    private function setFixtureEnv(string $distribution, string $wslRepoRoot, array $bashCommandMap): void
    {
        $commandMap = [
            $this->buildWslPathCommand($distribution, $this->repoRoot) => $wslRepoRoot,
        ];

        foreach ($bashCommandMap as $bashCommand => $fixture) {
            $commandMap[$this->buildWslBashCommand($distribution, $bashCommand)] = $fixture;
        }

        putenv('W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_DISTROS_JSON=' . json_encode([$distribution], JSON_THROW_ON_ERROR));
        putenv('W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_COMMAND_MAP_JSON=' . json_encode($commandMap, JSON_THROW_ON_ERROR));
    }

    private function clearFixtureEnv(): void
    {
        putenv('W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_DISTROS_JSON');
        putenv('W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_COMMAND_MAP_JSON');
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

    private function quoteWslDistribution(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1) {
            return $value;
        }

        return $this->quoteForWindowsCommand($value);
    }

    private function quoteForWindowsCommand(string $value): string
    {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    /**
     * @param list<string> $command
     * @return array{exitCode: int, stdout: string, stderr: string}
     */
    private function runPhpScript(array $command): array
    {
        array_unshift($command, PHP_BINARY);

        $processEnvironment = [];
        foreach (array_merge($_ENV, $_SERVER) as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if (is_string($value) || is_int($value) || is_float($value) || is_bool($value)) {
                $processEnvironment[$key] = (string) $value;
            }
        }

        foreach ([
            'W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_DISTROS_JSON',
            'W4_RUN_NATIVE_DBUS_REAL_HOST_VALIDATION_WSL_COMMAND_MAP_JSON',
        ] as $variable) {
            $value = getenv($variable);
            if ($value !== false) {
                $processEnvironment[$variable] = $value;
            }
        }

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            $this->repoRoot,
            $processEnvironment
        );

        self::assertIsResource($process);

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        return [
            'exitCode' => $exitCode,
            'stdout' => is_string($stdout) ? $stdout : '',
            'stderr' => is_string($stderr) ? $stderr : '',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJson(string $json): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer %s', $path));

        return $this->decodeJson($raw);
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

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath)) {
                $this->removeDirectory($itemPath);
                continue;
            }

            @unlink($itemPath);
        }

        @rmdir($path);
    }
}
