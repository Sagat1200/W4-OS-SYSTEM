<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class InstallationPlanCliTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-installation-plan-cli-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateInstallationPlanReturnsCanonicalSuccessPayload(): void
    {
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-install-plan';
        $buildInputPath = $this->fixturePath('build/inputs/w4-os-home.build-input.json');
        $installationProfilePath = $this->fixturePath('build/install/w4-os-home/installation-profile.derived.json');
        $inventoryPath = $this->fixturePath('build/install/w4-os-home/disk-inventory.json');

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_installation_plan.php'),
            [
                '--profile',
                'w4-os-home',
                '--build-input',
                $buildInputPath,
                '--install-profile',
                $installationProfilePath,
                '--disk-inventory',
                $inventoryPath,
                '--output-dir',
                $outputDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('w4-os-home', $payload['profile_id']);
        self::assertSame($buildInputPath, $payload['build_input']);
        self::assertSame($installationProfilePath, $payload['installation_profile']);
        self::assertSame($inventoryPath, $payload['disk_inventory']);
        self::assertSame($outputDir, $payload['output_dir']);
        self::assertSame('/dev/sda', $payload['selected_disk']);
        self::assertNotSame('', $payload['binding_hash']);

        self::assertFileExists($outputDir . DIRECTORY_SEPARATOR . 'installation-plan.json');
        self::assertFileExists($outputDir . DIRECTORY_SEPARATOR . 'installation-bundle.json');
        self::assertFileExists($outputDir . DIRECTORY_SEPARATOR . 'installation-profile.json');
        self::assertFileExists($outputDir . DIRECTORY_SEPARATOR . 'disk-inventory.json');
        self::assertFileExists($outputDir . DIRECTORY_SEPARATOR . 'INSTALLATION_SUMMARY.txt');

        $plan = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'installation-plan.json');
        self::assertSame('w4-os-home', $plan['profile_id']);
        self::assertSame('/dev/sda', $plan['plan_binding']['selected_disk']['device']);
        self::assertSame($payload['binding_hash'], $plan['plan_binding']['binding_hash']);
    }

    private function fixturePath(string $relativePath): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
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
