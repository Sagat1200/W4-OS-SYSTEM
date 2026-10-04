<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class WslBuildRunnersCliTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-wsl-build-runners-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->clearEnvFixtures();
        $this->removeDirectory($this->tempDir);
    }

    public function testRunRootfsInWslReturnsCanonicalReadyPayload(): void
    {
        $bundlePath = $this->createBundle('rootfs-bundle', 'build-rootfs.sh');
        $scriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'build-rootfs.sh';
        $wslBundlePath = '/mnt/c/w4/rootfs-bundle';
        $wslScriptPath = '/mnt/c/w4/rootfs-bundle/build-rootfs.sh';
        $wslRootfsDir = '/var/tmp/w4-os-system/home/assembled-rootfs';

        $commandMap = [
            $this->wslpathCommand('Ubuntu-Fixture', $bundlePath) => $wslBundlePath,
            $this->wslpathCommand('Ubuntu-Fixture', $scriptPath) => $wslScriptPath,
            $this->dependencyCommand('Ubuntu-Fixture', 'debootstrap') => '/usr/sbin/debootstrap',
            $this->dependencyCommand('Ubuntu-Fixture', 'sudo') => '/usr/bin/sudo',
            $this->dependencyCommand('Ubuntu-Fixture', 'chroot') => '/usr/sbin/chroot',
        ];

        $this->setCommonWslFixtures($commandMap, true);

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_rootfs_in_wsl.php'),
            [
                '--bundle',
                $bundlePath,
                '--distribution',
                'Ubuntu-Fixture',
                '--rootfs-dir',
                $wslRootfsDir,
                '--check-only',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame('Ubuntu-Fixture', $payload['distribution']);
        self::assertSame($bundlePath, $payload['bundle_path_windows']);
        self::assertSame($wslBundlePath, $payload['bundle_path_wsl']);
        self::assertNull($payload['rootfs_dir_windows']);
        self::assertSame($wslRootfsDir, $payload['rootfs_dir_wsl']);
        self::assertSame('/usr/sbin/debootstrap', $payload['dependencies']['debootstrap']);
        self::assertSame([], $payload['missing_dependencies']);
        self::assertTrue($payload['sudo_non_interactive']);
        self::assertStringContainsString('sudo bash', $payload['run_command']);
    }

    public function testRunRootfsInWslReturnsCanonicalOkPayload(): void
    {
        $bundlePath = $this->createBundle('rootfs-bundle-ok', 'build-rootfs.sh');
        $scriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'build-rootfs.sh';
        $rootfsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'rootfs-output';
        $wslBundlePath = '/mnt/c/w4/rootfs-bundle-ok';
        $wslScriptPath = '/mnt/c/w4/rootfs-bundle-ok/build-rootfs.sh';
        $wslRootfsDir = '/mnt/c/w4/rootfs-output';

        $executionCommand = sprintf(
            'wsl -d %s -- bash -lc %s',
            'Ubuntu-Fixture',
            $this->quoteForWindowsCommand(sprintf(
                'chmod +x %s && %s %s',
                $this->quoteForBash($wslScriptPath),
                'sudo bash',
                implode(' ', [
                    $this->quoteForBash($wslScriptPath),
                    $this->quoteForBash($wslRootfsDir),
                ])
            ))
        );

        $commandMap = [
            $this->wslpathCommand('Ubuntu-Fixture', $bundlePath) => $wslBundlePath,
            $this->wslpathCommand('Ubuntu-Fixture', $scriptPath) => $wslScriptPath,
            $this->wslpathCommand('Ubuntu-Fixture', $rootfsDir) => $wslRootfsDir,
            $this->dependencyCommand('Ubuntu-Fixture', 'debootstrap') => '/usr/sbin/debootstrap',
            $this->dependencyCommand('Ubuntu-Fixture', 'sudo') => '/usr/bin/sudo',
            $this->dependencyCommand('Ubuntu-Fixture', 'chroot') => '/usr/sbin/chroot',
            $executionCommand => 'rootfs assembled',
        ];

        $this->setCommonWslFixtures($commandMap, true);

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_rootfs_in_wsl.php'),
            [
                '--bundle',
                $bundlePath,
                '--distribution',
                'Ubuntu-Fixture',
                '--rootfs-dir',
                $rootfsDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('Ubuntu-Fixture', $payload['distribution']);
        self::assertSame($bundlePath, $payload['bundle_path_windows']);
        self::assertSame($rootfsDir, $payload['rootfs_dir_windows']);
        self::assertSame($wslRootfsDir, $payload['rootfs_dir_wsl']);
        self::assertSame('rootfs assembled', $payload['execution_output']);
    }

    public function testRunOverlayInWslReturnsCanonicalReadyPayload(): void
    {
        $overlayPath = $this->createBundle('overlay-bundle', 'apply-overlay.sh');
        $scriptPath = $overlayPath . DIRECTORY_SEPARATOR . 'apply-overlay.sh';
        $rootfsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'overlay-rootfs';
        $wslOverlayPath = '/mnt/c/w4/overlay-bundle';
        $wslScriptPath = '/mnt/c/w4/overlay-bundle/apply-overlay.sh';
        $wslRootfsDir = '/mnt/c/w4/overlay-rootfs';

        $runCommand = sprintf(
            'wsl -d %s -u root -- bash -lc %s',
            'Ubuntu-Fixture',
            $this->quoteForWindowsCommand(sprintf(
                'test -d %s && chmod +x %s && bash %s %s',
                $this->quoteForBash($wslRootfsDir),
                $this->quoteForBash($wslScriptPath),
                $this->quoteForBash($wslScriptPath),
                $this->quoteForBash($wslRootfsDir)
            ))
        );

        $this->setCommonWslFixtures([
            $this->wslpathCommand('Ubuntu-Fixture', $rootfsDir) => $wslRootfsDir,
            $this->wslpathCommand('Ubuntu-Fixture', $overlayPath) => $wslOverlayPath,
            $this->wslpathCommand('Ubuntu-Fixture', $scriptPath) => $wslScriptPath,
        ]);

        self::assertTrue(mkdir($rootfsDir, 0777, true), 'No se pudo crear rootfs temporal');

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_overlay_in_wsl.php'),
            [
                '--overlay',
                $overlayPath,
                '--distribution',
                'Ubuntu-Fixture',
                '--rootfs-dir',
                $rootfsDir,
                '--check-only',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame($overlayPath, $payload['overlay_path_windows']);
        self::assertSame($wslOverlayPath, $payload['overlay_path_wsl']);
        self::assertSame($rootfsDir, $payload['rootfs_dir_windows']);
        self::assertSame($wslRootfsDir, $payload['rootfs_dir_wsl']);
        self::assertSame($runCommand, $payload['run_command']);
    }

    public function testRunOverlayInWslReturnsCanonicalOkPayload(): void
    {
        $overlayPath = $this->createBundle('overlay-bundle-ok', 'apply-overlay.sh');
        $scriptPath = $overlayPath . DIRECTORY_SEPARATOR . 'apply-overlay.sh';
        $rootfsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'overlay-rootfs-ok';
        $wslOverlayPath = '/mnt/c/w4/overlay-bundle-ok';
        $wslScriptPath = '/mnt/c/w4/overlay-bundle-ok/apply-overlay.sh';
        $wslRootfsDir = '/mnt/c/w4/overlay-rootfs-ok';

        self::assertTrue(mkdir($rootfsDir, 0777, true), 'No se pudo crear rootfs temporal');

        $runCommand = sprintf(
            'wsl -d %s -u root -- bash -lc %s',
            $this->quoteWslDistribution('Ubuntu-Fixture'),
            $this->quoteForWindowsCommand(sprintf(
                'test -d %s && chmod +x %s && bash %s %s',
                $this->quoteForBash($wslRootfsDir),
                $this->quoteForBash($wslScriptPath),
                $this->quoteForBash($wslScriptPath),
                $this->quoteForBash($wslRootfsDir)
            ))
        );

        $this->setCommonWslFixtures([
            $this->wslpathCommand('Ubuntu-Fixture', $rootfsDir) => $wslRootfsDir,
            $this->wslpathCommand('Ubuntu-Fixture', $overlayPath) => $wslOverlayPath,
            $this->wslpathCommand('Ubuntu-Fixture', $scriptPath) => $wslScriptPath,
            $runCommand => 'overlay applied',
        ]);

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_overlay_in_wsl.php'),
            [
                '--overlay',
                $overlayPath,
                '--distribution',
                'Ubuntu-Fixture',
                '--rootfs-dir',
                $rootfsDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($overlayPath, $payload['overlay_path_windows']);
        self::assertSame($rootfsDir, $payload['rootfs_dir_windows']);
        self::assertSame($wslRootfsDir, $payload['rootfs_dir_wsl']);
        self::assertSame('overlay applied', $payload['execution_output']);
    }

    public function testRunLiveBundleInWslReturnsCanonicalReadyPayload(): void
    {
        $bundlePath = $this->createBundle('live-bundle', 'compose-live.sh');
        $scriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'compose-live.sh';
        $rootfsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'live-rootfs';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'live-output';
        $wslBundlePath = '/mnt/c/w4/live-bundle';
        $wslScriptPath = '/mnt/c/w4/live-bundle/compose-live.sh';
        $wslRootfsDir = '/mnt/c/w4/live-rootfs';
        $wslOutputDir = '/mnt/c/w4/live-output';

        self::assertTrue(mkdir($rootfsDir, 0777, true), 'No se pudo crear rootfs temporal');

        $this->setCommonWslFixtures([
            $this->wslpathCommand('Ubuntu-Fixture', $rootfsDir) => $wslRootfsDir,
            $this->wslpathCommand('Ubuntu-Fixture', $bundlePath) => $wslBundlePath,
            $this->wslpathCommand('Ubuntu-Fixture', $scriptPath) => $wslScriptPath,
            $this->wslpathCommand('Ubuntu-Fixture', $outputDir) => $wslOutputDir,
        ]);

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_live_bundle_in_wsl.php'),
            [
                '--bundle',
                $bundlePath,
                '--distribution',
                'Ubuntu-Fixture',
                '--rootfs-dir',
                $rootfsDir,
                '--output-dir',
                $outputDir,
                '--check-only',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame($bundlePath, $payload['bundle_path_windows']);
        self::assertSame($wslBundlePath, $payload['bundle_path_wsl']);
        self::assertSame($rootfsDir, $payload['rootfs_dir_windows']);
        self::assertSame($wslRootfsDir, $payload['rootfs_dir_wsl']);
        self::assertSame($outputDir, $payload['output_dir_windows']);
        self::assertSame($wslOutputDir, $payload['output_dir_wsl']);
    }

    public function testRunLiveBundleInWslReturnsCanonicalOkPayload(): void
    {
        $bundlePath = $this->createBundle('live-bundle-ok', 'compose-live.sh');
        $scriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'compose-live.sh';
        $rootfsDir = $this->tempDir . DIRECTORY_SEPARATOR . 'live-rootfs-ok';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'live-output-ok';
        $wslBundlePath = '/mnt/c/w4/live-bundle-ok';
        $wslScriptPath = '/mnt/c/w4/live-bundle-ok/compose-live.sh';
        $wslRootfsDir = '/mnt/c/w4/live-rootfs-ok';
        $wslOutputDir = '/mnt/c/w4/live-output-ok';

        self::assertTrue(mkdir($rootfsDir, 0777, true), 'No se pudo crear rootfs temporal');

        $runCommand = sprintf(
            'wsl -d %s -u root -- bash -lc %s',
            $this->quoteWslDistribution('Ubuntu-Fixture'),
            $this->quoteForWindowsCommand(sprintf(
                'test -d %s && mkdir -p %s && chmod +x %s && bash %s %s %s',
                $this->quoteForBash($wslRootfsDir),
                $this->quoteForBash($wslOutputDir),
                $this->quoteForBash($wslScriptPath),
                $this->quoteForBash($wslScriptPath),
                $this->quoteForBash($wslRootfsDir),
                $this->quoteForBash($wslOutputDir)
            ))
        );

        $this->setCommonWslFixtures([
            $this->wslpathCommand('Ubuntu-Fixture', $rootfsDir) => $wslRootfsDir,
            $this->wslpathCommand('Ubuntu-Fixture', $bundlePath) => $wslBundlePath,
            $this->wslpathCommand('Ubuntu-Fixture', $scriptPath) => $wslScriptPath,
            $this->wslpathCommand('Ubuntu-Fixture', $outputDir) => $wslOutputDir,
            $runCommand => 'live bundle composed',
        ]);

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_live_bundle_in_wsl.php'),
            [
                '--bundle',
                $bundlePath,
                '--distribution',
                'Ubuntu-Fixture',
                '--rootfs-dir',
                $rootfsDir,
                '--output-dir',
                $outputDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($bundlePath, $payload['bundle_path_windows']);
        self::assertSame($rootfsDir, $payload['rootfs_dir_windows']);
        self::assertSame($outputDir, $payload['output_dir_windows']);
        self::assertSame('live bundle composed', $payload['execution_output']);
    }

    public function testRunIsoBundleInWslReturnsCanonicalReadyPayload(): void
    {
        $bundlePath = $this->createBundle('iso-bundle', 'compose-iso.sh');
        $scriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'compose-iso.sh';
        $imageRootDir = $this->tempDir . DIRECTORY_SEPARATOR . 'image-root';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'iso-output';
        $wslBundlePath = '/mnt/c/w4/iso-bundle';
        $wslScriptPath = '/mnt/c/w4/iso-bundle/compose-iso.sh';
        $wslImageRootDir = '/mnt/c/w4/image-root';
        $wslOutputDir = '/mnt/c/w4/iso-output';

        self::assertTrue(mkdir($imageRootDir, 0777, true), 'No se pudo crear image-root temporal');

        $this->setCommonWslFixtures([
            $this->wslpathCommand('Ubuntu-Fixture', $imageRootDir) => $wslImageRootDir,
            $this->wslpathCommand('Ubuntu-Fixture', $bundlePath) => $wslBundlePath,
            $this->wslpathCommand('Ubuntu-Fixture', $scriptPath) => $wslScriptPath,
            $this->wslpathCommand('Ubuntu-Fixture', $outputDir) => $wslOutputDir,
        ]);

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_iso_bundle_in_wsl.php'),
            [
                '--bundle',
                $bundlePath,
                '--distribution',
                'Ubuntu-Fixture',
                '--image-root',
                $imageRootDir,
                '--output-dir',
                $outputDir,
                '--check-only',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame($bundlePath, $payload['bundle_path_windows']);
        self::assertSame($wslBundlePath, $payload['bundle_path_wsl']);
        self::assertSame($imageRootDir, $payload['image_root_windows']);
        self::assertSame($wslImageRootDir, $payload['image_root_wsl']);
        self::assertSame($outputDir, $payload['output_dir_windows']);
        self::assertSame($wslOutputDir, $payload['output_dir_wsl']);
    }

    public function testRunIsoBundleInWslReturnsCanonicalOkPayload(): void
    {
        $bundlePath = $this->createBundle('iso-bundle-ok', 'compose-iso.sh');
        $scriptPath = $bundlePath . DIRECTORY_SEPARATOR . 'compose-iso.sh';
        $imageRootDir = $this->tempDir . DIRECTORY_SEPARATOR . 'image-root-ok';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'iso-output-ok';
        $wslBundlePath = '/mnt/c/w4/iso-bundle-ok';
        $wslScriptPath = '/mnt/c/w4/iso-bundle-ok/compose-iso.sh';
        $wslImageRootDir = '/mnt/c/w4/image-root-ok';
        $wslOutputDir = '/mnt/c/w4/iso-output-ok';

        self::assertTrue(mkdir($imageRootDir, 0777, true), 'No se pudo crear image-root temporal');

        $runCommand = sprintf(
            'wsl -d %s -u root -- bash -lc %s',
            $this->quoteWslDistribution('Ubuntu-Fixture'),
            $this->quoteForWindowsCommand(sprintf(
                'test -d %s && mkdir -p %s && chmod +x %s && bash %s %s %s',
                $this->quoteForBash($wslImageRootDir),
                $this->quoteForBash($wslOutputDir),
                $this->quoteForBash($wslScriptPath),
                $this->quoteForBash($wslScriptPath),
                $this->quoteForBash($wslImageRootDir),
                $this->quoteForBash($wslOutputDir)
            ))
        );

        $this->setCommonWslFixtures([
            $this->wslpathCommand('Ubuntu-Fixture', $imageRootDir) => $wslImageRootDir,
            $this->wslpathCommand('Ubuntu-Fixture', $bundlePath) => $wslBundlePath,
            $this->wslpathCommand('Ubuntu-Fixture', $scriptPath) => $wslScriptPath,
            $this->wslpathCommand('Ubuntu-Fixture', $outputDir) => $wslOutputDir,
            $runCommand => 'iso composed',
        ]);

        $result = $this->runPhpScript(
            $this->scriptPath('scripts/run_iso_bundle_in_wsl.php'),
            [
                '--bundle',
                $bundlePath,
                '--distribution',
                'Ubuntu-Fixture',
                '--image-root',
                $imageRootDir,
                '--output-dir',
                $outputDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);
        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($bundlePath, $payload['bundle_path_windows']);
        self::assertSame($imageRootDir, $payload['image_root_windows']);
        self::assertSame($outputDir, $payload['output_dir_windows']);
        self::assertSame('iso composed', $payload['execution_output']);
    }

    private function setCommonWslFixtures(array $commandMap, ?bool $canUseSudo = null): void
    {
        putenv('W4_WSL_RUNNER_DISTROS_JSON=' . json_encode(['Ubuntu-Fixture'], JSON_THROW_ON_ERROR));
        putenv('W4_WSL_RUNNER_COMMAND_MAP_JSON=' . json_encode($commandMap, JSON_THROW_ON_ERROR));

        if ($canUseSudo !== null) {
            putenv('W4_WSL_RUNNER_CAN_USE_SUDO=' . ($canUseSudo ? 'true' : 'false'));
        }
    }

    private function clearEnvFixtures(): void
    {
        foreach ([
            'W4_WSL_RUNNER_DISTROS_JSON',
            'W4_WSL_RUNNER_COMMAND_MAP_JSON',
            'W4_WSL_RUNNER_CAN_USE_SUDO',
        ] as $variable) {
            putenv($variable);
        }
    }

    private function createBundle(string $directoryName, string $scriptName): string
    {
        $path = $this->tempDir . DIRECTORY_SEPARATOR . $directoryName;
        self::assertTrue(mkdir($path, 0777, true), sprintf('No se pudo crear %s', $path));
        self::assertNotFalse(file_put_contents($path . DIRECTORY_SEPARATOR . $scriptName, "#!/usr/bin/env bash\n"), 'No se pudo escribir script');

        return $path;
    }

    private function scriptPath(string $relativePath): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    private function wslpathCommand(string $distribution, string $path): string
    {
        return sprintf(
            'wsl -d %s -- wslpath -a %s',
            $this->quoteWslDistribution($distribution),
            $this->quoteForWindowsCommand($path)
        );
    }

    private function dependencyCommand(string $distribution, string $command): string
    {
        return sprintf(
            'wsl -d %s -- bash -lc %s',
            $this->quoteWslDistribution($distribution),
            $this->quoteForWindowsCommand(sprintf('command -v %s || true', $command))
        );
    }

    private function quoteForWindowsCommand(string $value): string
    {
        return '"' . str_replace('"', '\"', $value) . '"';
    }

    private function quoteForBash(string $value): string
    {
        return "'" . str_replace("'", "'\"'\"'", $value) . "'";
    }

    private function quoteWslDistribution(string $value): string
    {
        if (preg_match('/^[A-Za-z0-9._-]+$/', $value) === 1) {
            return $value;
        }

        return $this->quoteForWindowsCommand($value);
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
