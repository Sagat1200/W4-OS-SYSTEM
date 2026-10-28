<?php

declare(strict_types=1);

namespace W4\OS\Home;

final class HomeOnboardingLightUiToolkit
{
    private HomeOnboardingLiveOutputToolkit $liveOutputToolkit;

    public function __construct(string $rootDir)
    {
        $this->liveOutputToolkit = new HomeOnboardingLiveOutputToolkit($rootDir);
    }

    /**
     * @return array<string, mixed>
     */
    public function createBundle(string $profileId, ?string $liveOutputDir = null): array
    {
        $validation = $this->liveOutputToolkit->validateMaterializedLiveOutput($profileId, $liveOutputDir);
        $firstSession = is_array($validation['first_session'] ?? null) ? $validation['first_session'] : [];

        $visibleSteps = [
            [
                'id' => 'welcome',
                'title' => 'Bienvenida',
                'status' => 'ready',
                'summary' => 'Explica que el equipo ya esta listo para primer inicio sin pedir decisiones innecesarias.',
                'action' => 'Continuar',
                'kind' => 'informative',
            ],
            [
                'id' => 'privacy',
                'title' => 'Privacidad local',
                'status' => 'ready',
                'summary' => 'Presenta una explicacion corta sobre decisiones locales y pasos opcionales diferidos.',
                'action' => 'Revisar',
                'kind' => 'informative',
            ],
            [
                'id' => 'settings',
                'title' => 'W4 Settings',
                'status' => 'available',
                'summary' => 'Reutiliza la ruta validada de Settings para ajustes iniciales sin abrir una superficie paralela.',
                'action' => 'Abrir W4 Settings',
                'entrypoint' => 'w4-control-center-home-home.desktop',
                'kind' => 'entrypoint',
            ],
            [
                'id' => 'updates',
                'title' => 'Actualizaciones',
                'status' => 'available',
                'summary' => 'Mantiene visible el acceso a updates desde la ruta ya validada del perfil Home.',
                'action' => 'Abrir Updates',
                'entrypoint' => 'w4-control-center-home-updates.desktop',
                'kind' => 'entrypoint',
            ],
            [
                'id' => 'finish',
                'title' => 'Finalizar',
                'status' => 'ready',
                'summary' => 'Cierra el onboarding local y evita reaparicion arbitraria del recorrido ligero.',
                'action' => 'Empezar a usar W4 OS Home',
                'kind' => 'completion',
            ],
        ];

        $deferredSteps = [];
        foreach ((array) ($validation['deferred_steps'] ?? []) as $stepId) {
            if (!is_string($stepId)) {
                continue;
            }

            $deferredSteps[] = [
                'id' => $stepId,
                'status' => 'deferred',
                'summary' => $this->deferredSummary($stepId),
            ];
        }

        $bundle = [
            'home_onboarding_light_ui_schema_version' => 1,
            'kind' => 'home-onboarding-light-ui-bundle',
            'profile_id' => $profileId,
            'generated_at' => (string) ($validation['generated_at'] ?? gmdate('c')),
            'live_output_dir' => (string) ($validation['live_output_dir'] ?? 'build/live-output/' . $profileId),
            'default_target' => (string) ($firstSession['default_target'] ?? ''),
            'live_user' => (string) ($firstSession['live_user'] ?? ''),
            'live_hostname' => (string) ($firstSession['live_hostname'] ?? ''),
            'hero' => [
                'eyebrow' => 'W4 OS Home',
                'title' => 'Primer inicio ligero',
                'summary' => 'Recorrido pequeno y accionable para aterrizar Home sin convertirlo en un wizard grande.',
            ],
            'visible_steps' => $visibleSteps,
            'deferred_steps' => $deferredSteps,
            'guardrails' => [
                'reuse-gnome-settings',
                'no-large-wizard',
                'no-complete-accessibility-scope',
            ],
        ];

        return [
            'bundle' => $bundle,
            'html' => $this->renderDocument($bundle),
            'route' => 'index.html',
        ];
    }

    public function renderStylesheet(): string
    {
        return str_replace(["\r\n", "\r"], "\n", <<<CSS
body {
  margin: 0;
  font-family: "Segoe UI", Arial, sans-serif;
  background: #0f172a;
  color: #e5e7eb;
}

a {
  color: #93c5fd;
  text-decoration: none;
}

a:hover {
  text-decoration: underline;
}

.layout {
  max-width: 1120px;
  margin: 0 auto;
  padding: 32px 24px 56px;
}

.hero,
.panel,
.step-card {
  background: rgba(15, 23, 42, 0.92);
  border: 1px solid rgba(148, 163, 184, 0.18);
  border-radius: 20px;
  box-shadow: 0 20px 45px rgba(2, 6, 23, 0.28);
}

.hero,
.panel {
  padding: 24px;
  margin-bottom: 20px;
}

.eyebrow {
  margin: 0 0 8px;
  color: #93c5fd;
  font-size: 12px;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.hero h1 {
  margin: 0 0 12px;
  font-size: 36px;
}

.hero p,
.panel p,
.step-card p,
.step-card li {
  color: #cbd5e1;
  line-height: 1.5;
}

.meta-line {
  margin: 0 0 12px;
  color: #94a3b8;
  font-size: 14px;
}

.grid {
  display: grid;
  gap: 16px;
}

.step-grid {
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
}

.step-card {
  padding: 20px;
}

.step-card h2 {
  margin: 0 0 8px;
  font-size: 24px;
}

.badge {
  display: inline-flex;
  align-items: center;
  border-radius: 999px;
  padding: 6px 10px;
  font-size: 12px;
  font-weight: 700;
  letter-spacing: 0.06em;
  margin-bottom: 14px;
}

.badge-ready,
.badge-available {
  background: rgba(34, 197, 94, 0.16);
  color: #86efac;
}

.badge-deferred {
  background: rgba(245, 158, 11, 0.16);
  color: #fcd34d;
}

.action-line {
  margin-top: 14px;
  color: #bfdbfe;
  font-weight: 600;
}

.bullet-list {
  margin: 0;
  padding-left: 18px;
}
CSS
        );
    }

    /**
     * @param array<string, mixed> $bundle
     */
    private function renderDocument(array $bundle): string
    {
        $hero = is_array($bundle['hero'] ?? null) ? $bundle['hero'] : [];
        $visibleSteps = is_array($bundle['visible_steps'] ?? null) ? $bundle['visible_steps'] : [];
        $deferredSteps = is_array($bundle['deferred_steps'] ?? null) ? $bundle['deferred_steps'] : [];

        $visibleHtml = [];
        foreach ($visibleSteps as $step) {
            if (!is_array($step)) {
                continue;
            }

            $status = (string) ($step['status'] ?? 'ready');
            $visibleHtml[] = sprintf(
                '<article class="step-card"><div class="badge badge-%s">%s</div><h2>%s</h2><p>%s</p><p class="action-line">%s</p></article>',
                $this->escape($status),
                $this->escape(strtoupper($status)),
                $this->escape((string) ($step['title'] ?? 'Paso')),
                $this->escape((string) ($step['summary'] ?? '')),
                $this->escape((string) ($step['action'] ?? 'Continuar'))
            );
        }

        $deferredHtml = [];
        foreach ($deferredSteps as $step) {
            if (!is_array($step)) {
                continue;
            }

            $deferredHtml[] = sprintf(
                '<li><strong>%s</strong>: %s</li>',
                $this->escape((string) ($step['id'] ?? 'deferred-step')),
                $this->escape((string) ($step['summary'] ?? ''))
            );
        }

        return str_replace(["\r\n", "\r"], "\n", sprintf(
            '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Home Onboarding · %s</title><link rel="stylesheet" href="styles/home-onboarding.css"></head><body><main class="layout"><section class="hero"><p class="eyebrow">%s</p><p class="meta-line">Target: %s · Live user: %s · Host: %s</p><h1>%s</h1><p>%s</p></section><section class="panel"><h2>Pasos visibles</h2><div class="grid step-grid">%s</div></section><section class="panel"><h2>Pasos diferidos</h2><ul class="bullet-list">%s</ul></section></main></body></html>',
            $this->escape((string) ($bundle['profile_id'] ?? 'w4-os-home')),
            $this->escape((string) ($hero['eyebrow'] ?? 'W4 OS Home')),
            $this->escape((string) ($bundle['default_target'] ?? 'graphical.target')),
            $this->escape((string) ($bundle['live_user'] ?? 'w4live')),
            $this->escape((string) ($bundle['live_hostname'] ?? 'w4-home-live')),
            $this->escape((string) ($hero['title'] ?? 'Primer inicio ligero')),
            $this->escape((string) ($hero['summary'] ?? '')),
            implode('', $visibleHtml),
            implode('', $deferredHtml)
        ));
    }

    private function deferredSummary(string $stepId): string
    {
        return match ($stepId) {
            'privacy-step-ui' => 'Paso visual de privacidad mas amplio, fuera del alcance ligero.',
            'local-account-onboarding-ui' => 'Flujo guiado de cuenta local, diferido para no convertir este slice en wizard grande.',
            'external-backup-guidance-ui' => 'Guia ampliada de respaldo externo, diferida a un slice posterior.',
            'telemetry-opt-in-ui' => 'Opt-in interactivo completo, fuera del alcance de la UI ligera inicial.',
            default => 'Paso diferido a un slice posterior de onboarding o accesibilidad.',
        };
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
