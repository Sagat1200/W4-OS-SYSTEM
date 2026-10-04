<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class DiskInventoryWslRunnerCliTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-disk-inventory-wsl-runner-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->clearEnvFixtures();
        $this->removeDirectory($this->tempDir);
    }

    public function testRunDiskInventoryInWslReturnsCanonicalReadyPayload(): void
    {
        $outputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'wsl-ready-inventory.json';
        $this->setFixtureEnv();

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_disk_inventory_in_wsl.php',
            [
                '--distribution',
                'Ubuntu-Fixture',
                '--id',
                'wsl-ready-inventory',
                '--output',
                $outputPath,
                '--check-only',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame('Ubuntu-Fixture', $payload['distribution']);
        self::assertSame($outputPath, $payload['output_path_windows']);
        self::assertStringContainsString('generate_disk_inventory.php', $payload['collector_path_windows']);
        self::assertStringContainsString('--wsl-distribution', $payload['run_command']);
        self::assertStringContainsString('Ubuntu-Fixture', $payload['run_command']);
    }

    public function testRunDiskInventoryInWslReturnsCanonicalOkPayload(): void
    {
        $outputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'wsl-executed-inventory.json';
        $this->setFixtureEnv();

        $expectedCommand = sprintf(
            'php %s --wsl-distribution %s --id %s --output %s',
            escapeshellarg($this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_disk_inventory.php'),
            escapeshellarg('Ubuntu-Fixture'),
            escapeshellarg('wsl-executed-inventory'),
            escapeshellarg($outputPath)
        );

        putenv('W4_RUN_DISK_INVENTORY_WSL_COMMAND_MAP_JSON=' . json_encode([
            $expectedCommand => '{"status":"ok","inventory_id":"wsl-executed-inventory"}',
        ], JSON_THROW_ON_ERROR));

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'run_disk_inventory_in_wsl.php',
            [
                '--distribution',
                'Ubuntu-Fixture',
                '--id',
                'wsl-executed-inventory',
                '--output',
                $outputPath,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('Ubuntu-Fixture', $payload['distribution']);
        self::assertSame('wsl-executed-inventory', $payload['inventory_id']);
        self::assertSame($outputPath, $payload['output_path_windows']);
        self::assertSame('{"status":"ok","inventory_id":"wsl-executed-inventory"}', $payload['execution_output']);
    }

    private function setFixtureEnv(): void
    {
        putenv('W4_RUN_DISK_INVENTORY_WSL_DISTROS_JSON=' . json_encode([
            'Ubuntu-Fixture',
            'Ubuntu-Secondary',
        ], JSON_THROW_ON_ERROR));
    }

    private function clearEnvFixtures(): void
    {
        foreach ([
            'W4_RUN_DISK_INVENTORY_WSL_DISTROS_JSON',
            'W4_RUN_DISK_INVENTORY_WSL_COMMAND_MAP_JSON',
        ] as $variable) {
            putenv($variable);
        }
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
