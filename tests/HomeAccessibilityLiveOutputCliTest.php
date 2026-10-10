<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class HomeAccessibilityLiveOutputCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-home-accessibility-live-output-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testValidateHomeAccessibilityLiveOutputReturnsExpectedPayload(): void
    {
        $liveOutputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output' . DIRECTORY_SEPARATOR . 'w4-os-home';
        $this->seedLiveOutput($liveOutputDir);

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_home_accessibility_live_output.php',
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
        self::assertSame('home-accessibility-live-output-validation', $payload['kind']);
        self::assertSame('w4-os-home', $payload['profile_id']);
        self::assertContains('accessibility-baseline', $payload['feature_flags']);
        self::assertTrue($payload['required_packages']['present']['orca']);
        self::assertTrue($payload['required_packages']['present']['at-spi2-core']);
        self::assertTrue($payload['required_packages']['present']['speech-dispatcher']);
        self::assertSame('available', $payload['capabilities'][0]['status']);
        self::assertSame('available', $payload['capabilities'][1]['status']);
        self::assertSame('available', $payload['capabilities'][2]['status']);
        self::assertSame('home', $payload['settings_route']['launcher_id']);
        self::assertSame('w4-control-center-home-home.desktop', $payload['settings_route']['favorite']);
        self::assertContains('screen-reader-task-flow', $payload['deferred_steps']);
    }

    public function testValidateHomeAccessibilityLiveOutputSupportsTextFormat(): void
    {
        $liveOutputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'materialized-live';
        $this->seedLiveOutput($liveOutputDir);

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_home_accessibility_live_output.php',
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
        self::assertStringContainsString('Home accessibility live output', $result['stdout']);
        self::assertStringContainsString('orca: yes', $result['stdout']);
        self::assertStringContainsString('Lector de pantalla: available', $result['stdout']);
        self::assertStringContainsString('Contraste y ayudas visuales: available', $result['stdout']);
        self::assertStringContainsString('Ruta visible a W4 Settings: available', $result['stdout']);
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
W4_GENERATED_AT="2026-10-09T08:20:00Z"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env',
            <<<'ENV'
W4_PROFILE_ID="w4-os-home"
W4_PROFILE_NAME="W4 OS Home"
W4_HOSTNAME="w4-home"
W4_LIVE_HOSTNAME="w4-home-live"
W4_LIVE_USER="w4live"
W4_DEFAULT_TARGET="graphical.target"
W4_FEATURES="accessibility-baseline,desktop-defaults,gnome-gdm-default-route,home-onboarding,local-backup-ready,reversible-branding-defaults"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json',
            <<<'JSON'
{
  "schema_version": 1,
  "kind": "desktop-defaults",
  "profile_id": "w4-os-home",
  "favorites": [
    "org.gnome.Nautilus.desktop",
    "w4-control-center-home-home.desktop",
    "firefox-esr.desktop",
    "org.libreoffice.LibreOffice.StartCenter.desktop",
    "w4-control-center-home-updates.desktop"
  ]
}
JSON
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'dconf' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'local.d' . DIRECTORY_SEPARATOR . '00-w4-home',
            <<<'TXT'
[org/gnome/shell]
favorite-apps=['org.gnome.Nautilus.desktop', 'w4-control-center-home-home.desktop', 'firefox-esr.desktop', 'org.libreoffice.LibreOffice.StartCenter.desktop', 'w4-control-center-home-updates.desktop']
TXT
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers.json',
            <<<'JSON'
{
  "control_center_gnome_launchers_schema_version": 1,
  "kind": "control-center-gnome-launchers",
  "profile_id": "w4-os-home",
  "generated_at": "2026-10-09T08:20:00Z",
  "launchers": [
    { "id": "home", "desktop_file": "w4-control-center-home-home.desktop" },
    { "id": "system", "desktop_file": "w4-control-center-home-system.desktop" },
    { "id": "updates", "desktop_file": "w4-control-center-home-updates.desktop" }
  ]
}
JSON
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest',
            <<<'TXT'
at-spi2-core 2.54.1-3
gnome-accessibility-themes 3.28-2
gnome-control-center 1:48.4-1~deb13u1
gsettings-desktop-schemas 48.0-1
libatk-adaptor 2.54.1-3
orca 48.4-1
speech-dispatcher 0.12.0-2
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
