<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class BusinessKdeLiveOutputCliTest extends TestCase
{
    private string $repoRoot;
    private string $workspaceRoot;

    protected function setUp(): void
    {
        $this->repoRoot = dirname(__DIR__);
        $this->workspaceRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-business-kde-live-output-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->workspaceRoot, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->workspaceRoot);
    }

    public function testValidateBusinessKdeLiveOutputReturnsExpectedPayload(): void
    {
        $liveOutputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output' . DIRECTORY_SEPARATOR . 'w4-os-business';
        $this->seedLiveOutput($liveOutputDir);

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_business_kde_live_output.php',
            [
                '--profile',
                'w4-os-business',
                '--root-dir',
                $this->workspaceRoot,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);

        self::assertSame('ok', $payload['status']);
        self::assertSame('business-kde-live-output-validation', $payload['kind']);
        self::assertSame('w4-os-business', $payload['profile_id']);
        self::assertContains('desktop-defaults', $payload['feature_flags']);
        self::assertContains('kde-sddm-default-route', $payload['feature_flags']);
        self::assertTrue($payload['required_packages']['present']['plasma-desktop']);
        self::assertTrue($payload['required_packages']['present']['sddm']);
        self::assertSame('available', $payload['capabilities'][0]['status']);
        self::assertSame('available', $payload['capabilities'][1]['status']);
        self::assertSame('available', $payload['capabilities'][2]['status']);
        self::assertSame('plasma-desktop', $payload['desktop_defaults']['shell']);
        self::assertSame('sddm', $payload['desktop_defaults']['display_manager']);
        self::assertSame('absent', $payload['stale_payloads']['image-root/system-overlay/etc/xdg/autostart/w4-home-onboarding-light-ui.desktop']);
        self::assertContains('business-enrollment-readiness', $payload['deferred_steps']);
    }

    public function testValidateBusinessKdeLiveOutputSupportsTextFormat(): void
    {
        $liveOutputDir = $this->workspaceRoot . DIRECTORY_SEPARATOR . 'materialized-live';
        $this->seedLiveOutput($liveOutputDir);

        $result = $this->runPhpScript(
            $this->repoRoot . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'validate_business_kde_live_output.php',
            [
                '--profile',
                'w4-os-business',
                '--root-dir',
                $this->workspaceRoot,
                '--live-output-dir',
                $liveOutputDir,
                '--format',
                'text',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        self::assertStringContainsString('Business KDE live output', $result['stdout']);
        self::assertStringContainsString('plasma-desktop: yes', $result['stdout']);
        self::assertStringContainsString('Login manager SDDM: available', $result['stdout']);
        self::assertStringContainsString('Ruta visible a System Settings: available', $result['stdout']);
    }

    private function seedLiveOutput(string $liveOutputDir): void
    {
        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'live-summary.env',
            <<<'ENV'
W4_PROFILE_ID="w4-os-business"
W4_PROFILE_NAME="W4 OS Business"
W4_LIVE_USER="w4live"
W4_LIVE_HOSTNAME="w4-business-live"
W4_DEFAULT_TARGET="graphical.target"
W4_GENERATED_AT="2026-10-09T19:10:00Z"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env',
            <<<'ENV'
W4_PROFILE_ID="w4-os-business"
W4_PROFILE_NAME="W4 OS Business"
W4_HOSTNAME="w4-business"
W4_LIVE_HOSTNAME="w4-business-live"
W4_LIVE_USER="w4live"
W4_DEFAULT_TARGET="graphical.target"
W4_FEATURES="desktop-defaults,business-policy-hooks,device-enrollment-ready,inventory-ready,kde-sddm-default-route,reversible-branding-defaults"
ENV
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json',
            <<<'JSON'
{
  "schema_version": 1,
  "kind": "desktop-defaults",
  "profile_id": "w4-os-business",
  "desktop": {
    "shell": "plasma-desktop",
    "session": "plasma",
    "display_manager": "sddm"
  },
  "application": {
    "method": "overlay-kde-defaults",
    "scope": "profile-defaults"
  },
  "wallpaper": {
    "uri": "file:///usr/share/w4/branding/business/wallpapers/w4-business-default.svg",
    "asset_path": "/usr/share/w4/branding/business/wallpapers/w4-business-default.svg"
  },
  "favorites": [
    "org.kde.dolphin.desktop",
    "systemsettings.desktop",
    "firefox-esr.desktop",
    "org.libreoffice.LibreOffice.StartCenter.desktop",
    "org.kde.konsole.desktop"
  ]
}
JSON
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'branding' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'wallpapers' . DIRECTORY_SEPARATOR . 'w4-business-default.svg',
            <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" width="3840" height="2160" viewBox="0 0 3840 2160">
  <text x="0" y="40">W4 OS Business</text>
  <text x="0" y="80">PLASMA + SDDM default route</text>
</svg>
SVG
        );

        $this->writeFile(
            $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest',
            <<<'TXT'
dolphin 24.12.0-1
konsole 24.12.0-1
plasma-desktop 6.3.6-1
plasma-nm 6.3.6-1
plasma-workspace 6.3.6-1
sddm 0.21.0-2
systemsettings 6.3.6-1
xdg-desktop-portal-kde 6.3.6-1
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
