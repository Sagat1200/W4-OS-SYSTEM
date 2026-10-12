<?php

declare(strict_types=1);

namespace W4\OS\W4ctl;

use W4\OS\Manifest\ManifestToolkit;
use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;

final class W4ctlApplication
{
    private string $rootDir;
    private ManifestToolkit $manifestToolkit;
    private ArtifactMetadataToolkit $metadataToolkit;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->manifestToolkit = new ManifestToolkit($rootDir);
        $this->metadataToolkit = new ArtifactMetadataToolkit();
    }

    /**
     * @param list<string> $arguments
     * @return array<string, mixed>
     */
    public function handle(array $arguments): array
    {
        $command = $arguments[1] ?? 'help';
        $options = $this->parseOptions(array_slice($arguments, 2));

        return match ($command) {
            'help', '--help', '-h' => $this->renderHelp(),
            'profile:list' => $this->handleProfileList(),
            'profile:show' => $this->handleProfileShow($options),
            'build:validate' => $this->handleBuildValidate($options),
            default => throw new ValidationError(sprintf('Comando no soportado: %s', $command)),
        };
    }

    /**
     * @param list<string> $arguments
     * @return array<string, string|bool>
     */
    private function parseOptions(array $arguments): array
    {
        $options = [];

        for ($index = 0, $count = count($arguments); $index < $count; $index++) {
            $argument = $arguments[$index];
            if (!str_starts_with($argument, '--')) {
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
            }

            if ($argument === '--help') {
                $options['help'] = true;
                continue;
            }

            if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
                throw new ValidationError(sprintf('Falta el valor para %s', $argument));
            }

            $options[substr($argument, 2)] = $arguments[++$index];
        }

        return $options;
    }

    /**
     * @return array{manifests: array<string, array<string, mixed>>, profiles: array<string, array<string, mixed>>}
     */
    private function loadProfiles(): array
    {
        $manifests = $this->manifestToolkit->loadManifests();
        $this->manifestToolkit->validateAll($manifests);

        $profiles = array_filter(
            $manifests,
            static fn (array $manifest): bool => ($manifest['kind'] ?? null) === 'edition-profile'
        );
        ksort($profiles);

        return [
            'manifests' => $manifests,
            'profiles' => $profiles,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function handleProfileList(): array
    {
        $loaded = $this->loadProfiles();
        $profiles = [];

        foreach ($loaded['profiles'] as $profile) {
            $features = $profile['features'] ?? [];
            $notes = $profile['notes'] ?? [];
            $requiredPackages = $profile['packages']['required'] ?? [];
            $requiredMetaPackages = $profile['meta_packages']['required'] ?? [];

            $profiles[] = [
                'id' => $profile['id'],
                'name' => $profile['name'],
                'inherits' => $profile['inherits'],
                'feature_count' => is_array($features) ? count($features) : 0,
                'required_package_count' => is_array($requiredPackages) ? count($requiredPackages) : 0,
                'required_meta_package_count' => is_array($requiredMetaPackages) ? count($requiredMetaPackages) : 0,
                'notes_count' => is_array($notes) ? count($notes) : 0,
            ];
        }

        return $this->metadataToolkit->createStatusPayload([
            'command' => 'profile:list',
            'profiles' => $profiles,
            'count' => count($profiles),
        ]);
    }

    /**
     * @param array<string, string|bool> $options
     * @return array<string, mixed>
     */
    private function handleProfileShow(array $options): array
    {
        $profileId = $options['profile'] ?? null;
        if (!is_string($profileId) || $profileId === '') {
            throw new ValidationError('Debe indicar un PROFILE_ID usando --profile');
        }

        $loaded = $this->loadProfiles();
        $resolvedProfile = $this->manifestToolkit->resolveProfile($loaded['manifests'], $profileId);
        $profile = $loaded['profiles'][$profileId] ?? null;
        if (!is_array($profile)) {
            throw new ValidationError(sprintf('Perfil inexistente: %s', $profileId));
        }

        return $this->metadataToolkit->createStatusPayload([
            'command' => 'profile:show',
            'profile' => [
                'id' => $profile['id'],
                'name' => $profile['name'],
                'inherits' => $profile['inherits'],
                'features' => $profile['features'] ?? [],
                'notes' => $profile['notes'] ?? [],
                'source_manifest' => $profile['_path'],
            ],
            'resolved_profile' => $resolvedProfile,
        ]);
    }

    /**
     * @param array<string, string|bool> $options
     * @return array<string, mixed>
     */
    private function handleBuildValidate(array $options): array
    {
        $profileId = $options['profile'] ?? null;
        if (!is_string($profileId) || $profileId === '') {
            throw new ValidationError('Debe indicar un PROFILE_ID usando --profile');
        }

        $channel = $options['channel'] ?? 'testing';
        $imageFormat = $options['format'] ?? 'iso';

        if (!is_string($channel) || $channel === '') {
            throw new ValidationError('Debe indicar un canal valido usando --channel');
        }

        if (!is_string($imageFormat) || $imageFormat === '') {
            throw new ValidationError('Debe indicar un formato valido usando --format');
        }

        $loaded = $this->loadProfiles();
        $buildInput = $this->manifestToolkit->createBuildInput(
            $loaded['manifests'],
            $profileId,
            $channel,
            $imageFormat
        );

        return $this->metadataToolkit->createSuccessPayload($profileId, [
            'command' => 'build:validate',
            'profile_name' => $buildInput['profile_name'],
            'base_manifest_id' => $buildInput['base_manifest_id'],
            'release_channel' => $buildInput['target']['release_channel'],
            'image_format' => $buildInput['target']['image_format'],
            'counts' => $buildInput['counts'],
            'source_manifests' => $buildInput['source_manifests'],
            'features' => $buildInput['features'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function renderHelp(): array
    {
        return $this->metadataToolkit->createStatusPayload([
            'command' => 'help',
            'tool' => 'w4ctl',
            'version' => 1,
            'commands' => [
                [
                    'name' => 'profile:list',
                    'description' => 'Lista los edition-profile disponibles.',
                ],
                [
                    'name' => 'profile:show',
                    'description' => 'Resuelve y muestra un perfil.',
                    'arguments' => ['--profile <PROFILE_ID>'],
                ],
                [
                    'name' => 'build:validate',
                    'description' => 'Valida el contrato build-input para un perfil.',
                    'arguments' => [
                        '--profile <PROFILE_ID>',
                        '--channel <testing|stable>',
                        '--format <iso>',
                    ],
                ],
            ],
        ]);
    }
}
