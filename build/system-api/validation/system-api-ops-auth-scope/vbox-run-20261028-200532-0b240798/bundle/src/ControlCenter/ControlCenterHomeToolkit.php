<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

use W4\OS\ControlCenter\ControlCenterSliceAToolkit;
use W4\OS\Support\ValidationError;

final class ControlCenterHomeToolkit
{
    private ControlCenterSliceAToolkit $sliceAToolkit;

    public function __construct(private readonly string $rootDir)
    {
        $this->sliceAToolkit = new ControlCenterSliceAToolkit($rootDir);
    }

    /**
     * @return array<string, mixed>
     */
    public function createHomeModel(string $profileId): array
    {
        $sliceModel = $this->sliceAToolkit->createReadModel($profileId);
        $modules = is_array($sliceModel['modules'] ?? null) ? $sliceModel['modules'] : [];

        $cards = [];
        $summary = [
            'modules_total' => 0,
            'healthy_modules' => 0,
            'attention_modules' => 0,
            'unknown_modules' => 0,
            'deep_links_available' => 0,
        ];

        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $card = $this->buildModuleCard($module);
            $cards[] = $card;
            $summary['modules_total']++;
            $summary[$card['status'] . '_modules']++;
            $summary['deep_links_available'] += count($card['entrypoints']);
        }

        return [
            'control_center_home_schema_version' => 1,
            'kind' => 'control-center-home-model',
            'profile_id' => $profileId,
            'generated_at' => (string) ($sliceModel['generated_at'] ?? gmdate('c')),
            'mode' => 'read-first',
            'hero' => [
                'title' => 'Settings',
                'summary' => $this->buildHeroSummary($profileId, $summary),
            ],
            'summary' => $summary,
            'modules' => $cards,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createModuleDetailModel(string $profileId, string $moduleId): array
    {
        $homeModel = $this->createHomeModel($profileId);
        $module = $this->findModule($homeModel, $moduleId);
        if ($module === null) {
            throw new ValidationError(sprintf('Modulo de Control Center no soportado: %s', $moduleId));
        }

        $entrypoints = $this->entrypointList($module);
        $sourceOfTruth = $this->stringList($module['source_of_truth'] ?? []);
        $actions = $this->stringList($module['actions'] ?? []);

        return [
            'control_center_module_schema_version' => 1,
            'kind' => 'control-center-module-detail',
            'profile_id' => $profileId,
            'generated_at' => (string) ($homeModel['generated_at'] ?? gmdate('c')),
            'mode' => 'read-first',
            'module' => $module,
            'detail' => [
                'status_reason' => (string) ($module['status_reason'] ?? ''),
                'recommended_entrypoint' => $entrypoints[0] ?? null,
                'evidence_keys' => array_keys(is_array($module['highlights'] ?? null) ? $module['highlights'] : []),
                'source_count' => count($sourceOfTruth),
                'entrypoint_count' => count($entrypoints),
                'action_count' => count($actions),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $module
     * @return array<string, mixed>
     */
    private function buildModuleCard(array $module): array
    {
        $moduleId = (string) ($module['id'] ?? 'unknown');
        $evidence = is_array($module['evidence'] ?? null) ? $module['evidence'] : [];
        $entrypoints = $this->buildEntrypoints($moduleId);
        $status = $this->resolveStatus($moduleId, $evidence);
        $statusReason = $this->resolveStatusReason($moduleId, $evidence, $status);

        return [
            'id' => $moduleId,
            'title' => (string) ($module['title'] ?? $moduleId),
            'class' => (string) ($module['class'] ?? 'W4-augmented'),
            'status' => $status,
            'status_reason' => $statusReason,
            'mode' => 'read-first',
            'summary' => (string) ($module['summary'] ?? ''),
            'authority' => (string) ($module['authority'] ?? ''),
            'capabilities' => $this->stringList($module['capabilities'] ?? []),
            'last_checked_at' => (string) ($module['last_checked_at'] ?? ''),
            'source_of_truth' => $this->stringList($module['source_of_truth'] ?? []),
            'badges' => $this->buildBadges($module, $status, $entrypoints),
            'highlights' => $this->buildHighlights($moduleId, $evidence),
            'entrypoints' => $entrypoints,
            'actions' => $this->stringList($module['actions'] ?? []),
        ];
    }

    /**
     * @param array<string, mixed> $evidence
     */
    private function resolveStatusReason(string $moduleId, array $evidence, string $status): string
    {
        return match ($moduleId) {
            'system' => $status === 'healthy'
                ? 'Shell, sesion y display manager ya quedaron materializados.'
                : 'Falta evidencia suficiente de shell o display manager efectivos.',
            'security' => match ($status) {
                'healthy' => sprintf(
                    'La baseline materializada no declara gaps y registra %d controles implementados.',
                    (int) ($evidence['implemented_controls'] ?? 0)
                ),
                'attention' => sprintf(
                    'La baseline todavia declara %d gaps pendientes.',
                    (int) ($evidence['gap_controls'] ?? 0)
                ),
                default => 'No hay baseline suficiente para determinar el estado de seguridad.',
            },
            'updates' => match ($status) {
                'healthy' => sprintf(
                    'La ultima operacion conocida ya quedo confirmada hacia %s.',
                    (string) ($evidence['target_version'] ?? 'la version objetivo')
                ),
                'attention' => sprintf(
                    'La operacion de update sigue en etapa %s y necesita revision.',
                    (string) ($evidence['stage'] ?? 'desconocida')
                ),
                default => 'No hay evidencia materializada de operaciones de update para este perfil.',
            },
            'storage' => match ($status) {
                'healthy' => sprintf(
                    'La politica y el perfil de instalacion convergen en %s con %d subvolumenes visibles.',
                    (string) ($evidence['root_filesystem'] ?? 'el layout esperado'),
                    (int) ($evidence['subvolume_count'] ?? 0)
                ),
                'attention' => 'La politica de almacenamiento existe, pero faltan subvolumenes o evidencia de layout suficiente.',
                default => 'No hay evidencia suficiente para confirmar el layout de almacenamiento.',
            },
            default => 'No existe razon de estado disponible para este modulo.',
        };
    }

    /**
     * @param array<string, mixed> $evidence
     * @return array<string, mixed>
     */
    private function buildHighlights(string $moduleId, array $evidence): array
    {
        return match ($moduleId) {
            'system' => [
                'shell' => (string) ($evidence['shell'] ?? ''),
                'display_manager' => (string) ($evidence['display_manager'] ?? ''),
                'hostname_prefix' => (string) ($evidence['hostname_prefix'] ?? ''),
            ],
            'security' => [
                'implemented_controls' => (int) ($evidence['implemented_controls'] ?? 0),
                'gap_controls' => (int) ($evidence['gap_controls'] ?? 0),
                'firewall_backend' => (string) ($evidence['firewall_backend'] ?? ''),
            ],
            'updates' => [
                'stage' => (string) ($evidence['stage'] ?? ''),
                'target_version' => (string) ($evidence['target_version'] ?? ''),
                'channel' => (string) ($evidence['channel'] ?? ''),
            ],
            'storage' => [
                'root_filesystem' => (string) ($evidence['root_filesystem'] ?? ''),
                'encryption_default' => (string) ($evidence['encryption_default'] ?? ''),
                'subvolume_count' => (int) ($evidence['subvolume_count'] ?? 0),
            ],
            default => [],
        };
    }

    /**
     * @param array<string, mixed> $module
     * @param list<array<string, string>> $entrypoints
     * @return list<string>
     */
    private function buildBadges(array $module, string $status, array $entrypoints): array
    {
        $badges = [
            (string) ($module['class'] ?? 'W4-augmented'),
            'Solo lectura',
        ];

        if ($entrypoints !== []) {
            $badges[] = 'Deep-link';
        }

        $badges[] = match ($status) {
            'healthy' => 'Evidencia consistente',
            'attention' => 'Revisar estado',
            default => 'Evidencia parcial',
        };

        return $badges;
    }

    /**
     * @param array<string, mixed> $evidence
     */
    private function resolveStatus(string $moduleId, array $evidence): string
    {
        return match ($moduleId) {
            'system' => $this->resolveSystemStatus($evidence),
            'security' => $this->resolveSecurityStatus($evidence),
            'updates' => $this->resolveUpdatesStatus($evidence),
            'storage' => $this->resolveStorageStatus($evidence),
            default => 'unknown',
        };
    }

    /**
     * @param array<string, mixed> $evidence
     */
    private function resolveSystemStatus(array $evidence): string
    {
        $shell = (string) ($evidence['shell'] ?? '');
        $displayManager = (string) ($evidence['display_manager'] ?? '');

        if ($shell === '' || $shell === 'unknown' || $displayManager === '' || $displayManager === 'unknown') {
            return 'attention';
        }

        return 'healthy';
    }

    /**
     * @param array<string, mixed> $evidence
     */
    private function resolveSecurityStatus(array $evidence): string
    {
        $implemented = (int) ($evidence['implemented_controls'] ?? 0);
        $gap = (int) ($evidence['gap_controls'] ?? 0);

        if ($implemented === 0 && $gap === 0) {
            return 'unknown';
        }

        return $gap > 0 ? 'attention' : 'healthy';
    }

    /**
     * @param array<string, mixed> $evidence
     */
    private function resolveUpdatesStatus(array $evidence): string
    {
        $stage = (string) ($evidence['stage'] ?? '');

        if ($stage === '') {
            return 'unknown';
        }

        return match ($stage) {
            'confirmed' => 'healthy',
            'failed', 'pending_health' => 'attention',
            default => 'attention',
        };
    }

    /**
     * @param array<string, mixed> $evidence
     */
    private function resolveStorageStatus(array $evidence): string
    {
        $rootFilesystem = (string) ($evidence['root_filesystem'] ?? '');
        $subvolumeCount = (int) ($evidence['subvolume_count'] ?? 0);

        if ($rootFilesystem === '' || $rootFilesystem === 'unknown') {
            return 'unknown';
        }

        return $subvolumeCount > 0 ? 'healthy' : 'attention';
    }

    /**
     * @return list<array<string, string>>
     */
    private function buildEntrypoints(string $moduleId): array
    {
        return match ($moduleId) {
            'system' => [
                $this->buildEntrypoint(
                    'gnome-info-overview',
                    'Informacion del sistema',
                    'gnome-control-center',
                    'info-overview',
                    'gnome-control-center info-overview'
                ),
                $this->buildEntrypoint(
                    'gnome-region',
                    'Idioma y region',
                    'gnome-control-center',
                    'region',
                    'gnome-control-center region'
                ),
                $this->buildEntrypoint(
                    'gnome-datetime',
                    'Fecha y hora',
                    'gnome-control-center',
                    'datetime',
                    'gnome-control-center datetime'
                ),
            ],
            'security' => [
                $this->buildEntrypoint(
                    'gnome-privacy',
                    'Privacidad',
                    'gnome-control-center',
                    'privacy',
                    'gnome-control-center privacy'
                ),
                $this->buildEntrypoint(
                    'gnome-sharing',
                    'Compartir',
                    'gnome-control-center',
                    'sharing',
                    'gnome-control-center sharing'
                ),
            ],
            'updates' => [
                $this->buildEntrypoint(
                    'gnome-software-updates',
                    'Actualizaciones',
                    'gnome-software',
                    'updates',
                    'gnome-software --mode=updates'
                ),
            ],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    private function buildEntrypoint(
        string $id,
        string $label,
        string $kind,
        string $target,
        string $command
    ): array {
        return [
            'id' => $id,
            'label' => $label,
            'kind' => $kind,
            'target' => $target,
            'command' => $command,
        ];
    }

    /**
     * @param array<string, int> $summary
     */
    private function buildHeroSummary(string $profileId, array $summary): string
    {
        if ($summary['attention_modules'] > 0) {
            return sprintf(
                '%s expone %d modulos; %d requieren atencion y %d siguen con evidencia parcial.',
                $profileId,
                $summary['modules_total'],
                $summary['attention_modules'],
                $summary['unknown_modules']
            );
        }

        if ($summary['unknown_modules'] > 0) {
            return sprintf(
                '%s expone %d modulos; %d ya tienen evidencia consistente y %d aun no materializan estado suficiente.',
                $profileId,
                $summary['modules_total'],
                $summary['healthy_modules'],
                $summary['unknown_modules']
            );
        }

        return sprintf(
            '%s expone %d modulos con evidencia consistente y %d deep-links listos para GNOME.',
            $profileId,
            $summary['modules_total'],
            $summary['deep_links_available']
        );
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

        $normalized = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $string = trim((string) $item);
            if ($string === '') {
                continue;
            }

            $normalized[] = $string;
        }

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<string, mixed> $homeModel
     * @return array<string, mixed>|null
     */
    private function findModule(array $homeModel, string $moduleId): ?array
    {
        $modules = is_array($homeModel['modules'] ?? null) ? $homeModel['modules'] : [];
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
     * @param array<string, mixed> $module
     * @return list<array<string, string>>
     */
    private function entrypointList(array $module): array
    {
        $entrypoints = $module['entrypoints'] ?? [];
        if (!is_array($entrypoints)) {
            return [];
        }

        $normalized = [];
        foreach ($entrypoints as $entrypoint) {
            if (!is_array($entrypoint)) {
                continue;
            }

            $id = trim((string) ($entrypoint['id'] ?? ''));
            $label = trim((string) ($entrypoint['label'] ?? ''));
            $kind = trim((string) ($entrypoint['kind'] ?? ''));
            $target = trim((string) ($entrypoint['target'] ?? ''));
            $command = trim((string) ($entrypoint['command'] ?? ''));

            if ($id === '' || $label === '' || $command === '') {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'label' => $label,
                'kind' => $kind,
                'target' => $target,
                'command' => $command,
            ];
        }

        return $normalized;
    }
}
