<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

use JsonException;
use W4\OS\Installer\EditionPolicyToolkit;
use W4\OS\Support\ValidationError;

final class ControlCenterSliceAToolkit
{
    private EditionPolicyToolkit $editionPolicyToolkit;

    public function __construct(private readonly string $rootDir)
    {
        $this->editionPolicyToolkit = new EditionPolicyToolkit();
    }

    /**
     * @return array<string, mixed>
     */
    public function createReadModel(string $profileId): array
    {
        $generatedAt = gmdate('c');
        $edition = $this->editionFromProfileId($profileId);

        $policyPath = $this->absolutePath($this->editionPolicyToolkit->defaultPolicyPath($profileId));
        $policy = $this->readOptionalJsonFile($policyPath) ?? [];

        $installerProfilePath = $this->resolveInstallerProfilePath($profileId);
        $installerProfile = $installerProfilePath !== null ? $this->readOptionalJsonFile($installerProfilePath) : null;
        $normalizedPolicy = $this->editionPolicyToolkit->normalizeForProfile(
            $profileId,
            ['edition' => $edition],
            $policy,
            $this->relativePath($policyPath)
        );

        $liveSummaryPath = $this->absolutePath(sprintf('build/live-output/%s/metadata/live-summary.env', $profileId));
        $liveSummary = $this->readOptionalEnvFile($liveSummaryPath);

        $desktopDefaultsPath = $this->absolutePath(sprintf(
            'build/live-output/%s/image-root/system-overlay/etc/w4/desktop-defaults.json',
            $profileId
        ));
        $desktopDefaults = $this->readOptionalJsonFile($desktopDefaultsPath);

        $securityBaselinePath = $this->absolutePath(sprintf('build/security/%s/security-baseline.json', $profileId));
        $securityBaseline = $this->readOptionalJsonFile($securityBaselinePath);

        $updateContext = $this->resolveLatestUpdateContext($profileId);
        $publicationManifest = $this->resolveLatestPublicationManifest();

        return [
            'control_center_slice_schema_version' => 1,
            'kind' => 'control-center-slice-a-read-model',
            'profile_id' => $profileId,
            'generated_at' => $generatedAt,
            'modules' => [
                $this->buildSystemModule(
                    $profileId,
                    $generatedAt,
                    $normalizedPolicy,
                    $installerProfilePath,
                    $installerProfile,
                    $liveSummaryPath,
                    $liveSummary,
                    $desktopDefaultsPath,
                    $desktopDefaults
                ),
                $this->buildSecurityModule(
                    $generatedAt,
                    $normalizedPolicy,
                    $policyPath,
                    $securityBaselinePath,
                    $securityBaseline
                ),
                $this->buildUpdatesModule(
                    $generatedAt,
                    $updateContext,
                    $publicationManifest
                ),
                $this->buildStorageModule(
                    $generatedAt,
                    $policy,
                    $policyPath,
                    $installerProfilePath,
                    $installerProfile,
                    $updateContext
                ),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $normalizedPolicy
     * @param array<string, mixed>|null $installerProfile
     * @param array<string, string>|null $liveSummary
     * @param array<string, mixed>|null $desktopDefaults
     * @return array<string, mixed>
     */
    private function buildSystemModule(
        string $profileId,
        string $generatedAt,
        array $normalizedPolicy,
        ?string $installerProfilePath,
        ?array $installerProfile,
        string $liveSummaryPath,
        ?array $liveSummary,
        string $desktopDefaultsPath,
        ?array $desktopDefaults
    ): array {
        $hostnamePrefix = (string) ($normalizedPolicy['branding']['hostname_prefix'] ?? 'w4-home');
        $defaultTarget = (string) ($normalizedPolicy['boot']['default_target'] ?? 'graphical.target');
        $shell = (string) ($desktopDefaults['desktop']['shell'] ?? 'unknown');
        $session = (string) ($desktopDefaults['desktop']['session'] ?? 'unknown');
        $displayManager = (string) ($desktopDefaults['desktop']['display_manager'] ?? 'unknown');
        $liveHostname = (string) (($liveSummary['W4_LIVE_HOSTNAME'] ?? null) ?: '');
        $installedHostname = is_array($installerProfile['identity'] ?? null)
            ? (string) (($installerProfile['identity']['hostname'] ?? null) ?: '')
            : '';

        $summary = sprintf(
            '%s usa %s/%s con %s, target %s y prefijo de hostname %s.',
            $profileId,
            $shell,
            $session,
            $displayManager,
            $defaultTarget,
            $hostnamePrefix
        );

        $sourceOfTruth = [$this->relativePath($this->absolutePath($this->editionPolicyToolkit->defaultPolicyPath($profileId)))];
        if ($liveSummary !== null) {
            $sourceOfTruth[] = $this->relativePath($liveSummaryPath);
        }
        if ($desktopDefaults !== null) {
            $sourceOfTruth[] = $this->relativePath($desktopDefaultsPath);
        }
        if ($installerProfilePath !== null) {
            $sourceOfTruth[] = $this->relativePath($installerProfilePath);
        }

        return $this->buildModule(
            id: 'system',
            title: 'Sistema',
            class: 'W4-augmented',
            summary: $summary,
            authority: 'edition-policy',
            capabilities: ['read', 'deep-link'],
            sourceOfTruth: $sourceOfTruth,
            lastCheckedAt: (string) (($liveSummary['W4_GENERATED_AT'] ?? null) ?: $generatedAt),
            evidence: [
                'hostname_prefix' => $hostnamePrefix,
                'default_target' => $defaultTarget,
                'live_hostname' => $liveHostname,
                'installed_hostname' => $installedHostname,
                'shell' => $shell,
                'session' => $session,
                'display_manager' => $displayManager,
                'locale' => is_array($installerProfile['identity'] ?? null) ? (string) (($installerProfile['identity']['locale'] ?? null) ?: '') : '',
                'keyboard' => is_array($installerProfile['identity'] ?? null) ? (string) (($installerProfile['identity']['keyboard'] ?? null) ?: '') : '',
            ],
            actions: []
        );
    }

    /**
     * @param array<string, mixed> $normalizedPolicy
     * @param array<string, mixed>|null $securityBaseline
     * @return array<string, mixed>
     */
    private function buildSecurityModule(
        string $generatedAt,
        array $normalizedPolicy,
        string $policyPath,
        string $securityBaselinePath,
        ?array $securityBaseline
    ): array {
        $baselineSummary = is_array($securityBaseline['summary'] ?? null) ? $securityBaseline['summary'] : [];
        $controls = $this->indexControls(is_array($securityBaseline['controls'] ?? null) ? $securityBaseline['controls'] : []);

        $implementedControls = (int) ($baselineSummary['implemented'] ?? 0);
        $gapControls = (int) ($baselineSummary['gap'] ?? 0);
        $firewallBackend = (string) ($normalizedPolicy['firewall']['backend'] ?? 'ufw');
        $incomingPolicy = strtoupper((string) ($normalizedPolicy['firewall']['incoming'] ?? 'deny'));
        $outgoingPolicy = strtoupper((string) ($normalizedPolicy['firewall']['outgoing'] ?? 'allow'));
        $sshEnabled = ($normalizedPolicy['ssh']['enabled'] ?? false) === true;

        $summary = $securityBaseline !== null
            ? sprintf(
                'Baseline de seguridad con %d controles implementados y %d gaps; firewall %s %s/%s; SSH por defecto %s.',
                $implementedControls,
                $gapControls,
                $firewallBackend,
                $incomingPolicy,
                $outgoingPolicy,
                $sshEnabled ? 'habilitado' : 'deshabilitado'
            )
            : sprintf(
                'No hay baseline materializada; la politica vigente declara firewall %s %s/%s y SSH por defecto %s.',
                $firewallBackend,
                $incomingPolicy,
                $outgoingPolicy,
                $sshEnabled ? 'habilitado' : 'deshabilitado'
            );

        $sourceOfTruth = [$this->relativePath($policyPath)];
        if ($securityBaseline !== null) {
            $sourceOfTruth[] = $this->relativePath($securityBaselinePath);
        }

        return $this->buildModule(
            id: 'security',
            title: 'Seguridad',
            class: 'W4-augmented',
            summary: $summary,
            authority: 'edition-policy+security-baseline',
            capabilities: ['read'],
            sourceOfTruth: $sourceOfTruth,
            lastCheckedAt: $securityBaseline !== null ? $this->timestampFromPath($securityBaselinePath, $generatedAt) : $generatedAt,
            evidence: [
                'implemented_controls' => $implementedControls,
                'gap_controls' => $gapControls,
                'firewall_backend' => $firewallBackend,
                'firewall_incoming' => strtolower($incomingPolicy),
                'firewall_outgoing' => strtolower($outgoingPolicy),
                'ssh_enabled_by_policy' => $sshEnabled,
                'apparmor_expected' => array_key_exists('mac-enforcement', $controls),
                'apparmor_enforced_profiles_expected' => (int) (($controls['apparmor-enforced-profiles']['expected']['minimum_enforced_profiles'] ?? 0)),
            ],
            actions: []
        );
    }

    /**
     * @param array<string, mixed>|null $publicationManifest
     * @param array<string, mixed>|null $updateContext
     * @return array<string, mixed>
     */
    private function buildUpdatesModule(
        string $generatedAt,
        ?array $updateContext,
        ?array $publicationManifest
    ): array {
        $operation = is_array($updateContext['operation'] ?? null) ? $updateContext['operation'] : [];
        $healthReport = is_array($updateContext['health_report'] ?? null) ? $updateContext['health_report'] : [];
        $snapshotManifest = is_array($updateContext['snapshot_manifest'] ?? null) ? $updateContext['snapshot_manifest'] : [];

        $sourceOfTruth = [];
        if (isset($updateContext['operation_path'])) {
            $sourceOfTruth[] = (string) $updateContext['operation_path'];
        }
        if (isset($updateContext['health_report_path'])) {
            $sourceOfTruth[] = (string) $updateContext['health_report_path'];
        }
        if (isset($updateContext['snapshot_manifest_path'])) {
            $sourceOfTruth[] = (string) $updateContext['snapshot_manifest_path'];
        }
        if (is_string($publicationManifest['__path'] ?? null)) {
            $sourceOfTruth[] = (string) $publicationManifest['__path'];
        }

        if ($operation !== []) {
            $summary = sprintf(
                'Ultima operacion %s en estado %s hacia %s sobre %s.',
                (string) ($operation['operation_id'] ?? 'unknown'),
                (string) ($operation['stage'] ?? 'unknown'),
                (string) ($operation['target_version'] ?? 'unknown'),
                (string) ($operation['repository_snapshot']['channel'] ?? 'unknown')
            );
        } else {
            $summary = 'No hay evidencia materializada de operaciones de update para este perfil.';
        }

        return $this->buildModule(
            id: 'updates',
            title: 'Actualizaciones',
            class: 'W4-augmented',
            summary: $summary,
            authority: 'update-operation-store',
            capabilities: ['read'],
            sourceOfTruth: $sourceOfTruth,
            lastCheckedAt: (string) (($healthReport['updated_at'] ?? null) ?: ($operation['timestamps']['updated_at'] ?? null) ?: $generatedAt),
            evidence: [
                'operation_id' => (string) ($operation['operation_id'] ?? ''),
                'stage' => (string) ($operation['stage'] ?? ''),
                'status' => (string) ($healthReport['status'] ?? ''),
                'source_version' => (string) ($operation['source_version'] ?? ''),
                'target_version' => (string) ($operation['target_version'] ?? ''),
                'channel' => (string) ($operation['repository_snapshot']['channel'] ?? ''),
                'snapshot_name' => (string) ($snapshotManifest['snapshot_name'] ?? ($operation['snapshot']['snapshot_name'] ?? '')),
                'snapshot_retention' => (string) ($operation['snapshot']['retention'] ?? ''),
                'published_signing_profile' => (string) ($publicationManifest['signing_profile'] ?? ''),
                'published_snapshot_id' => (string) ($publicationManifest['repository_snapshot']['id'] ?? ''),
            ],
            actions: []
        );
    }

    /**
     * @param array<string, mixed> $policy
     * @param array<string, mixed>|null $installerProfile
     * @param array<string, mixed>|null $updateContext
     * @return array<string, mixed>
     */
    private function buildStorageModule(
        string $generatedAt,
        array $policy,
        string $policyPath,
        ?string $installerProfilePath,
        ?array $installerProfile,
        ?array $updateContext
    ): array {
        $storage = is_array($installerProfile['storage'] ?? null) ? $installerProfile['storage'] : [];
        $root = is_array($storage['root'] ?? null) ? $storage['root'] : [];
        $subvolumes = is_array($root['subvolumes'] ?? null) ? $root['subvolumes'] : [];
        $snapshotManifest = is_array($updateContext['snapshot_manifest'] ?? null) ? $updateContext['snapshot_manifest'] : [];

        /** @var array<string, mixed> $storagePolicy */
        $storagePolicy = is_array($policy['storage'] ?? null) ? $policy['storage'] : [];
        /** @var array<string, mixed> $updatePolicy */
        $updatePolicy = is_array($policy['update'] ?? null) ? $policy['update'] : [];

        $rootFilesystem = (string) ($storagePolicy['root_filesystem'] ?? 'unknown');
        $snapshotBackend = (string) ($updatePolicy['snapshot_backend'] ?? 'unknown');
        $encryptionDefault = (string) ($storagePolicy['encryption_default'] ?? 'unknown');

        $summary = sprintf(
            'Home espera raiz %s cifrada con %s, %d subvolumenes declarados y backend de snapshots %s.',
            $rootFilesystem,
            $encryptionDefault,
            count($subvolumes),
            $snapshotBackend
        );

        $sourceOfTruth = [$this->relativePath($policyPath)];
        if ($installerProfilePath !== null) {
            $sourceOfTruth[] = $this->relativePath($installerProfilePath);
        }
        if (is_string($updateContext['snapshot_manifest_path'] ?? null)) {
            $sourceOfTruth[] = (string) $updateContext['snapshot_manifest_path'];
        }

        return $this->buildModule(
            id: 'storage',
            title: 'Almacenamiento',
            class: 'W4-augmented',
            summary: $summary,
            authority: 'edition-policy+installation-profile',
            capabilities: ['read'],
            sourceOfTruth: $sourceOfTruth,
            lastCheckedAt: is_string($updateContext['snapshot_manifest_path'] ?? null)
                ? $this->timestampFromPath($this->absolutePath((string) $updateContext['snapshot_manifest_path']), $generatedAt)
                : ($installerProfilePath !== null ? $this->timestampFromPath($installerProfilePath, $generatedAt) : $generatedAt),
            evidence: [
                'root_filesystem' => $rootFilesystem,
                'encryption_default' => $encryptionDefault,
                'snapshot_backend' => $snapshotBackend,
                'layout' => (string) ($storage['layout'] ?? ''),
                'luks_name' => (string) ($root['luks_name'] ?? ''),
                'subvolume_count' => count($subvolumes),
                'subvolume_names' => array_values(array_map(
                    static fn (array $subvolume): string => (string) ($subvolume['name'] ?? ''),
                    array_filter($subvolumes, 'is_array')
                )),
                'latest_snapshot_name' => (string) ($snapshotManifest['snapshot_name'] ?? ''),
                'latest_snapshot_path' => (string) ($snapshotManifest['snapshot_path'] ?? ''),
            ],
            actions: []
        );
    }

    /**
     * @param list<string> $capabilities
     * @param list<string> $sourceOfTruth
     * @param array<string, mixed> $evidence
     * @param list<string> $actions
     * @return array<string, mixed>
     */
    private function buildModule(
        string $id,
        string $title,
        string $class,
        string $summary,
        string $authority,
        array $capabilities,
        array $sourceOfTruth,
        string $lastCheckedAt,
        array $evidence,
        array $actions
    ): array {
        return [
            'id' => $id,
            'title' => $title,
            'class' => $class,
            'summary' => $summary,
            'source_of_truth' => array_values(array_unique($sourceOfTruth)),
            'authority' => $authority,
            'capabilities' => $capabilities,
            'last_checked_at' => $lastCheckedAt,
            'evidence' => $evidence,
            'actions' => $actions,
        ];
    }

    private function resolveInstallerProfilePath(string $profileId): ?string
    {
        $candidates = [
            $this->absolutePath(sprintf('build/install/%s/installation-profile.derived.json', $profileId)),
            $this->absolutePath(sprintf('build/install/%s/installation-profile.json', $profileId)),
            $this->absolutePath(sprintf('installer-profiles/%s.vm-install.json', $profileId)),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveLatestUpdateContext(string $profileId): ?array
    {
        $candidates = [];

        foreach ([
            $this->absolutePath('build/update/validation'),
            $this->absolutePath('build/update/operations'),
        ] as $parentDirectory) {
            if (!is_dir($parentDirectory)) {
                continue;
            }

            $directories = glob($parentDirectory . DIRECTORY_SEPARATOR . '*', GLOB_ONLYDIR);
            if ($directories === false) {
                continue;
            }

            foreach ($directories as $directory) {
                $operationPath = $directory . DIRECTORY_SEPARATOR . 'operation.json';
                if (!is_file($operationPath)) {
                    continue;
                }

                $operation = $this->readOptionalJsonFile($operationPath);
                if (!is_array($operation) || ($operation['profile_id'] ?? null) !== $profileId) {
                    continue;
                }

                $updatedAt = (string) (($operation['timestamps']['updated_at'] ?? null) ?: '');
                $sortKey = $updatedAt !== '' ? strtotime($updatedAt) : filemtime($operationPath);
                $candidates[] = [
                    'sort_key' => $sortKey !== false ? (int) $sortKey : 0,
                    'directory' => $directory,
                    'operation' => $operation,
                    'operation_path' => $this->relativePath($operationPath),
                    'health_report' => $this->readOptionalJsonFile($directory . DIRECTORY_SEPARATOR . 'health-report.json'),
                    'health_report_path' => is_file($directory . DIRECTORY_SEPARATOR . 'health-report.json')
                        ? $this->relativePath($directory . DIRECTORY_SEPARATOR . 'health-report.json')
                        : null,
                    'snapshot_manifest' => $this->readOptionalJsonFile($directory . DIRECTORY_SEPARATOR . 'snapshot-manifest.json'),
                    'snapshot_manifest_path' => is_file($directory . DIRECTORY_SEPARATOR . 'snapshot-manifest.json')
                        ? $this->relativePath($directory . DIRECTORY_SEPARATOR . 'snapshot-manifest.json')
                        : null,
                ];
            }
        }

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static fn (array $left, array $right): int => $right['sort_key'] <=> $left['sort_key']
        );

        return $candidates[0];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveLatestPublicationManifest(): ?array
    {
        $repositoryOutputDir = $this->absolutePath('build/update/repository-output');
        if (!is_dir($repositoryOutputDir)) {
            return null;
        }

        $manifests = glob($repositoryOutputDir . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'publication-manifest.json');
        if ($manifests === false || $manifests === []) {
            return null;
        }

        $candidates = [];
        foreach ($manifests as $manifestPath) {
            $manifest = $this->readOptionalJsonFile($manifestPath);
            if ($manifest === null) {
                continue;
            }

            $publishedAt = (string) (($manifest['published_at'] ?? null) ?: '');
            $sortKey = $publishedAt !== '' ? strtotime($publishedAt) : filemtime($manifestPath);
            $manifest['__path'] = $this->relativePath($manifestPath);
            $candidates[] = [
                'sort_key' => $sortKey !== false ? (int) $sortKey : 0,
                'manifest' => $manifest,
            ];
        }

        if ($candidates === []) {
            return null;
        }

        usort(
            $candidates,
            static fn (array $left, array $right): int => $right['sort_key'] <=> $left['sort_key']
        );

        return $candidates[0]['manifest'];
    }

    /**
     * @param list<array<string, mixed>> $controls
     * @return array<string, array<string, mixed>>
     */
    private function indexControls(array $controls): array
    {
        $indexed = [];
        foreach ($controls as $control) {
            $id = (string) ($control['id'] ?? '');
            if ($id === '') {
                continue;
            }

            $indexed[$id] = $control;
        }

        return $indexed;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readOptionalJsonFile(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

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

        return is_array($data) ? $data : null;
    }

    /**
     * @return array<string, string>|null
     */
    private function readOptionalEnvFile(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new ValidationError(sprintf('No se pudo leer el archivo env: %s', $path));
        }

        $values = [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            $position = strpos($trimmed, '=');
            if ($position === false) {
                continue;
            }

            $key = substr($trimmed, 0, $position);
            $value = substr($trimmed, $position + 1);
            $values[$key] = $this->normalizeEnvValue($value);
        }

        return $values;
    }

    private function normalizeEnvValue(string $value): string
    {
        $trimmed = trim($value);
        if (strlen($trimmed) >= 2 && $trimmed[0] === '"' && $trimmed[strlen($trimmed) - 1] === '"') {
            $trimmed = substr($trimmed, 1, -1);
        }

        return str_replace(['\\"', '\\n'], ['"', "\n"], $trimmed);
    }

    private function editionFromProfileId(string $profileId): string
    {
        return str_replace('w4-os-', '', strtolower($profileId));
    }

    private function absolutePath(string $relativePath): string
    {
        $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

        return rtrim($this->rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $normalized;
    }

    private function relativePath(string $path): string
    {
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, rtrim($this->rootDir, DIRECTORY_SEPARATOR));
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (str_starts_with($normalizedPath, $normalizedRoot . DIRECTORY_SEPARATOR)) {
            $normalizedPath = substr($normalizedPath, strlen($normalizedRoot) + 1);
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $normalizedPath);
    }

    private function timestampFromPath(string $path, string $fallback): string
    {
        if (!is_file($path)) {
            return $fallback;
        }

        $timestamp = filemtime($path);
        if ($timestamp === false) {
            return $fallback;
        }

        return gmdate('c', $timestamp);
    }
}
