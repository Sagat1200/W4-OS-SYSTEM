<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessPilotLocalStateToolkit
{
    public function __construct(private readonly string $rootDir)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function validateLocalState(
        string $profileId,
        ?string $overlayDir = null,
        ?string $installBundleDir = null
    ): array {
        $contractPath = $this->rootDir
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'editions'
            . DIRECTORY_SEPARATOR . 'business'
            . DIRECTORY_SEPARATOR . 'pilot-local-state.json';
        $sourceContract = $this->readRequiredJsonFile(
            $contractPath,
            'No se pudo leer pilot-local-state.json de Business'
        );

        if (($sourceContract['profile_id'] ?? null) !== $profileId) {
            throw new ValidationError('El contrato local de politica/inventario no corresponde al perfil solicitado');
        }

        $resolvedOverlayDir = $overlayDir ?? $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays' . DIRECTORY_SEPARATOR . $profileId;
        $overlayContract = $this->readRequiredJsonFile(
            $resolvedOverlayDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'business-pilot-local-state.json',
            'No se pudo leer business-pilot-local-state.json del overlay materializado'
        );

        $resolvedInstallBundleDir = $installBundleDir ?? $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install' . DIRECTORY_SEPARATOR . $profileId;
        $bundlePolicy = $this->readRequiredJsonFile(
            $resolvedInstallBundleDir . DIRECTORY_SEPARATOR . 'edition-policy.json',
            'No se pudo leer edition-policy.json del bundle de instalacion'
        );
        $installationPlan = $this->readRequiredJsonFile(
            $resolvedInstallBundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json',
            'No se pudo leer installation-plan.json del bundle de instalacion'
        );
        $runtimeExports = $this->readRuntimeExports(
            $resolvedInstallBundleDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'install.env',
            'No se pudo leer install.env del runtime de instalacion'
        );
        $manifest = $this->readRequiredJsonFile(
            $this->rootDir . DIRECTORY_SEPARATOR . 'manifests' . DIRECTORY_SEPARATOR . $profileId . '.profile.json',
            'No se pudo leer el manifiesto Business'
        );

        $featureFlags = is_array($manifest['features'] ?? null) ? array_values(array_filter($manifest['features'], 'is_string')) : [];
        foreach (['business-policy-hooks', 'inventory-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en manifests/%s.profile.json', $requiredFeature, $profileId));
            }
        }

        if (($bundlePolicy['boot']['default_target'] ?? null) !== 'graphical.target') {
            throw new ValidationError('edition-policy.json debe conservar graphical.target para Business');
        }

        if (($bundlePolicy['storage']['root_filesystem'] ?? null) !== 'btrfs') {
            throw new ValidationError('edition-policy.json debe conservar storage.root_filesystem=btrfs');
        }

        if (($bundlePolicy['update']['snapshot_backend'] ?? null) !== 'btrfs') {
            throw new ValidationError('edition-policy.json debe conservar update.snapshot_backend=btrfs');
        }

        $inventoryMount = $this->resolveInventoryMount($installationPlan);
        if ($inventoryMount !== '/var/lib/w4') {
            throw new ValidationError(sprintf('installation-plan.json debe montar @inventory en /var/lib/w4 y obtuvo %s', $inventoryMount));
        }

        $policyContract = is_array($sourceContract['policy'] ?? null) ? $sourceContract['policy'] : [];
        $inventoryContract = is_array($sourceContract['inventory'] ?? null) ? $sourceContract['inventory'] : [];
        $scope = is_array($sourceContract['scope'] ?? null) ? $sourceContract['scope'] : [];
        $precedence = is_array($policyContract['precedence'] ?? null) ? array_values(array_filter($policyContract['precedence'], 'is_string')) : [];
        $minimumFields = is_array($inventoryContract['minimum_fields'] ?? null) ? array_values(array_filter($inventoryContract['minimum_fields'], 'is_string')) : [];

        foreach (['edition-baseline', 'last-known-valid'] as $requiredPrecedence) {
            if (!in_array($requiredPrecedence, $precedence, true)) {
                throw new ValidationError(sprintf('El contrato local debe incluir la precedencia %s', $requiredPrecedence));
            }
        }

        foreach (['profile_id', 'hostname', 'policy_state'] as $requiredField) {
            if (!in_array($requiredField, $minimumFields, true)) {
                throw new ValidationError(sprintf('El inventario minimo debe incluir el campo %s', $requiredField));
            }
        }

        $expectedExports = [
            'W4_EDITION_POLICY_FILE' => '../edition-policy.json',
            'W4_POLICY_BASELINE_SOURCE' => '../edition-policy.json',
            'W4_POLICY_BASELINE_RUNTIME_ENV' => (string) ($policyContract['baseline_runtime_env'] ?? '/etc/w4/edition-policy.env'),
            'W4_POLICY_LAST_KNOWN_FILE' => (string) ($policyContract['last_known_policy_file'] ?? ''),
            'W4_POLICY_EFFECTIVE_FILE' => (string) ($policyContract['effective_policy_file'] ?? ''),
            'W4_POLICY_INVALID_BEHAVIOR' => (string) ($policyContract['invalid_policy_behavior'] ?? 'keep-last-valid'),
            'W4_INVENTORY_DEVICE_STATE_FILE' => (string) ($inventoryContract['device_state_file'] ?? ''),
            'W4_INVENTORY_TRANSPORT' => (string) ($inventoryContract['transport'] ?? 'local-only'),
        ];

        foreach ($expectedExports as $key => $expectedValue) {
            if (($runtimeExports[$key] ?? null) !== $expectedValue) {
                throw new ValidationError(sprintf('install.env no conserva %s=%s', $key, $expectedValue));
            }
        }

        $sourceContractHash = md5(json_encode($sourceContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $overlayContractHash = md5(json_encode($overlayContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if ($sourceContractHash !== $overlayContractHash) {
            throw new ValidationError('El contrato runtime del overlay no coincide con el contrato fuente local de politica/inventario');
        }

        $capabilities = [
            [
                'id' => 'policy-baseline-bridge',
                'label' => 'Puente local de politica base',
                'status' => 'available',
                'baseline_source' => (string) ($policyContract['baseline_source'] ?? ''),
                'baseline_runtime_env' => (string) ($policyContract['baseline_runtime_env'] ?? ''),
            ],
            [
                'id' => 'last-known-valid-cache',
                'label' => 'Cache local de ultima politica valida',
                'status' => 'available',
                'last_known_policy_file' => (string) ($policyContract['last_known_policy_file'] ?? ''),
                'invalid_policy_behavior' => (string) ($policyContract['invalid_policy_behavior'] ?? ''),
            ],
            [
                'id' => 'effective-policy-slot',
                'label' => 'Slot local de politica efectiva',
                'status' => 'available',
                'effective_policy_file' => (string) ($policyContract['effective_policy_file'] ?? ''),
                'precedence' => $precedence,
            ],
            [
                'id' => 'inventory-minimum-record',
                'label' => 'Inventario minimo local',
                'status' => 'available',
                'device_state_file' => (string) ($inventoryContract['device_state_file'] ?? ''),
                'transport' => (string) ($inventoryContract['transport'] ?? ''),
                'minimum_fields' => $minimumFields,
            ],
            [
                'id' => 'offline-pilot-boundary',
                'label' => 'Frontera local sin backend obligatorio',
                'status' => (($scope['backend_required'] ?? null) === false) ? 'available' : 'missing',
                'policy_state' => (string) ($scope['policy_state'] ?? ''),
                'inventory_transport' => (string) ($scope['inventory_transport'] ?? ''),
            ],
        ];

        return [
            'business_pilot_local_state_validation_schema_version' => 1,
            'kind' => 'business-pilot-local-state-validation',
            'profile_id' => $profileId,
            'overlay_dir' => $this->relativePath($resolvedOverlayDir),
            'install_bundle_dir' => $this->relativePath($resolvedInstallBundleDir),
            'feature_flags' => $featureFlags,
            'scope' => $scope,
            'policy_contract' => $policyContract,
            'inventory_contract' => $inventoryContract,
            'runtime_exports' => $expectedExports,
            'installation_policy' => [
                'default_target' => (string) ($bundlePolicy['boot']['default_target'] ?? ''),
                'root_filesystem' => (string) ($bundlePolicy['storage']['root_filesystem'] ?? ''),
                'snapshot_backend' => (string) ($bundlePolicy['update']['snapshot_backend'] ?? ''),
                'inventory_mount' => $inventoryMount,
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
        $installationPolicy = is_array($validation['installation_policy'] ?? null) ? $validation['installation_policy'] : [];
        $policyContract = is_array($validation['policy_contract'] ?? null) ? $validation['policy_contract'] : [];
        $inventoryContract = is_array($validation['inventory_contract'] ?? null) ? $validation['inventory_contract'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];
        $capabilities = is_array($validation['capabilities'] ?? null) ? $validation['capabilities'] : [];

        $lines = [
            sprintf('Business pilot local state · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Overlay: %s', (string) ($validation['overlay_dir'] ?? 'build/overlays/...')),
            sprintf('Install bundle: %s', (string) ($validation['install_bundle_dir'] ?? 'build/install/...')),
            sprintf('Policy state: %s', (string) (($validation['scope']['policy_state'] ?? ''))),
            sprintf('Last known policy: %s', (string) ($policyContract['last_known_policy_file'] ?? '')),
            sprintf('Effective policy: %s', (string) ($policyContract['effective_policy_file'] ?? '')),
            sprintf('Inventory state: %s', (string) ($inventoryContract['device_state_file'] ?? '')),
            sprintf('Inventory mount: %s', (string) ($installationPolicy['inventory_mount'] ?? '')),
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

    /**
     * @param array<string, mixed> $installationPlan
     */
    private function resolveInventoryMount(array $installationPlan): string
    {
        $storage = is_array($installationPlan['storage'] ?? null) ? $installationPlan['storage'] : [];
        $btrfs = is_array($storage['btrfs'] ?? null) ? $storage['btrfs'] : [];
        $subvolumes = is_array($btrfs['subvolumes'] ?? null) ? $btrfs['subvolumes'] : [];

        foreach ($subvolumes as $subvolume) {
            if (!is_array($subvolume)) {
                continue;
            }

            if (($subvolume['name'] ?? null) === '@inventory') {
                return (string) ($subvolume['mountpoint'] ?? '');
            }
        }

        throw new ValidationError('installation-plan.json no define el subvolumen @inventory');
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
