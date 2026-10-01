<?php

declare(strict_types=1);

namespace W4\OS\Installer;

use JsonException;
use W4\OS\Support\ValidationError;

final class InstallerToolkit
{
    /**
     * @return array<string, mixed>
     */
    public function readJsonFile(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('No se pudo leer el archivo JSON: %s', $path));
        }

        try {
            /** @var array<string, mixed> $data */
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new ValidationError(sprintf('JSON invalido en %s: %s', $path, $exception->getMessage()));
        }

        if (!is_array($data)) {
            throw new ValidationError(sprintf(
                'JSON invalido en %s: la raiz debe ser un objeto o arreglo JSON',
                $path
            ));
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $buildInput
     */
    public function validateBuildInput(array $buildInput, string $sourcePath): void
    {
        if (($buildInput['build_schema_version'] ?? null) !== 1) {
            throw new ValidationError(sprintf('%s: build_schema_version debe ser 1', basename($sourcePath)));
        }

        if (($buildInput['kind'] ?? null) !== 'build-input') {
            throw new ValidationError(sprintf('%s: kind debe ser build-input', basename($sourcePath)));
        }

        foreach (['profile_id', 'profile_name', 'base_manifest_id'] as $field) {
            $value = $buildInput[$field] ?? null;
            if (!is_string($value) || $value === '') {
                throw new ValidationError(sprintf('%s: falta el campo %s', basename($sourcePath), $field));
            }
        }
    }

    /**
     * @param array<string, mixed> $installationProfile
     */
    public function validateInstallationProfile(array $installationProfile, string $sourcePath): void
    {
        if (($installationProfile['installation_schema_version'] ?? null) !== 1) {
            throw new ValidationError(sprintf('%s: installation_schema_version debe ser 1', basename($sourcePath)));
        }

        if (($installationProfile['kind'] ?? null) !== 'installation-profile') {
            throw new ValidationError(sprintf('%s: kind debe ser installation-profile', basename($sourcePath)));
        }

        foreach (['id', 'name', 'edition', 'build_profile_id'] as $field) {
            $value = $installationProfile[$field] ?? null;
            if (!is_string($value) || $value === '') {
                throw new ValidationError(sprintf('%s: falta o es invalido el campo %s', basename($sourcePath), $field));
            }
        }

        /** @var array<string, mixed> $target */
        $target = $this->requireAssocArray($installationProfile, 'target', $installationProfile['target'] ?? null, $sourcePath);
        /** @var array<string, mixed> $storage */
        $storage = $this->requireAssocArray($installationProfile, 'storage', $installationProfile['storage'] ?? null, $sourcePath);
        /** @var array<string, mixed> $identity */
        $identity = $this->requireAssocArray($installationProfile, 'identity', $installationProfile['identity'] ?? null, $sourcePath);
        /** @var array<string, mixed> $security */
        $security = $this->requireAssocArray($installationProfile, 'security', $installationProfile['security'] ?? null, $sourcePath);
        /** @var array<string, mixed> $installer */
        $installer = $this->requireAssocArray($installationProfile, 'installer', $installationProfile['installer'] ?? null, $sourcePath);

        $selector = $this->requireAssocArray($target, 'target.disk_selector', $target['disk_selector'] ?? null, $sourcePath);
        $matches = 0;
        foreach (['serial', 'wwid', 'by_path'] as $field) {
            $value = $selector[$field] ?? null;
            if (is_string($value) && $value !== '') {
                $matches++;
            }
        }

        if ($matches === 0) {
            throw new ValidationError(sprintf('%s: target.disk_selector debe incluir serial, wwid o by_path', basename($sourcePath)));
        }

        $expectedState = $target['expected_state'] ?? null;
        if (!is_string($expectedState) || !in_array($expectedState, ['empty'], true)) {
            throw new ValidationError(sprintf('%s: target.expected_state solo admite "empty" en esta etapa', basename($sourcePath)));
        }

        $confirmDestroy = $target['confirm_destroy'] ?? null;
        if ($confirmDestroy !== true) {
            throw new ValidationError(sprintf('%s: target.confirm_destroy debe ser true para un plan destructivo', basename($sourcePath)));
        }

        $table = $storage['table'] ?? null;
        if ($table !== 'gpt') {
            throw new ValidationError(sprintf('%s: storage.table debe ser gpt', basename($sourcePath)));
        }

        $layout = $storage['layout'] ?? null;
        if ($layout !== 'btrfs-luks2') {
            throw new ValidationError(sprintf('%s: storage.layout debe ser btrfs-luks2', basename($sourcePath)));
        }

        $esp = $this->requireAssocArray($storage, 'storage.esp', $storage['esp'] ?? null, $sourcePath);
        $boot = $this->requireAssocArray($storage, 'storage.boot', $storage['boot'] ?? null, $sourcePath);
        $root = $this->requireAssocArray($storage, 'storage.root', $storage['root'] ?? null, $sourcePath);

        $this->requirePositiveInt($esp, 'storage.esp.size_mib', $esp['size_mib'] ?? null, $sourcePath);
        $this->requirePositiveInt($boot, 'storage.boot.size_mib', $boot['size_mib'] ?? null, $sourcePath);
        $this->requireNonEmptyString($root, 'storage.root.luks_name', $root['luks_name'] ?? null, $sourcePath);

        $subvolumes = $root['subvolumes'] ?? null;
        if (!is_array($subvolumes) || $subvolumes === []) {
            throw new ValidationError(sprintf('%s: storage.root.subvolumes debe contener al menos un subvolumen', basename($sourcePath)));
        }

        foreach ($subvolumes as $index => $subvolume) {
            if (!is_array($subvolume)) {
                throw new ValidationError(sprintf('%s: storage.root.subvolumes[%d] debe ser un objeto', basename($sourcePath), $index));
            }

            $this->requireNonEmptyString($subvolume, sprintf('storage.root.subvolumes[%d].name', $index), $subvolume['name'] ?? null, $sourcePath);
            $this->requireNonEmptyString($subvolume, sprintf('storage.root.subvolumes[%d].mountpoint', $index), $subvolume['mountpoint'] ?? null, $sourcePath);
        }

        $account = $this->requireAssocArray($identity, 'identity.account', $identity['account'] ?? null, $sourcePath);
        $this->requireNonEmptyString($identity, 'identity.locale', $identity['locale'] ?? null, $sourcePath);
        $this->requireNonEmptyString($identity, 'identity.keyboard', $identity['keyboard'] ?? null, $sourcePath);
        $this->requireNonEmptyString($identity, 'identity.hostname', $identity['hostname'] ?? null, $sourcePath);
        $this->requireNonEmptyString($account, 'identity.account.username', $account['username'] ?? null, $sourcePath);
        $this->requireNonEmptyString($account, 'identity.account.display_name', $account['display_name'] ?? null, $sourcePath);
        $this->requireNonEmptyString($account, 'identity.account.password_source', $account['password_source'] ?? null, $sourcePath);

        $encryption = $this->requireAssocArray($security, 'security.encryption', $security['encryption'] ?? null, $sourcePath);
        if (($encryption['enabled'] ?? null) !== true) {
            throw new ValidationError(sprintf('%s: security.encryption.enabled debe ser true', basename($sourcePath)));
        }

        if (($encryption['type'] ?? null) !== 'luks2-passphrase') {
            throw new ValidationError(sprintf('%s: security.encryption.type debe ser luks2-passphrase', basename($sourcePath)));
        }

        $this->requireNonEmptyString($encryption, 'security.encryption.passphrase_source', $encryption['passphrase_source'] ?? null, $sourcePath);
        $this->requireNonEmptyString($installer, 'installer.engine', $installer['engine'] ?? null, $sourcePath);

        $revalidate = $installer['revalidate_before_write'] ?? null;
        if ($revalidate !== true) {
            throw new ValidationError(sprintf('%s: installer.revalidate_before_write debe ser true', basename($sourcePath)));
        }
    }

    /**
     * @param array<string, mixed> $inventory
     */
    public function validateDiskInventory(array $inventory, string $sourcePath): void
    {
        if (($inventory['inventory_schema_version'] ?? null) !== 1) {
            throw new ValidationError(sprintf('%s: inventory_schema_version debe ser 1', basename($sourcePath)));
        }

        if (($inventory['kind'] ?? null) !== 'disk-inventory') {
            throw new ValidationError(sprintf('%s: kind debe ser disk-inventory', basename($sourcePath)));
        }

        $disks = $inventory['disks'] ?? null;
        if (!is_array($disks) || $disks === []) {
            throw new ValidationError(sprintf('%s: disks debe contener al menos un disco', basename($sourcePath)));
        }

        foreach ($disks as $index => $disk) {
            if (!is_array($disk)) {
                throw new ValidationError(sprintf('%s: disks[%d] debe ser un objeto', basename($sourcePath), $index));
            }

            $this->requireNonEmptyString($disk, sprintf('disks[%d].device', $index), $disk['device'] ?? null, $sourcePath);
            $this->requirePositiveInt($disk, sprintf('disks[%d].size_bytes', $index), $disk['size_bytes'] ?? null, $sourcePath);

            foreach (['serial', 'wwid', 'by_path'] as $field) {
                $value = $disk[$field] ?? null;
                if ($value !== null && (!is_string($value) || $value === '')) {
                    throw new ValidationError(sprintf('%s: disks[%d].%s debe ser string no vacio si esta presente', basename($sourcePath), $index, $field));
                }
            }

            foreach (['is_installation_media', 'has_partitions', 'has_filesystem_signatures', 'read_only'] as $field) {
                if (!is_bool($disk[$field] ?? null)) {
                    throw new ValidationError(sprintf('%s: disks[%d].%s debe ser booleano', basename($sourcePath), $index, $field));
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $installationProfile
     * @param array<string, mixed> $buildInput
     * @param array<string, mixed> $inventory
     * @return array<string, mixed>
     */
    public function createInstallationPlan(
        array $installationProfile,
        array $buildInput,
        array $inventory,
        array $editionPolicy = [],
        ?string $editionPolicyPath = null
    ): array
    {
        $profileBuildId = (string) $installationProfile['build_profile_id'];
        $buildInputId = (string) $buildInput['profile_id'];
        if ($profileBuildId !== $buildInputId) {
            throw new ValidationError(sprintf(
                'El installation-profile (%s) no coincide con el build-input (%s)',
                $profileBuildId,
                $buildInputId
            ));
        }

        /** @var array<string, mixed> $target */
        $target = $installationProfile['target'];
        /** @var array<string, mixed> $selector */
        $selector = $target['disk_selector'];
        /** @var list<array<string, mixed>> $disks */
        $disks = $inventory['disks'];

        $matchedDisks = array_values(array_filter(
            $disks,
            fn (array $disk): bool => $this->matchesDiskSelector($disk, $selector)
        ));

        if ($matchedDisks === []) {
            throw new ValidationError('El selector de disco no coincide con ningun disco del inventario');
        }

        if (count($matchedDisks) > 1) {
            throw new ValidationError('El selector de disco es ambiguo y coincide con varios discos');
        }

        $selectedDisk = $matchedDisks[0];

        if (($selectedDisk['is_installation_media'] ?? false) === true) {
            throw new ValidationError('El disco seleccionado corresponde al medio instalador y debe ser rechazado');
        }

        if (($selectedDisk['read_only'] ?? false) === true) {
            throw new ValidationError('El disco seleccionado es de solo lectura y debe ser rechazado');
        }

        if (($selectedDisk['has_partitions'] ?? true) === true || ($selectedDisk['has_filesystem_signatures'] ?? true) === true) {
            throw new ValidationError('El disco seleccionado no esta vacio segun el inventario y el MVP actual solo admite discos vacios');
        }

        $diskSizeBytes = (int) $selectedDisk['size_bytes'];
        $layout = $this->buildStorageLayout($installationProfile, $diskSizeBytes);

        $diskBinding = [
            'device' => (string) $selectedDisk['device'],
            'serial' => (string) ($selectedDisk['serial'] ?? ''),
            'wwid' => (string) ($selectedDisk['wwid'] ?? ''),
            'by_path' => (string) ($selectedDisk['by_path'] ?? ''),
            'size_bytes' => $diskSizeBytes,
        ];
        $planBindingHash = hash('sha256', json_encode($diskBinding, JSON_THROW_ON_ERROR));

        /** @var array<string, mixed> $identity */
        $identity = $installationProfile['identity'];
        /** @var array<string, mixed> $account */
        $account = $identity['account'];
        /** @var array<string, mixed> $installer */
        $installer = $installationProfile['installer'];
        /** @var array<string, mixed> $security */
        $security = $installationProfile['security'];
        $normalizedEditionPolicy = (new EditionPolicyToolkit())->normalizeForProfile(
            $buildInputId,
            $installationProfile,
            $editionPolicy,
            $editionPolicyPath
        );

        $hostname = (string) $identity['hostname'];
        $hostnamePrefix = (string) $normalizedEditionPolicy['branding']['hostname_prefix'];
        if (!str_starts_with($hostname, $hostnamePrefix)) {
            throw new ValidationError(sprintf(
                'El installation-profile (%s) define hostname %s fuera del prefijo de politica %s',
                $installationProfile['id'],
                $hostname,
                $hostnamePrefix
            ));
        }

        return [
            'installation_plan_schema_version' => 1,
            'kind' => 'installation-plan',
            'profile_id' => $buildInputId,
            'profile_name' => $buildInput['profile_name'],
            'base_manifest_id' => $buildInput['base_manifest_id'],
            'installation_profile' => [
                'id' => $installationProfile['id'],
                'name' => $installationProfile['name'],
                'edition' => $installationProfile['edition'],
            ],
            'edition_policy' => $normalizedEditionPolicy,
            'plan_binding' => [
                'selector' => $selector,
                'selected_disk' => $diskBinding,
                'expected_state' => $target['expected_state'],
                'binding_hash' => $planBindingHash,
                'revalidate_before_write' => $installer['revalidate_before_write'],
            ],
            'identity' => [
                'locale' => $identity['locale'],
                'keyboard' => $identity['keyboard'],
                'hostname' => $identity['hostname'],
                'user' => [
                    'username' => $account['username'],
                    'display_name' => $account['display_name'],
                    'password_source' => $account['password_source'],
                ],
            ],
            'security' => [
                'encryption' => $security['encryption'],
            ],
            'storage' => $layout,
            'execution' => [
                'engine' => $installer['engine'],
                'stages' => [
                    'revalidate-disk',
                    'partition-gpt',
                    'format-esp',
                    'format-boot',
                    'open-luks-root',
                    'create-btrfs',
                    'create-subvolumes',
                    'mount-layout',
                    'bootstrap-system',
                    'install-bootloader',
                    'write-fstab-and-crypttab',
                    'verify-first-boot',
                ],
            ],
            'summary' => $this->buildPlanSummary($installationProfile, $selectedDisk, $layout),
            'notes' => [
                'Este plan es destructivo y esta limitado a discos vacios.',
                'La ejecucion real debe revalidar el binding_hash antes de escribir en disco.',
                'El perfil unattended versiona solo referencias a secretos; no material secreto persistido.',
                'La instalacion debe respetar la politica de edicion versionada para arranque, hostname y hardening base.',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $installationProfile
     * @param array<string, mixed> $buildInput
     * @param array<string, mixed> $inventory
     * @param array<string, mixed> $plan
     */
    public function createInstallationBundleManifest(
        array $installationProfile,
        array $buildInput,
        array $inventory,
        array $plan,
        array $editionPolicy = [],
        ?string $editionPolicyPath = null
    ): array {
        $normalizedEditionPolicy = (new EditionPolicyToolkit())->normalizeForProfile(
            (string) $buildInput['profile_id'],
            $installationProfile,
            $editionPolicy,
            $editionPolicyPath
        );

        return [
            'installation_bundle_schema_version' => 1,
            'kind' => 'installation-bundle',
            'profile_id' => $buildInput['profile_id'],
            'installation_profile_id' => $installationProfile['id'],
            'inventory_id' => $inventory['id'] ?? 'inventory',
            'plan_kind' => $plan['kind'] ?? 'installation-plan',
            'edition_policy' => [
                'path' => $normalizedEditionPolicy['path'],
                'default_target' => $normalizedEditionPolicy['boot']['default_target'],
            ],
            'generated_artifacts' => [
                'installation-profile.json',
                'disk-inventory.json',
                'edition-policy.json',
                'installation-plan.json',
                'INSTALLATION_SUMMARY.txt',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $plan
     */
    public function buildHumanSummary(array $plan): string
    {
        /** @var array<string, mixed> $summary */
        $summary = $plan['summary'];
        /** @var array<string, mixed> $disk */
        $disk = $plan['plan_binding']['selected_disk'];
        /** @var array<string, mixed> $storage */
        $storage = $plan['storage'];
        /** @var array<string, mixed> $btrfs */
        $btrfs = $storage['btrfs'];
        /** @var array<string, mixed> $editionPolicy */
        $editionPolicy = is_array($plan['edition_policy'] ?? null) ? $plan['edition_policy'] : [];
        /** @var array<string, mixed> $bootPolicy */
        $bootPolicy = is_array($editionPolicy['boot'] ?? null) ? $editionPolicy['boot'] : [];
        /** @var array<string, mixed> $sshPolicy */
        $sshPolicy = is_array($editionPolicy['ssh'] ?? null) ? $editionPolicy['ssh'] : [];
        /** @var array<string, mixed> $firewallPolicy */
        $firewallPolicy = is_array($editionPolicy['firewall'] ?? null) ? $editionPolicy['firewall'] : [];

        $subvolumeLines = [];
        foreach ($btrfs['subvolumes'] as $subvolume) {
            $subvolumeLines[] = sprintf(
                '- %s -> %s',
                $subvolume['name'],
                $subvolume['mountpoint']
            );
        }

        return str_replace(["\r\n", "\r"], "\n", sprintf(
            "W4 OS Installation Summary\n\n".
            "Perfil: %s\n".
            "Destino: %s (%s GiB)\n".
            "Layout: GPT + ESP + /boot + LUKS2 + Btrfs\n".
            "Usuario inicial: %s\n".
            "Hostname: %s\n".
            "Target por defecto: %s\n".
            "SSH habilitado por politica: %s\n".
            "Firewall por politica: %s (%s/%s)\n".
            "Resumen destructivo: %s\n".
            "Espacio estimado para root cifrada: %s GiB\n\n".
            "Subvolumenes declarados:\n%s\n",
            $plan['profile_name'],
            $disk['device'],
            number_format(((int) $disk['size_bytes']) / 1073741824, 2, '.', ''),
            $plan['identity']['user']['username'],
            $plan['identity']['hostname'],
            (string) ($bootPolicy['default_target'] ?? 'multi-user.target'),
            (($sshPolicy['enabled'] ?? false) === true) ? 'si' : 'no',
            (string) ($firewallPolicy['backend'] ?? 'ufw'),
            (string) ($firewallPolicy['incoming'] ?? 'deny'),
            (string) ($firewallPolicy['outgoing'] ?? 'allow'),
            ($summary['destructive'] ?? false) === true ? 'si' : 'no',
            number_format(((int) $summary['encrypted_root_bytes']) / 1073741824, 2, '.', ''),
            implode("\n", $subvolumeLines)
        ));
    }

    /**
     * @param array<string, mixed> $disk
     * @param array<string, mixed> $selector
     */
    private function matchesDiskSelector(array $disk, array $selector): bool
    {
        foreach (['serial', 'wwid', 'by_path'] as $field) {
            $expected = $selector[$field] ?? null;
            if (!is_string($expected) || $expected === '') {
                continue;
            }

            if (($disk[$field] ?? null) !== $expected) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $installationProfile
     * @return array<string, mixed>
     */
    private function buildStorageLayout(array $installationProfile, int $diskSizeBytes): array
    {
        /** @var array<string, mixed> $storage */
        $storage = $installationProfile['storage'];
        /** @var array<string, mixed> $esp */
        $esp = $storage['esp'];
        /** @var array<string, mixed> $boot */
        $boot = $storage['boot'];
        /** @var array<string, mixed> $root */
        $root = $storage['root'];

        $espBytes = ((int) $esp['size_mib']) * 1048576;
        $bootBytes = ((int) $boot['size_mib']) * 1048576;
        $reservedBytes = 16 * 1048576;
        $rootBytes = $diskSizeBytes - $espBytes - $bootBytes - $reservedBytes;

        if ($rootBytes <= 8 * 1073741824) {
            throw new ValidationError('El disco seleccionado no deja espacio suficiente para una raiz cifrada Btrfs utilizable');
        }

        return [
            'table' => 'gpt',
            'wipe_strategy' => 'full-disk-for-empty-target',
            'partitions' => [
                [
                    'number' => 1,
                    'role' => 'esp',
                    'size_bytes' => $espBytes,
                    'filesystem' => 'vfat',
                    'label' => 'W4-ESP',
                    'mountpoint' => '/boot/efi',
                ],
                [
                    'number' => 2,
                    'role' => 'boot',
                    'size_bytes' => $bootBytes,
                    'filesystem' => 'ext4',
                    'label' => 'W4-BOOT',
                    'mountpoint' => '/boot',
                ],
                [
                    'number' => 3,
                    'role' => 'root-crypt',
                    'size_bytes' => $rootBytes,
                    'container' => 'luks2',
                    'label' => 'W4-CRYPTROOT',
                ],
            ],
            'encryption' => [
                'type' => 'luks2-passphrase',
                'mapping_name' => $root['luks_name'],
                'passphrase_source' => $installationProfile['security']['encryption']['passphrase_source'],
            ],
            'btrfs' => [
                'label' => 'W4-SYSTEM',
                'mount_options' => ['compress=zstd', 'noatime'],
                'subvolumes' => $root['subvolumes'],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $installationProfile
     * @param array<string, mixed> $selectedDisk
     * @param array<string, mixed> $layout
     * @return array<string, mixed>
     */
    private function buildPlanSummary(array $installationProfile, array $selectedDisk, array $layout): array
    {
        $encryptedRootBytes = (int) $layout['partitions'][2]['size_bytes'];

        return [
            'edition' => $installationProfile['edition'],
            'disk_device' => $selectedDisk['device'],
            'disk_size_bytes' => (int) $selectedDisk['size_bytes'],
            'destructive' => true,
            'expected_state' => 'empty',
            'encrypted_root_bytes' => $encryptedRootBytes,
            'subvolume_count' => count($layout['btrfs']['subvolumes']),
            'requires_post_install_validation' => [
                'efi-entry',
                'cryptroot-unlock',
                'btrfs-mounts',
                'first-boot',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $parent
     * @return array<string, mixed>
     */
    private function requireAssocArray(array $parent, string $fieldName, mixed $value, string $sourcePath): array
    {
        if (!is_array($value)) {
            throw new ValidationError(sprintf('%s: %s debe ser un objeto', basename($sourcePath), $fieldName));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $parent
     */
    private function requireNonEmptyString(array $parent, string $fieldName, mixed $value, string $sourcePath): void
    {
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: %s debe ser un string no vacio', basename($sourcePath), $fieldName));
        }
    }

    /**
     * @param array<string, mixed> $parent
     */
    private function requirePositiveInt(array $parent, string $fieldName, mixed $value, string $sourcePath): void
    {
        if (!is_int($value) || $value <= 0) {
            throw new ValidationError(sprintf('%s: %s debe ser un entero positivo', basename($sourcePath), $fieldName));
        }
    }
}
