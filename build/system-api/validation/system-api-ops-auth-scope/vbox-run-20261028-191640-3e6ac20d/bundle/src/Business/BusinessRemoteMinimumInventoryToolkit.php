<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessRemoteMinimumInventoryToolkit
{
    public function __construct(private readonly string $rootDir)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function validateRemoteMinimumInventory(
        string $profileId,
        ?string $overlayDir = null,
        ?string $installBundleDir = null
    ): array {
        $localGate = (new BusinessPilotLocalStateToolkit($this->rootDir))
            ->validateLocalState($profileId, $overlayDir, $installBundleDir);

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
            throw new ValidationError('El contrato remoto minimo de inventario no corresponde al perfil solicitado');
        }

        $resolvedOverlayDir = $overlayDir ?? $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays' . DIRECTORY_SEPARATOR . $profileId;
        $overlayContract = $this->readRequiredJsonFile(
            $resolvedOverlayDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'business-remote-minimum-inventory.json',
            'No se pudo leer business-remote-minimum-inventory.json del overlay materializado'
        );

        $resolvedInstallBundleDir = $installBundleDir ?? $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . $profileId;
        $runtimeExports = $this->readRuntimeExports(
            $resolvedInstallBundleDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'install.env',
            'No se pudo leer install.env del runtime de instalacion'
        );
        $manifest = $this->readRequiredJsonFile(
            $this->rootDir . DIRECTORY_SEPARATOR . 'manifests' . DIRECTORY_SEPARATOR . $profileId . '.profile.json',
            'No se pudo leer el manifiesto Business'
        );
        $localStateContract = $this->readRequiredJsonFile(
            $this->rootDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editions' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'pilot-local-state.json',
            'No se pudo leer pilot-local-state.json de Business'
        );

        $featureFlags = is_array($manifest['features'] ?? null) ? array_values(array_filter($manifest['features'], 'is_string')) : [];
        foreach (['business-policy-hooks', 'inventory-ready', 'inventory-remote-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en manifests/%s.profile.json', $requiredFeature, $profileId));
            }
        }

        $scope = is_array($sourceContract['scope'] ?? null) ? $sourceContract['scope'] : [];
        $payload = is_array($sourceContract['payload'] ?? null) ? $sourceContract['payload'] : [];
        $remote = is_array($sourceContract['remote'] ?? null) ? $sourceContract['remote'] : [];
        $boundaries = is_array($sourceContract['boundaries'] ?? null) ? $sourceContract['boundaries'] : [];
        $localInventory = is_array($localStateContract['inventory'] ?? null) ? $localStateContract['inventory'] : [];
        $minimumFields = is_array($payload['minimum_fields'] ?? null) ? array_values(array_filter($payload['minimum_fields'], 'is_string')) : [];
        $redactedFields = is_array($payload['redacted_fields'] ?? null) ? array_values(array_filter($payload['redacted_fields'], 'is_string')) : [];

        if (($scope['backend_required'] ?? null) !== false) {
            throw new ValidationError('El slice remoto minimo debe mantener backend_required=false');
        }

        if (($scope['activation'] ?? null) !== 'configured-or-enrolled') {
            throw new ValidationError('El slice remoto minimo requiere activation=configured-or-enrolled');
        }

        if (($scope['delivery_state'] ?? null) !== 'deferred-ready') {
            throw new ValidationError('El slice remoto minimo requiere delivery_state=deferred-ready');
        }

        $sourceDeviceState = (string) ($payload['source_device_state_file'] ?? '');
        if ($sourceDeviceState === '' || $sourceDeviceState !== (string) ($localInventory['device_state_file'] ?? '')) {
            throw new ValidationError('El source_device_state_file remoto debe anclarse al device_state_file local de Slice C');
        }

        foreach (['queue_file', 'last_delivery_file', 'last_payload_file'] as $remoteStateFile) {
            $value = (string) ($payload[$remoteStateFile] ?? '');
            if ($value === '' || !str_starts_with($value, '/var/lib/w4/inventory/remote/')) {
                throw new ValidationError(sprintf('El contrato remoto requiere %s dentro de /var/lib/w4/inventory/remote/', $remoteStateFile));
            }
        }

        if (!str_starts_with((string) ($remote['endpoint_config_file'] ?? ''), '/etc/w4/inventory/')) {
            throw new ValidationError('El endpoint_config_file remoto debe vivir bajo /etc/w4/inventory/');
        }

        if (($remote['transport'] ?? null) !== 'https-json-manifest') {
            throw new ValidationError('El contrato remoto minimo requiere transport=https-json-manifest');
        }

        if (($remote['transport_security'] ?? null) !== 'tls-required') {
            throw new ValidationError('El contrato remoto minimo requiere transport_security=tls-required');
        }

        if (($remote['authentication_mode'] ?? null) !== 'device-credential') {
            throw new ValidationError('El contrato remoto minimo requiere authentication_mode=device-credential');
        }

        if (($remote['delivery_mode'] ?? null) !== 'deferred-push') {
            throw new ValidationError('El contrato remoto minimo requiere delivery_mode=deferred-push');
        }

        if (($remote['retry_policy'] ?? null) !== 'local-backoff') {
            throw new ValidationError('El contrato remoto minimo requiere retry_policy=local-backoff');
        }

        if ((int) ($remote['max_batch_size'] ?? 0) !== 1) {
            throw new ValidationError('El contrato remoto minimo requiere max_batch_size=1');
        }

        foreach (['profile_id', 'hostname', 'policy_state', 'update_channel'] as $requiredField) {
            if (!in_array($requiredField, $minimumFields, true)) {
                throw new ValidationError(sprintf('El payload remoto minimo debe incluir el campo %s', $requiredField));
            }
        }

        if (!in_array('local_username', $redactedFields, true)) {
            throw new ValidationError('El payload remoto minimo debe declarar local_username como campo redaccionado');
        }

        if (($boundaries['policy_authority'] ?? null) !== 'local-baseline') {
            throw new ValidationError('El slice remoto minimo debe mantener policy_authority=local-baseline');
        }

        if (($boundaries['remote_writeback'] ?? null) !== 'inventory-only') {
            throw new ValidationError('El slice remoto minimo debe mantener remote_writeback=inventory-only');
        }

        if (($boundaries['update_windows_deferred'] ?? null) !== true) {
            throw new ValidationError('El slice remoto minimo debe mantener update_windows_deferred=true');
        }

        $expectedExports = [
            'W4_REMOTE_INVENTORY_SOURCE_FILE' => $sourceDeviceState,
            'W4_REMOTE_INVENTORY_QUEUE_FILE' => (string) ($payload['queue_file'] ?? ''),
            'W4_REMOTE_INVENTORY_LAST_DELIVERY_FILE' => (string) ($payload['last_delivery_file'] ?? ''),
            'W4_REMOTE_INVENTORY_LAST_PAYLOAD_FILE' => (string) ($payload['last_payload_file'] ?? ''),
            'W4_REMOTE_INVENTORY_ENDPOINT_FILE' => (string) ($remote['endpoint_config_file'] ?? ''),
            'W4_REMOTE_INVENTORY_TRANSPORT' => (string) ($remote['transport'] ?? ''),
            'W4_REMOTE_INVENTORY_AUTH_MODE' => (string) ($remote['authentication_mode'] ?? ''),
            'W4_REMOTE_INVENTORY_ACTIVATION' => (string) ($scope['activation'] ?? ''),
        ];

        foreach ($expectedExports as $key => $expectedValue) {
            if (($runtimeExports[$key] ?? null) !== $expectedValue) {
                throw new ValidationError(sprintf('install.env no conserva %s=%s', $key, $expectedValue));
            }
        }

        $sourceContractHash = md5(json_encode($sourceContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $overlayContractHash = md5(json_encode($overlayContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if ($sourceContractHash !== $overlayContractHash) {
            throw new ValidationError('El contrato runtime del overlay no coincide con el contrato fuente remoto minimo de inventario');
        }

        $capabilities = [
            [
                'id' => 'remote-inventory-contract',
                'label' => 'Contrato remoto minimo de inventario',
                'status' => 'available',
                'transport' => (string) ($remote['transport'] ?? ''),
                'activation' => (string) ($scope['activation'] ?? ''),
            ],
            [
                'id' => 'remote-inventory-queue',
                'label' => 'Cola local de inventario remoto diferido',
                'status' => 'available',
                'source_device_state_file' => $sourceDeviceState,
                'queue_file' => (string) ($payload['queue_file'] ?? ''),
                'last_delivery_file' => (string) ($payload['last_delivery_file'] ?? ''),
            ],
            [
                'id' => 'remote-endpoint-slot',
                'label' => 'Slot tipado para endpoint remoto',
                'status' => 'available',
                'endpoint_config_file' => (string) ($remote['endpoint_config_file'] ?? ''),
                'authentication_mode' => (string) ($remote['authentication_mode'] ?? ''),
                'transport_security' => (string) ($remote['transport_security'] ?? ''),
            ],
            [
                'id' => 'remote-minimum-redaction',
                'label' => 'Payload minimo remoto con redaccion explicita',
                'status' => 'available',
                'minimum_fields' => $minimumFields,
                'redacted_fields' => $redactedFields,
            ],
            [
                'id' => 'remote-pilot-boundary',
                'label' => 'Frontera remota acotada sin backend obligatorio',
                'status' => 'available',
                'policy_authority' => (string) ($boundaries['policy_authority'] ?? ''),
                'remote_writeback' => (string) ($boundaries['remote_writeback'] ?? ''),
            ],
        ];

        return [
            'business_remote_minimum_inventory_validation_schema_version' => 1,
            'kind' => 'business-remote-minimum-inventory-validation',
            'profile_id' => $profileId,
            'overlay_dir' => $this->relativePath($resolvedOverlayDir),
            'install_bundle_dir' => $this->relativePath($resolvedInstallBundleDir),
            'local_gate' => [
                'kind' => (string) ($localGate['kind'] ?? 'business-pilot-local-state-validation'),
                'policy_state' => (string) ($localGate['scope']['policy_state'] ?? ''),
                'inventory_state' => (string) ($localGate['inventory_contract']['device_state_file'] ?? ''),
            ],
            'feature_flags' => $featureFlags,
            'scope' => $scope,
            'payload_contract' => $payload,
            'remote_contract' => $remote,
            'boundaries' => $boundaries,
            'runtime_exports' => $expectedExports,
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
        $payload = is_array($validation['payload_contract'] ?? null) ? $validation['payload_contract'] : [];
        $remote = is_array($validation['remote_contract'] ?? null) ? $validation['remote_contract'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];
        $capabilities = is_array($validation['capabilities'] ?? null) ? $validation['capabilities'] : [];

        $lines = [
            sprintf('Business remote minimum inventory · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Overlay: %s', (string) ($validation['overlay_dir'] ?? 'build/overlays/...')),
            sprintf('Install bundle: %s', (string) ($validation['install_bundle_dir'] ?? 'build/install/...')),
            sprintf('Activation: %s', (string) ($validation['scope']['activation'] ?? '')),
            sprintf('Source device state: %s', (string) ($payload['source_device_state_file'] ?? '')),
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
            /** @var array<string, mixed> $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $exception->getMessage()));
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    private function readRuntimeExports(string $path, string $errorMessage): array
    {
        if (!is_file($path)) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        $exports = [];
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || !str_starts_with($line, 'export ') || !str_contains($line, '=')) {
                continue;
            }

            $assignment = substr($line, 7);
            [$key, $value] = explode('=', $assignment, 2);
            $exports[$key] = trim($value, "\"'");
        }

        return $exports;
    }

    private function relativePath(string $path): string
    {
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->rootDir);
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (str_starts_with($normalizedPath, $normalizedRoot . DIRECTORY_SEPARATOR)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($normalizedPath, strlen($normalizedRoot) + 1));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $normalizedPath);
    }
}
