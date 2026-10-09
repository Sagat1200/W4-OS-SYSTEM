<?php

declare(strict_types=1);

namespace W4\OS\Home;

use W4\OS\Support\ValidationError;

final class HomeOnboardingLiveOutputToolkit
{
    public function __construct(private readonly string $rootDir)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function validateMaterializedLiveOutput(string $profileId, ?string $liveOutputDir = null): array
    {
        $resolvedLiveOutputDir = $liveOutputDir ?? $this->defaultLiveOutputDir($profileId);
        $liveSummary = $this->readRequiredEnvFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'live-summary.env',
            'No se pudo leer live-summary.env del live materializado'
        );

        $defaultTarget = (string) ($liveSummary['W4_DEFAULT_TARGET'] ?? '');
        if ($defaultTarget !== 'graphical.target') {
            throw new ValidationError(sprintf('Home onboarding local requiere graphical.target y obtuvo %s', $defaultTarget));
        }

        $profileEnv = $this->readRequiredEnvFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env',
            'No se pudo leer profile.env del live materializado'
        );

        $featureList = $this->parseFeatureList((string) ($profileEnv['W4_FEATURES'] ?? ''));
        foreach (['home-onboarding', 'local-backup-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureList, true)) {
                throw new ValidationError(sprintf('Falta la feature requerida %s en profile.env', $requiredFeature));
            }
        }

        $firstbootServicePath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'w4-firstboot.service';
        $firstbootService = $this->readRequiredTextFile(
            $firstbootServicePath,
            'No se pudo leer w4-firstboot.service del live materializado'
        );
        $this->assertContainsAll(
            $firstbootService,
            [
                'ConditionPathExists=!/var/lib/w4/firstboot-complete',
                'ExecStart=/usr/local/lib/w4/w4-firstboot.sh',
                'WantedBy=graphical.target',
            ],
            'w4-firstboot.service'
        );

        $livePrepServicePath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'w4-live-prep.service';
        $livePrepService = $this->readRequiredTextFile(
            $livePrepServicePath,
            'No se pudo leer w4-live-prep.service del live materializado'
        );
        $this->assertContainsAll(
            $livePrepService,
            [
                'ExecStart=/usr/local/lib/w4/w4-live-prep.sh',
                'Before=display-manager.service getty@tty1.service',
                'WantedBy=graphical.target',
            ],
            'w4-live-prep.service'
        );

        $firstbootScriptPath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'w4-firstboot.sh';
        $firstbootScript = $this->readRequiredTextFile(
            $firstbootScriptPath,
            'No se pudo leer w4-firstboot.sh del live materializado'
        );
        $this->assertContainsAll(
            $firstbootScript,
            [
                'STATE_FILE="${STATE_DIR}/firstboot-complete"',
                'cat > /etc/w4/firstboot-state.env <<EOF',
                'W4_FIRSTBOOT_COMPLETED_AT=',
            ],
            'w4-firstboot.sh'
        );

        $livePrepScriptPath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'w4-live-prep.sh';
        $livePrepScript = $this->readRequiredTextFile(
            $livePrepScriptPath,
            'No se pudo leer w4-live-prep.sh del live materializado'
        );
        $this->assertContainsAll(
            $livePrepScript,
            [
                'useradd -m -s /bin/bash "${LIVE_USER}"',
                'autologin ${LIVE_USER}',
                'cat > /etc/w4/live-state.env <<EOF',
            ],
            'w4-live-prep.sh'
        );

        $firstbootSymlink = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'graphical.target.wants' . DIRECTORY_SEPARATOR . 'w4-firstboot.service';
        $livePrepSymlink = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'systemd' . DIRECTORY_SEPARATOR . 'system' . DIRECTORY_SEPARATOR . 'graphical.target.wants' . DIRECTORY_SEPARATOR . 'w4-live-prep.service';

        if (!is_link($firstbootSymlink) && !is_file($firstbootSymlink)) {
            throw new ValidationError(sprintf('No se encontro el enlace esperado de firstboot: %s', $firstbootSymlink));
        }

        if (!is_link($livePrepSymlink) && !is_file($livePrepSymlink)) {
            throw new ValidationError(sprintf('No se encontro el enlace esperado de live-prep: %s', $livePrepSymlink));
        }

        $stateFile = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'firstboot-complete';
        if (is_file($stateFile)) {
            throw new ValidationError('El artefacto live no debe traer firstboot-complete ya materializado');
        }

        return [
            'home_onboarding_live_output_validation_schema_version' => 1,
            'kind' => 'home-onboarding-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($liveSummary['W4_GENERATED_AT'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'feature_flags' => $featureList,
            'first_session' => [
                'default_target' => $defaultTarget,
                'live_user' => (string) ($liveSummary['W4_LIVE_USER'] ?? ''),
                'live_hostname' => (string) ($liveSummary['W4_LIVE_HOSTNAME'] ?? ''),
                'profile_hostname' => (string) ($profileEnv['W4_HOSTNAME'] ?? ''),
                'services' => [
                    'firstboot' => 'image-root/system-overlay/etc/systemd/system/w4-firstboot.service',
                    'live_prep' => 'image-root/system-overlay/etc/systemd/system/w4-live-prep.service',
                ],
                'scripts' => [
                    'firstboot' => 'image-root/system-overlay/usr/local/lib/w4/w4-firstboot.sh',
                    'live_prep' => 'image-root/system-overlay/usr/local/lib/w4/w4-live-prep.sh',
                ],
                'activation' => [
                    'firstboot' => 'image-root/system-overlay/etc/systemd/system/graphical.target.wants/w4-firstboot.service',
                    'live_prep' => 'image-root/system-overlay/etc/systemd/system/graphical.target.wants/w4-live-prep.service',
                ],
                'state_files' => [
                    'firstboot_runtime' => '/etc/w4/firstboot-state.env',
                    'live_runtime' => '/etc/w4/live-state.env',
                    'firstboot_complete_expected_absent' => 'image-root/system-overlay/var/lib/w4/firstboot-complete',
                ],
            ],
            'deferred_steps' => [
                'privacy-step-ui',
                'local-account-onboarding-ui',
                'external-backup-guidance-ui',
                'telemetry-opt-in-ui',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $validation
     */
    public function renderSummaryText(array $validation): string
    {
        $firstSession = is_array($validation['first_session'] ?? null) ? $validation['first_session'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];
        $deferredSteps = is_array($validation['deferred_steps'] ?? null) ? $validation['deferred_steps'] : [];

        $lines = [
            sprintf('Home onboarding live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Live output: %s', (string) ($validation['live_output_dir'] ?? 'build/live-output/...')),
            sprintf('Target: %s', (string) ($firstSession['default_target'] ?? '')),
            sprintf('Live user: %s', (string) ($firstSession['live_user'] ?? '')),
            sprintf('Live host: %s', (string) ($firstSession['live_hostname'] ?? '')),
            sprintf('Profile host: %s', (string) ($firstSession['profile_hostname'] ?? '')),
            '',
            'Feature flags:',
        ];

        foreach ($featureFlags as $featureFlag) {
            if (is_string($featureFlag)) {
                $lines[] = sprintf('- %s', $featureFlag);
            }
        }

        $lines[] = '';
        $lines[] = 'Runtime first session:';
        $lines[] = sprintf('- firstboot: %s', (string) (($firstSession['services']['firstboot'] ?? '')));
        $lines[] = sprintf('- live-prep: %s', (string) (($firstSession['services']['live_prep'] ?? '')));
        $lines[] = sprintf('- firstboot-state: %s', (string) (($firstSession['state_files']['firstboot_runtime'] ?? '')));
        $lines[] = sprintf('- live-state: %s', (string) (($firstSession['state_files']['live_runtime'] ?? '')));

        $lines[] = '';
        $lines[] = 'Deferred steps:';
        foreach ($deferredSteps as $deferredStep) {
            if (is_string($deferredStep)) {
                $lines[] = sprintf('- %s', $deferredStep);
            }
        }

        return rtrim(implode(PHP_EOL, $lines)) . PHP_EOL;
    }

    private function defaultLiveOutputDir(string $profileId): string
    {
        return $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live-output' . DIRECTORY_SEPARATOR . $profileId;
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
            $values[trim($key)] = trim($value, "\"' ");
        }

        return $values;
    }

    private function readRequiredTextFile(string $path, string $errorMessage): string
    {
        if (!is_file($path)) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        return $raw;
    }

    /**
     * @return list<string>
     */
    private function parseFeatureList(string $raw): array
    {
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_filter($parts, static fn (string $value): bool => $value !== '');

        return array_values(array_unique($parts));
    }

    /**
     * @param list<string> $needles
     */
    private function assertContainsAll(string $haystack, array $needles, string $label): void
    {
        foreach ($needles as $needle) {
            if (!str_contains($haystack, $needle)) {
                throw new ValidationError(sprintf('%s no contiene %s', $label, $needle));
            }
        }
    }

    private function relativePath(string $path): string
    {
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($this->rootDir, "\\/"));
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (str_starts_with($normalizedPath, $normalizedRoot . DIRECTORY_SEPARATOR)) {
            return substr($normalizedPath, strlen($normalizedRoot) + 1);
        }

        return $normalizedPath;
    }
}
