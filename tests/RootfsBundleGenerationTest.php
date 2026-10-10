<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class RootfsBundleGenerationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-rootfs-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateRootfsBundlePublishesCanonicalMetadata(): void
    {
        $buildInputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server.build-input.json';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server-rootfs';

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

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_rootfs_bundle.php',
            [
                '--input',
                $buildInputPath,
                '--output',
                $outputDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('w4-os-server', $payload['profile_id']);
        self::assertSame($outputDir, $payload['output_directory']);
        self::assertSame([
            'build-rootfs.sh',
            'directories.list',
            'packages.recommended.list',
            'packages.required.list',
            'repositories.list',
            'rootfs-manifest.json',
        ], $payload['generated_files']);

        $manifest = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'rootfs-manifest.json');
        self::assertSame('rootfs-bundle', $manifest['kind']);
        self::assertSame('w4-os-server', $manifest['profile_id']);
        self::assertSame([
            'build-rootfs.sh',
            'directories.list',
            'packages.recommended.list',
            'packages.required.list',
            'repositories.list',
        ], $manifest['generated_files']);
        self::assertContains('/etc', $manifest['rootfs_directories']);
        self::assertContains('/etc/machine-id', $manifest['identity_cleanup']);
    }

    public function testGenerateHomeRootfsBundleIncludesGnomeAndGdmBaseline(): void
    {
        $buildInputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home.build-input.json';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-rootfs';

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

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_rootfs_bundle.php',
            [
                '--input',
                $buildInputPath,
                '--output',
                $outputDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $manifest = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'rootfs-manifest.json');
        self::assertContains('w4-desktop-gnome-meta', $manifest['meta_packages']['required']);
        self::assertContains('at-spi2-core', $manifest['packages']['required']);
        self::assertContains('evince', $manifest['packages']['required']);
        self::assertContains('gdm3', $manifest['packages']['required']);
        self::assertContains('gnome-accessibility-themes', $manifest['packages']['required']);
        self::assertContains('gnome-session', $manifest['packages']['required']);
        self::assertContains('gnome-shell', $manifest['packages']['required']);
        self::assertContains('gnome-software', $manifest['packages']['required']);
        self::assertContains('libatk-adaptor', $manifest['packages']['required']);
        self::assertContains('nautilus', $manifest['packages']['required']);
        self::assertContains('orca', $manifest['packages']['required']);
        self::assertContains('speech-dispatcher', $manifest['packages']['required']);
        self::assertContains('vlc', $manifest['packages']['required']);
        self::assertContains('xdg-desktop-portal-gnome', $manifest['packages']['required']);

        $requiredPackages = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'packages.required.list');
        self::assertNotFalse($requiredPackages);
        self::assertStringContainsString("at-spi2-core\n", $requiredPackages);
        self::assertStringContainsString("evince\n", $requiredPackages);
        self::assertStringContainsString("gdm3\n", $requiredPackages);
        self::assertStringContainsString("gnome-accessibility-themes\n", $requiredPackages);
        self::assertStringContainsString("gnome-shell\n", $requiredPackages);
        self::assertStringContainsString("gnome-session\n", $requiredPackages);
        self::assertStringContainsString("libatk-adaptor\n", $requiredPackages);
        self::assertStringContainsString("orca\n", $requiredPackages);
        self::assertStringContainsString("speech-dispatcher\n", $requiredPackages);
        self::assertStringContainsString("vlc\n", $requiredPackages);

        $buildScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'build-rootfs.sh');
        self::assertNotFalse($buildScript);
        self::assertStringContainsString('--include=', $buildScript);
        self::assertStringContainsString('at-spi2-core', $buildScript);
        self::assertStringContainsString('evince', $buildScript);
        self::assertStringContainsString('gdm3', $buildScript);
        self::assertStringContainsString('gnome-accessibility-themes', $buildScript);
        self::assertStringContainsString('gnome-shell', $buildScript);
        self::assertStringContainsString('gnome-session', $buildScript);
        self::assertStringContainsString('libatk-adaptor', $buildScript);
        self::assertStringContainsString('orca', $buildScript);
        self::assertStringContainsString('speech-dispatcher', $buildScript);
        self::assertStringContainsString('vlc', $buildScript);
        self::assertStringContainsString('bootstrap_with_mmdebstrap()', $buildScript);
        self::assertStringContainsString('bootstrap_with_debootstrap()', $buildScript);
        self::assertStringContainsString('mmdebstrap fallo con codigo', $buildScript);
        self::assertStringContainsString('Bootstrap base Debian con debootstrap (fallback)', $buildScript);
        self::assertStringContainsString('reset_rootfs_dir()', $buildScript);
    }

    public function testGenerateBusinessRootfsBundleIncludesKdeAndSddmBaseline(): void
    {
        $buildInputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-business.build-input.json';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-business-rootfs';

        $buildInput = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_build_input.php',
            [
                '--profile',
                'w4-os-business',
                '--output',
                $buildInputPath,
            ]
        );
        self::assertSame(0, $buildInput['exitCode'], $buildInput['stderr']);

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_rootfs_bundle.php',
            [
                '--input',
                $buildInputPath,
                '--output',
                $outputDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $manifest = $this->decodeJsonFile($outputDir . DIRECTORY_SEPARATOR . 'rootfs-manifest.json');
        self::assertContains('w4-desktop-kde-meta', $manifest['meta_packages']['required']);
        self::assertContains('desktop-defaults', $manifest['features']);
        self::assertContains('kde-sddm-default-route', $manifest['features']);
        self::assertContains('dolphin', $manifest['packages']['required']);
        self::assertContains('konsole', $manifest['packages']['required']);
        self::assertContains('plasma-desktop', $manifest['packages']['required']);
        self::assertContains('plasma-nm', $manifest['packages']['required']);
        self::assertContains('plasma-workspace', $manifest['packages']['required']);
        self::assertContains('sddm', $manifest['packages']['required']);
        self::assertContains('systemsettings', $manifest['packages']['required']);
        self::assertContains('xdg-desktop-portal-kde', $manifest['packages']['required']);
        self::assertNotContains('gdm3', $manifest['packages']['required']);
        self::assertNotContains('gnome-shell', $manifest['packages']['required']);

        $requiredPackages = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'packages.required.list');
        self::assertNotFalse($requiredPackages);
        self::assertStringContainsString("dolphin\n", $requiredPackages);
        self::assertStringContainsString("konsole\n", $requiredPackages);
        self::assertStringContainsString("plasma-desktop\n", $requiredPackages);
        self::assertStringContainsString("plasma-workspace\n", $requiredPackages);
        self::assertStringContainsString("sddm\n", $requiredPackages);
        self::assertStringContainsString("systemsettings\n", $requiredPackages);
        self::assertStringContainsString("xdg-desktop-portal-kde\n", $requiredPackages);

        $buildScript = file_get_contents($outputDir . DIRECTORY_SEPARATOR . 'build-rootfs.sh');
        self::assertNotFalse($buildScript);
        self::assertStringContainsString('dolphin', $buildScript);
        self::assertStringContainsString('konsole', $buildScript);
        self::assertStringContainsString('plasma-desktop', $buildScript);
        self::assertStringContainsString('plasma-workspace', $buildScript);
        self::assertStringContainsString('sddm', $buildScript);
        self::assertStringContainsString('systemsettings', $buildScript);
        self::assertStringContainsString('xdg-desktop-portal-kde', $buildScript);
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
