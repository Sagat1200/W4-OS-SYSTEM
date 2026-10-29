<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

use W4\OS\Support\ValidationError;

final class ControlCenterSnapshotToolkit
{
    public function __construct(private readonly string $rootDir)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function createHomeView(string $profileId, ?string $snapshotDir = null): array
    {
        $context = $this->loadSnapshotContext($profileId, $snapshotDir);
        $home = $context['home'];
        $manifest = $context['manifest'];

        return [
            'control_center_snapshot_view_schema_version' => 1,
            'kind' => 'control-center-snapshot-home-view',
            'profile_id' => $profileId,
            'generated_at' => (string) ($manifest['generated_at'] ?? $home['generated_at'] ?? gmdate('c')),
            'mode' => (string) ($manifest['mode'] ?? 'read-first'),
            'snapshot' => [
                'manifest_file' => 'control-center-snapshot.json',
                'home_json_path' => (string) (($manifest['home']['json_path'] ?? null) ?: 'control-center-home.json'),
                'home_text_path' => (string) (($manifest['home']['text_path'] ?? null) ?: 'control-center-home.txt'),
                'generated_files' => $this->stringList($manifest['generated_files'] ?? []),
                'module_ids' => $this->moduleIds($manifest['modules'] ?? []),
            ],
            'home' => $home,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createModuleView(string $profileId, string $moduleId, ?string $snapshotDir = null): array
    {
        $context = $this->loadSnapshotContext($profileId, $snapshotDir);
        $manifest = $context['manifest'];
        $home = $context['home'];
        $moduleManifest = $this->findModuleManifest($manifest['modules'] ?? [], $moduleId);
        if ($moduleManifest === null) {
            throw new ValidationError(sprintf('Modulo persistido de Control Center no soportado: %s', $moduleId));
        }

        $moduleJsonPath = $this->requireRelativePath($moduleManifest, 'json_path', $moduleManifest['json_path'] ?? null);
        $moduleDetail = $this->readRequiredJsonFile(
            $context['snapshot_dir'] . DIRECTORY_SEPARATOR . $this->normalizeRelativePath($moduleJsonPath),
            sprintf('No se pudo leer el snapshot del modulo %s', $moduleId)
        );

        $kind = (string) ($moduleDetail['kind'] ?? '');
        if ($kind !== 'control-center-module-detail') {
            throw new ValidationError(sprintf('El snapshot del modulo %s no tiene un kind soportado', $moduleId));
        }

        return [
            'control_center_snapshot_view_schema_version' => 1,
            'kind' => 'control-center-snapshot-module-view',
            'profile_id' => $profileId,
            'generated_at' => (string) ($manifest['generated_at'] ?? $moduleDetail['generated_at'] ?? gmdate('c')),
            'mode' => (string) ($manifest['mode'] ?? 'read-first'),
            'snapshot' => [
                'manifest_file' => 'control-center-snapshot.json',
                'module_json_path' => $moduleJsonPath,
                'module_text_path' => $this->requireRelativePath($moduleManifest, 'text_path', $moduleManifest['text_path'] ?? null),
                'available_modules' => $this->moduleIds($manifest['modules'] ?? []),
            ],
            'home_summary' => [
                'hero' => is_array($home['hero'] ?? null) ? $home['hero'] : [],
                'summary' => is_array($home['summary'] ?? null) ? $home['summary'] : [],
            ],
            'module' => is_array($moduleDetail['module'] ?? null) ? $moduleDetail['module'] : [],
            'detail' => is_array($moduleDetail['detail'] ?? null) ? $moduleDetail['detail'] : [],
        ];
    }

    /**
     * @return array{manifest: array<string, mixed>, home: array<string, mixed>, snapshot_dir: string}
     */
    private function loadSnapshotContext(string $profileId, ?string $snapshotDir): array
    {
        $resolvedSnapshotDir = $snapshotDir ?? $this->defaultSnapshotDir($profileId);
        $manifestPath = $resolvedSnapshotDir . DIRECTORY_SEPARATOR . 'control-center-snapshot.json';
        $manifest = $this->readRequiredJsonFile($manifestPath, 'No se pudo leer el manifest persistido de Control Center');

        $kind = (string) ($manifest['kind'] ?? '');
        if ($kind !== 'control-center-snapshot') {
            throw new ValidationError('control-center-snapshot.json no tiene un kind soportado');
        }

        $home = $this->readHomePayload($resolvedSnapshotDir, $manifest);

        return [
            'manifest' => $manifest,
            'home' => $home,
            'snapshot_dir' => $resolvedSnapshotDir,
        ];
    }

    /**
     * @param array<string, mixed> $manifest
     * @return array<string, mixed>
     */
    private function readHomePayload(string $snapshotDir, array $manifest): array
    {
        $home = is_array($manifest['home'] ?? null) ? $manifest['home'] : [];
        $homeJsonPath = $this->requireRelativePath($home, 'json_path', $home['json_path'] ?? null);
        $payload = $this->readRequiredJsonFile(
            $snapshotDir . DIRECTORY_SEPARATOR . $this->normalizeRelativePath($homeJsonPath),
            'No se pudo leer el payload principal persistido de Control Center'
        );

        if (($payload['kind'] ?? null) !== 'control-center-home-model') {
            throw new ValidationError('El payload principal persistido no tiene un kind soportado');
        }

        return $payload;
    }

    private function defaultSnapshotDir(string $profileId): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . $profileId;
    }

    /**
     * @param mixed $modules
     * @return list<string>
     */
    private function moduleIds(mixed $modules): array
    {
        if (!is_array($modules)) {
            return [];
        }

        $moduleIds = [];
        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $moduleId = trim((string) ($module['id'] ?? ''));
            if ($moduleId === '') {
                continue;
            }

            $moduleIds[] = $moduleId;
        }

        return array_values(array_unique($moduleIds));
    }

    /**
     * @param mixed $modules
     * @return array<string, mixed>|null
     */
    private function findModuleManifest(mixed $modules, string $moduleId): ?array
    {
        if (!is_array($modules)) {
            return null;
        }

        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            if ((string) ($module['id'] ?? '') === $moduleId) {
                return $module;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function readRequiredJsonFile(string $path, string $errorMessage): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new ValidationError(sprintf('El archivo JSON no es valido: %s', $path));
        }

        return $decoded;
    }

    /**
     * @param array<string, mixed> $context
     */
    private function requireRelativePath(array $context, string $field, mixed $value): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationError(sprintf('Falta una ruta relativa valida en %s', $field));
        }

        return trim($value);
    }

    private function normalizeRelativePath(string $path): string
    {
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * @param mixed $items
     * @return list<string>
     */
    private function stringList(mixed $items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $values = [];
        foreach ($items as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $value = trim((string) $item);
            if ($value === '') {
                continue;
            }

            $values[] = $value;
        }

        return array_values(array_unique($values));
    }
}
