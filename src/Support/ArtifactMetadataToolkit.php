<?php

declare(strict_types=1);

namespace W4\OS\Support;

final class ArtifactMetadataToolkit
{
    /**
     * @param array<string, mixed> $identity
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function createManifestEnvelope(
        string $schemaField,
        string $kind,
        array $identity = [],
        array $attributes = []
    ): array {
        $manifest = [
            $schemaField => 1,
            'kind' => $kind,
        ];

        foreach ($identity as $key => $value) {
            $manifest[$key] = $value;
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
    public function createManifest(
        string $schemaField,
        string $kind,
        string $profileId,
        ?string $profileName,
        array $attributes = []
    ): array {
        $identity = [
            'profile_id' => $profileId,
        ];

        if ($profileName !== null && $profileName !== '') {
            $identity['profile_name'] = $profileName;
        }

        return $this->createManifestEnvelope($schemaField, $kind, $identity, $attributes);
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function createStatusPayload(array $attributes = [], string $status = 'ok'): array
    {
        return array_merge(
            [
                'status' => $status,
            ],
            $attributes
        );
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    public function createSuccessPayload(string $profileId, array $attributes = []): array
    {
        return $this->createStatusPayload(
            array_merge(
                [
                    'profile_id' => $profileId,
                ],
                $attributes
            )
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
