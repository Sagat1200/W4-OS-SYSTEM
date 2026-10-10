<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessEnrollmentReadinessToolkit
{
    public function __construct(private readonly string $rootDir)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function validateLocalReadiness(
        string $profileId,
        ?string $overlayDir = null,
        ?string $installBundleDir = null
    ): array {
        $contractPath = $this->rootDir
            . DIRECTORY_SEPARATOR . 'config'
            . DIRECTORY_SEPARATOR . 'editions'
            . DIRECTORY_SEPARATOR . 'business'
            . DIRECTORY_SEPARATOR . 'enrollment-readiness.json';
        $sourceContract = $this->readRequiredJsonFile(
            $contractPath,
            'No se pudo leer enrollment-readiness.json de Business'
        );

        if (($sourceContract['profile_id'] ?? null) !== $profileId) {
            throw new ValidationError('El contrato de readiness no corresponde al perfil solicitado');
        }

        $resolvedOverlayDir = $overlayDir ?? $this->rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays' . DIRECTORY_SEPARATOR . $profileId;
        $overlayContract = $this->readRequiredJsonFile(
            $resolvedOverlayDir . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'business-enrollment-readiness.json',
            'No se pudo leer business-enrollment-readiness.json del overlay materializado'
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
        $manifest = $this->readRequiredJsonFile(
            $this->rootDir . DIRECTORY_SEPARATOR . 'manifests' . DIRECTORY_SEPARATOR . $profileId . '.profile.json',
            'No se pudo leer el manifiesto Business'
        );

        $runtimeExports = $this->readRuntimeExports(
            $resolvedInstallBundleDir . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'install.env',
            'No se pudo leer install.env del runtime de instalacion'
        );

        $hostnamePrefix = (string) ($bundlePolicy['branding']['hostname_prefix'] ?? '');
        if ($hostnamePrefix === '') {
            throw new ValidationError('edition-policy.json no define branding.hostname_prefix');
        }

        $defaultTarget = (string) ($bundlePolicy['boot']['default_target'] ?? '');
        if ($defaultTarget !== 'graphical.target') {
            throw new ValidationError(sprintf('edition-policy.json requiere graphical.target y obtuvo %s', $defaultTarget));
        }

        $enterpriseAgent = is_array($bundlePolicy['enterprise_agent'] ?? null) ? $bundlePolicy['enterprise_agent'] : [];
        if (($enterpriseAgent['installed'] ?? null) !== false || ($enterpriseAgent['enrolled'] ?? null) !== false) {
            throw new ValidationError('La readiness local requiere enterprise_agent instalado=false y enrolled=false');
        }

        $featureFlags = is_array($manifest['features'] ?? null) ? array_values(array_filter($manifest['features'], 'is_string')) : [];
        foreach (['business-policy-hooks', 'device-enrollment-ready', 'inventory-ready'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en manifests/w4-os-business.profile.json', $requiredFeature));
            }
        }

        $planIdentity = is_array($installationPlan['identity'] ?? null) ? $installationPlan['identity'] : [];
        $hostname = (string) ($planIdentity['hostname'] ?? '');
        if ($hostname === '' || !str_starts_with($hostname, $hostnamePrefix)) {
            throw new ValidationError(sprintf('installation-plan.json debe usar hostname con prefijo %s', $hostnamePrefix));
        }

        $user = is_array($planIdentity['user'] ?? null) ? $planIdentity['user'] : [];
        $username = (string) ($user['username'] ?? '');
        $passwordSource = (string) ($user['password_source'] ?? '');
        if ($username === '' || !str_starts_with($passwordSource, 'secret://install/')) {
            throw new ValidationError('installation-plan.json no define un usuario local con password_source tipado');
        }

        $inventoryMount = $this->resolveInventoryMount($installationPlan);
        if ($inventoryMount !== '/var/lib/w4') {
            throw new ValidationError(sprintf('La readiness de inventory requiere /var/lib/w4 y obtuvo %s', $inventoryMount));
        }

        $expectedExports = [
            'W4_EDITION_POLICY_FILE' => '../edition-policy.json',
            'W4_DEFAULT_TARGET' => 'graphical.target',
            'W4_HOSTNAME_PREFIX' => $hostnamePrefix,
        ];
        foreach ($expectedExports as $key => $expectedValue) {
            if (($runtimeExports[$key] ?? null) !== $expectedValue) {
                throw new ValidationError(sprintf('install.env no conserva %s=%s', $key, $expectedValue));
            }
        }

        if (($runtimeExports['W4_SSH_ENABLED'] ?? null) !== '0') {
            throw new ValidationError('install.env debe mantener W4_SSH_ENABLED=0 para Business');
        }

        $sourceContractHash = md5(json_encode($sourceContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $overlayContractHash = md5(json_encode($overlayContract, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        if ($sourceContractHash !== $overlayContractHash) {
            throw new ValidationError('El contrato runtime del overlay no coincide con el contrato fuente de readiness');
        }

        $deviceIdentity = is_array($sourceContract['device_identity'] ?? null) ? $sourceContract['device_identity'] : [];
        $policy = is_array($sourceContract['policy'] ?? null) ? $sourceContract['policy'] : [];
        $inventory = is_array($sourceContract['inventory'] ?? null) ? $sourceContract['inventory'] : [];

        $capabilities = [
            [
                'id' => 'local-device-identity-contract',
                'label' => 'Contrato local de identidad de dispositivo',
                'status' => 'available',
                'state_file' => (string) ($deviceIdentity['state_file'] ?? ''),
                'private_key_file' => (string) ($deviceIdentity['private_key_file'] ?? ''),
                'credential_file' => (string) ($deviceIdentity['credential_file'] ?? ''),
            ],
            [
                'id' => 'policy-cache-contract',
                'label' => 'Contrato local de ultima politica valida',
                'status' => 'available',
                'last_known_policy_file' => (string) ($policy['last_known_policy_file'] ?? ''),
                'effective_policy_file' => (string) ($policy['effective_policy_file'] ?? ''),
                'invalid_policy_behavior' => (string) ($policy['invalid_policy_behavior'] ?? ''),
            ],
            [
                'id' => 'installation-policy-bridge',
                'label' => 'Puente de politica entre bundle y runtime',
                'status' => 'available',
                'bundle_policy_file' => 'edition-policy.json',
                'runtime_env_file' => 'runtime/install.env',
            ],
            [
                'id' => 'inventory-storage-contract',
                'label' => 'Ruta local para estado de inventario',
                'status' => 'available',
                'inventory_mount' => $inventoryMount,
                'device_state_file' => (string) ($inventory['device_state_file'] ?? ''),
            ],
            [
                'id' => 'pilot-boundary',
                'label' => 'Frontera local de piloto no inscrito',
                'status' => 'available',
                'agent_installed' => false,
                'agent_enrolled' => false,
            ],
        ];

        return [
            'business_enrollment_readiness_validation_schema_version' => 1,
            'kind' => 'business-enrollment-readiness-validation',
            'profile_id' => $profileId,
            'overlay_dir' => $this->relativePath($resolvedOverlayDir),
            'install_bundle_dir' => $this->relativePath($resolvedInstallBundleDir),
            'feature_flags' => $featureFlags,
            'scope' => $sourceContract['scope'] ?? [],
            'device_identity' => $deviceIdentity,
            'policy_contract' => $policy,
            'inventory_contract' => $inventory,
            'installation_identity' => [
                'hostname' => $hostname,
                'username' => $username,
                'password_source' => $passwordSource,
                'hostname_prefix' => $hostnamePrefix,
            ],
            'runtime_exports' => $expectedExports + [
                'W4_SSH_ENABLED' => (string) ($runtimeExports['W4_SSH_ENABLED'] ?? ''),
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
        $installationIdentity = is_array($validation['installation_identity'] ?? null) ? $validation['installation_identity'] : [];
        $policyContract = is_array($validation['policy_contract'] ?? null) ? $validation['policy_contract'] : [];
        $inventoryContract = is_array($validation['inventory_contract'] ?? null) ? $validation['inventory_contract'] : [];
        $capabilities = is_array($validation['capabilities'] ?? null) ? $validation['capabilities'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];

        $lines = [
            sprintf('Business enrollment readiness · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Overlay: %s', (string) ($validation['overlay_dir'] ?? 'build/overlays/...')),
            sprintf('Install bundle: %s', (string) ($validation['install_bundle_dir'] ?? 'build/install/...')),
            sprintf('Hostname prefix: %s', (string) ($installationIdentity['hostname_prefix'] ?? '')),
            sprintf('Hostname plan: %s', (string) ($installationIdentity['hostname'] ?? '')),
            sprintf('Last known policy: %s', (string) ($policyContract['last_known_policy_file'] ?? '')),
            sprintf('Inventory state: %s', (string) ($inventoryContract['device_state_file'] ?? '')),
            '',
            'Feature flags:',
        ];

        foreach ($featureFlags as $flag) {
            if (is_string($flag)) {
                $lines[] = sprintf('- %s', $flag);
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
