<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessEnrollmentReadinessLiveOutputToolkit
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
        $baselineValidation = (new BusinessKdeLiveOutputToolkit($this->rootDir))
            ->validateMaterializedLiveOutput($profileId, $resolvedLiveOutputDir);

        $sourceContractPath = $this->rootDir
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'editions'
            . DIRECTORY_SEPARATOR . 'business'
            . DIRECTORY_SEPARATOR . 'enrollment-readiness.json';
        $sourceContract = $this->readRequiredJsonFile(
            $sourceContractPath,
            'No se pudo leer enrollment-readiness.json de Business'
        );

        if (($sourceContract['profile_id'] ?? null) !== $profileId) {
            throw new ValidationError('El contrato fuente de readiness no corresponde al perfil solicitado');
        }

        $liveContractPath = $resolvedLiveOutputDir
            . DIRECTORY_SEPARATOR . 'image-root'
            . DIRECTORY_SEPARATOR . 'system-overlay'
            . DIRECTORY_SEPARATOR . 'etc'
            . DIRECTORY_SEPARATOR . 'w4'
            . DIRECTORY_SEPARATOR . 'business-enrollment-readiness.json';
        $liveContract = $this->readRequiredJsonFile(
            $liveContractPath,
            'No se pudo leer business-enrollment-readiness.json del live materializado'
        );

        if (($liveContract['kind'] ?? null) !== 'business-enrollment-readiness') {
            throw new ValidationError('El contrato runtime del live-output no tiene el kind esperado');
        }

        if (($liveContract['profile_id'] ?? null) !== $profileId) {
            throw new ValidationError('El contrato runtime del live-output no corresponde al perfil solicitado');
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
        foreach (['business-policy-hooks', 'device-enrollment-ready', 'inventory-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en profile.env del live-output', $requiredFeature));
            }
        }

        $deviceIdentity = is_array($sourceContract['device_identity'] ?? null) ? $sourceContract['device_identity'] : [];
        $policyContract = is_array($sourceContract['policy'] ?? null) ? $sourceContract['policy'] : [];
        $inventoryContract = is_array($sourceContract['inventory'] ?? null) ? $sourceContract['inventory'] : [];
        $scope = is_array($sourceContract['scope'] ?? null) ? $sourceContract['scope'] : [];

        $hostnamePrefix = (string) ($deviceIdentity['hostname_prefix'] ?? '');
        if ($hostnamePrefix === '') {
            throw new ValidationError('El contrato fuente de readiness no define device_identity.hostname_prefix');
        }

        $profileHostname = (string) ($profileEnv['W4_HOSTNAME'] ?? '');
        if ($profileHostname === '' || !str_starts_with($profileHostname, $hostnamePrefix)) {
            throw new ValidationError(sprintf('profile.env debe exponer un hostname con prefijo %s', $hostnamePrefix));
        }

        $liveSummary = is_array($baselineValidation['live_summary'] ?? null) ? $baselineValidation['live_summary'] : [];
        $liveHostname = (string) ($liveSummary['live_hostname'] ?? '');
        if ($liveHostname === '' || !str_starts_with($liveHostname, $hostnamePrefix)) {
            throw new ValidationError(sprintf('live-summary.env debe conservar un live hostname con prefijo %s', $hostnamePrefix));
        }

        $sourceContractHash = md5(json_encode($sourceContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $liveContractHash = md5(json_encode($liveContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if ($sourceContractHash !== $liveContractHash) {
            throw new ValidationError('El contrato runtime del live-output no coincide con el contrato fuente de readiness');
        }

        $capabilities = [
            [
                'id' => 'materialized-readiness-contract',
                'label' => 'Contrato de readiness materializado en live-output',
                'status' => 'available',
                'runtime_file' => 'image-root/system-overlay/etc/w4/business-enrollment-readiness.json',
            ],
            [
                'id' => 'device-identity-anchor',
                'label' => 'Anclaje local de identidad de dispositivo',
                'status' => str_starts_with((string) ($deviceIdentity['state_file'] ?? ''), '/var/lib/w4/') ? 'available' : 'missing',
                'state_file' => (string) ($deviceIdentity['state_file'] ?? ''),
                'private_key_file' => (string) ($deviceIdentity['private_key_file'] ?? ''),
                'credential_file' => (string) ($deviceIdentity['credential_file'] ?? ''),
            ],
            [
                'id' => 'policy-cache-anchor',
                'label' => 'Anclaje local de cache de politica',
                'status' => str_starts_with((string) ($policyContract['last_known_policy_file'] ?? ''), '/var/lib/w4/') ? 'available' : 'missing',
                'last_known_policy_file' => (string) ($policyContract['last_known_policy_file'] ?? ''),
                'effective_policy_file' => (string) ($policyContract['effective_policy_file'] ?? ''),
            ],
            [
                'id' => 'inventory-anchor',
                'label' => 'Anclaje local de estado de inventario',
                'status' => str_starts_with((string) ($inventoryContract['device_state_file'] ?? ''), '/var/lib/w4/') ? 'available' : 'missing',
                'device_state_file' => (string) ($inventoryContract['device_state_file'] ?? ''),
                'runtime_root' => '/var/lib/w4',
            ],
            [
                'id' => 'pilot-boundary-live',
                'label' => 'Frontera visible de piloto no inscrito',
                'status' => (($scope['initial_state'] ?? null) === 'unenrolled-ready') ? 'available' : 'missing',
                'initial_state' => (string) ($scope['initial_state'] ?? ''),
                'backend_required' => ($scope['backend_required'] ?? null) === true,
            ],
        ];

        return [
            'business_enrollment_readiness_live_output_validation_schema_version' => 1,
            'kind' => 'business-enrollment-readiness-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($baselineValidation['generated_at'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'baseline_gate' => [
                'kind' => (string) ($baselineValidation['kind'] ?? 'business-kde-live-output-validation'),
                'default_target' => (string) ($liveSummary['default_target'] ?? ''),
                'live_user' => (string) ($liveSummary['live_user'] ?? ''),
                'live_hostname' => $liveHostname,
            ],
            'feature_flags' => $featureFlags,
            'scope' => $scope,
            'device_identity' => $deviceIdentity,
            'policy_contract' => $policyContract,
            'inventory_contract' => $inventoryContract,
            'runtime_projection' => [
                'source_contract' => 'config/editions/business/enrollment-readiness.json',
                'live_contract' => 'image-root/system-overlay/etc/w4/business-enrollment-readiness.json',
                'state_root' => '/var/lib/w4',
                'profile_hostname' => $profileHostname,
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
            sprintf('Business enrollment readiness live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Live output: %s', (string) ($validation['live_output_dir'] ?? 'build/live-output/...')),
            sprintf('Live host: %s', (string) ($baselineGate['live_hostname'] ?? '')),
            sprintf('Profile host: %s', (string) ($runtimeProjection['profile_hostname'] ?? '')),
            sprintf('Contract: %s', (string) ($runtimeProjection['live_contract'] ?? '')),
            sprintf('State root: %s', (string) ($runtimeProjection['state_root'] ?? '')),
            sprintf('Last known policy: %s', (string) ($policyContract['last_known_policy_file'] ?? '')),
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
