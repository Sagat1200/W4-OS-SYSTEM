<?php

declare(strict_types=1);

namespace W4\OS\Business;

use W4\OS\Support\ValidationError;

final class BusinessKdeLiveOutputToolkit
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
        $liveSummary = $this->readRequiredEnvFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'metadata' . DIRECTORY_SEPARATOR . 'live-summary.env',
            'No se pudo leer live-summary.env del live materializado'
        );

        $defaultTarget = (string) ($liveSummary['W4_DEFAULT_TARGET'] ?? '');
        if ($defaultTarget !== 'graphical.target') {
            throw new ValidationError(sprintf('Business KDE baseline requiere graphical.target y obtuvo %s', $defaultTarget));
        }

        $profileEnv = $this->readRequiredEnvFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env',
            'No se pudo leer profile.env del live materializado'
        );

        $featureFlags = $this->parseFeatureList((string) ($profileEnv['W4_FEATURES'] ?? ''));
        foreach (['desktop-defaults', 'kde-sddm-default-route', 'reversible-branding-defaults'] as $requiredFeature) {
            if (!in_array($requiredFeature, $featureFlags, true)) {
                throw new ValidationError(sprintf('Falta la feature %s en profile.env', $requiredFeature));
            }
        }

        $filesystemPackages = $this->readFilesystemManifestLines(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest'
        );

        $requiredPackages = [
            'dolphin',
            'konsole',
            'plasma-desktop',
            'plasma-nm',
            'plasma-workspace',
            'sddm',
            'systemsettings',
            'xdg-desktop-portal-kde',
        ];
        $packageStatus = $this->packageStatus($filesystemPackages, $requiredPackages);

        $desktopDefaults = $this->readRequiredJsonFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json',
            'No se pudo leer desktop-defaults.json del live materializado'
        );

        if (($desktopDefaults['profile_id'] ?? null) !== $profileId) {
            throw new ValidationError('desktop-defaults.json no corresponde al perfil Business materializado');
        }

        $desktopSection = is_array($desktopDefaults['desktop'] ?? null) ? $desktopDefaults['desktop'] : [];
        if (($desktopSection['shell'] ?? null) !== 'plasma-desktop') {
            throw new ValidationError('desktop-defaults.json no fija plasma-desktop como shell');
        }

        if (($desktopSection['session'] ?? null) !== 'plasma') {
            throw new ValidationError('desktop-defaults.json no fija plasma como session');
        }

        if (($desktopSection['display_manager'] ?? null) !== 'sddm') {
            throw new ValidationError('desktop-defaults.json no fija sddm como display_manager');
        }

        $favorites = is_array($desktopDefaults['favorites'] ?? null) ? array_values(array_filter($desktopDefaults['favorites'], 'is_string')) : [];
        foreach (['org.kde.dolphin.desktop', 'systemsettings.desktop', 'org.kde.konsole.desktop'] as $requiredFavorite) {
            if (!in_array($requiredFavorite, $favorites, true)) {
                throw new ValidationError(sprintf('Falta el favorito requerido %s en desktop-defaults.json', $requiredFavorite));
            }
        }

        $wallpaper = is_array($desktopDefaults['wallpaper'] ?? null) ? $desktopDefaults['wallpaper'] : [];
        $wallpaperAssetPath = (string) ($wallpaper['asset_path'] ?? '');
        if ($wallpaperAssetPath !== '/usr/share/w4/branding/business/wallpapers/w4-business-default.svg') {
            throw new ValidationError('desktop-defaults.json no fija el wallpaper de Business esperado');
        }

        $wallpaperRuntimePath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $wallpaperAssetPath), DIRECTORY_SEPARATOR);
        if (!is_file($wallpaperRuntimePath)) {
            throw new ValidationError(sprintf('No existe el wallpaper runtime de Business: %s', $wallpaperRuntimePath));
        }

        $stalePaths = [
            'image-root/system-overlay/etc/dconf/db/local.d/00-w4-business',
            'image-root/system-overlay/etc/dconf/db/gdm.d/00-w4-login',
            'image-root/system-overlay/etc/xdg/autostart/w4-home-onboarding-light-ui.desktop',
            'image-root/system-overlay/usr/local/lib/w4/w4-home-onboarding-light-ui.sh',
            'image-root/system-overlay/usr/share/applications/w4-home-onboarding-light-ui.desktop',
        ];

        $stalePayloads = [];
        foreach ($stalePaths as $stalePath) {
            $absolutePath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $stalePath);
            $stalePayloads[$stalePath] = is_file($absolutePath) ? 'present' : 'absent';
            if ($stalePayloads[$stalePath] !== 'absent') {
                throw new ValidationError(sprintf('El live-output de Business arrastra payload ajeno: %s', $stalePath));
            }
        }

        $capabilities = [
            [
                'id' => 'desktop-shell',
                'label' => 'Shell KDE Plasma',
                'status' => $this->allPresent($packageStatus['present'], ['plasma-desktop', 'plasma-workspace']) ? 'available' : 'missing',
                'packages' => ['plasma-desktop', 'plasma-workspace'],
            ],
            [
                'id' => 'display-manager',
                'label' => 'Login manager SDDM',
                'status' => $packageStatus['present']['sddm'] ? 'available' : 'missing',
                'packages' => ['sddm'],
            ],
            [
                'id' => 'settings-route',
                'label' => 'Ruta visible a System Settings',
                'status' => in_array('systemsettings.desktop', $favorites, true) && $packageStatus['present']['systemsettings'] ? 'available' : 'missing',
                'packages' => ['systemsettings'],
                'favorite' => 'systemsettings.desktop',
            ],
            [
                'id' => 'files-route',
                'label' => 'Ruta visible a Dolphin',
                'status' => in_array('org.kde.dolphin.desktop', $favorites, true) && $packageStatus['present']['dolphin'] ? 'available' : 'missing',
                'packages' => ['dolphin'],
                'favorite' => 'org.kde.dolphin.desktop',
            ],
            [
                'id' => 'terminal-route',
                'label' => 'Ruta visible a Konsole',
                'status' => in_array('org.kde.konsole.desktop', $favorites, true) && $packageStatus['present']['konsole'] ? 'available' : 'missing',
                'packages' => ['konsole'],
                'favorite' => 'org.kde.konsole.desktop',
            ],
        ];

        return [
            'business_kde_live_output_validation_schema_version' => 1,
            'kind' => 'business-kde-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($liveSummary['W4_GENERATED_AT'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'feature_flags' => $featureFlags,
            'required_packages' => $packageStatus,
            'desktop_defaults' => [
                'runtime_file' => 'image-root/system-overlay/etc/w4/desktop-defaults.json',
                'shell' => 'plasma-desktop',
                'session' => 'plasma',
                'display_manager' => 'sddm',
                'favorites' => $favorites,
                'wallpaper' => 'image-root/system-overlay/usr/share/w4/branding/business/wallpapers/w4-business-default.svg',
            ],
            'capabilities' => $capabilities,
            'stale_payloads' => $stalePayloads,
            'deferred_steps' => [
                'sddm-login-qa',
                'first-run-kde-shell-qa',
                'kde-branding-persistence-qa',
                'business-enrollment-readiness',
            ],
            'live_summary' => [
                'default_target' => $defaultTarget,
                'live_user' => (string) ($liveSummary['W4_LIVE_USER'] ?? ''),
                'live_hostname' => (string) ($liveSummary['W4_LIVE_HOSTNAME'] ?? ''),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $validation
     */
    public function renderSummaryText(array $validation): string
    {
        $requiredPackages = is_array($validation['required_packages'] ?? null) ? $validation['required_packages'] : [];
        $capabilities = is_array($validation['capabilities'] ?? null) ? $validation['capabilities'] : [];
        $liveSummary = is_array($validation['live_summary'] ?? null) ? $validation['live_summary'] : [];
        $featureFlags = is_array($validation['feature_flags'] ?? null) ? $validation['feature_flags'] : [];

        $lines = [
            sprintf('Business KDE live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Live output: %s', (string) ($validation['live_output_dir'] ?? 'build/live-output/...')),
            sprintf('Target: %s', (string) ($liveSummary['default_target'] ?? '')),
            sprintf('Live user: %s', (string) ($liveSummary['live_user'] ?? '')),
            sprintf('Live host: %s', (string) ($liveSummary['live_hostname'] ?? '')),
            '',
            'Feature flags:',
        ];

        foreach ($featureFlags as $flag) {
            if (is_string($flag)) {
                $lines[] = sprintf('- %s', $flag);
            }
        }

        $lines[] = '';
        $lines[] = 'Required packages:';
        foreach ((array) ($requiredPackages['present'] ?? []) as $package => $present) {
            $lines[] = sprintf('- %s: %s', (string) $package, $present ? 'yes' : 'no');
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

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new ValidationError(sprintf('El archivo JSON no es valido: %s', $path));
        }

        return $decoded;
    }

    private function readRequiredTextFile(string $path, string $errorMessage): string
    {
        if (!is_file($path)) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('%s: %s', $errorMessage, $path));
        }

        return $raw;
    }

    /**
     * @return array<int, string>
     */
    private function readFilesystemManifestLines(string $path): array
    {
        $raw = $this->readRequiredTextFile($path, 'No se pudo leer filesystem.manifest del live materializado');
        $lines = preg_split('/\r?\n/', $raw) ?: [];

        return array_values(array_filter(array_map('trim', $lines), static fn (string $line): bool => $line !== ''));
    }

    /**
     * @param array<int, string> $filesystemPackages
     * @param list<string> $requiredPackages
     * @return array<string, mixed>
     */
    private function packageStatus(array $filesystemPackages, array $requiredPackages): array
    {
        $present = [];
        foreach ($requiredPackages as $package) {
            $present[$package] = $this->hasPackage($filesystemPackages, $package);
            if ($present[$package] !== true) {
                throw new ValidationError(sprintf('Falta el paquete requerido %s en filesystem.manifest', $package));
            }
        }

        return [
            'required' => $requiredPackages,
            'present' => $present,
        ];
    }

    /**
     * @param array<int, string> $filesystemPackages
     */
    private function hasPackage(array $filesystemPackages, string $packageName): bool
    {
        $prefix = $packageName . ' ';
        foreach ($filesystemPackages as $line) {
            if (str_starts_with($line, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, bool> $packageStatus
     * @param list<string> $requiredPackages
     */
    private function allPresent(array $packageStatus, array $requiredPackages): bool
    {
        foreach ($requiredPackages as $package) {
            if (($packageStatus[$package] ?? false) !== true) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function parseFeatureList(string $rawFeatures): array
    {
        if (trim($rawFeatures) === '') {
            return [];
        }

        $parts = array_map(
            static fn (string $feature): string => trim($feature),
            explode(',', $rawFeatures)
        );

        return array_values(array_filter($parts, static fn (string $feature): bool => $feature !== ''));
    }

    private function relativePath(string $path): string
    {
        $normalizedRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->rootDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

        if (str_starts_with($normalizedPath, $normalizedRoot)) {
            return str_replace(DIRECTORY_SEPARATOR, '/', substr($normalizedPath, strlen($normalizedRoot)));
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', $normalizedPath);
    }
}
