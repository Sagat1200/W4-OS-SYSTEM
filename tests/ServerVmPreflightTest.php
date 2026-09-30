<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class ServerVmPreflightTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-server-vm-preflight-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal de pruebas');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testPreflightAcceptsReadyServerArtifactsWithoutHostHypervisorCheck(): void
    {
        $fixture = $this->createArtifactFixture([
            'bash 5.2',
            'openssh-server 1:10.0p1-7',
            'php-cli 2:8.4+96',
        ]);

        $result = $this->runPhpScript($this->fixturePath('scripts/preflight_server_vm_validation.php'), [
            '--profile',
            'w4-os-server',
            '--hypervisor',
            'none',
            '--expected-sha256',
            $fixture['sha256'],
            '--iso-path',
            $fixture['iso'],
            '--manifest-path',
            $fixture['manifest'],
            '--checksum-path',
            $fixture['checksum'],
            '--summary-path',
            $fixture['summary'],
            '--bundle-dir',
            $fixture['bundle'],
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ready', $payload['status']);
        self::assertSame('w4-os-server', $payload['profile']);
        self::assertSame($fixture['sha256'], $payload['iso_sha256']);
        self::assertTrue($payload['checks']['iso_sha256_matches']);
        self::assertTrue($payload['checks']['manifest_headless']);
        self::assertTrue($payload['checks']['hypervisor_ready']);
        self::assertSame([], $payload['forbidden_packages_found']);
    }

    public function testPreflightBlocksForbiddenDesktopPackageInManifest(): void
    {
        $fixture = $this->createArtifactFixture([
            'bash 5.2',
            'w4-desktop-meta 1.0.0',
        ]);

        $result = $this->runPhpScript($this->fixturePath('scripts/preflight_server_vm_validation.php'), [
            '--profile',
            'w4-os-server',
            '--hypervisor',
            'none',
            '--expected-sha256',
            $fixture['sha256'],
            '--iso-path',
            $fixture['iso'],
            '--manifest-path',
            $fixture['manifest'],
            '--checksum-path',
            $fixture['checksum'],
            '--summary-path',
            $fixture['summary'],
            '--bundle-dir',
            $fixture['bundle'],
        ]);

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('blocked', $payload['status']);
        self::assertFalse($payload['checks']['manifest_headless']);
        self::assertSame(['w4-desktop-meta'], $payload['forbidden_packages_found']);
    }

    public function testPreflightRejectsUnsupportedHypervisorArgument(): void
    {
        $result = $this->runPhpScript($this->fixturePath('scripts/preflight_server_vm_validation.php'), [
            '--hypervisor',
            'unsupported',
        ]);

        self::assertSame(1, $result['exitCode']);
        self::assertStringContainsString('--hypervisor debe ser auto, virtualbox, hyperv o none', $result['stderr']);
    }

    /**
     * @param list<string> $manifestLines
     * @return array{iso:string,manifest:string,checksum:string,summary:string,bundle:string,sha256:string}
     */
    private function createArtifactFixture(array $manifestLines): array
    {
        $isoPath = $this->tempDir . DIRECTORY_SEPARATOR . 'w4-os-server-live-amd64.iso';
        $manifestPath = $this->tempDir . DIRECTORY_SEPARATOR . 'filesystem.manifest';
        $checksumPath = $this->tempDir . DIRECTORY_SEPARATOR . 'SHA256SUMS';
        $summaryPath = $this->tempDir . DIRECTORY_SEPARATOR . 'iso-summary.env';
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'install-bundle';

        self::assertNotFalse(file_put_contents($isoPath, 'fake-server-iso'));
        $sha256 = hash_file('sha256', $isoPath);
        self::assertIsString($sha256);

        self::assertNotFalse(file_put_contents($manifestPath, implode(PHP_EOL, $manifestLines) . PHP_EOL));
        self::assertNotFalse(file_put_contents($checksumPath, $sha256 . '  w4-os-server-live-amd64.iso' . PHP_EOL));
        self::assertNotFalse(file_put_contents($summaryPath, implode(PHP_EOL, [
            'W4_PROFILE_ID="w4-os-server"',
            'W4_VOLUME_ID="W4_OS_SERVER_LIVE"',
        ]) . PHP_EOL));
        self::assertTrue(mkdir($bundleDir, 0777, true));
        self::assertNotFalse(file_put_contents($bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh', '#!/usr/bin/env bash' . PHP_EOL));
        self::assertNotFalse(file_put_contents($bundleDir . DIRECTORY_SEPARATOR . 'verify-installation.sh', '#!/usr/bin/env bash' . PHP_EOL));

        return [
            'iso' => $isoPath,
            'manifest' => $manifestPath,
            'checksum' => $checksumPath,
            'summary' => $summaryPath,
            'bundle' => $bundleDir,
            'sha256' => $sha256,
        ];
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
