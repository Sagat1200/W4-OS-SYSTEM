<?php

declare(strict_types=1);

namespace W4\OS\Home;

use W4\OS\Support\ValidationError;

final class HomeAccessibilityLiveOutputToolkit
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
            throw new ValidationError(sprintf('Home accessibility baseline requiere graphical.target y obtuvo %s', $defaultTarget));
        }

        $profileEnv = $this->readRequiredEnvFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'profile.env',
            'No se pudo leer profile.env del live materializado'
        );

        $featureFlags = $this->parseFeatureList((string) ($profileEnv['W4_FEATURES'] ?? ''));
        if (!in_array('accessibility-baseline', $featureFlags, true)) {
            throw new ValidationError('Falta la feature accessibility-baseline en profile.env');
        }

        $filesystemPackages = $this->readFilesystemManifestLines(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest'
        );

        $requiredPackages = [
            'at-spi2-core',
            'gnome-accessibility-themes',
            'gnome-control-center',
            'gsettings-desktop-schemas',
            'libatk-adaptor',
            'orca',
            'speech-dispatcher',
        ];
        $packageStatus = $this->packageStatus($filesystemPackages, $requiredPackages);

        $desktopDefaults = $this->readRequiredJsonFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json',
            'No se pudo leer desktop-defaults.json del live materializado'
        );
        $favorites = is_array($desktopDefaults['favorites'] ?? null) ? $desktopDefaults['favorites'] : [];
        if (!in_array('w4-control-center-home-home.desktop', $favorites, true)) {
            throw new ValidationError('Falta W4 Settings en desktop-defaults.json para accesibilidad baseline');
        }

        $dconfPath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'dconf' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'local.d' . DIRECTORY_SEPARATOR . '00-w4-home';
        $dconfFavorites = $this->readRequiredTextFile(
            $dconfPath,
            'No se pudo leer el payload dconf de Home para accesibilidad baseline'
        );
        if (!str_contains($dconfFavorites, 'w4-control-center-home-home.desktop')) {
            throw new ValidationError('Falta W4 Settings en el payload dconf de Home para accesibilidad baseline');
        }

        $launcherManifest = $this->readRequiredJsonFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers.json',
            'No se pudo leer gnome-launchers.json del live materializado'
        );
        $launcherIds = $this->launcherIds($launcherManifest);
        if (!in_array('home', $launcherIds, true)) {
            throw new ValidationError('Falta el launcher home en gnome-launchers.json para accesibilidad baseline');
        }

        $capabilities = [
            [
                'id' => 'screen-reader',
                'label' => 'Lector de pantalla',
                'status' => $this->allPresent($packageStatus['present'], ['orca', 'speech-dispatcher', 'at-spi2-core', 'libatk-adaptor']) ? 'available' : 'missing',
                'packages' => ['orca', 'speech-dispatcher', 'at-spi2-core', 'libatk-adaptor'],
            ],
            [
                'id' => 'visual-assist',
                'label' => 'Contraste y ayudas visuales',
                'status' => $this->allPresent($packageStatus['present'], ['gnome-accessibility-themes', 'gsettings-desktop-schemas', 'gnome-control-center']) ? 'available' : 'missing',
                'packages' => ['gnome-accessibility-themes', 'gsettings-desktop-schemas', 'gnome-control-center'],
            ],
            [
                'id' => 'settings-route',
                'label' => 'Ruta visible a W4 Settings',
                'status' => in_array('home', $launcherIds, true) && in_array('w4-control-center-home-home.desktop', $favorites, true) ? 'available' : 'missing',
                'launcher_id' => 'home',
                'favorite' => 'w4-control-center-home-home.desktop',
            ],
        ];

        return [
            'home_accessibility_live_output_validation_schema_version' => 1,
            'kind' => 'home-accessibility-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($liveSummary['W4_GENERATED_AT'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'feature_flags' => $featureFlags,
            'required_packages' => $packageStatus,
            'capabilities' => $capabilities,
            'settings_route' => [
                'runtime_defaults' => 'image-root/system-overlay/etc/w4/desktop-defaults.json',
                'runtime_dconf' => 'image-root/system-overlay/etc/dconf/db/local.d/00-w4-home',
                'runtime_launchers' => 'image-root/system-overlay/etc/w4/control-center/gnome-launchers.json',
                'favorite' => 'w4-control-center-home-home.desktop',
                'launcher_id' => 'home',
            ],
            'deferred_steps' => [
                'pre-login-accessibility-flow',
                'keyboard-only-first-run-flow',
                'screen-reader-task-flow',
                'accessibility-persistence-qa',
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
            sprintf('Home accessibility live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
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
     * @param array<string, mixed> $launcherManifest
     * @return list<string>
     */
    private function launcherIds(array $launcherManifest): array
    {
        $launchers = is_array($launcherManifest['launchers'] ?? null) ? $launcherManifest['launchers'] : [];
        $ids = [];

        foreach ($launchers as $launcher) {
            if (is_array($launcher) && is_string($launcher['id'] ?? null)) {
                $ids[] = $launcher['id'];
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<string>
     */
    private function parseFeatureList(string $raw): array
    {
        $parts = array_map('trim', explode(',', $raw));
        $parts = array_values(array_filter($parts, static fn (string $value): bool => $value !== ''));
        sort($parts);

        return array_values(array_unique($parts));
    }

    private function relativePath(string $absolutePath): string
    {
        $normalizedRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $this->rootDir);
        $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $absolutePath);

        if (str_starts_with($normalizedPath, $normalizedRoot . DIRECTORY_SEPARATOR)) {
            return substr($normalizedPath, strlen($normalizedRoot) + 1);
        }

        return $absolutePath;
    }
}
