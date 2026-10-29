<?php

declare(strict_types=1);

namespace W4\OS\W4ctl;

use JsonException;
use W4\OS\Manifest\ManifestToolkit;
use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError;
use W4\OS\Update\UpdateToolkit;

final class W4ctlApplication
{
    private string $rootDir;
    private ManifestToolkit $manifestToolkit;
    private ArtifactMetadataToolkit $metadataToolkit;
    private UpdateToolkit $updateToolkit;

    public function __construct(string $rootDir)
    {
        $this->rootDir = $rootDir;
        $this->manifestToolkit = new ManifestToolkit($rootDir);
        $this->metadataToolkit = new ArtifactMetadataToolkit();
        $this->updateToolkit = new UpdateToolkit();
    }

    /**
     * @param list<string> $arguments
     * @return array<string, mixed>
     */
    public function handle(array $arguments): array
    {
        $command = $arguments[1] ?? 'help';
        $options = $this->parseOptions(array_slice($arguments, 2));

        return $this->handleCommand($command, $options);
    }

    /**
     * @param array<string, string|bool> $options
     * @return array<string, mixed>
     */
    public function handleCommand(string $command, array $options = []): array
    {
        return match ($command) {
            'help', '--help', '-h' => $this->renderHelp(),
            'profile:list' => $this->handleProfileList(),
            'profile:show' => $this->handleProfileShow($options),
            'build:validate' => $this->handleBuildValidate($options),
            'live:inspect' => $this->handleLiveInspect($options),
            'artifact:manifest' => $this->handleArtifactManifest($options),
            'update:plan' => $this->handleUpdatePlan($options),
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
     * @param array<string, string|bool> $options
     * @return array<string, mixed>
     */
    private function handleLiveInspect(array $options): array
    {
        $profileId = $options['profile'] ?? null;
        if (!is_string($profileId) || $profileId === '') {
            throw new ValidationError('Debe indicar un PROFILE_ID usando --profile');
        }

        $loaded = $this->loadProfiles();
        if (!isset($loaded['profiles'][$profileId]) || !is_array($loaded['profiles'][$profileId])) {
            throw new ValidationError(sprintf('Perfil inexistente: %s', $profileId));
        }

        $liveOutputDir = $this->resolveLiveOutputDir($profileId, $options['live-output-dir'] ?? null);
        $liveSummaryPath = $liveOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'live-summary.env';
        $liveSummary = $this->readRequiredEnvFile($liveSummaryPath, 'No se pudo leer live-summary.env');

        $filesystemManifestPath = $liveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest';
        $filesystemPackages = $this->readFilesystemManifestPackages($filesystemManifestPath);
        $resolvedProfile = $this->manifestToolkit->resolveProfile($loaded['manifests'], $profileId);

        $requiredPackages = $this->stringList($resolvedProfile['required_packages'] ?? []);
        $presentRequiredPackages = array_values(array_intersect($requiredPackages, $filesystemPackages));
        sort($presentRequiredPackages);
        $missingRequiredPackages = array_values(array_diff($requiredPackages, $filesystemPackages));
        sort($missingRequiredPackages);

        $liveManifestPath = $this->resolveArtifactManifestPath('live', $profileId, $options['live-manifest'] ?? null);
        $liveManifest = $this->readRequiredJsonFile($liveManifestPath);
        $generatedFiles = $this->stringList($liveManifest['generated_files'] ?? []);
        $features = $this->stringList($liveManifest['features'] ?? $resolvedProfile['features'] ?? []);
        $branding = is_array($liveManifest['branding'] ?? null) ? $liveManifest['branding'] : [];
        $editionPolicy = is_array($liveManifest['edition_policy'] ?? null) ? $liveManifest['edition_policy'] : [];

        return $this->metadataToolkit->createSuccessPayload($profileId, [
            'command' => 'live:inspect',
            'profile_name' => (string) ($loaded['profiles'][$profileId]['name'] ?? $resolvedProfile['name'] ?? ''),
            'live_output_dir' => $this->relativePath($liveOutputDir),
            'live_manifest_path' => $this->relativePath($liveManifestPath),
            'live_summary_path' => $this->relativePath($liveSummaryPath),
            'filesystem_manifest_path' => $this->relativePath($filesystemManifestPath),
            'live_summary' => [
                'generated_at' => (string) ($liveSummary['W4_GENERATED_AT'] ?? ''),
                'live_user' => (string) ($liveSummary['W4_LIVE_USER'] ?? ''),
                'live_hostname' => (string) ($liveSummary['W4_LIVE_HOSTNAME'] ?? ''),
                'default_target' => (string) ($liveSummary['W4_DEFAULT_TARGET'] ?? ''),
                'kernel_basename' => (string) ($liveSummary['W4_KERNEL_BASENAME'] ?? ''),
                'initrd_basename' => (string) ($liveSummary['W4_INITRD_BASENAME'] ?? ''),
                'iso_tooling_available' => $this->toBooleanStringFlag($liveSummary['ISO_TOOLING_AVAILABLE'] ?? null),
            ],
            'filesystem' => [
                'package_count' => count($filesystemPackages),
                'required_package_count' => count($requiredPackages),
                'present_required_package_count' => count($presentRequiredPackages),
                'missing_required_package_count' => count($missingRequiredPackages),
                'missing_required_packages' => $missingRequiredPackages,
            ],
            'features' => $features,
            'generated_files' => $generatedFiles,
            'generated_file_count' => count($generatedFiles),
            'branding' => [
                'edition' => (string) ($branding['edition'] ?? ''),
                'volume_id' => (string) ($branding['volume_id'] ?? ''),
                'default_target' => (string) ($branding['default_target'] ?? ''),
            ],
            'edition_policy' => [
                'path' => (string) ($editionPolicy['path'] ?? ''),
                'default_target' => (string) ($editionPolicy['default_target'] ?? ''),
            ],
        ]);
    }

    /**
     * @param array<string, string|bool> $options
     * @return array<string, mixed>
     */
    private function handleArtifactManifest(array $options): array
    {
        $artifact = $options['artifact'] ?? null;
        if (!is_string($artifact) || $artifact === '') {
            throw new ValidationError('Debe indicar un tipo de artifact usando --artifact');
        }

        $manifestPathOption = $options['manifest'] ?? null;
        $profileIdOption = $options['profile'] ?? null;

        if (!is_string($manifestPathOption) && (!is_string($profileIdOption) || $profileIdOption === '')) {
            throw new ValidationError('Debe indicar --profile o una ruta explicita usando --manifest');
        }

        $manifestPath = $this->resolveArtifactManifestPath(
            $artifact,
            is_string($profileIdOption) ? $profileIdOption : null,
            $manifestPathOption
        );

        $manifest = $this->readRequiredJsonFile($manifestPath);
        $manifestProfileId = is_string($manifest['profile_id'] ?? null) ? $manifest['profile_id'] : '';
        $generatedFiles = $this->stringList($manifest['generated_files'] ?? []);
        $nextSteps = $this->stringList($manifest['next_steps'] ?? []);

        return $this->metadataToolkit->createStatusPayload([
            'command' => 'artifact:manifest',
            'artifact' => $artifact,
            'profile_id' => $manifestProfileId !== '' ? $manifestProfileId : $profileIdOption,
            'manifest_path' => $this->relativePath($manifestPath),
            'manifest_kind' => (string) ($manifest['kind'] ?? ''),
            'generated_file_count' => count($generatedFiles),
            'next_step_count' => count($nextSteps),
            'artifact_manifest' => $manifest,
        ]);
    }

    /**
     * @param array<string, string|bool> $options
     * @return array<string, mixed>
     */
    private function handleUpdatePlan(array $options): array
    {
        $requestPathOption = $options['request'] ?? null;
        if (!is_string($requestPathOption) || $requestPathOption === '') {
            throw new ValidationError('Debe indicar un archivo usando --request');
        }

        $requestPath = $this->toAbsolutePath($requestPathOption);
        $request = $this->updateToolkit->readJsonFile($requestPath);
        $this->updateToolkit->validateUpdateRequest($request, $requestPath);

        $operationId = $options['operation-id'] ?? null;
        if ($operationId !== null && (!is_string($operationId) || $operationId === '')) {
            throw new ValidationError('Debe indicar un OPERATION_ID valido usando --operation-id');
        }

        $plan = $this->updateToolkit->createUpdatePlan(
            $request,
            is_string($operationId) ? $operationId : null
        );
        $this->updateToolkit->validateUpdatePlan($plan, $requestPath);

        $outputPathOption = $options['output'] ?? null;
        $outputPath = is_string($outputPathOption) && $outputPathOption !== ''
            ? $this->toAbsolutePath($outputPathOption)
            : $this->rootDir
                . DIRECTORY_SEPARATOR . 'build'
                . DIRECTORY_SEPARATOR . 'update'
                . DIRECTORY_SEPARATOR . 'plans'
                . DIRECTORY_SEPARATOR . $plan['operation_id'] . '.update-plan.json';

        $this->ensureDirectory(dirname($outputPath), 'No se pudo crear la carpeta de salida');
        $this->writeJsonFile($outputPath, $plan, 'No se pudo escribir el update plan');

        return $this->metadataToolkit->createSuccessPayload((string) $plan['profile_id'], [
            'command' => 'update:plan',
            'request_path' => $this->relativePath($requestPath),
            'output_path' => $this->relativePath($outputPath),
            'operation_id' => $plan['operation_id'],
            'source_version' => $plan['source_version'],
            'target_version' => $plan['target_version'],
            'repository_snapshot' => $plan['repository_snapshot'],
            'apply_mode' => $plan['execution']['apply_mode'] ?? null,
            'stages' => $plan['execution']['stages'] ?? [],
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
                [
                    'name' => 'live:inspect',
                    'description' => 'Inspecciona el live-output materializado y su contrato live.',
                    'arguments' => [
                        '--profile <PROFILE_ID>',
                        '--live-output-dir <DIR opcional>',
                        '--live-manifest <PATH opcional>',
                    ],
                ],
                [
                    'name' => 'artifact:manifest',
                    'description' => 'Lee el manifest canonico de un artifact del pipeline.',
                    'arguments' => [
                        '--artifact <rootfs|overlay|live|iso>',
                        '--profile <PROFILE_ID>',
                        '--manifest <PATH opcional>',
                    ],
                ],
                [
                    'name' => 'update:plan',
                    'description' => 'Genera un update-plan canonico desde un update-request.',
                    'arguments' => [
                        '--request <PATH>',
                        '--operation-id <ID opcional>',
                        '--output <PATH opcional>',
                    ],
                ],
            ],
        ]);
    }

    private function resolveLiveOutputDir(string $profileId, string|bool|null $option): string
    {
        if (is_string($option) && $option !== '') {
            return $this->toAbsolutePath($option);
        }

        return $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output' . DIRECTORY_SEPARATOR . $profileId;
    }

    private function resolveArtifactManifestPath(string $artifact, ?string $profileId, string|bool|null $option): string
    {
        if (is_string($option) && $option !== '') {
            return $this->toAbsolutePath($option);
        }

        if ($profileId === null || $profileId === '') {
            throw new ValidationError('Debe indicar --profile cuando no se usa --manifest');
        }

        $relativePath = match ($artifact) {
            'rootfs' => 'build/rootfs/' . $profileId . '/rootfs-manifest.json',
            'overlay' => 'build/overlays/' . $profileId . '/overlay-manifest.json',
            'live' => 'build/live/' . $profileId . '/live-manifest.json',
            'iso' => 'build/iso/' . $profileId . '/iso-manifest.json',
            default => throw new ValidationError(sprintf('Artifact no soportado: %s', $artifact)),
        };

        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    /**
     * @return array<string, string>
     */
    private function readRequiredEnvFile(string $path, string $errorMessage): array
    {
        if (!is_file($path)) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        $values = [];
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            if ($key === '') {
                continue;
            }

            $values[$key] = trim($value, "\"'");
        }

        return $values;
    }

    /**
     * @return array<string, mixed>
     */
    private function readRequiredJsonFile(string $path): array
    {
        if (!is_file($path)) {
            throw new ValidationError(sprintf('No se pudo leer el archivo JSON: %s', $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('No se pudo leer el archivo JSON: %s', $path));
        }

        try {
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ValidationError(sprintf('%s: JSON invalido (%s)', $path, $exception->getMessage()));
        }

        if (!is_array($decoded)) {
            throw new ValidationError(sprintf('%s: el JSON debe decodificar a un objeto', $path));
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJsonFile(string $path, array $payload, string $errorMessage): void
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            throw new ValidationError('No se pudo serializar el archivo JSON');
        }

        $written = file_put_contents($path, $json . PHP_EOL);
        if ($written === false) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }
    }

    /**
     * @return list<string>
     */
    private function readFilesystemManifestPackages(string $path): array
    {
        if (!is_file($path)) {
            throw new ValidationError(sprintf('No se pudo leer filesystem.manifest: %s', $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('No se pudo leer filesystem.manifest: %s', $path));
        }

        $packages = [];
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if (!is_array($parts) || $parts === []) {
                continue;
            }

            $package = trim((string) $parts[0]);
            if ($package !== '') {
                $packages[] = $package;
            }
        }

        $packages = array_values(array_unique($packages));
        sort($packages);

        return $packages;
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $item) {
            if (is_string($item) && $item !== '') {
                $result[] = $item;
            }
        }

        return $result;
    }

    private function toBooleanStringFlag(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function toAbsolutePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1 || str_starts_with($path, '\\\\')) {
            return $path;
        }

        return $this->rootDir . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    private function ensureDirectory(string $path, string $errorMessage): void
    {
        if (is_dir($path)) {
            return;
        }

        if (!mkdir($path, 0777, true) && !is_dir($path)) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }
    }

    private function relativePath(string $path): string
    {
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->rootDir);
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        $rootWithSeparator = rtrim($normalizedRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($normalizedPath, $rootWithSeparator)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($normalizedPath, strlen($rootWithSeparator)));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $normalizedPath);
    }
}
