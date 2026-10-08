<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class ControlCenterLiveOutputCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-control-center-live-output-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testValidateControlCenterLiveOutputReturnsExpectedPayload(): void
    {
        $liveOutputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output' . DIRECTORY_SEPARATOR . 'w4-os-home';
        $this->seedLiveOutput($liveOutputDir);

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_control_center_live_output.php',
            [
                '--profile',
                'w4-os-home',
                '--root-dir',
                $this->workspaceRoot,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('control-center-live-output-validation', $payload['kind']);
        self::assertSame('w4-os-home', $payload['profile_id']);
        self::assertSame(4, $payload['launcher_count']);
        self::assertSame(1, $payload['pending_module_count']);
        self::assertContains(
            'image-root/system-overlay/usr/share/applications/w4-control-center-home-updates.desktop',
            $payload['desktop_files']
        );
        self::assertTrue($payload['filesystem_packages']['gnome-control-center']);
        self::assertTrue($payload['filesystem_packages']['gnome-software']);
        self::assertSame('graphical.target', $payload['live_summary']['default_target']);
    }

    public function testValidateControlCenterLiveOutputSupportsTextFormat(): void
    {
        $liveOutputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'materialized-live';
        $this->seedLiveOutput($liveOutputDir);

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_control_center_live_output.php',
            [
                '--profile',
                'w4-os-home',
                '--root-dir',
                $this->workspaceRoot,
                '--live-output-dir',
                $liveOutputDir,
                '--format',
                'text',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('Control Center live output', $result['stdout']);
        self::assertStringContainsString('Launchers: 4', $result['stdout']);
        self::assertStringContainsString('gnome-software: yes', $result['stdout']);
    }

    private function seedLiveOutput(string $liveOutputDir): void
    {
        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'live-summary.env',
            <<<'ENV'
W4_PROFILE_ID="w4-os-home"
W4_PROFILE_NAME="W4 OS Home"
W4_LIVE_USER="w4live"
W4_LIVE_HOSTNAME="w4-home-live"
W4_DEFAULT_TARGET="graphical.target"
W4_GENERATED_AT="2026-10-08T13:20:00Z"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers-summary.txt',
            <<<'TXT'
Control Center launchers · w4-os-home
Strategy: gnome-augmented
Launchers: 4
Pending modules: 1
TXT
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers.json',
            <<<'JSON'
{
  "control_center_gnome_launchers_schema_version": 1,
  "kind": "control-center-gnome-launchers",
  "profile_id": "w4-os-home",
  "generated_at": "2026-10-08T13:20:00Z",
  "launchers": [
    { "desktop_file": "w4-control-center-home-home.desktop" },
    { "desktop_file": "w4-control-center-home-system.desktop" },
    { "desktop_file": "w4-control-center-home-security.desktop" },
    { "desktop_file": "w4-control-center-home-updates.desktop" }
  ],
  "pending_modules": [
    { "id": "storage" }
  ]
}
JSON
        );

        $desktopFiles = [
            'w4-control-center-home-home.desktop' => "Exec=gnome-control-center\n",
            'w4-control-center-home-system.desktop' => "Exec=gnome-control-center system\n",
            'w4-control-center-home-security.desktop' => "Exec=gnome-control-center privacy\n",
            'w4-control-center-home-updates.desktop' => "Exec=gnome-software --mode=updates\n",
        ];

        foreach ($desktopFiles as $file => $contents) {
            $this->writeFile(
                $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'applications' . DIRECTORY_SEPARATOR . $file,
                "[Desktop Entry]\n" . $contents
            );
        }

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest',
            <<<'TXT'
gnome-control-center 48.2-1
gnome-software 48.1-1
gnome-shell 48.7-1
TXT
        );
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

        $process = proc_open($command, $descriptorSpec, $pipes, $this->repoRoot);
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

    private function writeFile(string $path, string $contents): void
    {
        $directory = dirname($path);
        if (!is_dir($directory)) {
            self::assertTrue(mkdir($directory, 0777, true), sprintf('No se pudo crear %s', $directory));
        }

        self::assertNotFalse(file_put_contents($path, $contents), sprintf('No se pudo escribir %s', $path));
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
