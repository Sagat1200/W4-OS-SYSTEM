<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

final class ControlCenterGnomeIntegrationToolkit
{
    private ControlCenterApiToolkit $apiToolkit;

    public function __construct(private readonly string $rootDir)
    {
        $this->apiToolkit = new ControlCenterApiToolkit($rootDir);
    }

    /**
     * @return array<string, mixed>
     */
    public function createIntegrationMap(
        string $profileId,
        ?string $snapshotDir = null,
        ?string $uiBundleDir = null
    ): array {
        $homeResponse = $this->apiToolkit->createHomeResponse($profileId, $snapshotDir);
        $homeData = is_array($homeResponse['data'] ?? null) ? $homeResponse['data'] : [];
        $modules = is_array($homeData['modules'] ?? null) ? $homeData['modules'] : [];
        $summary = is_array($homeData['summary'] ?? null) ? $homeData['summary'] : [];
        $hero = is_array($homeData['hero'] ?? null) ? $homeData['hero'] : [];

        $resolvedUiBundleDir = $uiBundleDir ?? $this->defaultUiBundleDir($profileId);
        $routes = [
            'home' => 'index.html',
            'modules' => [],
        ];

        $moduleMaps = [];
        foreach ($modules as $moduleSummary) {
            if (!is_array($moduleSummary)) {
                continue;
            }

            $moduleId = trim((string) ($moduleSummary['id'] ?? ''));
            if ($moduleId === '') {
                continue;
            }

            $moduleResponse = $this->apiToolkit->createModuleResponse($profileId, $moduleId, $snapshotDir);
            $moduleData = is_array($moduleResponse['data'] ?? null) ? $moduleResponse['data'] : [];
            $module = is_array($moduleData['module'] ?? null) ? $moduleData['module'] : [];
            $detail = is_array($moduleData['detail'] ?? null) ? $moduleData['detail'] : [];
            $entrypoints = is_array($module['entrypoints'] ?? null) ? $module['entrypoints'] : [];
            $primaryEntrypoint = $this->primaryEntrypoint($entrypoints);
            $strategy = $this->resolveIntegrationStrategy((string) ($module['class'] ?? ''), $entrypoints);

            $routes['modules'][$moduleId] = sprintf('modules/%s.html', $moduleId);

            $moduleMaps[] = [
                'id' => $moduleId,
                'title' => (string) ($module['title'] ?? $moduleId),
                'class' => (string) ($module['class'] ?? 'W4-augmented'),
                'integration_strategy' => $strategy,
                'status' => (string) ($module['status'] ?? 'unknown'),
                'status_reason' => (string) ($module['status_reason'] ?? ''),
                'authority' => (string) ($module['authority'] ?? ''),
                'recommended_surface' => $this->recommendedSurface($strategy, $primaryEntrypoint),
                'primary_entrypoint' => $primaryEntrypoint,
                'entrypoint_count' => count($entrypoints),
                'available_entrypoints' => $entrypoints,
                'source_of_truth' => $this->stringList($module['source_of_truth'] ?? []),
                'w4_api_resource' => (string) ($moduleResponse['links']['self'] ?? sprintf('/control-center/modules/%s', $moduleId)),
                'reference_html_path' => $this->relativePath($resolvedUiBundleDir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $moduleId . '.html'),
                'detail' => [
                    'source_count' => (int) ($detail['source_count'] ?? 0),
                    'entrypoint_count' => (int) ($detail['entrypoint_count'] ?? count($entrypoints)),
                    'action_count' => (int) ($detail['action_count'] ?? 0),
                ],
            ];
        }

        return [
            'control_center_gnome_integration_schema_version' => 1,
            'kind' => 'control-center-gnome-integration',
            'profile_id' => $profileId,
            'generated_at' => (string) ($homeResponse['generated_at'] ?? gmdate('c')),
            'strategy' => 'gnome-augmented',
            'ui_bundle_role' => 'reference-only',
            'home' => [
                'title' => (string) ($hero['title'] ?? 'Settings'),
                'summary' => (string) ($hero['summary'] ?? ''),
                'status_overview' => $summary,
                'api_resource' => (string) ($homeResponse['links']['self'] ?? '/control-center/home'),
                'reference_html_path' => $this->relativePath($resolvedUiBundleDir . DIRECTORY_SEPARATOR . 'index.html'),
            ],
            'modules' => $moduleMaps,
            'routes' => $routes,
            'guidelines' => [
                'delegate_upstream_when_sufficient',
                'use_w4_read_model_for_policy_and_evidence',
                'keep_html_bundle_as_reference_not_official_frontend',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $integrationMap
     */
    public function renderSummaryText(array $integrationMap): string
    {
        $home = is_array($integrationMap['home'] ?? null) ? $integrationMap['home'] : [];
        $modules = is_array($integrationMap['modules'] ?? null) ? $integrationMap['modules'] : [];

        $lines = [
            sprintf('Control Center · %s · GNOME-augmented', (string) ($integrationMap['profile_id'] ?? 'unknown')),
            sprintf('Bundle HTML: %s', (string) ($integrationMap['ui_bundle_role'] ?? 'reference-only')),
            sprintf('Home API: %s', (string) ($home['api_resource'] ?? '/control-center/home')),
            sprintf('Home HTML: %s', (string) ($home['reference_html_path'] ?? 'build/control-center-ui/.../index.html')),
            '',
        ];

        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $lines[] = sprintf(
                '- %s [%s] -> %s',
                (string) ($module['title'] ?? $module['id'] ?? 'Modulo'),
                strtoupper((string) ($module['status'] ?? 'unknown')),
                (string) ($module['integration_strategy'] ?? 'augment')
            );

            $primaryEntrypoint = is_array($module['primary_entrypoint'] ?? null) ? $module['primary_entrypoint'] : [];
            if ($primaryEntrypoint !== []) {
                $lines[] = sprintf(
                    '  Entrypoint principal: %s',
                    (string) ($primaryEntrypoint['command'] ?? '')
                );
            } else {
                $lines[] = '  Entrypoint principal: ninguno';
            }

            $lines[] = sprintf('  API: %s', (string) ($module['w4_api_resource'] ?? ''));
            $lines[] = sprintf('  HTML: %s', (string) ($module['reference_html_path'] ?? ''));
            $lines[] = '';
        }

        return rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
    }

    /**
     * @param list<array<string, mixed>> $entrypoints
     * @return array<string, mixed>|null
     */
    private function primaryEntrypoint(array $entrypoints): ?array
    {
        foreach ($entrypoints as $entrypoint) {
            if (is_array($entrypoint)) {
                return $entrypoint;
            }
        }

        return null;
    }

    /**
     * @param list<array<string, mixed>> $entrypoints
     */
    private function resolveIntegrationStrategy(string $class, array $entrypoints): string
    {
        return match (true) {
            $class === 'GNOME-native' => 'delegate',
            $entrypoints !== [] => 'augment',
            default => 'w4-surface',
        };
    }

    /**
     * @param array<string, mixed>|null $primaryEntrypoint
     */
    private function recommendedSurface(string $strategy, ?array $primaryEntrypoint): string
    {
        return match ($strategy) {
            'delegate' => 'gnome-control-center',
            'augment' => is_string($primaryEntrypoint['kind'] ?? null) ? (string) $primaryEntrypoint['kind'] : 'gnome-control-center',
            default => 'w4-native-surface',
        };
    }

    private function defaultUiBundleDir(string $profileId): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-ui' . DIRECTORY_SEPARATOR . $profileId;
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

    private function relativePath(string $path): string
    {
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        $normalizedRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->rootDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if (str_starts_with($normalizedPath, $normalizedRoot)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($normalizedPath, strlen($normalizedRoot)));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $normalizedPath);
    }
}
