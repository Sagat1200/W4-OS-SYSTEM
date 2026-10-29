<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessRemoteUpdateWindowsLiveOutputToolkit
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
        $inventoryValidation = (new BusinessRemoteMinimumInventoryLiveOutputToolkit($this->rootDir))
            ->validateMaterializedLiveOutput($profileId, $resolvedLiveOutputDir);

        $sourceContractPath = $this->rootDir
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'editions'
            . DIRECTORY_SEPARATOR . 'business'
            . DIRECTORY_SEPARATOR . 'remote-update-windows.json';
        $sourceContract = $this->readRequiredJsonFile(
            $sourceContractPath,
            'No se pudo leer remote-update-windows.json de Business'
        );

        if (($sourceContract['profile_id'] ?? null) !== $profileId) {
            throw new ValidationError('El contrato fuente remoto de ventanas de update no corresponde al perfil solicitado');
        }

        $liveContractPath = $resolvedLiveOutputDir
            . DIRECTORY_SEPARATOR . 'image-root'
            . DIRECTORY_SEPARATOR . 'system-overlay'
            . DIRECTORY_SEPARATOR . 'etc'
            . DIRECTORY_SEPARATOR . 'w4'
            . DIRECTORY_SEPARATOR . 'business-remote-update-windows.json';
        $liveContract = $this->readRequiredJsonFile(
            $liveContractPath,
            'No se pudo leer business-remote-update-windows.json del live materializado'
        );

        if (($liveContract['kind'] ?? null) !== 'business-remote-update-windows') {
            throw new ValidationError('El contrato runtime del live-output no tiene el kind esperado para ventanas de update');
        }

        $profileEnv = $this->readRequiredEnvFile(
            $resolvedLiveOutputDir
            . DIRECTORY_SEPARATOR . 'image-root'
            . DIRECTORY_SEPARATOR . 'system-overlay'
            . DIRECTORY_SEPARATOR . 'etc'
            . DIRECTORY_SEPARATOR . 'w4'
            . DIRECTORY_SEPARATOR . 'profile.env',
            'No se pudo leer profile.env del live materializado'
        );

        $featureFlags = $this->parseFeatureList((string) ($profileEnv['W4_FEATURES'] ?? ''));
        foreach (['inventory-remote-ready', 'update-windows-remote-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en profile.env del live-output', $requiredFeature));
            }
        }

        $coordination = is_array($sourceContract['coordination'] ?? null) ? $sourceContract['coordination'] : [];
        $policy = is_array($sourceContract['policy'] ?? null) ? $sourceContract['policy'] : [];
        $boundaries = is_array($sourceContract['boundaries'] ?? null) ? $sourceContract['boundaries'] : [];
        $scope = is_array($sourceContract['scope'] ?? null) ? $sourceContract['scope'] : [];
        $allowedChannels = is_array($policy['allowed_channels'] ?? null) ? array_values(array_filter($policy['allowed_channels'], 'is_string')) : [];
        $healthChecks = is_array($policy['require_health_checks'] ?? null) ? array_values(array_filter($policy['require_health_checks'], 'is_string')) : [];

        if (($scope['enforcement'] ?? null) !== 'local-engine-only') {
            throw new ValidationError('El live-output remoto de ventanas de update requiere enforcement=local-engine-only');
        }

        foreach (['cohort_assignment_file', 'window_definition_file', 'plan_request_file', 'last_result_file'] as $requiredPath) {
            $value = (string) ($coordination[$requiredPath] ?? '');
            if ($value === '' || !str_starts_with($value, '/var/lib/w4/update/remote/')) {
                throw new ValidationError(sprintf('El contrato runtime requiere %s dentro de /var/lib/w4/update/remote/', $requiredPath));
            }
        }

        if (($policy['reboot_outside_window'] ?? null) !== false) {
            throw new ValidationError('El live-output remoto de ventanas de update debe mantener reboot_outside_window=false');
        }

        if ((string) ($policy['schedule_timezone'] ?? '') !== 'UTC') {
            throw new ValidationError('El live-output remoto de ventanas de update requiere schedule_timezone=UTC');
        }

        if (($boundaries['local_update_engine'] ?? null) !== 'reuse-mx-004') {
            throw new ValidationError('El live-output remoto de ventanas de update debe mantener local_update_engine=reuse-mx-004');
        }

        if (($boundaries['remote_installer_allowed'] ?? null) !== false) {
            throw new ValidationError('El live-output remoto de ventanas de update debe mantener remote_installer_allowed=false');
        }

        $sourceContractHash = md5(json_encode($sourceContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $liveContractHash = md5(json_encode($liveContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if ($sourceContractHash !== $liveContractHash) {
            throw new ValidationError('El contrato runtime del live-output no coincide con el contrato fuente remoto de ventanas de update');
        }

        $inventoryGate = is_array($inventoryValidation['baseline_gate'] ?? null) ? $inventoryValidation['baseline_gate'] : [];
        $capabilities = [
            [
                'id' => 'materialized-remote-update-window-contract',
                'label' => 'Contrato remoto de ventanas de update materializado en live-output',
                'status' => 'available',
                'runtime_file' => 'image-root/system-overlay/etc/w4/business-remote-update-windows.json',
            ],
            [
                'id' => 'materialized-remote-update-window-cohort',
                'label' => 'Cohorte y ventana remotas visibles en runtime',
                'status' => 'available',
                'cohort_assignment_file' => (string) ($coordination['cohort_assignment_file'] ?? ''),
                'window_definition_file' => (string) ($coordination['window_definition_file'] ?? ''),
                'plan_request_file' => (string) ($coordination['plan_request_file'] ?? ''),
            ],
            [
                'id' => 'materialized-remote-update-window-policy',
                'label' => 'Politica remota de update visible en runtime',
                'status' => 'available',
                'allowed_channels' => $allowedChannels,
                'default_channel' => (string) ($policy['default_channel'] ?? ''),
                'max_deferral_minutes' => (int) ($policy['max_deferral_minutes'] ?? 0),
            ],
            [
                'id' => 'materialized-remote-update-window-health',
                'label' => 'Reuse del motor local y health checks visibles',
                'status' => 'available',
                'local_update_engine' => (string) ($boundaries['local_update_engine'] ?? ''),
                'require_health_checks' => $healthChecks,
            ],
            [
                'id' => 'materialized-remote-update-window-boundary',
                'label' => 'Frontera remota acotada sin instalador paralelo',
                'status' => 'available',
                'remote_installer_allowed' => (($boundaries['remote_installer_allowed'] ?? false) === true) ? 'true' : 'false',
                'downgrade_allowed' => (($boundaries['downgrade_allowed'] ?? false) === true) ? 'true' : 'false',
            ],
        ];

        return [
            'business_remote_update_windows_live_output_validation_schema_version' => 1,
            'kind' => 'business-remote-update-windows-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($inventoryValidation['generated_at'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'baseline_gate' => [
                'kind' => (string) ($inventoryGate['kind'] ?? 'business-remote-minimum-inventory-live-output-validation'),
                'default_target' => (string) ($inventoryGate['default_target'] ?? ''),
                'live_user' => (string) ($inventoryGate['live_user'] ?? ''),
                'live_hostname' => (string) ($inventoryGate['live_hostname'] ?? ''),
            ],
            'feature_flags' => $featureFlags,
            'scope' => $scope,
            'coordination_contract' => $coordination,
            'policy_contract' => $policy,
            'boundaries' => $boundaries,
            'runtime_projection' => [
                'source_contract' => 'config/editions/business/remote-update-windows.json',
                'live_contract' => 'image-root/system-overlay/etc/w4/business-remote-update-windows.json',
                'cohort_assignment_file' => (string) ($coordination['cohort_assignment_file'] ?? ''),
                'window_definition_file' => (string) ($coordination['window_definition_file'] ?? ''),
            ],
            'capabilities' => $capabilities,
            'deferred_steps' => is_array($sourceContract['deferred_steps'] ?? null)
                ? array_values(array_filter($sourceContract['deferred_steps'], 'is_string'))
                : [],
        ];
    }

    /**
     * @param array<string, mixed> $validation
     */
    public function renderSummaryText(array $validation): string
    {
        $runtimeProjection = is_array($validation['runtime_projection'] ?? null) ? $validation['runtime_projection'] : [];
        $policy = is_array($validation['policy_contract'] ?? null) ? $validation['policy_contract'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];
        $capabilities = is_array($validation['capabilities'] ?? null) ? $validation['capabilities'] : [];

        $lines = [
            sprintf('Business remote update windows live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Live output: %s', (string) ($validation['live_output_dir'] ?? 'build/live-output/...')),
            sprintf('Contract: %s', (string) ($runtimeProjection['live_contract'] ?? '')),
            sprintf('Cohort assignment: %s', (string) ($runtimeProjection['cohort_assignment_file'] ?? '')),
            sprintf('Window definition: %s', (string) ($runtimeProjection['window_definition_file'] ?? '')),
            sprintf('Default channel: %s', (string) ($policy['default_channel'] ?? '')),
            '',
            'Feature flags:',
        ];

        foreach ($featureFlags as $featureFlag) {
            if (is_string($featureFlag)) {
                $lines[] = sprintf('- %s', $featureFlag);
            }
        }

        $lines[] = '';
        $lines[] = 'Capabilities:';
        foreach ($capabilities as $capability) {
            if (!is_array($capability)) {
                continue;
            }

            $lines[] = sprintf(
                '- %s: %s',
                (string) ($capability['label'] ?? $capability['id'] ?? 'capability'),
                (string) ($capability['status'] ?? 'unknown')
            );
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

    /**
     * @return array<string, mixed>
     */
    private function readRequiredJsonFile(string $path, string $errorMessage): array
    {
        if (!is_file($path)) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $exception->getMessage()));
        }

        return $decoded;
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
