<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Support\ValidationError;
use W4\OS\Update\RepositoryPublicationToolkit;

final class RepositoryPublicationToolkitTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'w4-os-repo-publication-tests-' . bin2hex(random_bytes(6));
        self::assertTrue(mkdir($this->tempDir, 0777, true), 'No se pudo crear el directorio temporal');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);
    }

    public function testValidatePublishedRepositoryAcceptsSignedProdArtifacts(): void
    {
        $toolkit = new RepositoryPublicationToolkit();
        $bundleDir = $this->tempDir . DIRECTORY_SEPARATOR . 'bundle';
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'published';

        self::assertTrue(mkdir($bundleDir, 0777, true), 'No se pudo crear el bundle');
        self::assertTrue(mkdir($outputDir . DIRECTORY_SEPARATOR . 'dists' . DIRECTORY_SEPARATOR . 'testing', 0777, true), 'No se pudo crear el arbol dists');
        self::assertTrue(mkdir($outputDir . DIRECTORY_SEPARATOR . 'keyrings', 0777, true), 'No se pudo crear el arbol keyrings');

        $bundleManifest = [
            'repository_bundle_schema_version' => 1,
            'kind' => 'update-repository-bundle',
            'repository_snapshot' => [
                'id' => 'w4-main-2026-09-21T210000Z',
                'channel' => 'testing',
            ],
            'target_version' => '1.0.2',
        ];

        self::assertNotFalse(file_put_contents(
            $bundleDir . DIRECTORY_SEPARATOR . 'repository-manifest.json',
            json_encode($bundleManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL
        ));

        self::assertNotFalse(file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'dists' . DIRECTORY_SEPARATOR . 'testing' . DIRECTORY_SEPARATOR . 'Release', "Origin: W4\n"));
        self::assertNotFalse(file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'dists' . DIRECTORY_SEPARATOR . 'testing' . DIRECTORY_SEPARATOR . 'InRelease', "-----BEGIN PGP SIGNED MESSAGE-----\n"));
        self::assertNotFalse(file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'dists' . DIRECTORY_SEPARATOR . 'testing' . DIRECTORY_SEPARATOR . 'Release.gpg', "binary-signature"));
        self::assertNotFalse(file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'keyrings' . DIRECTORY_SEPARATOR . 'w4-update-archive-keyring.gpg', "binary-keyring"));
        self::assertNotFalse(file_put_contents(
            $outputDir . DIRECTORY_SEPARATOR . 'repo.env',
            implode(PHP_EOL, [
                'W4_REPOSITORY_SNAPSHOT_ID="w4-main-2026-09-21T210000Z"',
                'W4_REPOSITORY_CHANNEL="testing"',
                'W4_REPOSITORY_TARGET_VERSION="1.0.2"',
                'W4_REPOSITORY_SIGNING_MODE="gpg"',
                'W4_REPOSITORY_KEYRING_RELATIVE_PATH="keyrings/w4-update-archive-keyring.gpg"',
                'W4_UPDATE_APT_SOURCE_MODE_DEFAULT="dists"',
                'W4_UPDATE_APT_SOURCE_LINE_DEFAULT_TEMPLATE="deb [signed-by=__W4_REPO_ROOT__/keyrings/w4-update-archive-keyring.gpg] file:__W4_REPO_ROOT__ testing main"',
                'W4_UPDATE_APT_SOURCE_LINE_DEFAULT_LOCAL="deb [signed-by=/mnt/c/W4/repo/keyrings/w4-update-archive-keyring.gpg] file:/mnt/c/W4/repo testing main"',
                'W4_UPDATE_APT_SOURCE_LINE_SIGNED_TEMPLATE="deb [signed-by=__W4_REPO_ROOT__/keyrings/w4-update-archive-keyring.gpg] file:__W4_REPO_ROOT__ testing main"',
                'W4_UPDATE_APT_SOURCE_LINE_SIGNED_LOCAL="deb [signed-by=/mnt/c/W4/repo/keyrings/w4-update-archive-keyring.gpg] file:/mnt/c/W4/repo testing main"',
            ]) . PHP_EOL
        ));

        $manifest = $toolkit->readRepositoryBundleManifest($bundleDir);
        $validation = $toolkit->validatePublishedRepository($outputDir, $manifest);

        self::assertSame('w4-main-2026-09-21T210000Z', $validation['snapshot_id']);
        self::assertSame('testing', $validation['channel']);
        self::assertSame('1.0.2', $validation['target_version']);
        self::assertSame('gpg', $validation['repo_env']['signing_mode']);
        self::assertStringContainsString('signed-by=', $validation['repo_env']['signed_source_line_local']);
        self::assertContains('repo.env', $validation['verified_artifacts']);
    }

    public function testValidatePublishedRepositoryRejectsUnsignedRepoEnv(): void
    {
        $toolkit = new RepositoryPublicationToolkit();
        $bundleManifest = [
            'repository_bundle_schema_version' => 1,
            'kind' => 'update-repository-bundle',
            'repository_snapshot' => [
                'id' => 'w4-main-2026-09-21T220000Z',
                'channel' => 'testing',
            ],
            'target_version' => '1.0.2',
        ];
        $outputDir = $this->tempDir . DIRECTORY_SEPARATOR . 'invalid-published';

        self::assertTrue(mkdir($outputDir . DIRECTORY_SEPARATOR . 'dists' . DIRECTORY_SEPARATOR . 'testing', 0777, true), 'No se pudo crear el arbol dists');
        self::assertTrue(mkdir($outputDir . DIRECTORY_SEPARATOR . 'keyrings', 0777, true), 'No se pudo crear el arbol keyrings');

        foreach (['Release', 'InRelease', 'Release.gpg'] as $fileName) {
            self::assertNotFalse(file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'dists' . DIRECTORY_SEPARATOR . 'testing' . DIRECTORY_SEPARATOR . $fileName, 'placeholder'));
        }
        self::assertNotFalse(file_put_contents($outputDir . DIRECTORY_SEPARATOR . 'keyrings' . DIRECTORY_SEPARATOR . 'w4-update-archive-keyring.gpg', 'placeholder'));
        self::assertNotFalse(file_put_contents(
            $outputDir . DIRECTORY_SEPARATOR . 'repo.env',
            implode(PHP_EOL, [
                'W4_REPOSITORY_SNAPSHOT_ID="w4-main-2026-09-21T220000Z"',
                'W4_REPOSITORY_CHANNEL="testing"',
                'W4_REPOSITORY_TARGET_VERSION="1.0.2"',
                'W4_REPOSITORY_SIGNING_MODE="unsigned"',
                'W4_REPOSITORY_KEYRING_RELATIVE_PATH="keyrings/w4-update-archive-keyring.gpg"',
                'W4_UPDATE_APT_SOURCE_MODE_DEFAULT="dists"',
                'W4_UPDATE_APT_SOURCE_LINE_DEFAULT_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ testing main"',
                'W4_UPDATE_APT_SOURCE_LINE_DEFAULT_LOCAL="deb [trusted=yes] file:/mnt/c/W4/repo testing main"',
                'W4_UPDATE_APT_SOURCE_LINE_SIGNED_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ testing main"',
                'W4_UPDATE_APT_SOURCE_LINE_SIGNED_LOCAL="deb [trusted=yes] file:/mnt/c/W4/repo testing main"',
            ]) . PHP_EOL
        ));

        $this->expectException(ValidationError::class);
        $this->expectExceptionMessage('W4_REPOSITORY_SIGNING_MODE debe ser gpg');

        $toolkit->validatePublishedRepository($outputDir, $bundleManifest);
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
