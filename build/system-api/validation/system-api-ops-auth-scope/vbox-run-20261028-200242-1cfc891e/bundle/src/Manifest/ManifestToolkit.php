<?php

declare(strict_types=1);

namespace W4\OS\Manifest;

use JsonException;
use W4\OS\Support\ValidationError;

final class ManifestToolkit
{
    private string $manifestsDir;

    public function __construct(string $rootDir)
    {
        $this->manifestsDir = $rootDir . DIRECTORY_SEPARATOR . 'manifests';
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function loadManifests(): array
    {
        $paths = glob($this->manifestsDir . DIRECTORY_SEPARATOR . '*.json');
        if ($paths === false || $paths === []) {
            throw new ValidationError('No se encontraron manifiestos en la carpeta manifests');
        }

        sort($paths);

        $manifests = [];

        foreach ($paths as $path) {
            $raw = file_get_contents($path);
            if ($raw === false) {
                throw new ValidationError(sprintf('No se pudo leer el archivo %s', basename($path)));
            }

            try {
                /** @var array<string, mixed> $data */
                $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new ValidationError(sprintf('%s: JSON invalido (%s)', basename($path), $exception->getMessage()));
            }

            $manifestId = $data['id'] ?? null;
            if (!is_string($manifestId) || $manifestId === '') {
                throw new ValidationError(sprintf("%s: falta el campo 'id'", basename($path)));
            }

            if (array_key_exists($manifestId, $manifests)) {
                throw new ValidationError(sprintf('id duplicado: %s', $manifestId));
            }

            $data['_path'] = basename($path);
            $manifests[$manifestId] = $data;
        }

        return $manifests;
    }

    /**
     * @param array<string, array<string, mixed>> $manifests
     */
    public function validateAll(array $manifests): void
    {
        foreach ($manifests as $manifest) {
            $this->validateCommon($manifest);
        }

        foreach ($manifests as $manifest) {
            $kind = $manifest['kind'];
            if ($kind === 'base-manifest') {
                $this->validateBaseManifest($manifest);
                continue;
            }

            $this->validateEditionProfile($manifest, $manifests);
        }
    }

    /**
     * @param array<string, array<string, mixed>> $manifests
     * @return array<string, mixed>
     */
    public function resolveProfile(array $manifests, string $profileId): array
    {
        if (!array_key_exists($profileId, $manifests)) {
            throw new ValidationError(sprintf('Perfil inexistente: %s', $profileId));
        }

        $profile = $manifests[$profileId];
        if (($profile['kind'] ?? null) !== 'edition-profile') {
            throw new ValidationError(sprintf('%s no es un edition-profile', $profileId));
        }

        /** @var array<string, mixed> $base */
        $base = $manifests[$profile['inherits']];

        $baseRequired = $this->toUniqueSortedStrings(($base['packages']['required'] ?? []));
        $baseRecommended = $this->toUniqueSortedStrings(($base['packages']['recommended'] ?? []));
        $profileRequired = $this->toUniqueSortedStrings(($profile['packages']['required'] ?? []));
        $profileRecommended = $this->toUniqueSortedStrings(($profile['packages']['recommended'] ?? []));
        $removed = $this->toUniqueSortedStrings(($profile['packages']['remove'] ?? []));

        $requiredPackages = $this->sortedDifference(
            array_unique(array_merge($baseRequired, $profileRequired)),
            $removed
        );
        $recommendedPackages = $this->sortedDifference(
            array_unique(array_merge($baseRecommended, $profileRecommended)),
            array_unique(array_merge($removed, $requiredPackages))
        );

        $requiredMetaPackages = $this->toUniqueSortedStrings(array_merge(
            $base['meta_packages']['required'] ?? [],
            $profile['meta_packages']['required'] ?? []
        ));
        $recommendedMetaPackages = $this->sortedDifference(
            $this->toUniqueSortedStrings(array_merge(
                $base['meta_packages']['recommended'] ?? [],
                $profile['meta_packages']['recommended'] ?? []
            )),
            $requiredMetaPackages
        );

        return [
            'schema_version' => 1,
            'kind' => 'resolved-profile',
            'id' => $profileId,
            'name' => $profile['name'],
            'inherits' => $profile['inherits'],
            'required_meta_packages' => $requiredMetaPackages,
            'recommended_meta_packages' => $recommendedMetaPackages,
            'required_packages' => $requiredPackages,
            'recommended_packages' => $recommendedPackages,
            'features' => $profile['features'] ?? [],
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $manifests
     * @return array<string, mixed>
     */
    public function createBuildInput(
        array $manifests,
        string $profileId,
        string $channel = 'testing',
        string $imageFormat = 'iso'
    ): array {
        $resolved = $this->resolveProfile($manifests, $profileId);
        /** @var array<string, mixed> $profile */
        $profile = $manifests[$profileId];
        /** @var array<string, mixed> $base */
        $base = $manifests[$profile['inherits']];
        /** @var array<string, mixed> $artifacts */
        $artifacts = $base['artifacts'];

        $allowedChannels = $this->ensureStringList($base, 'artifacts.release_channels', $artifacts['release_channels'] ?? []);
        $allowedFormats = $this->ensureStringList($base, 'artifacts.image_formats', $artifacts['image_formats'] ?? []);

        if (!in_array($channel, $allowedChannels, true)) {
            throw new ValidationError(sprintf(
                "Canal de release no soportado '%s' para %s",
                $channel,
                $base['id']
            ));
        }

        if (!in_array($imageFormat, $allowedFormats, true)) {
            throw new ValidationError(sprintf(
                "Formato de imagen no soportado '%s' para %s",
                $imageFormat,
                $base['id']
            ));
        }

        /** @var array<string, mixed> $upstream */
        $upstream = $base['upstream'];
        $repositories = $this->ensureStringList($base, 'repositories', $base['repositories'] ?? []);

        return [
            'build_schema_version' => 1,
            'kind' => 'build-input',
            'profile_id' => $profileId,
            'profile_name' => $profile['name'],
            'base_manifest_id' => $base['id'],
            'upstream' => [
                'distribution' => $upstream['distribution'],
                'track' => $upstream['track'],
                'codename' => $upstream['codename'] ?? $upstream['track'],
                'architectures' => $upstream['architectures'],
                'boot_modes' => $upstream['boot_modes'],
            ],
            'target' => [
                'release_channel' => $channel,
                'image_format' => $imageFormat,
            ],
            'repositories' => $repositories,
            'meta_packages' => [
                'required' => $resolved['required_meta_packages'],
                'recommended' => $resolved['recommended_meta_packages'],
            ],
            'packages' => [
                'required' => $resolved['required_packages'],
                'recommended' => $resolved['recommended_packages'],
            ],
            'features' => $resolved['features'],
            'source_manifests' => [
                'base' => $base['_path'],
                'profile' => $profile['_path'],
            ],
            'counts' => [
                'required_meta_packages' => count($resolved['required_meta_packages']),
                'recommended_meta_packages' => count($resolved['recommended_meta_packages']),
                'required_packages' => count($resolved['required_packages']),
                'recommended_packages' => count($resolved['recommended_packages']),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function validateCommon(array $manifest): void
    {
        if (($manifest['schema_version'] ?? null) !== 1) {
            throw new ValidationError(sprintf("%s: schema_version debe ser 1", $manifest['_path']));
        }

        $kind = $manifest['kind'] ?? null;
        if (!is_string($kind) || !in_array($kind, ['base-manifest', 'edition-profile'], true)) {
            throw new ValidationError(sprintf("%s: kind no soportado", $manifest['_path']));
        }

        foreach (['id', 'name'] as $field) {
            $value = $manifest[$field] ?? null;
            if (!is_string($value) || $value === '') {
                throw new ValidationError(sprintf("%s: falta o es invalido el campo '%s'", $manifest['_path'], $field));
            }
        }

        $metaPackages = $manifest['meta_packages'] ?? null;
        $packages = $manifest['packages'] ?? null;
        if (!is_array($metaPackages) || !is_array($packages)) {
            throw new ValidationError(sprintf("%s: meta_packages y packages deben ser objetos", $manifest['_path']));
        }

        $requiredMeta = $this->ensureStringList($manifest, 'meta_packages.required', $metaPackages['required'] ?? []);
        $recommendedMeta = $this->ensureStringList($manifest, 'meta_packages.recommended', $metaPackages['recommended'] ?? []);
        $requiredPackages = $this->ensureStringList($manifest, 'packages.required', $packages['required'] ?? []);
        $recommendedPackages = $this->ensureStringList($manifest, 'packages.recommended', $packages['recommended'] ?? []);

        $repeated = array_intersect($requiredMeta, $recommendedMeta);
        if ($repeated === []) {
            $repeated = array_intersect($requiredPackages, $recommendedPackages);
        }

        if ($repeated !== []) {
            sort($repeated);
            throw new ValidationError(sprintf(
                '%s: elementos duplicados entre required y recommended: %s',
                $manifest['_path'],
                implode(', ', $repeated)
            ));
        }
    }

    /**
     * @param array<string, mixed> $manifest
     */
    private function validateBaseManifest(array $manifest): void
    {
        $upstream = $manifest['upstream'] ?? null;
        $artifacts = $manifest['artifacts'] ?? null;
        if (!is_array($upstream) || !is_array($artifacts)) {
            throw new ValidationError(sprintf("%s: upstream y artifacts deben ser objetos", $manifest['_path']));
        }

        foreach (['distribution', 'track'] as $field) {
            $value = $upstream[$field] ?? null;
            if (!is_string($value) || $value === '') {
                throw new ValidationError(sprintf("%s: upstream.%s es obligatorio", $manifest['_path'], $field));
            }
        }

        if (array_key_exists('codename', $upstream)) {
            $codename = $upstream['codename'];
            if (!is_string($codename) || $codename === '') {
                throw new ValidationError(sprintf("%s: upstream.codename debe ser un string no vacio", $manifest['_path']));
            }
        }

        $this->ensureStringList($manifest, 'upstream.architectures', $upstream['architectures'] ?? []);
        $this->ensureStringList($manifest, 'upstream.boot_modes', $upstream['boot_modes'] ?? []);
        $this->ensureStringList($manifest, 'repositories', $manifest['repositories'] ?? []);
        $this->ensureStringList($manifest, 'artifacts.image_formats', $artifacts['image_formats'] ?? []);
        $this->ensureStringList($manifest, 'artifacts.release_channels', $artifacts['release_channels'] ?? []);

        $packages = $manifest['packages'];
        if (!is_array($packages)) {
            throw new ValidationError(sprintf("%s: packages debe ser un objeto", $manifest['_path']));
        }

        $this->ensureStringList($manifest, 'packages.conflicts', $packages['conflicts'] ?? []);
    }

    /**
     * @param array<string, mixed> $manifest
     * @param array<string, array<string, mixed>> $manifests
     */
    private function validateEditionProfile(array $manifest, array $manifests): void
    {
        $inherits = $manifest['inherits'] ?? null;
        if (!is_string($inherits) || $inherits === '') {
            throw new ValidationError(sprintf("%s: inherits es obligatorio", $manifest['_path']));
        }

        if (!array_key_exists($inherits, $manifests)) {
            throw new ValidationError(sprintf("%s: inherits referencia un manifiesto inexistente: %s", $manifest['_path'], $inherits));
        }

        if (($manifests[$inherits]['kind'] ?? null) !== 'base-manifest') {
            throw new ValidationError(sprintf("%s: inherits debe apuntar a un base-manifest", $manifest['_path']));
        }

        /** @var array<string, mixed> $packages */
        $packages = $manifest['packages'];
        $removed = $this->ensureStringList($manifest, 'packages.remove', $packages['remove'] ?? []);
        $required = $this->ensureStringList($manifest, 'packages.required', $packages['required'] ?? []);
        $recommended = $this->ensureStringList($manifest, 'packages.recommended', $packages['recommended'] ?? []);

        $overlap = array_unique(array_merge(
            array_intersect($required, $removed),
            array_intersect($recommended, $removed)
        ));

        if ($overlap !== []) {
            sort($overlap);
            throw new ValidationError(sprintf(
                '%s: packages.remove no puede repetir paquetes activos: %s',
                $manifest['_path'],
                implode(', ', $overlap)
            ));
        }

        $basePackages = $manifests[$inherits]['packages'] ?? null;
        if (!is_array($basePackages)) {
            throw new ValidationError(sprintf('%s: el manifiesto base no contiene packages validos', $manifest['_path']));
        }

        $baseRequired = $this->ensureStringList($manifests[$inherits], 'packages.required', $basePackages['required'] ?? []);
        $invalidRemove = array_intersect($baseRequired, $removed);
        if ($invalidRemove !== []) {
            sort($invalidRemove);
            throw new ValidationError(sprintf(
                '%s: no se pueden eliminar paquetes required del manifiesto base: %s',
                $manifest['_path'],
                implode(', ', $invalidRemove)
            ));
        }

        $this->ensureStringList($manifest, 'features', $manifest['features'] ?? []);
    }

    /**
     * @param array<string, mixed> $manifest
     * @param mixed $value
     * @return list<string>
     */
    private function ensureStringList(array $manifest, string $fieldName, mixed $value): array
    {
        if (!is_array($value)) {
            throw new ValidationError(sprintf("%s: el campo '%s' debe ser una lista de strings no vacios", $manifest['_path'], $fieldName));
        }

        $result = [];
        foreach ($value as $item) {
            if (!is_string($item) || $item === '') {
                throw new ValidationError(sprintf("%s: el campo '%s' debe ser una lista de strings no vacios", $manifest['_path'], $fieldName));
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * @param mixed[] $values
     * @return list<string>
     */
    private function toUniqueSortedStrings(array $values): array
    {
        $strings = [];
        foreach ($values as $value) {
            if (is_string($value) && $value !== '') {
                $strings[] = $value;
            }
        }

        $strings = array_values(array_unique($strings));
        sort($strings);
        return $strings;
    }

    /**
     * @param list<string> $values
     * @param list<string> $excluded
     * @return list<string>
     */
    private function sortedDifference(array $values, array $excluded): array
    {
        $difference = array_values(array_diff($values, $excluded));
        sort($difference);
        return $difference;
    }
}
