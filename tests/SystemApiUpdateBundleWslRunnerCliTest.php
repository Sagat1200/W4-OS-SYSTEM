<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemApiUpdateBundleWslRunnerCliTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-system-api-update-bundle-wsl-runner-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->clearEnvFixtures();
        $this->removeDirectory($this->tempDir);
    }

    public function testRunnerReturnsCanonicalReadyPayloadForEmbeddedBundle(): void
    {
        $storeDir = $this->createEmbeddedBundleStore('system-api-update-op-ready');
        $this->setFixtureEnv([]);

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_system_api_update_bundle_in_wsl.php',
            [
                '--store-dir',
                $storeDir,
                '--distribution',
                'Ubuntu-Fixture',
                '--stage',
                'offline',
                '--repository-dir',
                $this->tempDir . DIRECTORY_SEPARATOR . 'repo-copy',
                '--check-only',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame('Ubuntu-Fixture', $payload['distribution']);
        self::assertSame($storeDir, $payload['store_dir_windows']);
        self::assertSame('/mnt/c/w4/system-api-update-op-ready', $payload['store_dir_wsl']);
        self::assertSame('offline', $payload['stage']);
        self::assertStringContainsString('source ./executor.env', $payload['offline_command']);
        self::assertStringContainsString('./run-update-with-repo-env.sh --repo-dir', $payload['offline_command']);
        self::assertStringContainsString('./run-health-checks.sh', $payload['health_command']);
        self::assertStringContainsString('./reconcile-after-reboot.sh', $payload['reconcile_command']);
        self::assertStringContainsString('wsl -d Ubuntu-Fixture -- bash -lc', $payload['run_command']);
        self::assertContains('executor.env', $payload['required_artifacts']);
    }

    public function testRunnerReturnsCanonicalOkPayloadWhenExecutingHealthStage(): void
    {
        $storeDir = $this->createEmbeddedBundleStore('system-api-update-op-health');
        $expectedRunCommand = sprintf(
            'wsl -d %s -- bash -lc %s',
            'Ubuntu-Fixture',
            $this->quoteForWindowsCommand(sprintf(
                'cd %s && %s && source ./executor.env && ./run-health-checks.sh',
                $this->quoteForBash('/mnt/c/w4/system-api-update-op-health'),
                'chmod +x \'./run-update-offline.sh\' \'./run-update-with-repo-env.sh\' \'./run-health-checks.sh\' \'./reconcile-after-reboot.sh\''
            ))
        );

        $this->setFixtureEnv([
            $expectedRunCommand => '{"status":"ok","health":"executed"}',
        ]);

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_system_api_update_bundle_in_wsl.php',
            [
                '--store-dir',
                $storeDir,
                '--distribution',
                'Ubuntu-Fixture',
                '--stage',
                'health',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('health', $payload['stage']);
        self::assertSame($expectedRunCommand, $payload['run_command']);
        self::assertSame('{"status":"ok","health":"executed"}', $payload['execution_output']);
    }

    private function setFixtureEnv(array $commandMap): void
    {
        putenv('W4_RUN_SYSTEM_API_UPDATE_BUNDLE_WSL_DISTROS_JSON=' . json_encode(['Ubuntu-Fixture'], JSON_THROW_ON_ERROR));
        putenv('W4_RUN_SYSTEM_API_UPDATE_BUNDLE_WSL_COMMAND_MAP_JSON=' . json_encode(array_merge([
            sprintf('wsl -d %s -- wslpath -a %s', 'Ubuntu-Fixture', $this->quoteForWindowsCommand($this->tempDir . DIRECTORY_SEPARATOR . 'repo-copy')) => '/mnt/c/w4/repo-copy',
        ], $this->defaultWslPathFixtures(), $commandMap), JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, string>
     */
    private function defaultWslPathFixtures(): array
    {
        return [
            sprintf('wsl -d %s -- wslpath -a %s', 'Ubuntu-Fixture', $this->quoteForWindowsCommand($this->tempDir . DIRECTORY_SEPARATOR . 'system-api-update-op-ready')) => '/mnt/c/w4/system-api-update-op-ready',
            sprintf('wsl -d %s -- wslpath -a %s', 'Ubuntu-Fixture', $this->quoteForWindowsCommand($this->tempDir . DIRECTORY_SEPARATOR . 'system-api-update-op-ready' . DIRECTORY_SEPARATOR . 'health-check-results.json')) => '/mnt/c/w4/system-api-update-op-ready/health-check-results.json',
            sprintf('wsl -d %s -- wslpath -a %s', 'Ubuntu-Fixture', $this->quoteForWindowsCommand($this->tempDir . DIRECTORY_SEPARATOR . 'system-api-update-op-health')) => '/mnt/c/w4/system-api-update-op-health',
            sprintf('wsl -d %s -- wslpath -a %s', 'Ubuntu-Fixture', $this->quoteForWindowsCommand($this->tempDir . DIRECTORY_SEPARATOR . 'system-api-update-op-health' . DIRECTORY_SEPARATOR . 'health-check-results.json')) => '/mnt/c/w4/system-api-update-op-health/health-check-results.json',
        ];
    }

    private function clearEnvFixtures(): void
    {
        foreach ([
            'W4_RUN_SYSTEM_API_UPDATE_BUNDLE_WSL_DISTROS_JSON',
            'W4_RUN_SYSTEM_API_UPDATE_BUNDLE_WSL_COMMAND_MAP_JSON',
        ] as $variable) {
            putenv($variable);
        }
    }

    private function createEmbeddedBundleStore(string $directoryName): string
    {
        $path = $this->tempDir . DIRECTORY_SEPARATOR . $directoryName;
        self::assertTrue(mkdir($path, 0777, true), sprintf('No se pudo crear %s', $path));

        foreach ([
            'executor.env' => "#!/usr/bin/env bash\nexport W4_UPDATE_STORE_DIR=\"\${W4_UPDATE_STORE_DIR:-\${SCRIPT_DIR}}\"\n",
            'run-update-offline.sh' => "#!/usr/bin/env bash\n",
            'run-update-with-repo-env.sh' => "#!/usr/bin/env bash\n",
            'run-health-checks.sh' => "#!/usr/bin/env bash\n",
            'reconcile-after-reboot.sh' => "#!/usr/bin/env bash\n",
            'update-executor.json' => "{}\n",
            'update-plan.json' => "{}\n",
        ] as $file => $contents) {
            self::assertNotFalse(file_put_contents($path . DIRECTORY_SEPARATOR . $file, $contents), sprintf('No se pudo escribir %s', $file));
        }

        return $path;
    }

    /**
     * @param list<string> $arguments
     * @return array{exitCode:int,stdout:string,stderr:string}
     */
    private function runPhpScript(string $scriptPath, array $arguments): array
    {
        $command = array_merge([PHP_BINARY, $scriptPath], $arguments);
        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes, $this->rootDir);
        self::assertIsResource($process, sprintf('No se pudo ejecutar %s', $scriptPath));

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

    private function quoteForWindowsCommand(string $value): string
    {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    private function quoteForBash(string $value): string
    {
        return "'" . str_replace("'", "'\"'\"'", $value) . "'";
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
