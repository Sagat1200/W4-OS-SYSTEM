<?php

declare(strict_types=1);

namespace W4\OS\Installer;

use W4\OS\Support\ValidationError;

final class EditionPolicyToolkit
{
    public const TARGET_ENV_PATH = '/etc/w4/edition-policy.env';

    /**
     * @param array<string, mixed> $installationProfile
     * @param array<string, mixed> $editionPolicy
     * @return array<string, mixed>
     */
    public function normalizeForProfile(
        string $profileId,
        array $installationProfile,
        array $editionPolicy = [],
        ?string $editionPolicyPath = null
    ): array {
        $edition = strtolower((string) ($installationProfile['edition'] ?? 'home'));
        $editionName = ucfirst($edition);
        $hostnamePrefix = 'w4-' . $edition;
        $defaultTarget = $edition === 'server' ? 'multi-user.target' : 'graphical.target';
        $sshEnabled = $edition === 'server';

        if ($editionPolicy !== []) {
            if (($editionPolicy['profile_id'] ?? null) !== $profileId) {
                throw new ValidationError(sprintf(
                    'La politica de edicion no corresponde al profile_id %s',
                    $profileId
                ));
            }

            /** @var array<string, mixed> $branding */
            $branding = is_array($editionPolicy['branding'] ?? null) ? $editionPolicy['branding'] : [];
            /** @var array<string, mixed> $boot */
            $boot = is_array($editionPolicy['boot'] ?? null) ? $editionPolicy['boot'] : [];
            /** @var array<string, mixed> $ssh */
            $ssh = is_array($editionPolicy['ssh'] ?? null) ? $editionPolicy['ssh'] : [];
            /** @var array<string, mixed> $firewall */
            $firewall = is_array($editionPolicy['firewall'] ?? null) ? $editionPolicy['firewall'] : [];

            $editionName = (string) ($branding['edition'] ?? $editionName);
            $hostnamePrefix = (string) ($branding['hostname_prefix'] ?? $hostnamePrefix);
            $defaultTarget = (string) ($boot['default_target'] ?? $defaultTarget);
            $sshEnabled = ($ssh['enabled'] ?? $sshEnabled) === true;

            return [
                'profile_id' => $profileId,
                'path' => $editionPolicyPath ?? $this->defaultPolicyPath($profileId),
                'branding' => [
                    'edition' => $editionName,
                    'hostname_prefix' => $hostnamePrefix,
                ],
                'boot' => [
                    'default_target' => $defaultTarget,
                    'firmware' => (string) ($boot['firmware'] ?? 'uefi'),
                ],
                'ssh' => [
                    'enabled' => $sshEnabled,
                    'root_login' => ($ssh['root_login'] ?? false) === true,
                    'authentication' => (string) ($ssh['authentication'] ?? ($sshEnabled ? 'publickey' : 'disabled')),
                ],
                'firewall' => [
                    'backend' => (string) ($firewall['backend'] ?? 'ufw'),
                    'incoming' => (string) ($firewall['incoming'] ?? 'deny'),
                    'outgoing' => (string) ($firewall['outgoing'] ?? 'allow'),
                ],
            ];
        }

        return [
            'profile_id' => $profileId,
            'path' => $editionPolicyPath ?? $this->defaultPolicyPath($profileId),
            'branding' => [
                'edition' => $editionName,
                'hostname_prefix' => $hostnamePrefix,
            ],
            'boot' => [
                'default_target' => $defaultTarget,
                'firmware' => 'uefi',
            ],
            'ssh' => [
                'enabled' => $sshEnabled,
                'root_login' => false,
                'authentication' => $sshEnabled ? 'publickey' : 'disabled',
            ],
            'firewall' => [
                'backend' => 'ufw',
                'incoming' => 'deny',
                'outgoing' => 'allow',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    public function normalizeFromPlan(array $plan): array
    {
        /** @var array<string, mixed> $installationProfile */
        $installationProfile = is_array($plan['installation_profile'] ?? null) ? $plan['installation_profile'] : [];
        /** @var array<string, mixed> $summary */
        $summary = is_array($plan['summary'] ?? null) ? $plan['summary'] : [];
        /** @var array<string, mixed> $embeddedPolicy */
        $embeddedPolicy = is_array($plan['edition_policy'] ?? null) ? $plan['edition_policy'] : [];

        $profileId = (string) ($plan['profile_id'] ?? '');
        $edition = (string) ($installationProfile['edition'] ?? $summary['edition'] ?? 'home');

        return $this->normalizeForProfile(
            $profileId !== '' ? $profileId : ('w4-os-' . strtolower($edition)),
            ['edition' => $edition],
            $embeddedPolicy,
            (string) ($embeddedPolicy['path'] ?? 'edition-policy.json')
        );
    }

    /**
     * @param array<string, mixed> $normalizedPolicy
     * @return array<string, mixed>
     */
    public function compactRuntimeView(array $normalizedPolicy, ?string $pathOverride = null): array
    {
        return [
            'path' => $pathOverride ?? (string) ($normalizedPolicy['path'] ?? 'edition-policy.json'),
            'default_target' => (string) ($normalizedPolicy['boot']['default_target'] ?? 'multi-user.target'),
            'hostname_prefix' => (string) ($normalizedPolicy['branding']['hostname_prefix'] ?? 'w4-home'),
            'ssh_enabled' => ($normalizedPolicy['ssh']['enabled'] ?? false) === true,
            'firewall_backend' => (string) ($normalizedPolicy['firewall']['backend'] ?? 'ufw'),
            'firewall_incoming' => (string) ($normalizedPolicy['firewall']['incoming'] ?? 'deny'),
            'firewall_outgoing' => (string) ($normalizedPolicy['firewall']['outgoing'] ?? 'allow'),
        ];
    }

    public function defaultPolicyPath(string $profileId): string
    {
        return sprintf('config/editions/%s/policy.json', str_replace('w4-os-', '', $profileId));
    }
}
