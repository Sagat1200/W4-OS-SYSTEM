<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class InstallationScriptsIntegrationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal de pruebas');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testPrepareInstallationBundleGeneratesDerivedArtifactsInCustomBundleDir(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'prepared-home-bundle';
        $inventoryPath = $this->fixturePath('build/install-inventory/virtualbox-live-home.json');

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_installation_bundle.php'),
            [
                '--profile',
                'w4-os-home',
                '--disk-inventory',
                $inventoryPath,
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('/dev/sda', $payload['selected_disk']);
        self::assertSame($bundleDir, $payload['bundle_dir']);

        $derivedProfile = $this->decodeJsonFile($bundleDir . DIRECTORY_SEPARATOR . 'installation-profile.derived.json');
        self::assertSame(
            'VBOX_HARDDISK_VB85d286f4-23d1ffff',
            $derivedProfile['target']['disk_selector']['serial']
        );
        self::assertSame(
            'pci-0000:00:0d.0-ata-1.0',
            $derivedProfile['target']['disk_selector']['by_path']
        );

        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'verify-installation.sh');
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'CHECK_ONLY_PREPARATION.txt');
    }

    public function testGenerateInstallationExecutorEmitsCheckOnlyScriptAndUpdatesBundleManifest(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'executor-home-bundle';
        self::assertTrue(mkdir($bundleDir, 0777, true), 'No se pudo crear el bundle temporal');

        copy(
            $this->fixturePath('build/install/w4-os-home/installation-plan.json'),
            $bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json'
        );
        copy(
            $this->fixturePath('build/install/w4-os-home/installation-bundle.json'),
            $bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json'
        );

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/generate_installation_executor.php'),
            [
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);

        $applyScriptPath = $bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh';
        $applyScript = file_get_contents($applyScriptPath);
        self::assertNotFalse($applyScript);
        self::assertStringContainsString('Modo por defecto: check-only', $applyScript);
        self::assertStringContainsString('W4_INSTALL_EXECUTE=1', $applyScript);
        self::assertStringContainsString("SIZE_TOLERANCE_BYTES='1048576'", $applyScript);
        self::assertStringContainsString("TARGET_DISK='/dev/sda'", $applyScript);
        self::assertStringContainsString('mkdir -p "${TARGET_ROOT}/boot/efi"', $applyScript);
        self::assertStringContainsString('mount "${BOOT_PART}" "${TARGET_ROOT}/boot"', $applyScript);
        self::assertStringContainsString('mount "${ESP_PART}" "${TARGET_ROOT}/boot/efi"', $applyScript);

        $bundleManifest = $this->decodeJsonFile($bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json');
        self::assertContains('apply-installation.sh', $bundleManifest['generated_artifacts']);
        self::assertContains('verify-installation.sh', $bundleManifest['generated_artifacts']);
        self::assertContains('installation-executor.json', $bundleManifest['generated_artifacts']);
        self::assertFileExists($bundleDir . DIRECTORY_SEPARATOR . 'INSTALLATION_EXECUTOR_README.txt');
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
        self::assertNotFalse($raw, sprintf('No se pudo leer el archivo JSON %s', $path));

        return $this->decodeJson($raw);
    }

    private function fixturePath(string $relativePath): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
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
