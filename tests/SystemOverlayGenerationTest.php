<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class SystemOverlayGenerationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-overlay-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateSystemOverlayIncludesSecurityBaselineFiles(): void
    {
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home';

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_system_overlay.php',
            [
                '--profile',
                'w4-os-home',
                '--output',
                $outputDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $manifest = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'overlay-manifest.json');
        self::assertSame('system-overlay', $manifest['kind']);
        self::assertSame('ufw', $manifest['security']['firewall']['tool']);
        self::assertSame('firstboot-enables-service', $manifest['security']['apparmor']['activation']);

        $grubDefaults = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'grub.d' . DIRECTORY_SEPARATOR . '50-w4-security.cfg');
        self::assertNotFalse($grubDefaults);
        self::assertStringContainsString('apparmor=1 security=apparmor', $grubDefaults);

        $ufwDefaults = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'ufw');
        self::assertNotFalse($ufwDefaults);
        self::assertStringContainsString('DEFAULT_INPUT_POLICY="DROP"', $ufwDefaults);

        $ufwConfig = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'ufw' . DIRECTORY_SEPARATOR . 'ufw.conf');
        self::assertNotFalse($ufwConfig);
        self::assertStringContainsString('ENABLED=yes', $ufwConfig);

        $firstbootScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'w4-firstboot.sh');
        self::assertNotFalse($firstbootScript);
        self::assertStringContainsString('systemctl enable apparmor.service', $firstbootScript);
        self::assertStringContainsString('ufw default deny incoming', $firstbootScript);
        self::assertStringContainsString('ufw --force enable', $firstbootScript);
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
