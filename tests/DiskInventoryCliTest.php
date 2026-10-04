<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;

final class DiskInventoryCliTest extends TestCase
{
    private string $rootDir;
    private string $tempDir;

    protected function setUp(): void
    {
        $this->rootDir = dirname(__DIR__);
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-disk-inventory-cli-tests-' . bin2hex(random_bytes(6));

        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->clearEnvFixtures();
        $this->removeDirectory($this->tempDir);
    }

    public function testGenerateDiskInventoryReturnsCanonicalStatusPayloadWithFixtureMode(): void
    {
        $outputPath = $this->tempDir . DIRECTORY_SEPARATOR . 'fixture-disk-inventory.json';
        $this->setFixtureEnv();

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_disk_inventory.php',
            [
                '--id',
                'fixture-disk-inventory',
                '--wsl-distribution',
                'Ubuntu-Fixture',
                '--output',
                $outputPath,
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $payload = $this->decodeJson($result['stdout']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('fixture-disk-inventory', $payload['inventory_id']);
        self::assertSame($outputPath, $payload['output']);
        self::assertSame(2, $payload['disk_count']);
        self::assertSame('Ubuntu-Fixture', $payload['source']);

        $inventory = $this->decodeJsonFile($outputPath);
        self::assertSame('disk-inventory', $inventory['kind']);
        self::assertSame('fixture-disk-inventory', $inventory['id']);
        self::assertSame('Ubuntu-Fixture', $inventory['host']);
        self::assertCount(2, $inventory['disks']);
        self::assertSame('/dev/sda', $inventory['disks'][0]['device']);
        self::assertSame('FIXTURE_DISK_001', $inventory['disks'][0]['serial']);
        self::assertSame('pci-0000:00:0d.0-ata-1.0', $inventory['disks'][0]['by_path']);
        self::assertTrue($inventory['disks'][0]['has_partitions']);
        self::assertTrue($inventory['disks'][0]['has_filesystem_signatures']);
        self::assertFalse($inventory['disks'][0]['is_installation_media']);
        self::assertTrue($inventory['disks'][1]['is_installation_media']);
    }

    public function testGenerateDiskInventoryKeepsRawInventoryOnStdoutWithoutOutputPath(): void
    {
        $this->setFixtureEnv();
        putenv('W4_DISK_INVENTORY_HOST_OVERRIDE=fixture-host');

        $result = $this->runPhpScript(
            $this->rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'generate_disk_inventory.php',
            [
                '--id',
                'stdout-disk-inventory',
            ]
        );

        self::assertSame(0, $result['exitCode'], $result['stderr']);

        $inventory = $this->decodeJson($result['stdout']);
        self::assertSame('disk-inventory', $inventory['kind']);
        self::assertSame('stdout-disk-inventory', $inventory['id']);
        self::assertSame('fixture-host', $inventory['host']);
        self::assertCount(2, $inventory['disks']);
        self::assertArrayNotHasKey('status', $inventory);
    }

    private function setFixtureEnv(): void
    {
        putenv('W4_DISK_INVENTORY_LSBLK_JSON=' . json_encode([
            'blockdevices' => [
                [
                    'name' => 'sda',
                    'path' => '/dev/sda',
                    'type' => 'disk',
                    'size' => 34359738368,
                    'serial' => '',
                    'wwn' => '',
                    'model' => 'Fixture Disk',
                    'tran' => 'ata',
                    'rm' => '0',
                    'ro' => '0',
                    'fstype' => null,
                    'mountpoint' => null,
                    'children' => [
                        [
                            'name' => 'sda1',
                            'path' => '/dev/sda1',
                            'type' => 'part',
                            'fstype' => 'ext4',
                        ],
                    ],
                ],
                [
                    'name' => 'sr0',
                    'path' => '/dev/sr0',
                    'type' => 'rom',
                    'size' => 734003200,
                    'serial' => '',
                    'wwn' => '',
                    'model' => 'Fixture ISO',
                    'tran' => 'ata',
                    'rm' => '1',
                    'ro' => '1',
                    'fstype' => 'iso9660',
                    'mountpoint' => '/cdrom',
                ],
            ],
        ], JSON_THROW_ON_ERROR));

        putenv('W4_DISK_INVENTORY_UDEV_MAP_JSON=' . json_encode([
            '/dev/sda' => [
                'ID_SERIAL_SHORT' => 'FIXTURE_DISK_001',
                'ID_PATH' => 'pci-0000:00:0d.0-ata-1.0',
            ],
            '/dev/sr0' => [
                'ID_CDROM' => '1',
            ],
        ], JSON_THROW_ON_ERROR));

        putenv('W4_DISK_INVENTORY_WIPEFS_MAP_JSON=' . json_encode([
            '/dev/sda' => [
                'signatures' => [],
            ],
            '/dev/sr0' => [
                'signatures' => [
                    [
                        'offset' => '0x8001',
                        'type' => 'iso9660',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR));
    }

    private function clearEnvFixtures(): void
    {
        foreach ([
            'W4_DISK_INVENTORY_LSBLK_JSON',
            'W4_DISK_INVENTORY_UDEV_MAP_JSON',
            'W4_DISK_INVENTORY_WIPEFS_MAP_JSON',
            'W4_DISK_INVENTORY_HOST_OVERRIDE',
        ] as $variable) {
            putenv($variable);
        }
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
