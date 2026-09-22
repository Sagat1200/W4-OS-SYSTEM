<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class InstallationTransferPreparationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-transfer-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal de transfer');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testPrepareInstallationTransferRewritesRuntimeForPackagedSquashfsWithoutSecrets(): void
    {
        $bundleDir = $this->createBundleFixture('squashfs-bundle');
        $sourceSquashfs = $this->tempDir . DIRECTORY_SEPARATOR . 'filesystem.squashfs';
        $transferDir = $this->tempDir . DIRECTORY_SEPARATOR . 'transfer-squashfs';

        self::assertNotFalse(file_put_contents($sourceSquashfs, 'fake-squashfs'));

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_installation_transfer.php'),
            [
                '--bundle-dir',
                $bundleDir,
                '--transfer-dir',
                $transferDir,
                '--source-squashfs',
                $sourceSquashfs,
                '--no-secrets',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertFalse($payload['secrets']['requested']);
        self::assertFalse($payload['secrets']['included']);
        self::assertSame([], $payload['secrets']['files']);
        self::assertSame('source/filesystem.squashfs', $payload['source']['transfer_relative_path']);

        self::assertFileExists($transferDir . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'filesystem.squashfs');

        $installEnv = file_get_contents($transferDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'install.env');
        self::assertNotFalse($installEnv);
        self::assertStringContainsString('export W4_INSTALL_SOURCE_SQUASHFS="../source/filesystem.squashfs"', $installEnv);
        self::assertStringContainsString('# export W4_DISK_PASSPHRASE_FILE="disk-passphrase.txt"', $installEnv);
        self::assertStringContainsString('# export W4_LOCAL_USER_PASSWORD_FILE="local-user-password.txt"', $installEnv);

        $installRunner = file_get_contents($transferDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'run-installation.sh');
        self::assertNotFalse($installRunner);
        self::assertStringContainsString('ENV_FILE="${SCRIPT_DIR}/install.env"', $installRunner);
        self::assertStringNotContainsString('\${SCRIPT_DIR}/install.env', $installRunner);

        $runtimeManifest = $this->decodeJsonFile($transferDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'installation-runtime.json');
        self::assertSame('squashfs', $runtimeManifest['source']['type']);
        self::assertSame('../source/filesystem.squashfs', $runtimeManifest['source']['path']);
        self::assertTrue($runtimeManifest['source']['packaged']);
        self::assertFalse($runtimeManifest['generated_secrets']['enabled']);
        self::assertSame([], $runtimeManifest['generated_secrets']['files']);

        $transferManifest = $this->decodeJsonFile($transferDir . DIRECTORY_SEPARATOR . 'transfer-manifest.json');
        self::assertSame('cd ~/w4-transfer/runtime && bash run-check-only.sh', $transferManifest['vm_commands']['check_only']);
        self::assertFalse($transferManifest['secrets']['requested']);
        self::assertFalse($transferManifest['secrets']['included']);
    }

    public function testPrepareInstallationTransferCopiesRootfsAndIncludedSecrets(): void
    {
        $bundleDir = $this->createBundleFixture('rootfs-bundle', true);
        $sourceRootfs = $this->tempDir . DIRECTORY_SEPARATOR . 'source-rootfs';
        $transferDir = $this->tempDir . DIRECTORY_SEPARATOR . 'transfer-rootfs';

        self::assertTrue(mkdir($sourceRootfs . DIRECTORY_SEPARATOR . 'etc', 0777, true), 'No se pudo crear rootfs temporal');
        self::assertNotFalse(file_put_contents($sourceRootfs . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'hostname', "w4-home\n"));

        self::assertTrue(mkdir($transferDir . DIRECTORY_SEPARATOR . 'source', 0777, true), 'No se pudo crear source previo');
        self::assertTrue(mkdir($transferDir . DIRECTORY_SEPARATOR . 'runtime', 0777, true), 'No se pudo crear runtime previo');
        self::assertNotFalse(file_put_contents($transferDir . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'filesystem.squashfs', 'stale'));
        self::assertNotFalse(file_put_contents($transferDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'disk-passphrase.txt', 'stale-secret'));

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_installation_transfer.php'),
            [
                '--bundle-dir',
                $bundleDir,
                '--transfer-dir',
                $transferDir,
                '--source-rootfs',
                $sourceRootfs,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        self::assertFileDoesNotExist($transferDir . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'filesystem.squashfs');
        self::assertFileExists($transferDir . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'rootfs' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'hostname');
        self::assertSame(
            "w4-home\n",
            file_get_contents($transferDir . DIRECTORY_SEPARATOR . 'source' . DIRECTORY_SEPARATOR . 'rootfs' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'hostname')
        );

        $installEnv = file_get_contents($transferDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'install.env');
        self::assertNotFalse($installEnv);
        self::assertStringContainsString('export W4_INSTALL_SOURCE_ROOTFS="../source/rootfs"', $installEnv);
        self::assertStringContainsString('export W4_DISK_PASSPHRASE_FILE="disk-passphrase.txt"', $installEnv);
        self::assertStringContainsString('export W4_LOCAL_USER_PASSWORD_FILE="local-user-password.txt"', $installEnv);

        $installRunner = file_get_contents($transferDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'run-installation.sh');
        self::assertNotFalse($installRunner);
        self::assertStringContainsString('ENV_FILE="${SCRIPT_DIR}/install.env"', $installRunner);
        self::assertStringNotContainsString('\${SCRIPT_DIR}/install.env', $installRunner);

        self::assertFileExists($transferDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'disk-passphrase.txt');
        self::assertFileExists($transferDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'local-user-password.txt');

        $payload = $this->decodeJson($result['stdout']);
        self::assertTrue($payload['secrets']['requested']);
        self::assertTrue($payload['secrets']['included']);
        self::assertSame(
            ['runtime/disk-passphrase.txt', 'runtime/local-user-password.txt'],
            $payload['secrets']['files']
        );
    }

    public function testPrepareInstallationTransferRejectsScalarRuntimeManifest(): void
    {
        $bundleDir = $this->createBundleFixture('invalid-runtime-manifest');
        $transferDir = $this->tempDir . DIRECTORY_SEPARATOR . 'transfer-invalid-runtime';

        self::assertNotFalse(file_put_contents(
            $bundleDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'installation-runtime.json',
            '"valor-escalar"' . PHP_EOL
        ));

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_installation_transfer.php'),
            [
                '--bundle-dir',
                $bundleDir,
                '--transfer-dir',
                $transferDir,
            ]
        );

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('la raiz debe ser un objeto o arreglo JSON', $result['stderr']);
    }

    private function createBundleFixture(string $bundleName, bool $includeSecrets = false): string
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . $bundleName;
        $runtimeDir = $bundleDir . DIRECTORY_SEPARATOR . 'runtime';

        self::assertTrue(mkdir($runtimeDir, 0777, true), 'No se pudo crear el bundle temporal');

        foreach ([
            'installation-plan.json',
            'installation-bundle.json',
            'apply-installation.sh',
            'verify-installation.sh',
        ] as $fileName) {
            copy(
                $this->fixturePath('build/install/w4-os-home/' . $fileName),
                $bundleDir . DIRECTORY_SEPARATOR . $fileName
            );
        }

        foreach ([
            'run-check-only.sh',
            'run-installation.sh',
            'disk-passphrase.txt.template',
            'local-user-password.txt.template',
        ] as $fileName) {
            copy(
                $this->fixturePath('build/install/w4-os-home/runtime/' . $fileName),
                $runtimeDir . DIRECTORY_SEPARATOR . $fileName
            );
        }

        if ($includeSecrets) {
            self::assertNotFalse(file_put_contents($runtimeDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt', "transfer-passphrase\n"));
            self::assertNotFalse(file_put_contents($runtimeDir . DIRECTORY_SEPARATOR . 'local-user-password.txt', "transfer-password\n"));
        }

        return $bundleDir;
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
        self::assertNotFalse($raw, sprintf('No se pudo leer el JSON %s', $path));

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
