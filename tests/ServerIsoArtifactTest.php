<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ServerIsoArtifactTest extends TestCase
{
    private const EXPECTED_ISO_SHA256 = 'b5fbb8915e6af8760298c9e5b3b7f9eb797f20e1ddabd4c51fd2c6db925c3f21';

    /**
     * @var list<string>
     */
    private const FORBIDDEN_PACKAGES = [
        'w4-desktop-meta',
        'os-prober',
        'pipewire',
        'xdg-desktop-portal',
    ];

    /**
     * @var list<string>
     */
    private const INSTALLER_PACKAGES = [
        'dosfstools',
        'e2fsprogs',
        'gdisk',
        'parted',
        'squashfs-tools',
    ];

    public function testServerIsoArtifactIsHeadlessAndChecksummedWhenMaterialized(): void
    {
        $rootDir = dirname(__DIR__);
        $outputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'iso-output' . DIRECTORY_SEPARATOR . 'w4-os-server';

        if (!is_dir($outputDir)) {
            self::markTestSkipped('Server ISO output is not materialized in this workspace.');
        }

        $isoPath = $outputDir . DIRECTORY_SEPARATOR . 'w4-os-server-live-amd64.iso';
        $manifestPath = $outputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest';
        $checksumPath = $outputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'SHA256SUMS';
        $summaryPath = $outputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'iso-summary.env';

        self::assertFileExists($isoPath);
        self::assertFileExists($manifestPath);
        self::assertFileExists($checksumPath);
        self::assertFileExists($summaryPath);

        self::assertSame(self::EXPECTED_ISO_SHA256, hash_file('sha256', $isoPath));
        self::assertStringContainsString(self::EXPECTED_ISO_SHA256 . '  w4-os-server-live-amd64.iso', $this->readFile($checksumPath));

        $summary = $this->readFile($summaryPath);
        self::assertStringContainsString('W4_PROFILE_ID="w4-os-server"', $summary);
        self::assertStringContainsString('W4_VOLUME_ID="W4_OS_SERVER_LIVE"', $summary);

        $manifestPackages = $this->readManifestPackageNames($manifestPath);

        foreach (self::FORBIDDEN_PACKAGES as $packageName) {
            self::assertNotContains($packageName, $manifestPackages);
        }

        foreach (self::INSTALLER_PACKAGES as $packageName) {
            self::assertContains($packageName, $manifestPackages);
        }
    }

    /**
     * @return list<string>
     */
    private function readManifestPackageNames(string $path): array
    {
        $lines = preg_split('/\r?\n/', $this->readFile($path)) ?: [];
        $packages = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if ($parts === false || $parts === [] || $parts[0] === '') {
                continue;
            }

            $packages[] = $parts[0];
        }

        return $packages;
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);
        self::assertIsString($contents, sprintf('No se pudo leer el archivo: %s', $path));

        return $contents;
    }
}
