<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

final class ControlCenterGnomeLauncherToolkit
{
    private ControlCenterGnomeIntegrationToolkit $integrationToolkit;

    public function __construct(private readonly string $rootDir)
    {
        $this->integrationToolkit = new ControlCenterGnomeIntegrationToolkit($rootDir);
    }

    /**
     * @return array<string, mixed>
     */
    public function createLauncherBundle(
        string $profileId,
        ?string $snapshotDir = null,
        ?string $uiBundleDir = null
    ): array {
        $integrationMap = $this->integrationToolkit->createIntegrationMap($profileId, $snapshotDir, $uiBundleDir);
        $home = is_array($integrationMap['home'] ?? null) ? $integrationMap['home'] : [];
        $modules = is_array($integrationMap['modules'] ?? null) ? $integrationMap['modules'] : [];

        $launchers = [
            $this->buildHomeLauncher($profileId, $home),
        ];
        $pendingModules = [];

        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $primaryEntrypoint = is_array($module['primary_entrypoint'] ?? null) ? $module['primary_entrypoint'] : [];
            $command = trim((string) ($primaryEntrypoint['command'] ?? ''));
            $strategy = (string) ($module['integration_strategy'] ?? 'augment');

            if ($command === '' || $strategy === 'w4-surface') {
                $pendingModules[] = [
                    'id' => (string) ($module['id'] ?? 'module'),
                    'title' => (string) ($module['title'] ?? 'Modulo'),
                    'integration_strategy' => $strategy,
                    'reason' => $strategy === 'w4-surface' ? 'w4-surface-pending' : 'no-entrypoint',
                    'reference_html_path' => (string) ($module['reference_html_path'] ?? ''),
                    'w4_api_resource' => (string) ($module['w4_api_resource'] ?? ''),
                ];
                continue;
            }

            $launchers[] = $this->buildModuleLauncher($profileId, $module, $command);
        }

        return [
            'control_center_gnome_launchers_schema_version' => 1,
            'kind' => 'control-center-gnome-launchers',
            'profile_id' => $profileId,
            'generated_at' => (string) ($integrationMap['generated_at'] ?? gmdate('c')),
            'strategy' => (string) ($integrationMap['strategy'] ?? 'gnome-augmented'),
            'ui_bundle_role' => (string) ($integrationMap['ui_bundle_role'] ?? 'reference-only'),
            'home' => $home,
            'launchers' => $launchers,
            'pending_modules' => $pendingModules,
            'guidelines' => [
                'install-launchers-under-usr-share-applications',
                'reuse-gnome-entrypoints-when-available',
                'keep-w4-surfaces-outside-gnome-launchers-until-stable',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $bundle
     */
    public function renderSummaryText(array $bundle): string
    {
        $launchers = is_array($bundle['launchers'] ?? null) ? $bundle['launchers'] : [];
        $pendingModules = is_array($bundle['pending_modules'] ?? null) ? $bundle['pending_modules'] : [];

        $lines = [
            sprintf('Control Center launchers · %s', (string) ($bundle['profile_id'] ?? 'unknown')),
            sprintf('Strategy: %s', (string) ($bundle['strategy'] ?? 'gnome-augmented')),
            sprintf('Launchers: %d', count($launchers)),
            sprintf('Pending modules: %d', count($pendingModules)),
            '',
        ];

        foreach ($launchers as $launcher) {
            if (!is_array($launcher)) {
                continue;
            }

            $lines[] = sprintf(
                '- %s -> %s',
                (string) ($launcher['desktop_file'] ?? 'launcher.desktop'),
                (string) ($launcher['exec'] ?? '')
            );
        }

        if ($pendingModules !== []) {
            $lines[] = '';
            $lines[] = 'Pending modules:';

            foreach ($pendingModules as $module) {
                if (!is_array($module)) {
                    continue;
                }

                $lines[] = sprintf(
                    '- %s -> %s',
                    (string) ($module['title'] ?? $module['id'] ?? 'Modulo'),
                    (string) ($module['reason'] ?? 'pending')
                );
            }
        }

        return rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $launcher
     */
    public function renderDesktopEntry(array $launcher): string
    {
        $lines = [
            '[Desktop Entry]',
            'Type=Application',
            sprintf('Version=%s', (string) ($launcher['version'] ?? '1.0')),
            sprintf('Name=%s', (string) ($launcher['name'] ?? 'W4 Settings')),
            sprintf('Comment=%s', (string) ($launcher['comment'] ?? 'Open W4 Settings entrypoint')),
            sprintf('Exec=%s', (string) ($launcher['exec'] ?? 'gnome-control-center')),
            sprintf('Icon=%s', (string) ($launcher['icon'] ?? 'org.gnome.Settings')),
            sprintf('Categories=%s', (string) ($launcher['categories'] ?? 'Settings;System;')),
            sprintf('Keywords=%s', implode(';', $this->stringList($launcher['keywords'] ?? [])) . ';'),
            sprintf('StartupNotify=%s', !empty($launcher['startup_notify']) ? 'true' : 'false'),
            'Terminal=false',
        ];

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $home
     * @return array<string, mixed>
     */
    private function buildHomeLauncher(string $profileId, array $home): array
    {
        return [
            'id' => 'home',
            'desktop_file' => sprintf('%s.desktop', $this->launcherId($profileId, 'home')),
            'name' => 'W4 Settings',
            'comment' => (string) ($home['summary'] ?? 'Open W4 Settings entrypoint on GNOME'),
            'exec' => 'gnome-control-center',
            'icon' => 'org.gnome.Settings',
            'categories' => 'Settings;System;',
            'keywords' => ['w4', 'settings', 'control-center', 'gnome'],
            'startup_notify' => true,
            'module_id' => 'home',
            'strategy' => 'delegate',
            'api_resource' => (string) ($home['api_resource'] ?? '/control-center/home'),
            'reference_html_path' => (string) ($home['reference_html_path'] ?? ''),
            'version' => '1.0',
        ];
    }

    /**
     * @param array<string, mixed> $module
     * @return array<string, mixed>
     */
    private function buildModuleLauncher(string $profileId, array $module, string $command): array
    {
        $moduleId = (string) ($module['id'] ?? 'module');
        $title = (string) ($module['title'] ?? ucfirst($moduleId));

        return [
            'id' => $moduleId,
            'desktop_file' => sprintf('%s.desktop', $this->launcherId($profileId, $moduleId)),
            'name' => sprintf('W4 Settings · %s', $title),
            'comment' => (string) ($module['status_reason'] ?? $title),
            'exec' => $command,
            'icon' => $this->iconForModule($moduleId),
            'categories' => 'Settings;System;',
            'keywords' => ['w4', 'settings', $moduleId, 'gnome'],
            'startup_notify' => true,
            'module_id' => $moduleId,
            'strategy' => (string) ($module['integration_strategy'] ?? 'augment'),
            'api_resource' => (string) ($module['w4_api_resource'] ?? sprintf('/control-center/modules/%s', $moduleId)),
            'reference_html_path' => (string) ($module['reference_html_path'] ?? ''),
            'version' => '1.0',
        ];
    }

    private function launcherId(string $profileId, string $moduleId): string
    {
        return sprintf(
            'w4-control-center-%s-%s',
            str_replace('w4-os-', '', $profileId),
            preg_replace('/[^a-z0-9]+/i', '-', strtolower($moduleId)) ?? strtolower($moduleId)
        );
    }

    private function iconForModule(string $moduleId): string
    {
        return match ($moduleId) {
            'system' => 'org.gnome.Settings',
            'security' => 'org.gnome.Settings',
            'updates' => 'org.gnome.Software',
            default => 'org.gnome.Settings',
        };
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
