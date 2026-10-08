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
        self::assertSame('graphical.target', $manifest['edition_policy']['default_target']);
        self::assertSame('config/editions/home/policy.json', $manifest['edition_policy']['path']);
        self::assertSame('config/editions/home/desktop-defaults.json', $manifest['desktop_defaults']['path']);
        self::assertSame('overlay-dconf', $manifest['desktop_defaults']['application_method']);
        self::assertContains('files/etc/dconf/profile/user', $manifest['desktop_defaults']['dconf_profiles']);
        self::assertContains('files/etc/dconf/db/local.d/00-w4-home', $manifest['desktop_defaults']['dconf_databases']);
        self::assertContains('files/usr/share/w4/branding/home/wallpapers/w4-home-default.svg', $manifest['desktop_defaults']['assets']);
        self::assertArrayHasKey('control_center_gnome_launchers', $manifest);

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
        self::assertStringContainsString('systemctl set-default "${TARGET_DEFAULT}"', $firstbootScript);
        self::assertStringContainsString('systemctl enable apparmor.service', $firstbootScript);
        self::assertStringContainsString('ufw default deny incoming', $firstbootScript);
        self::assertStringContainsString('ufw --force enable', $firstbootScript);
        self::assertStringContainsString('dconf update', $firstbootScript);

        $livePrepScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'w4-live-prep.sh');
        self::assertNotFalse($livePrepScript);
        self::assertStringContainsString('dconf update', $livePrepScript);

        $profileEnv = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env');
        self::assertNotFalse($profileEnv);
        self::assertStringContainsString('W4_DEFAULT_TARGET="graphical.target"', $profileEnv);

        $desktopDefaults = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json');
        self::assertSame('w4-os-home', $desktopDefaults['profile_id']);
        self::assertSame('gdm', $desktopDefaults['desktop']['display_manager']);
        self::assertSame('gnome', $desktopDefaults['desktop']['session']);
        self::assertSame('overlay-dconf', $desktopDefaults['application']['method']);
        self::assertSame('file:///usr/share/w4/branding/home/wallpapers/w4-home-default.svg', $desktopDefaults['wallpaper']['uri']);

        $dconfUserProfile = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'dconf' . DIRECTORY_SEPARATOR . 'profile' . DIRECTORY_SEPARATOR . 'user');
        self::assertNotFalse($dconfUserProfile);
        self::assertStringContainsString('system-db:local', $dconfUserProfile);

        $userDefaults = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'dconf' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'local.d' . DIRECTORY_SEPARATOR . '00-w4-home');
        self::assertNotFalse($userDefaults);
        self::assertStringContainsString("[org/gnome/desktop/interface]", $userDefaults);
        self::assertStringContainsString("color-scheme='prefer-dark'", $userDefaults);
        self::assertStringContainsString("picture-uri='file:///usr/share/w4/branding/home/wallpapers/w4-home-default.svg'", $userDefaults);

        $wallpaper = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'branding' . DIRECTORY_SEPARATOR . 'home' . DIRECTORY_SEPARATOR . 'wallpapers' . DIRECTORY_SEPARATOR . 'w4-home-default.svg');
        self::assertNotFalse($wallpaper);
        self::assertStringContainsString('<svg', $wallpaper);
        self::assertStringContainsString('W4 OS Home', $wallpaper);

        $applyScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'apply-overlay.sh');
        self::assertNotFalse($applyScript);
        self::assertStringContainsString('DEFAULT_TARGET="graphical.target"', $applyScript);
        self::assertStringContainsString('${DEFAULT_TARGET}.wants', $applyScript);
        self::assertStringContainsString('etc/dconf', $applyScript);
        self::assertStringContainsString('usr/share/applications', $applyScript);

        $launcherState = $manifest['control_center_gnome_launchers']['status'];
        self::assertContains($launcherState, ['integrated', 'skipped']);

        if ($launcherState === 'integrated') {
            self::assertSame('files/etc/w4/control-center/gnome-launchers.json', $manifest['control_center_gnome_launchers']['runtime_manifest']);
            self::assertContains(
                'files/usr/share/applications/w4-control-center-home-updates.desktop',
                $manifest['control_center_gnome_launchers']['desktop_files']
            );

            $launcherManifest = $this->decodeJsonFile(
                $outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers.json'
            );
            self::assertSame('control-center-gnome-launchers', $launcherManifest['kind']);

            $updatesLauncher = file_get_contents(
                $outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'applications' . DIRECTORY_SEPARATOR . 'w4-control-center-home-updates.desktop'
            );
            self::assertNotFalse($updatesLauncher);
            self::assertStringContainsString('Exec=gnome-software --mode=updates', $updatesLauncher);
        } else {
            self::assertNotSame('', trim((string) $manifest['control_center_gnome_launchers']['reason']));
        }
    }

    public function testGenerateSystemOverlayUsesServerBrandingWithoutHomeFallback(): void
    {
        $buildInputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server.build-input.json';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server';

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
                $outputDir,
            ]
        );
        self::assertSame(0, $overlay['exitCode'], $overlay['stderr']);

        $manifest = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'overlay-manifest.json');
        self::assertSame('w4-os-server', $manifest['profile_id']);
        self::assertSame('Server', $manifest['branding']['edition']);
        self::assertSame('w4-server', $manifest['branding']['hostname']);
        self::assertSame('w4-server-live', $manifest['branding']['live_hostname']);
        self::assertSame('multi-user.target', $manifest['edition_policy']['default_target']);
        self::assertSame('config/editions/server/policy.json', $manifest['edition_policy']['path']);
        self::assertArrayNotHasKey('desktop_defaults', $manifest);

        $motd = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'motd');
        self::assertNotFalse($motd);
        self::assertStringContainsString('W4 OS Server', $motd);
        self::assertStringContainsString('entorno headless', $motd);
        self::assertStringNotContainsString('escritorio personal', $motd);

        $hosts = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'hosts');
        self::assertNotFalse($hosts);
        self::assertStringContainsString('127.0.1.1 w4-server', $hosts);

        $profileEnv = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env');
        self::assertNotFalse($profileEnv);
        self::assertStringContainsString('W4_DEFAULT_TARGET="multi-user.target"', $profileEnv);
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
