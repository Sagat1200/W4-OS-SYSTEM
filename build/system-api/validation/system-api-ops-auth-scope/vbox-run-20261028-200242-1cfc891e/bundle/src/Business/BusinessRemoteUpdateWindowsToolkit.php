<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessRemoteUpdateWindowsToolkit
{
    public function __construct(private readonly string $rootDir)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function validateRemoteUpdateWindows(
        string $profileId,
        ?string $overlayDir = null,
        ?string $installBundleDir = null
    ): array {
        $inventoryGate = (new BusinessRemoteMinimumInventoryToolkit($this->rootDir))
            ->validateRemoteMinimumInventory($profileId, $overlayDir, $installBundleDir);

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
            throw new ValidationError('El contrato remoto de ventanas de update no corresponde al perfil solicitado');
        }

        $resolvedOverlayDir = $overlayDir ?? $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays' . DIRECTORY_SEPARATOR . $profileId;
        $overlayContract = $this->readRequiredJsonFile(
            $resolvedOverlayDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'business-remote-update-windows.json',
            'No se pudo leer business-remote-update-windows.json del overlay materializado'
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
        $remoteInventoryContract = $this->readRequiredJsonFile(
            $this->rootDir . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'editions' . DIRECTORY_SEPARATOR . 'business' . DIRECTORY_SEPARATOR . 'remote-minimum-inventory.json',
            'No se pudo leer remote-minimum-inventory.json de Business'
        );

        $featureFlags = is_array($manifest['features'] ?? null) ? array_values(array_filter($manifest['features'], 'is_string')) : [];
        foreach (['inventory-remote-ready', 'update-windows-remote-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en manifests/%s.profile.json', $requiredFeature, $profileId));
            }
        }

        $scope = is_array($sourceContract['scope'] ?? null) ? $sourceContract['scope'] : [];
        $coordination = is_array($sourceContract['coordination'] ?? null) ? $sourceContract['coordination'] : [];
        $policy = is_array($sourceContract['policy'] ?? null) ? $sourceContract['policy'] : [];
        $boundaries = is_array($sourceContract['boundaries'] ?? null) ? $sourceContract['boundaries'] : [];
        $remoteInventoryPayload = is_array($remoteInventoryContract['payload'] ?? null) ? $remoteInventoryContract['payload'] : [];

        if (($scope['backend_required'] ?? null) !== false) {
            throw new ValidationError('El slice remoto de ventanas de update debe mantener backend_required=false');
        }

        if (($scope['activation'] ?? null) !== 'configured-or-enrolled') {
            throw new ValidationError('El slice remoto de ventanas de update requiere activation=configured-or-enrolled');
        }

        if (($scope['enforcement'] ?? null) !== 'local-engine-only') {
            throw new ValidationError('El slice remoto de ventanas de update requiere enforcement=local-engine-only');
        }

        if ((string) ($coordination['source_device_state_file'] ?? '') !== (string) ($remoteInventoryPayload['source_device_state_file'] ?? '')) {
            throw new ValidationError('Las ventanas remotas deben anclarse al mismo source_device_state_file del slice de inventario remoto');
        }

        if ((string) ($coordination['source_remote_queue_file'] ?? '') !== (string) ($remoteInventoryPayload['queue_file'] ?? '')) {
            throw new ValidationError('Las ventanas remotas deben anclarse a la misma queue_file del slice de inventario remoto');
        }

        foreach (['cohort_assignment_file', 'window_definition_file', 'plan_request_file', 'last_result_file'] as $requiredPath) {
            $value = (string) ($coordination[$requiredPath] ?? '');
            if ($value === '' || !str_starts_with($value, '/var/lib/w4/update/remote/')) {
                throw new ValidationError(sprintf('El contrato de ventanas remotas requiere %s dentro de /var/lib/w4/update/remote/', $requiredPath));
            }
        }

        $allowedChannels = is_array($policy['allowed_channels'] ?? null) ? array_values(array_filter($policy['allowed_channels'], 'is_string')) : [];
        if (!in_array('testing', $allowedChannels, true) || !in_array('stable', $allowedChannels, true)) {
            throw new ValidationError('Las ventanas remotas deben permitir al menos los canales testing y stable');
        }

        if (($policy['update_channel_source'] ?? null) !== 'device-state') {
            throw new ValidationError('Las ventanas remotas requieren update_channel_source=device-state');
        }

        $defaultChannel = (string) ($policy['default_channel'] ?? '');
        if ($defaultChannel === '' || !in_array($defaultChannel, $allowedChannels, true)) {
            throw new ValidationError('Las ventanas remotas requieren default_channel dentro de allowed_channels');
        }

        if ((string) ($policy['schedule_timezone'] ?? '') !== 'UTC') {
            throw new ValidationError('Las ventanas remotas requieren schedule_timezone=UTC');
        }

        if ((int) ($policy['max_deferral_minutes'] ?? 0) !== 1440) {
            throw new ValidationError('Las ventanas remotas requieren max_deferral_minutes=1440');
        }

        if (($policy['reboot_outside_window'] ?? null) !== false) {
            throw new ValidationError('Las ventanas remotas deben mantener reboot_outside_window=false');
        }

        $requiredHealthChecks = is_array($policy['require_health_checks'] ?? null)
            ? array_values(array_filter($policy['require_health_checks'], 'is_string'))
            : [];
        foreach (['compatibility', 'power', 'disk-space', 'snapshot', 'policy'] as $requiredHealthCheck) {
            if (!in_array($requiredHealthCheck, $requiredHealthChecks, true)) {
                throw new ValidationError(sprintf('Las ventanas remotas deben incluir el health check %s', $requiredHealthCheck));
            }
        }

        if (($boundaries['local_update_engine'] ?? null) !== 'reuse-mx-004') {
            throw new ValidationError('Las ventanas remotas deben mantener local_update_engine=reuse-mx-004');
        }

        $remoteDecides = is_array($boundaries['remote_decides'] ?? null)
            ? array_values(array_filter($boundaries['remote_decides'], 'is_string'))
            : [];
        foreach (['cohort', 'window', 'target-channel'] as $decision) {
            if (!in_array($decision, $remoteDecides, true)) {
                throw new ValidationError(sprintf('Las ventanas remotas deben declarar remote_decides=%s', $decision));
            }
        }

        $localValidates = is_array($boundaries['local_validates'] ?? null)
            ? array_values(array_filter($boundaries['local_validates'], 'is_string'))
            : [];
        foreach (['compatibility', 'power', 'disk-space', 'policy', 'health-checks'] as $validation) {
            if (!in_array($validation, $localValidates, true)) {
                throw new ValidationError(sprintf('Las ventanas remotas deben declarar local_validates=%s', $validation));
            }
        }

        if (($boundaries['remote_installer_allowed'] ?? null) !== false) {
            throw new ValidationError('Las ventanas remotas deben mantener remote_installer_allowed=false');
        }

        if (($boundaries['downgrade_allowed'] ?? null) !== false) {
            throw new ValidationError('Las ventanas remotas deben mantener downgrade_allowed=false');
        }

        $expectedExports = [
            'W4_REMOTE_UPDATE_COHORT_ASSIGNMENT_FILE' => (string) ($coordination['cohort_assignment_file'] ?? ''),
            'W4_REMOTE_UPDATE_WINDOW_DEFINITION_FILE' => (string) ($coordination['window_definition_file'] ?? ''),
            'W4_REMOTE_UPDATE_PLAN_REQUEST_FILE' => (string) ($coordination['plan_request_file'] ?? ''),
            'W4_REMOTE_UPDATE_LAST_RESULT_FILE' => (string) ($coordination['last_result_file'] ?? ''),
            'W4_REMOTE_UPDATE_DEFAULT_CHANNEL' => $defaultChannel,
            'W4_REMOTE_UPDATE_TIMEZONE' => (string) ($policy['schedule_timezone'] ?? ''),
            'W4_REMOTE_UPDATE_MAX_DEFERRAL_MINUTES' => (string) ($policy['max_deferral_minutes'] ?? ''),
            'W4_REMOTE_UPDATE_REBOOT_OUTSIDE_WINDOW' => (($policy['reboot_outside_window'] ?? false) === true) ? '1' : '0',
            'W4_REMOTE_UPDATE_ENGINE' => (string) ($boundaries['local_update_engine'] ?? ''),
            'W4_REMOTE_UPDATE_ACTIVATION' => (string) ($scope['activation'] ?? ''),
        ];

        foreach ($expectedExports as $key => $expectedValue) {
            if (($runtimeExports[$key] ?? null) !== $expectedValue) {
                throw new ValidationError(sprintf('install.env no conserva %s=%s', $key, $expectedValue));
            }
        }

        $sourceContractHash = md5(json_encode($sourceContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $overlayContractHash = md5(json_encode($overlayContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if ($sourceContractHash !== $overlayContractHash) {
            throw new ValidationError('El contrato runtime del overlay no coincide con el contrato fuente remoto de ventanas de update');
        }

        $capabilities = [
            [
                'id' => 'remote-update-window-contract',
                'label' => 'Contrato remoto minimo de ventanas de update',
                'status' => 'available',
                'activation' => (string) ($scope['activation'] ?? ''),
                'enforcement' => (string) ($scope['enforcement'] ?? ''),
            ],
            [
                'id' => 'remote-update-window-cohort',
                'label' => 'Asignacion remota de cohorte y ventana',
                'status' => 'available',
                'cohort_assignment_file' => (string) ($coordination['cohort_assignment_file'] ?? ''),
                'window_definition_file' => (string) ($coordination['window_definition_file'] ?? ''),
                'plan_request_file' => (string) ($coordination['plan_request_file'] ?? ''),
            ],
            [
                'id' => 'remote-update-window-policy',
                'label' => 'Politica remota de canal y deferimiento',
                'status' => 'available',
                'allowed_channels' => $allowedChannels,
                'default_channel' => $defaultChannel,
                'max_deferral_minutes' => (int) ($policy['max_deferral_minutes'] ?? 0),
            ],
            [
                'id' => 'remote-update-window-health',
                'label' => 'Reuse del motor local con health checks obligatorios',
                'status' => 'available',
                'local_update_engine' => (string) ($boundaries['local_update_engine'] ?? ''),
                'require_health_checks' => $requiredHealthChecks,
            ],
            [
                'id' => 'remote-update-window-boundary',
                'label' => 'Frontera remota acotada sin instalador paralelo',
                'status' => 'available',
                'remote_installer_allowed' => (($boundaries['remote_installer_allowed'] ?? false) === true) ? 'true' : 'false',
                'downgrade_allowed' => (($boundaries['downgrade_allowed'] ?? false) === true) ? 'true' : 'false',
            ],
        ];

        return [
            'business_remote_update_windows_validation_schema_version' => 1,
            'kind' => 'business-remote-update-windows-validation',
            'profile_id' => $profileId,
            'overlay_dir' => $this->relativePath($resolvedOverlayDir),
            'install_bundle_dir' => $this->relativePath($resolvedInstallBundleDir),
            'inventory_gate' => [
                'kind' => (string) ($inventoryGate['kind'] ?? 'business-remote-minimum-inventory-validation'),
                'queue_file' => (string) ($inventoryGate['payload_contract']['queue_file'] ?? ''),
                'endpoint_config_file' => (string) ($inventoryGate['remote_contract']['endpoint_config_file'] ?? ''),
            ],
            'feature_flags' => $featureFlags,
            'scope' => $scope,
            'coordination_contract' => $coordination,
            'policy_contract' => $policy,
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
        $coordination = is_array($validation['coordination_contract'] ?? null) ? $validation['coordination_contract'] : [];
        $policy = is_array($validation['policy_contract'] ?? null) ? $validation['policy_contract'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];
        $capabilities = is_array($validation['capabilities'] ?? null) ? $validation['capabilities'] : [];

        $lines = [
            sprintf('Business remote update windows · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Overlay: %s', (string) ($validation['overlay_dir'] ?? 'build/overlays/...')),
            sprintf('Install bundle: %s', (string) ($validation['install_bundle_dir'] ?? 'build/install/...')),
            sprintf('Activation: %s', (string) ($validation['scope']['activation'] ?? '')),
            sprintf('Cohort assignment: %s', (string) ($coordination['cohort_assignment_file'] ?? '')),
            sprintf('Window definition: %s', (string) ($coordination['window_definition_file'] ?? '')),
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
