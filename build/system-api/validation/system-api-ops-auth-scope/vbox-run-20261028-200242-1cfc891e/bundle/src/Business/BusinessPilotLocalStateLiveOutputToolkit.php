<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessPilotLocalStateLiveOutputToolkit
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
        $baselineValidation = (new BusinessEnrollmentReadinessLiveOutputToolkit($this->rootDir))
            ->validateMaterializedLiveOutput($profileId, $resolvedLiveOutputDir);

        $sourceContractPath = $this->rootDir
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'editions'
            . DIRECTORY_SEPARATOR . 'business'
            . DIRECTORY_SEPARATOR . 'pilot-local-state.json';
        $sourceContract = $this->readRequiredJsonFile(
            $sourceContractPath,
            'No se pudo leer pilot-local-state.json de Business'
        );

        if (($sourceContract['profile_id'] ?? null) !== $profileId) {
            throw new ValidationError('El contrato fuente local de politica/inventario no corresponde al perfil solicitado');
        }

        $liveContractPath = $resolvedLiveOutputDir
            . DIRECTORY_SEPARATOR . 'image-root'
            . DIRECTORY_SEPARATOR . 'system-overlay'
            . DIRECTORY_SEPARATOR . 'etc'
            . DIRECTORY_SEPARATOR . 'w4'
            . DIRECTORY_SEPARATOR . 'business-pilot-local-state.json';
        $liveContract = $this->readRequiredJsonFile(
            $liveContractPath,
            'No se pudo leer business-pilot-local-state.json del live materializado'
        );

        if (($liveContract['kind'] ?? null) !== 'business-pilot-local-state') {
            throw new ValidationError('El contrato runtime del live-output no tiene el kind esperado');
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
        foreach (['business-policy-hooks', 'inventory-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en profile.env del live-output', $requiredFeature));
            }
        }

        $scope = is_array($sourceContract['scope'] ?? null) ? $sourceContract['scope'] : [];
        $policyContract = is_array($sourceContract['policy'] ?? null) ? $sourceContract['policy'] : [];
        $inventoryContract = is_array($sourceContract['inventory'] ?? null) ? $sourceContract['inventory'] : [];
        $minimumFields = is_array($inventoryContract['minimum_fields'] ?? null) ? array_values(array_filter($inventoryContract['minimum_fields'], 'is_string')) : [];
        $precedence = is_array($policyContract['precedence'] ?? null) ? array_values(array_filter($policyContract['precedence'], 'is_string')) : [];

        foreach (['edition-baseline', 'last-known-valid'] as $requiredPrecedence) {
            if (!in_array($requiredPrecedence, $precedence, true)) {
                throw new ValidationError(sprintf('El contrato runtime debe incluir la precedencia %s', $requiredPrecedence));
            }
        }

        $sourceContractHash = md5(json_encode($sourceContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $liveContractHash = md5(json_encode($liveContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if ($sourceContractHash !== $liveContractHash) {
            throw new ValidationError('El contrato runtime del live-output no coincide con el contrato fuente local de politica/inventario');
        }

        $baselineGate = is_array($baselineValidation['baseline_gate'] ?? null) ? $baselineValidation['baseline_gate'] : [];
        $capabilities = [
            [
                'id' => 'materialized-policy-local-state',
                'label' => 'Contrato local de politica materializado en live-output',
                'status' => 'available',
                'runtime_file' => 'image-root/system-overlay/etc/w4/business-pilot-local-state.json',
            ],
            [
                'id' => 'policy-cache-anchor',
                'label' => 'Cache local de politica efectiva',
                'status' => str_starts_with((string) ($policyContract['effective_policy_file'] ?? ''), '/var/lib/w4/') ? 'available' : 'missing',
                'last_known_policy_file' => (string) ($policyContract['last_known_policy_file'] ?? ''),
                'effective_policy_file' => (string) ($policyContract['effective_policy_file'] ?? ''),
                'baseline_runtime_env' => (string) ($policyContract['baseline_runtime_env'] ?? ''),
            ],
            [
                'id' => 'inventory-minimum-state',
                'label' => 'Inventario minimo local visible',
                'status' => str_starts_with((string) ($inventoryContract['device_state_file'] ?? ''), '/var/lib/w4/') ? 'available' : 'missing',
                'device_state_file' => (string) ($inventoryContract['device_state_file'] ?? ''),
                'minimum_fields' => $minimumFields,
                'transport' => (string) ($inventoryContract['transport'] ?? ''),
            ],
            [
                'id' => 'offline-policy-boundary',
                'label' => 'Frontera offline con ultima politica valida',
                'status' => (($policyContract['invalid_policy_behavior'] ?? null) === 'keep-last-valid') ? 'available' : 'missing',
                'policy_state' => (string) ($scope['policy_state'] ?? ''),
                'inventory_transport' => (string) ($scope['inventory_transport'] ?? ''),
            ],
        ];

        return [
            'business_pilot_local_state_live_output_validation_schema_version' => 1,
            'kind' => 'business-pilot-local-state-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($baselineValidation['generated_at'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'baseline_gate' => [
                'kind' => (string) ($baselineGate['kind'] ?? 'business-enrollment-readiness-live-output-validation'),
                'default_target' => (string) ($baselineGate['default_target'] ?? ''),
                'live_user' => (string) ($baselineGate['live_user'] ?? ''),
                'live_hostname' => (string) ($baselineGate['live_hostname'] ?? ''),
            ],
            'feature_flags' => $featureFlags,
            'scope' => $scope,
            'policy_contract' => $policyContract,
            'inventory_contract' => $inventoryContract,
            'runtime_projection' => [
                'source_contract' => 'config/editions/business/pilot-local-state.json',
                'live_contract' => 'image-root/system-overlay/etc/w4/business-pilot-local-state.json',
                'state_root' => '/var/lib/w4',
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
        $baselineGate = is_array($validation['baseline_gate'] ?? null) ? $validation['baseline_gate'] : [];
        $runtimeProjection = is_array($validation['runtime_projection'] ?? null) ? $validation['runtime_projection'] : [];
        $policyContract = is_array($validation['policy_contract'] ?? null) ? $validation['policy_contract'] : [];
        $inventoryContract = is_array($validation['inventory_contract'] ?? null) ? $validation['inventory_contract'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];
        $capabilities = is_array($validation['capabilities'] ?? null) ? $validation['capabilities'] : [];

        $lines = [
            sprintf('Business pilot local state live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Live output: %s', (string) ($validation['live_output_dir'] ?? 'build/live-output/...')),
            sprintf('Live host: %s', (string) ($baselineGate['live_hostname'] ?? '')),
            sprintf('Contract: %s', (string) ($runtimeProjection['live_contract'] ?? '')),
            sprintf('State root: %s', (string) ($runtimeProjection['state_root'] ?? '')),
            sprintf('Last known policy: %s', (string) ($policyContract['last_known_policy_file'] ?? '')),
            sprintf('Effective policy: %s', (string) ($policyContract['effective_policy_file'] ?? '')),
            sprintf('Inventory state: %s', (string) ($inventoryContract['device_state_file'] ?? '')),
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
