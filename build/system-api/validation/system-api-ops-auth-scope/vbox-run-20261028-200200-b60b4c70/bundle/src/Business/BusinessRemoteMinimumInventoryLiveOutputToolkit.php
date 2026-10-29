<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessRemoteMinimumInventoryLiveOutputToolkit
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
        $baselineValidation = (new BusinessPilotLocalStateLiveOutputToolkit($this->rootDir))
            ->validateMaterializedLiveOutput($profileId, $resolvedLiveOutputDir);

        $sourceContractPath = $this->rootDir
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'editions'
            . DIRECTORY_SEPARATOR . 'business'
            . DIRECTORY_SEPARATOR . 'remote-minimum-inventory.json';
        $sourceContract = $this->readRequiredJsonFile(
            $sourceContractPath,
            'No se pudo leer remote-minimum-inventory.json de Business'
        );

        if (($sourceContract['profile_id'] ?? null) !== $profileId) {
            throw new ValidationError('El contrato fuente remoto minimo de inventario no corresponde al perfil solicitado');
        }

        $liveContractPath = $resolvedLiveOutputDir
            . DIRECTORY_SEPARATOR . 'image-root'
            . DIRECTORY_SEPARATOR . 'system-overlay'
            . DIRECTORY_SEPARATOR . 'etc'
            . DIRECTORY_SEPARATOR . 'w4'
            . DIRECTORY_SEPARATOR . 'business-remote-minimum-inventory.json';
        $liveContract = $this->readRequiredJsonFile(
            $liveContractPath,
            'No se pudo leer business-remote-minimum-inventory.json del live materializado'
        );

        if (($liveContract['kind'] ?? null) !== 'business-remote-minimum-inventory') {
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
        foreach (['inventory-ready', 'inventory-remote-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en profile.env del live-output', $requiredFeature));
            }
        }

        $payload = is_array($sourceContract['payload'] ?? null) ? $sourceContract['payload'] : [];
        $remote = is_array($sourceContract['remote'] ?? null) ? $sourceContract['remote'] : [];
        $scope = is_array($sourceContract['scope'] ?? null) ? $sourceContract['scope'] : [];
        $boundaries = is_array($sourceContract['boundaries'] ?? null) ? $sourceContract['boundaries'] : [];
        $minimumFields = is_array($payload['minimum_fields'] ?? null) ? array_values(array_filter($payload['minimum_fields'], 'is_string')) : [];
        $redactedFields = is_array($payload['redacted_fields'] ?? null) ? array_values(array_filter($payload['redacted_fields'], 'is_string')) : [];

        if (($scope['activation'] ?? null) !== 'configured-or-enrolled') {
            throw new ValidationError('El live-output remoto minimo requiere activation=configured-or-enrolled');
        }

        if (($remote['delivery_mode'] ?? null) !== 'deferred-push') {
            throw new ValidationError('El live-output remoto minimo requiere delivery_mode=deferred-push');
        }

        if (($boundaries['remote_writeback'] ?? null) !== 'inventory-only') {
            throw new ValidationError('El live-output remoto minimo requiere remote_writeback=inventory-only');
        }

        foreach (['queue_file', 'last_delivery_file', 'last_payload_file'] as $remoteStateFile) {
            $value = (string) ($payload[$remoteStateFile] ?? '');
            if ($value === '' || !str_starts_with($value, '/var/lib/w4/inventory/remote/')) {
                throw new ValidationError(sprintf('El contrato runtime requiere %s dentro de /var/lib/w4/inventory/remote/', $remoteStateFile));
            }
        }

        if (!str_starts_with((string) ($remote['endpoint_config_file'] ?? ''), '/etc/w4/inventory/')) {
            throw new ValidationError('El endpoint remoto runtime debe vivir bajo /etc/w4/inventory/');
        }

        $sourceContractHash = md5(json_encode($sourceContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $liveContractHash = md5(json_encode($liveContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if ($sourceContractHash !== $liveContractHash) {
            throw new ValidationError('El contrato runtime del live-output no coincide con el contrato fuente remoto minimo de inventario');
        }

        $baselineGate = is_array($baselineValidation['baseline_gate'] ?? null) ? $baselineValidation['baseline_gate'] : [];
        $capabilities = [
            [
                'id' => 'materialized-remote-inventory-contract',
                'label' => 'Contrato remoto minimo materializado en live-output',
                'status' => 'available',
                'runtime_file' => 'image-root/system-overlay/etc/w4/business-remote-minimum-inventory.json',
            ],
            [
                'id' => 'materialized-remote-inventory-queue',
                'label' => 'Cola remota diferida visible en runtime',
                'status' => 'available',
                'queue_file' => (string) ($payload['queue_file'] ?? ''),
                'last_delivery_file' => (string) ($payload['last_delivery_file'] ?? ''),
                'last_payload_file' => (string) ($payload['last_payload_file'] ?? ''),
            ],
            [
                'id' => 'materialized-remote-endpoint-slot',
                'label' => 'Slot remoto tipado visible en runtime',
                'status' => 'available',
                'endpoint_config_file' => (string) ($remote['endpoint_config_file'] ?? ''),
                'transport' => (string) ($remote['transport'] ?? ''),
                'authentication_mode' => (string) ($remote['authentication_mode'] ?? ''),
            ],
            [
                'id' => 'materialized-remote-minimum-payload',
                'label' => 'Payload remoto minimo y redaccionado',
                'status' => 'available',
                'minimum_fields' => $minimumFields,
                'redacted_fields' => $redactedFields,
            ],
            [
                'id' => 'materialized-remote-boundary',
                'label' => 'Frontera remota acotada del piloto',
                'status' => 'available',
                'activation' => (string) ($scope['activation'] ?? ''),
                'remote_writeback' => (string) ($boundaries['remote_writeback'] ?? ''),
            ],
        ];

        return [
            'business_remote_minimum_inventory_live_output_validation_schema_version' => 1,
            'kind' => 'business-remote-minimum-inventory-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($baselineValidation['generated_at'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'baseline_gate' => [
                'kind' => (string) ($baselineGate['kind'] ?? 'business-pilot-local-state-live-output-validation'),
                'default_target' => (string) ($baselineGate['default_target'] ?? ''),
                'live_user' => (string) ($baselineGate['live_user'] ?? ''),
                'live_hostname' => (string) ($baselineGate['live_hostname'] ?? ''),
            ],
            'feature_flags' => $featureFlags,
            'scope' => $scope,
            'payload_contract' => $payload,
            'remote_contract' => $remote,
            'boundaries' => $boundaries,
            'runtime_projection' => [
                'source_contract' => 'config/editions/business/remote-minimum-inventory.json',
                'live_contract' => 'image-root/system-overlay/etc/w4/business-remote-minimum-inventory.json',
                'endpoint_config_file' => (string) ($remote['endpoint_config_file'] ?? ''),
                'queue_root' => '/var/lib/w4/inventory/remote',
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
        $payload = is_array($validation['payload_contract'] ?? null) ? $validation['payload_contract'] : [];
        $remote = is_array($validation['remote_contract'] ?? null) ? $validation['remote_contract'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];
        $capabilities = is_array($validation['capabilities'] ?? null) ? $validation['capabilities'] : [];

        $lines = [
            sprintf('Business remote minimum inventory live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Live output: %s', (string) ($validation['live_output_dir'] ?? 'build/live-output/...')),
            sprintf('Contract: %s', (string) ($runtimeProjection['live_contract'] ?? '')),
            sprintf('Queue root: %s', (string) ($runtimeProjection['queue_root'] ?? '')),
            sprintf('Queue file: %s', (string) ($payload['queue_file'] ?? '')),
            sprintf('Endpoint config: %s', (string) ($remote['endpoint_config_file'] ?? '')),
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
