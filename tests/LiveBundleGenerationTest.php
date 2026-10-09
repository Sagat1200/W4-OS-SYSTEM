<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class LiveBundleGenerationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-live-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateLiveBundleCarriesAndAppliesSystemOverlay(): void
    {
        $buildInputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server.build-input.json';
        $overlayDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server-overlay';
        $liveDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server-live';

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

        $manifest = $this->decodeJsonFile($liveDir . DIRECTORY_SEPARATOR . 'live-manifest.json');
        self::assertSame('files/system-overlay', $manifest['source_overlay_payload']);
        self::assertContains('files/system-overlay/', $manifest['generated_files']);
        self::assertSame('multi-user.target', $manifest['edition_policy']['default_target']);
        self::assertSame('config/editions/server/policy.json', $manifest['edition_policy']['path']);

        self::assertFileExists($liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'w4-firstboot.service');
        self::assertFileExists($liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'w4-firstboot.sh');

        $composeScript = file_get_contents($liveDir . DIRECTORY_SEPARATOR . 'compose-live.sh');
        self::assertNotFalse($composeScript);
        self::assertStringContainsString('OVERLAY_FILES_DIR="${FILES_DIR}/system-overlay"', $composeScript);
        self::assertStringContainsString('apply_system_overlay "${WORK_ROOTFS}"', $composeScript);
        self::assertStringContainsString('chmod 0755 "${rootfs_dir}" "${rootfs_dir}/etc" "${rootfs_dir}/usr"', $composeScript);
        self::assertStringContainsString('chmod 0755 "${rootfs_dir}/etc/ufw"', $composeScript);
        self::assertStringContainsString('chmod 0644 "${rootfs_dir}/etc/ufw/ufw.conf"', $composeScript);
        self::assertStringContainsString('W4_DEFAULT_TARGET="${W4_DEFAULT_TARGET:-multi-user.target}"', $composeScript);
        self::assertStringContainsString('mkdir -p "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants"', $composeScript);
        self::assertStringContainsString('ln -sfn ../w4-firstboot.service "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants/w4-firstboot.service"', $composeScript);
        self::assertStringContainsString('ln -sfn ../w4-live-prep.service "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants/w4-live-prep.service"', $composeScript);
        self::assertStringContainsString('project_system_overlay_runtime_state() {', $composeScript);
        self::assertStringContainsString('local wants_dir="${stage_systemd_dir}/${W4_DEFAULT_TARGET}.wants"', $composeScript);
        self::assertStringContainsString('cp -a "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants/w4-firstboot.service" "${wants_dir}/w4-firstboot.service"', $composeScript);
        self::assertStringContainsString('cp -a "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants/w4-live-prep.service" "${wants_dir}/w4-live-prep.service"', $composeScript);
        self::assertStringContainsString('project_system_overlay_runtime_state "${WORK_ROOTFS}" "${STAGE_OUTPUT_DIR}"', $composeScript);
        self::assertLessThan(
            strpos($composeScript, 'mkdir -p "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants"'),
            strpos($composeScript, 'W4_DEFAULT_TARGET="${W4_DEFAULT_TARGET:-multi-user.target}"')
        );
    }

    public function testGenerateLiveBundleCarriesControlCenterGnomeLaunchersForHome(): void
    {
        $buildInputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home.build-input.json';
        $overlayDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-overlay';
        $liveDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-live';

        $buildInput = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_build_input.php',
            [
                '--profile',
                'w4-os-home',
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

        $manifest = $this->decodeJsonFile($liveDir . DIRECTORY_SEPARATOR . 'live-manifest.json');
        self::assertSame('graphical.target', $manifest['edition_policy']['default_target']);
        self::assertArrayHasKey('control_center_gnome_launchers', $manifest);
        self::assertSame('integrated', $manifest['control_center_gnome_launchers']['status']);
        self::assertContains(
            'files/usr/share/applications/w4-control-center-home-updates.desktop',
            $manifest['control_center_gnome_launchers']['desktop_files']
        );

        self::assertFileExists(
            $liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'applications' . DIRECTORY_SEPARATOR . 'w4-control-center-home-updates.desktop'
        );
        self::assertFileExists(
            $liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers.json'
        );
        self::assertFileExists(
            $liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json'
        );
        self::assertFileExists(
            $liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'dconf' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'local.d' . DIRECTORY_SEPARATOR . '00-w4-home'
        );

        $updatesLauncher = file_get_contents(
            $liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'applications' . DIRECTORY_SEPARATOR . 'w4-control-center-home-updates.desktop'
        );
        self::assertNotFalse($updatesLauncher);
        self::assertStringContainsString('Exec=gnome-software --mode=updates', $updatesLauncher);

        $desktopDefaults = $this->decodeJsonFile(
            $liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json'
        );
        self::assertContains('w4-control-center-home-home.desktop', $desktopDefaults['favorites']);
        self::assertContains('w4-control-center-home-updates.desktop', $desktopDefaults['favorites']);
        self::assertNotContains('org.gnome.Software.desktop', $desktopDefaults['favorites']);

        $userDefaults = file_get_contents(
            $liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'dconf' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'local.d' . DIRECTORY_SEPARATOR . '00-w4-home'
        );
        self::assertNotFalse($userDefaults);
        self::assertStringContainsString("'w4-control-center-home-home.desktop'", $userDefaults);
        self::assertStringContainsString("'w4-control-center-home-updates.desktop'", $userDefaults);
        self::assertStringNotContainsString("'org.gnome.Software.desktop'", $userDefaults);

        $launcherManifest = $this->decodeJsonFile(
            $liveDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers.json'
        );
        self::assertSame('control-center-gnome-launchers', $launcherManifest['kind']);
        self::assertSame(4, count($launcherManifest['launchers']));
        self::assertSame(1, count($launcherManifest['pending_modules']));

        $composeScript = file_get_contents($liveDir . DIRECTORY_SEPARATOR . 'compose-live.sh');
        self::assertNotFalse($composeScript);
        self::assertStringContainsString('W4_DEFAULT_TARGET="${W4_DEFAULT_TARGET:-graphical.target}"', $composeScript);
        self::assertStringContainsString('mkdir -p "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants"', $composeScript);
        self::assertStringContainsString('ln -sfn ../w4-firstboot.service "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants/w4-firstboot.service"', $composeScript);
        self::assertStringContainsString('ln -sfn ../w4-live-prep.service "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants/w4-live-prep.service"', $composeScript);
        self::assertStringContainsString('project_system_overlay_runtime_state() {', $composeScript);
        self::assertStringContainsString('local wants_dir="${stage_systemd_dir}/${W4_DEFAULT_TARGET}.wants"', $composeScript);
        self::assertStringContainsString('cp -a "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants/w4-firstboot.service" "${wants_dir}/w4-firstboot.service"', $composeScript);
        self::assertStringContainsString('cp -a "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants/w4-live-prep.service" "${wants_dir}/w4-live-prep.service"', $composeScript);
        self::assertStringContainsString('project_system_overlay_runtime_state "${WORK_ROOTFS}" "${STAGE_OUTPUT_DIR}"', $composeScript);
        self::assertLessThan(
            strpos($composeScript, 'mkdir -p "${rootfs_dir}/etc/systemd/system/${W4_DEFAULT_TARGET}.wants"'),
            strpos($composeScript, 'W4_DEFAULT_TARGET="${W4_DEFAULT_TARGET:-graphical.target}"')
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
