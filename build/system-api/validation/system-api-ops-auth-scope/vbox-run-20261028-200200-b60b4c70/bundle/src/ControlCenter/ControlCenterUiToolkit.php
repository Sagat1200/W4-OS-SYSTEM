<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

final class ControlCenterUiToolkit
{
    private ControlCenterApiToolkit $apiToolkit;

    public function __construct(string $rootDir)
    {
        $this->apiToolkit = new ControlCenterApiToolkit($rootDir);
    }

    /**
     * @return array<string, mixed>
     */
    public function createHomePage(string $profileId, ?string $snapshotDir = null): array
    {
        $response = $this->apiToolkit->createHomeResponse($profileId, $snapshotDir);
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $summary = is_array($data['summary'] ?? null) ? $data['summary'] : [];
        $hero = is_array($data['hero'] ?? null) ? $data['hero'] : [];
        $modules = is_array($data['modules'] ?? null) ? $data['modules'] : [];
        $snapshot = is_array($response['snapshot'] ?? null) ? $response['snapshot'] : [];

        $cardsHtml = [];
        foreach ($modules as $module) {
            if (!is_array($module)) {
                continue;
            }

            $cardsHtml[] = $this->renderHomeCard($module);
        }

        $html = $this->renderDocument(
            title: sprintf('Settings · %s', $profileId),
            bodyClass: 'page-home',
            body: sprintf(
                '<main class="layout"><section class="hero"><p class="eyebrow">W4 OS · Control Center</p><p class="meta-line">Snapshot: %s</p><h1>%s</h1><p class="hero-summary">%s</p><div class="summary-grid">%s</div></section><section class="module-grid">%s</section></main>',
                $this->escape((string) ($snapshot['manifest_file'] ?? 'control-center-snapshot.json')),
                $this->escape((string) ($hero['title'] ?? 'Settings')),
                $this->escape((string) ($hero['summary'] ?? '')),
                $this->renderSummaryGrid($summary),
                implode('', $cardsHtml)
            )
        );

        return [
            'response' => $response,
            'html' => $html,
            'route' => 'index.html',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function createModulePage(string $profileId, string $moduleId, ?string $snapshotDir = null): array
    {
        $response = $this->apiToolkit->createModuleResponse($profileId, $moduleId, $snapshotDir);
        $data = is_array($response['data'] ?? null) ? $response['data'] : [];
        $module = is_array($data['module'] ?? null) ? $data['module'] : [];
        $detail = is_array($data['detail'] ?? null) ? $data['detail'] : [];
        $homeSummary = is_array($data['home_summary'] ?? null) ? $data['home_summary'] : [];
        $snapshot = is_array($response['snapshot'] ?? null) ? $response['snapshot'] : [];

        $resolvedModuleId = (string) ($module['id'] ?? $moduleId);
        $html = $this->renderDocument(
            title: sprintf('Settings · %s · %s', $profileId, (string) ($module['title'] ?? $resolvedModuleId)),
            bodyClass: 'page-module',
            body: sprintf(
                '<main class="layout"><nav class="breadcrumb"><a href="../index.html">Settings</a><span>/</span><span>%s</span></nav><section class="module-hero"><p class="eyebrow">%s</p><p class="meta-line">Snapshot: %s</p><h1>%s</h1><p class="hero-summary">%s</p><div class="status-pill status-%s">%s</div></section><section class="module-content"><div class="panel">%s</div><div class="panel">%s</div><div class="panel">%s</div></section><section class="panel panel-wide">%s</section></main>',
                $this->escape((string) ($module['title'] ?? $resolvedModuleId)),
                $this->escape((string) ($module['class'] ?? 'W4-augmented')),
                $this->escape((string) ($snapshot['module_json_path'] ?? sprintf('modules/%s.json', $resolvedModuleId))),
                $this->escape((string) ($module['title'] ?? $resolvedModuleId)),
                $this->escape((string) ($module['summary'] ?? '')),
                $this->escape((string) ($module['status'] ?? 'unknown')),
                $this->escape(strtoupper((string) ($module['status'] ?? 'unknown'))),
                $this->renderModuleFacts($module, $detail),
                $this->renderListPanel('Highlights', is_array($module['highlights'] ?? null) ? $module['highlights'] : []),
                $this->renderEntrypointsPanel(is_array($module['entrypoints'] ?? null) ? $module['entrypoints'] : []),
                $this->renderHomeSummaryPanel($homeSummary)
            )
        );

        return [
            'response' => $response,
            'html' => $html,
            'route' => sprintf('modules/%s.html', $resolvedModuleId),
        ];
    }

    public function renderStylesheet(): string
    {
        return str_replace(["\r\n", "\r"], "\n", <<<CSS
body {
  margin: 0;
  font-family: "Segoe UI", Arial, sans-serif;
  background: #0b1020;
  color: #e5e7eb;
}

a {
  color: #7dd3fc;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

.layout {
  max-width: 1200px;
  margin: 0 auto;
  padding: 32px 24px 56px;
}

.eyebrow {
  margin: 0 0 8px;
  color: #93c5fd;
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.hero,
.module-hero,
.panel,
.module-card {
  background: rgba(15, 23, 42, 0.9);
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 20px;
  box-shadow: 0 20px 45px rgba(2, 6, 23, 0.25);
}

.hero,
.module-hero {
  padding: 28px;
  margin-bottom: 24px;
}

.hero h1,
.module-hero h1 {
  margin: 0 0 12px;
  font-size: 36px;
}

.hero-summary {
  margin: 0;
  color: #cbd5e1;
  line-height: 1.5;
}

.summary-grid,
.module-content,
.module-grid {
  display: grid;
  gap: 16px;
}

.summary-grid {
  grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
  margin-top: 24px;
}

.summary-item,
.panel,
.module-card {
  padding: 20px;
}

.summary-value {
  display: block;
  font-size: 28px;
  font-weight: 700;
  margin-bottom: 4px;
}

.summary-label {
  color: #94a3b8;
  font-size: 13px;
}

.module-grid {
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

.module-card h2 {
  margin: 0 0 8px;
  font-size: 24px;
}

.module-card p {
  margin: 0 0 14px;
  color: #cbd5e1;
  line-height: 1.5;
}

.badge-row,
.tag-list {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-bottom: 14px;
}

.badge,
.tag {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  background: rgba(30, 41, 59, 0.95);
  color: #bfdbfe;
  border: 1px solid rgba(125, 211, 252, 0.24);
}

.status-pill {
  display: inline-flex;
  border-radius: 999px;
  padding: 8px 14px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.08em;
}

.status-healthy { background: rgba(22, 163, 74, 0.18); color: #86efac; }
.status-attention { background: rgba(245, 158, 11, 0.18); color: #fcd34d; }
.status-unknown { background: rgba(148, 163, 184, 0.18); color: #cbd5e1; }

.kv-list,
.bullet-list {
  margin: 0;
  padding-left: 18px;
  color: #cbd5e1;
}

.kv-list li,
.bullet-list li {
  margin-bottom: 8px;
}

.module-content {
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  margin-bottom: 16px;
}

.panel h3 {
  margin: 0 0 12px;
  font-size: 20px;
}

.panel-wide {
  margin-top: 16px;
}

.breadcrumb {
  display: flex;
  gap: 8px;
  align-items: center;
  margin-bottom: 16px;
  color: #94a3b8;
}

.meta-line {
  color: #94a3b8;
  font-size: 13px;
  margin-bottom: 12px;
}
CSS);
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function renderSummaryGrid(array $summary): string
    {
        $items = [
            'Total' => (int) ($summary['modules_total'] ?? 0),
            'Healthy' => (int) ($summary['healthy_modules'] ?? 0),
            'Attention' => (int) ($summary['attention_modules'] ?? 0),
            'Unknown' => (int) ($summary['unknown_modules'] ?? 0),
            'Deep-links' => (int) ($summary['deep_links_available'] ?? 0),
        ];

        $html = [];
        foreach ($items as $label => $value) {
            $html[] = sprintf(
                '<div class="summary-item"><span class="summary-value">%d</span><span class="summary-label">%s</span></div>',
                $value,
                $this->escape($label)
            );
        }

        return implode('', $html);
    }

    /**
     * @param array<string, mixed> $module
     */
    private function renderHomeCard(array $module): string
    {
        $moduleId = (string) ($module['id'] ?? 'module');
        $badges = $this->renderTagList($this->stringList($module['badges'] ?? []), 'badge');
        $highlights = $this->renderListPanel('', is_array($module['highlights'] ?? null) ? $module['highlights'] : [], false);

        return sprintf(
            '<article class="module-card"><div class="meta-line">%s</div><h2>%s</h2><div class="status-pill status-%s">%s</div><p>%s</p>%s%s<a href="modules/%s.html">Abrir modulo</a></article>',
            $this->escape((string) ($module['class'] ?? 'W4-augmented')),
            $this->escape((string) ($module['title'] ?? $moduleId)),
            $this->escape((string) ($module['status'] ?? 'unknown')),
            $this->escape(strtoupper((string) ($module['status'] ?? 'unknown'))),
            $this->escape((string) ($module['summary'] ?? '')),
            $badges,
            $highlights,
            rawurlencode($moduleId)
        );
    }

    /**
     * @param array<string, mixed> $module
     * @param array<string, mixed> $detail
     */
    private function renderModuleFacts(array $module, array $detail): string
    {
        $facts = [
            'Authority' => (string) ($module['authority'] ?? ''),
            'Status reason' => (string) ($module['status_reason'] ?? ''),
            'Source count' => (string) ($detail['source_count'] ?? ''),
            'Entrypoints' => (string) ($detail['entrypoint_count'] ?? ''),
            'Actions' => (string) ($detail['action_count'] ?? ''),
        ];

        return $this->renderListPanel('Overview', $facts);
    }

    /**
     * @param array<string, mixed> $homeSummary
     */
    private function renderHomeSummaryPanel(array $homeSummary): string
    {
        $hero = is_array($homeSummary['hero'] ?? null) ? $homeSummary['hero'] : [];
        $summary = is_array($homeSummary['summary'] ?? null) ? $homeSummary['summary'] : [];

        return sprintf(
            '<h3>Contexto</h3><p>%s</p>%s',
            $this->escape((string) ($hero['summary'] ?? '')),
            $this->renderListPanel('', $summary, false)
        );
    }

    /**
     * @param list<array<string, mixed>> $entrypoints
     */
    private function renderEntrypointsPanel(array $entrypoints): string
    {
        if ($entrypoints === []) {
            return '<h3>Entrypoints</h3><p>No hay deep-links disponibles.</p>';
        }

        $items = [];
        foreach ($entrypoints as $entrypoint) {
            if (!is_array($entrypoint)) {
                continue;
            }

            $items[] = sprintf(
                '<li><strong>%s</strong><br><span>%s</span></li>',
                $this->escape((string) ($entrypoint['label'] ?? 'Entrypoint')),
                $this->escape((string) ($entrypoint['command'] ?? ''))
            );
        }

        return sprintf('<h3>Entrypoints</h3><ul class="bullet-list">%s</ul>', implode('', $items));
    }

    /**
     * @param array<string, mixed> $items
     */
    private function renderListPanel(string $title, array $items, bool $withPanel = true): string
    {
        $content = [];
        foreach ($items as $label => $value) {
            if (is_bool($value)) {
                $rendered = $value ? 'true' : 'false';
            } elseif (is_scalar($value)) {
                $rendered = (string) $value;
            } else {
                continue;
            }

            $content[] = sprintf(
                '<li><strong>%s:</strong> %s</li>',
                $this->escape((string) $label),
                $this->escape($rendered)
            );
        }

        $inner = ($title !== '' ? sprintf('<h3>%s</h3>', $this->escape($title)) : '') .
            sprintf('<ul class="kv-list">%s</ul>', implode('', $content));

        return $withPanel ? $inner : $inner;
    }

    /**
     * @param list<string> $items
     */
    private function renderTagList(array $items, string $className): string
    {
        if ($items === []) {
            return '';
        }

        $html = [];
        foreach ($items as $item) {
            $html[] = sprintf('<span class="%s">%s</span>', $this->escape($className), $this->escape($item));
        }

        return sprintf('<div class="tag-list">%s</div>', implode('', $html));
    }

    private function renderDocument(string $title, string $bodyClass, string $body): string
    {
        return str_replace(["\r\n", "\r"], "\n", sprintf(
            "<!DOCTYPE html>\n<html lang=\"es\">\n<head>\n  <meta charset=\"utf-8\">\n  <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">\n  <title>%s</title>\n  <link rel=\"stylesheet\" href=\"%s\">\n</head>\n<body class=\"%s\">\n%s\n</body>\n</html>\n",
            $this->escape($title),
            str_starts_with($bodyClass, 'page-module') ? '../styles/control-center.css' : 'styles/control-center.css',
            $this->escape($bodyClass),
            $body
        ));
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
