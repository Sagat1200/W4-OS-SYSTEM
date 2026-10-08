<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

use W4\OS\Support\ValidationError;

final class ControlCenterHomeRenderer
{
    /**
     * @param array<string, mixed> $payload
     */
    public function encodeJsonPayload(array $payload): string
    {
        $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            throw new ValidationError('No se pudo serializar el payload de Control Center');
        }

        return $encoded . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function renderTextPayload(array $payload): string
    {
        $kind = (string) ($payload['kind'] ?? '');

        return match ($kind) {
            'control-center-home-model' => $this->renderHomeText($payload),
            'control-center-module-detail' => $this->renderModuleDetailText($payload),
            'control-center-snapshot-home-view' => $this->renderSnapshotHomeView($payload),
            'control-center-snapshot-module-view' => $this->renderSnapshotModuleView($payload),
            default => throw new ValidationError(sprintf('Payload de Control Center no soportado: %s', $kind)),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderSnapshotHomeView(array $payload): string
    {
        $snapshot = is_array($payload['snapshot'] ?? null) ? $payload['snapshot'] : [];
        $home = is_array($payload['home'] ?? null) ? $payload['home'] : [];

        $lines = [
            sprintf(
                'Snapshot · manifest=%s · modulos=%d',
                (string) ($snapshot['manifest_file'] ?? 'control-center-snapshot.json'),
                count(is_array($snapshot['module_ids'] ?? null) ? $snapshot['module_ids'] : [])
            ),
            '',
        ];

        return implode(PHP_EOL, $lines) . $this->renderHomeText($home);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderSnapshotModuleView(array $payload): string
    {
        $snapshot = is_array($payload['snapshot'] ?? null) ? $payload['snapshot'] : [];
        $module = is_array($payload['module'] ?? null) ? $payload['module'] : [];
        $detail = is_array($payload['detail'] ?? null) ? $payload['detail'] : [];

        $moduleDetailPayload = [
            'profile_id' => (string) ($payload['profile_id'] ?? 'unknown'),
            'module' => $module,
            'detail' => $detail,
        ];

        $lines = [
            sprintf(
                'Snapshot · manifest=%s · modulo=%s',
                (string) ($snapshot['manifest_file'] ?? 'control-center-snapshot.json'),
                (string) ($module['id'] ?? 'unknown')
            ),
            '',
        ];

        return implode(PHP_EOL, $lines) . $this->renderModuleDetailText($moduleDetailPayload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderHomeText(array $payload): string
    {
        $summary = is_array($payload['summary'] ?? null) ? $payload['summary'] : [];
        $hero = is_array($payload['hero'] ?? null) ? $payload['hero'] : [];
        $modules = is_array($payload['modules'] ?? null) ? $payload['modules'] : [];

        $lines = [
            sprintf('Settings · %s', (string) ($payload['profile_id'] ?? 'unknown')),
            (string) ($hero['summary'] ?? ''),
            sprintf(
                'Resumen: total=%d, healthy=%d, attention=%d, unknown=%d, deep-links=%d',
                (int) ($summary['modules_total'] ?? 0),
                (int) ($summary['healthy_modules'] ?? 0),
                (int) ($summary['attention_modules'] ?? 0),
                (int) ($summary['unknown_modules'] ?? 0),
                (int) ($summary['deep_links_available'] ?? 0)
            ),
            '',
        ];

        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $entrypoints = is_array($module['entrypoints'] ?? null) ? $module['entrypoints'] : [];
            $highlights = is_array($module['highlights'] ?? null) ? $module['highlights'] : [];
            $lines[] = sprintf(
                '- %s [%s] (%s)',
                (string) ($module['title'] ?? $module['id'] ?? 'Modulo'),
                strtoupper((string) ($module['status'] ?? 'unknown')),
                (string) ($module['class'] ?? 'W4-augmented')
            );
            $lines[] = sprintf('  %s', (string) ($module['summary'] ?? ''));
            $lines[] = sprintf('  Motivo: %s', (string) ($module['status_reason'] ?? ''));
            $lines[] = sprintf('  Highlights: %s', $this->renderKeyValueInline($highlights));
            $lines[] = sprintf('  Entrypoints: %d', count($entrypoints));
            $lines[] = '';
        }

        return rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function renderModuleDetailText(array $payload): string
    {
        $module = is_array($payload['module'] ?? null) ? $payload['module'] : [];
        $detail = is_array($payload['detail'] ?? null) ? $payload['detail'] : [];
        $highlights = is_array($module['highlights'] ?? null) ? $module['highlights'] : [];
        $sourceOfTruth = is_array($module['source_of_truth'] ?? null) ? $module['source_of_truth'] : [];
        $entrypoints = is_array($module['entrypoints'] ?? null) ? $module['entrypoints'] : [];
        $actions = is_array($module['actions'] ?? null) ? $module['actions'] : [];

        $lines = [
            sprintf(
                'Settings · %s · %s',
                (string) ($payload['profile_id'] ?? 'unknown'),
                (string) ($module['title'] ?? $module['id'] ?? 'Modulo')
            ),
            sprintf(
                'Estado: %s | Clase: %s | Modo: %s',
                strtoupper((string) ($module['status'] ?? 'unknown')),
                (string) ($module['class'] ?? 'W4-augmented'),
                (string) ($module['mode'] ?? 'read-first')
            ),
            sprintf('Resumen: %s', (string) ($module['summary'] ?? '')),
            sprintf('Motivo: %s', (string) ($detail['status_reason'] ?? '')),
            sprintf('Authority: %s', (string) ($module['authority'] ?? '')),
            sprintf('Capabilities: %s', $this->renderListInline($module['capabilities'] ?? [])),
            sprintf('Highlights: %s', $this->renderKeyValueInline($highlights)),
            'Source of truth:',
        ];

        foreach ($sourceOfTruth as $source) {
            $lines[] = sprintf('  - %s', (string) $source);
        }

        $lines[] = 'Entrypoints:';
        if ($entrypoints === []) {
            $lines[] = '  - ninguno';
        } else {
            foreach ($entrypoints as $entrypoint) {
                if (!is_array($entrypoint)) {
                    continue;
                }

                $lines[] = sprintf(
                    '  - %s -> %s',
                    (string) ($entrypoint['label'] ?? 'Entrypoint'),
                    (string) ($entrypoint['command'] ?? '')
                );
            }
        }

        $lines[] = 'Actions:';
        if ($actions === []) {
            $lines[] = '  - ninguna';
        } else {
            foreach ($actions as $action) {
                $lines[] = sprintf('  - %s', (string) $action);
            }
        }

        return rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
    }

    /**
     * @param mixed $value
     */
    private function renderListInline(mixed $value): string
    {
        if (!is_array($value) || $value === []) {
            return 'ninguna';
        }

        $normalized = [];
        foreach ($value as $item) {
            if (!is_scalar($item)) {
                continue;
            }

            $normalized[] = (string) $item;
        }

        return $normalized === [] ? 'ninguna' : implode(', ', $normalized);
    }

    /**
     * @param array<string, mixed> $values
     */
    private function renderKeyValueInline(array $values): string
    {
        if ($values === []) {
            return 'sin evidencia destacada';
        }

        $parts = [];
        foreach ($values as $key => $value) {
            if (is_bool($value)) {
                $rendered = $value ? 'true' : 'false';
            } elseif (is_scalar($value)) {
                $rendered = (string) $value;
            } else {
                continue;
            }

            $parts[] = sprintf('%s=%s', $key, $rendered);
        }

        return $parts === [] ? 'sin evidencia destacada' : implode(', ', $parts);
    }
}
