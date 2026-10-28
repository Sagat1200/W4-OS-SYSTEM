<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

final class ControlCenterApiToolkit
{
    private ControlCenterSnapshotToolkit $snapshotToolkit;

    public function __construct(string $rootDir)
    {
        $this->snapshotToolkit = new ControlCenterSnapshotToolkit($rootDir);
    }

    /**
     * @return array<string, mixed>
     */
    public function createHomeResponse(string $profileId, ?string $snapshotDir = null): array
    {
        $view = $this->snapshotToolkit->createHomeView($profileId, $snapshotDir);
        $home = is_array($view['home'] ?? null) ? $view['home'] : [];
        $modules = is_array($home['modules'] ?? null) ? $home['modules'] : [];
        $summary = is_array($home['summary'] ?? null) ? $home['summary'] : [];
        $hero = is_array($home['hero'] ?? null) ? $home['hero'] : [];

        $moduleSummaries = [];
        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $moduleId = (string) ($module['id'] ?? '');
            if ($moduleId === '') {
                continue;
            }

            $entrypoints = is_array($module['entrypoints'] ?? null) ? $module['entrypoints'] : [];
            $moduleSummaries[] = [
                'id' => $moduleId,
                'title' => (string) ($module['title'] ?? $moduleId),
                'status' => (string) ($module['status'] ?? 'unknown'),
                'class' => (string) ($module['class'] ?? 'W4-augmented'),
                'badges' => $this->stringList($module['badges'] ?? []),
                'highlights' => is_array($module['highlights'] ?? null) ? $module['highlights'] : [],
                'last_checked_at' => (string) ($module['last_checked_at'] ?? ''),
                'has_deep_links' => $entrypoints !== [],
                'deep_link_count' => count($entrypoints),
                'links' => [
                    'self' => sprintf('/control-center/modules/%s', $moduleId),
                ],
            ];
        }

        $snapshot = is_array($view['snapshot'] ?? null) ? $view['snapshot'] : [];

        return $this->buildResponse(
            profileId: $profileId,
            generatedAt: (string) ($view['generated_at'] ?? gmdate('c')),
            resource: 'home',
            resourceId: null,
            mode: (string) ($view['mode'] ?? 'read-first'),
            snapshot: [
                'manifest_file' => (string) ($snapshot['manifest_file'] ?? 'control-center-snapshot.json'),
                'home_json_path' => (string) ($snapshot['home_json_path'] ?? 'control-center-home.json'),
                'module_count' => count($moduleSummaries),
            ],
            data: [
                'hero' => $hero,
                'summary' => $summary,
                'modules' => $moduleSummaries,
            ],
            links: [
                'self' => '/control-center/home',
                'collection' => '/control-center/modules',
            ]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function createModuleResponse(string $profileId, string $moduleId, ?string $snapshotDir = null): array
    {
        $view = $this->snapshotToolkit->createModuleView($profileId, $moduleId, $snapshotDir);
        $module = is_array($view['module'] ?? null) ? $view['module'] : [];
        $detail = is_array($view['detail'] ?? null) ? $view['detail'] : [];
        $snapshot = is_array($view['snapshot'] ?? null) ? $view['snapshot'] : [];
        $homeSummary = is_array($view['home_summary'] ?? null) ? $view['home_summary'] : [];

        $resolvedModuleId = (string) ($module['id'] ?? $moduleId);
        $entrypoints = is_array($module['entrypoints'] ?? null) ? $module['entrypoints'] : [];

        return $this->buildResponse(
            profileId: $profileId,
            generatedAt: (string) ($view['generated_at'] ?? gmdate('c')),
            resource: 'module',
            resourceId: $resolvedModuleId,
            mode: (string) ($view['mode'] ?? 'read-first'),
            snapshot: [
                'manifest_file' => (string) ($snapshot['manifest_file'] ?? 'control-center-snapshot.json'),
                'module_json_path' => (string) ($snapshot['module_json_path'] ?? sprintf('modules/%s.json', $resolvedModuleId)),
                'available_modules' => $this->stringList($snapshot['available_modules'] ?? []),
            ],
            data: [
                'module' => [
                    'id' => $resolvedModuleId,
                    'title' => (string) ($module['title'] ?? $resolvedModuleId),
                    'status' => (string) ($module['status'] ?? 'unknown'),
                    'status_reason' => (string) ($module['status_reason'] ?? ''),
                    'class' => (string) ($module['class'] ?? 'W4-augmented'),
                    'summary' => (string) ($module['summary'] ?? ''),
                    'authority' => (string) ($module['authority'] ?? ''),
                    'capabilities' => $this->stringList($module['capabilities'] ?? []),
                    'badges' => $this->stringList($module['badges'] ?? []),
                    'highlights' => is_array($module['highlights'] ?? null) ? $module['highlights'] : [],
                    'source_of_truth' => $this->stringList($module['source_of_truth'] ?? []),
                    'entrypoints' => $entrypoints,
                    'actions' => $this->stringList($module['actions'] ?? []),
                ],
                'detail' => $detail,
                'home_summary' => $homeSummary,
            ],
            links: [
                'self' => sprintf('/control-center/modules/%s', $resolvedModuleId),
                'home' => '/control-center/home',
            ]
        );
    }

    /**
     * @param array<string, mixed> $snapshot
     * @param array<string, mixed> $data
     * @param array<string, string> $links
     * @return array<string, mixed>
     */
    private function buildResponse(
        string $profileId,
        string $generatedAt,
        string $resource,
        ?string $resourceId,
        string $mode,
        array $snapshot,
        array $data,
        array $links
    ): array {
        return [
            'control_center_api_schema_version' => 1,
            'kind' => 'control-center-api-response',
            'profile_id' => $profileId,
            'generated_at' => $generatedAt,
            'resource' => $resource,
            'resource_id' => $resourceId,
            'mode' => $mode,
            'etag' => $this->buildEtag($profileId, $resource, $resourceId, $generatedAt, $data),
            'capabilities' => [
                'read-state',
                'read-persisted-snapshot',
            ],
            'snapshot' => $snapshot,
            'data' => $data,
            'links' => $links,
            'errors' => [],
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildEtag(string $profileId, string $resource, ?string $resourceId, string $generatedAt, array $data): string
    {
        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            $encoded = $resource . '|' . ($resourceId ?? '') . '|' . $generatedAt;
        }

        return sha1($profileId . '|' . $resource . '|' . ($resourceId ?? '') . '|' . $generatedAt . '|' . $encoded);
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
