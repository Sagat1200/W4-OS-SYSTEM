<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class InstallationRuntimePreparationTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-runtime-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal de runtime');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testPrepareInstallationRuntimeAutoDetectsSquashfsAndGeneratesEphemeralSecrets(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home';
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
            $this->fixturePath('scripts/prepare_installation_runtime.php'),
            [
                '--bundle-dir',
                $bundleDir,
                '--generate-secrets',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame($bundleDir, $payload['bundle_dir']);
        self::assertSame('/dev/sda', $payload['selected_disk']);
        self::assertTrue($payload['source']['auto_detected']);
        self::assertSame('squashfs', $payload['source']['type']);
        self::assertStringEndsWith('build\\live-output\\w4-os-home\\image-root\\live\\filesystem.squashfs', $payload['source']['path']);
        self::assertTrue($payload['generated_secrets']['enabled']);
        self::assertCount(2, $payload['generated_secrets']['files']);

        $runtimeDir = $bundleDir . DIRECTORY_SEPARATOR . 'runtime';
        $installEnv = file_get_contents($runtimeDir . DIRECTORY_SEPARATOR . 'install.env');
        self::assertNotFalse($installEnv);
        self::assertStringContainsString('export W4_INSTALL_EXECUTE=1', $installEnv);
        self::assertStringContainsString('export W4_INSTALL_SOURCE_SQUASHFS=', $installEnv);
        self::assertStringContainsString('export W4_DISK_PASSPHRASE_FILE=', $installEnv);
        self::assertStringContainsString('export W4_LOCAL_USER_PASSWORD_FILE=', $installEnv);
        self::assertStringContainsString('export W4_EDITION_POLICY_FILE=', $installEnv);
        self::assertStringContainsString('export W4_DEFAULT_TARGET="graphical.target"', $installEnv);
        self::assertStringContainsString('export W4_HOSTNAME_PREFIX="w4-home"', $installEnv);
        self::assertStringContainsString('export W4_FIREWALL_INCOMING="deny"', $installEnv);
        self::assertStringContainsString('export W4_FIREWALL_OUTGOING="allow"', $installEnv);

        self::assertFileExists($runtimeDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt');
        self::assertFileExists($runtimeDir . DIRECTORY_SEPARATOR . 'local-user-password.txt');
        self::assertFileExists($runtimeDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt.template');
        self::assertFileExists($runtimeDir . DIRECTORY_SEPARATOR . 'local-user-password.txt.template');
        self::assertFileExists($runtimeDir . DIRECTORY_SEPARATOR . 'run-check-only.sh');
        self::assertFileExists($runtimeDir . DIRECTORY_SEPARATOR . 'run-installation.sh');
        self::assertFileExists($runtimeDir . DIRECTORY_SEPARATOR . 'RUNTIME_PREPARATION.txt');
        self::assertFileExists($runtimeDir . DIRECTORY_SEPARATOR . 'installation-runtime.json');

        $installRunner = file_get_contents($runtimeDir . DIRECTORY_SEPARATOR . 'run-installation.sh');
        self::assertNotFalse($installRunner);
        self::assertStringContainsString('ENV_FILE="${SCRIPT_DIR}/install.env"', $installRunner);
        self::assertStringNotContainsString('\${SCRIPT_DIR}/install.env', $installRunner);

        $runtimeManifest = $this->decodeJsonFile($runtimeDir . DIRECTORY_SEPARATOR . 'installation-runtime.json');
        self::assertSame('graphical.target', $runtimeManifest['edition_policy']['default_target']);
        self::assertSame('w4-home', $runtimeManifest['edition_policy']['hostname_prefix']);
        self::assertFalse($runtimeManifest['edition_policy']['ssh_enabled']);
        self::assertSame('ufw', $runtimeManifest['edition_policy']['firewall_backend']);

        $bundleManifest = $this->decodeJsonFile($bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json');
        self::assertContains('runtime/install.env', $bundleManifest['generated_artifacts']);
        self::assertContains('runtime/run-installation.sh', $bundleManifest['generated_artifacts']);
        self::assertContains('runtime/installation-runtime.json', $bundleManifest['generated_artifacts']);
    }

    public function testPrepareInstallationRuntimeRejectsScalarBundleJson(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-invalid-runtime';
        self::assertTrue(mkdir($bundleDir, 0777, true), 'No se pudo crear el bundle temporal');

        copy(
            $this->fixturePath('build/install/w4-os-home/installation-plan.json'),
            $bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json'
        );
        self::assertNotFalse(file_put_contents($bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json', '"valor-escalar"' . PHP_EOL));

        $result = $this->runPhpScript(
            $this->fixturePath('scripts/prepare_installation_runtime.php'),
            [
                '--bundle-dir',
                $bundleDir,
            ]
        );

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('la raiz debe ser un objeto o arreglo JSON', $result['stderr']);
    }

    public function testPrepareInstallationRuntimeAppliesCredentialPolicies(): void
    {
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-home-policy-runtime';
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
            $this->fixturePath('scripts/prepare_installation_runtime.php'),
            [
                '--bundle-dir',
                $bundleDir,
                '--generate-secrets',
                '--passphrase-policy',
                'min_length=20,max_length=20,ascii_only=true,require_digit=true,require_alpha=true',
                '--password-policy',
                'min_length=14,max_length=14,ascii_only=true,require_digit=true,require_alpha=true',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame(20, $payload['generated_secrets']['policies']['passphrase']['min_length']);
        self::assertSame(14, $payload['generated_secrets']['policies']['password']['min_length']);

        $runtimeDir = $bundleDir . DIRECTORY_SEPARATOR . 'runtime';
        $diskPassphraseRaw = (string) file_get_contents($runtimeDir . DIRECTORY_SEPARATOR . 'disk-passphrase.txt');
        $userPasswordRaw = (string) file_get_contents($runtimeDir . DIRECTORY_SEPARATOR . 'local-user-password.txt');
        self::assertStringNotContainsString("\r", $diskPassphraseRaw);
        self::assertStringNotContainsString("\n", $diskPassphraseRaw);
        self::assertStringNotContainsString("\r", $userPasswordRaw);

        $diskPassphrase = trim($diskPassphraseRaw);
        $userPassword = trim($userPasswordRaw);

        self::assertSame(20, strlen($diskPassphrase));
        self::assertSame(14, strlen($userPassword));
        self::assertMatchesRegularExpression('/^[A-Za-z0-9!@#%^&*+=:.]+$/', $diskPassphrase);
        self::assertMatchesRegularExpression('/^[A-Za-z0-9!@#%^&*+=:.]+$/', $userPassword);
        self::assertMatchesRegularExpression('/[0-9]/', $diskPassphrase);
        self::assertMatchesRegularExpression('/[A-Za-z]/', $diskPassphrase);
        self::assertMatchesRegularExpression('/[0-9]/', $userPassword);
        self::assertMatchesRegularExpression('/[A-Za-z]/', $userPassword);
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
