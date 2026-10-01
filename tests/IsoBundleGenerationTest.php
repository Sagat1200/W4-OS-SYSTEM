<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class IsoBundleGenerationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-iso-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateIsoBundleCarriesEditionPolicyIntoIsoMetadata(): void
    {
        $buildInputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server.build-input.json';
        $overlayDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server-overlay';
        $liveDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server-live';
        $isoDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server-iso';

        $buildInput = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_build_input.php',
            [
                '--profile',
                'w4-os-server',
                '--output',
                $buildInputPath,
            ]
        );
        self::assertSame(0, $buildInput['exitCode'], $buildInput['stderr']);

        $overlay = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_system_overlay.php',
            [
                '--input',
                $buildInputPath,
                '--output',
                $overlayDir,
            ]
        );
        self::assertSame(0, $overlay['exitCode'], $overlay['stderr']);

        $live = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_live_bundle.php',
            [
                '--input',
                $buildInputPath,
                '--overlay-manifest',
                $overlayDir . DIRECTORY_SEPARATOR . 'overlay-manifest.json',
                '--output',
                $liveDir,
            ]
        );
        self::assertSame(0, $live['exitCode'], $live['stderr']);

        $iso = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_iso_bundle.php',
            [
                '--live-manifest',
                $liveDir . DIRECTORY_SEPARATOR . 'live-manifest.json',
                '--output',
                $isoDir,
            ]
        );
        self::assertSame(0, $iso['exitCode'], $iso['stderr']);

        $manifest = $this->decodeJsonFile($isoDir . DIRECTORY_SEPARATOR . 'iso-manifest.json');
        self::assertSame('multi-user.target', $manifest['edition_policy']['default_target']);
        self::assertSame('config/editions/server/policy.json', $manifest['edition_policy']['path']);
        self::assertSame('multi-user.target', $manifest['branding']['default_target']);

        $composeScript = file_get_contents($isoDir . DIRECTORY_SEPARATOR . 'compose-iso.sh');
        self::assertNotFalse($composeScript);
        self::assertStringContainsString('W4_DEFAULT_TARGET="multi-user.target"', $composeScript);
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
    private function decodeJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        self::assertNotFalse($raw, sprintf('No se pudo leer %s', $path));

        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);

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
