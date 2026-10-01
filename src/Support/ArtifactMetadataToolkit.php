<?php

declare(strict_types=1);

namespace W4\OS\Support;

final class ArtifactMetadataToolkit
{
    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function createManifest(
        string $schemaField,
        string $kind,
        string $profileId,
        ?string $profileName,
        array $attributes = []
    ): array {
        $manifest = [
            $schemaField => 1,
            'kind' => $kind,
            'profile_id' => $profileId,
        ];

        if ($profileName !== null && $profileName !== '') {
            $manifest['profile_name'] = $profileName;
        }

        foreach ($attributes as $key => $value) {
            $manifest[$key] = $value;
        }

        return $manifest;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function createSuccessPayload(string $profileId, array $attributes = []): array
    {
        return array_merge(
            [
                'status' => 'ok',
                'profile_id' => $profileId,
            ],
            $attributes
        );
    }

    /**
     * @param list<string> $files
     * @return list<string>
     */
    public function normalizeGeneratedFiles(array $files): array
    {
        $normalized = array_values(array_unique($files));
        sort($normalized);

        return $normalized;
    }
}
