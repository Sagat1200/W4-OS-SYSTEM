<?php

declare(strict_types=1);

namespace W4\OS\Tests;

use PHPUnit\Framework\TestCase;
use W4\OS\Support\ArtifactMetadataToolkit;

final class ArtifactMetadataToolkitTest extends TestCase
{
    public function testCreateManifestBuildsCanonicalArtifactMetadata(): void
    {
        $toolkit = new ArtifactMetadataToolkit();

        $manifest = $toolkit->createManifest(
            'live_bundle_schema_version',
            'live-bundle',
            'w4-os-server',
            'W4 OS Server',
            [
                'source_overlay_manifest' => 'overlay-manifest.json',
                'generated_files' => ['compose-live.sh', 'files/system-overlay/'],
            ]
        );

        self::assertSame(1, $manifest['live_bundle_schema_version']);
        self::assertSame('live-bundle', $manifest['kind']);
        self::assertSame('w4-os-server', $manifest['profile_id']);
        self::assertSame('W4 OS Server', $manifest['profile_name']);
        self::assertSame('overlay-manifest.json', $manifest['source_overlay_manifest']);
        self::assertSame(['compose-live.sh', 'files/system-overlay/'], $manifest['generated_files']);
    }

    public function testCreateManifestEnvelopeSupportsNonProfileArtifacts(): void
    {
        $toolkit = new ArtifactMetadataToolkit();

        $manifest = $toolkit->createManifestEnvelope(
            'repository_bundle_schema_version',
            'update-repository-bundle',
            [
                'repository_snapshot' => [
                    'id' => 'w4-main-2026-10-01T120000Z',
                    'channel' => 'testing',
                ],
            ],
            [
                'target_version' => '1.0.3',
            ]
        );

        self::assertSame(1, $manifest['repository_bundle_schema_version']);
        self::assertSame('update-repository-bundle', $manifest['kind']);
        self::assertSame('w4-main-2026-10-01T120000Z', $manifest['repository_snapshot']['id']);
        self::assertSame('testing', $manifest['repository_snapshot']['channel']);
        self::assertSame('1.0.3', $manifest['target_version']);
        self::assertArrayNotHasKey('profile_id', $manifest);
    }

    public function testNormalizeGeneratedFilesSortsAndDeduplicatesEntries(): void
    {
        $toolkit = new ArtifactMetadataToolkit();

        $normalized = $toolkit->normalizeGeneratedFiles([
            'z-file.txt',
            'a-file.txt',
            'z-file.txt',
            'b-dir/',
        ]);

        self::assertSame([
            'a-file.txt',
            'b-dir/',
            'z-file.txt',
        ], $normalized);
    }

    public function testCreateSuccessPayloadKeepsCanonicalEnvelope(): void
    {
        $toolkit = new ArtifactMetadataToolkit();

        $payload = $toolkit->createSuccessPayload('w4-os-home', [
            'output_directory' => '/tmp/w4-os-home',
        ]);

        self::assertSame('ok', $payload['status']);
        self::assertSame('w4-os-home', $payload['profile_id']);
        self::assertSame('/tmp/w4-os-home', $payload['output_directory']);
    }

    public function testCreateStatusPayloadSupportsGenericArtifacts(): void
    {
        $toolkit = new ArtifactMetadataToolkit();

        $payload = $toolkit->createStatusPayload([
            'bundle_dir' => '/tmp/repository-bundle',
            'snapshot_id' => 'w4-main-2026-10-01T120000Z',
        ]);

        self::assertSame('ok', $payload['status']);
        self::assertSame('/tmp/repository-bundle', $payload['bundle_dir']);
        self::assertSame('w4-main-2026-10-01T120000Z', $payload['snapshot_id']);
        self::assertArrayNotHasKey('profile_id', $payload);
    }
}
