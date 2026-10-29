<?php

declare(strict_types=1);

namespace W4\OS\ControlCenter;

use W4\OS\Support\ValidationError;

final class ControlCenterLiveOutputToolkit
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

        $launcherManifest = $this->readRequiredJsonFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers.json',
            'No se pudo leer el manifest runtime de launchers GNOME de Control Center'
        );

        if (($launcherManifest['kind'] ?? null) !== 'control-center-gnome-launchers') {
            throw new ValidationError('gnome-launchers.json no tiene un kind soportado');
        }

        $summaryPath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers-summary.txt';
        $summary = file_get_contents($summaryPath);
        if ($summary === false) {
            throw new ValidationError(sprintf('No se pudo leer el resumen runtime de launchers GNOME: %s', $summaryPath));
        }

        $launchers = is_array($launcherManifest['launchers'] ?? null) ? $launcherManifest['launchers'] : [];
        $pendingModules = is_array($launcherManifest['pending_modules'] ?? null) ? $launcherManifest['pending_modules'] : [];
        $desktopFiles = [];

        foreach ($launchers as $launcher) {
            if (!is_array($launcher)) {
                continue;
            }

            $desktopFile = trim((string) ($launcher['desktop_file'] ?? ''));
            if ($desktopFile === '') {
                continue;
            }

            $path = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'usr' . DIRECTORY_SEPARATOR . 'share' . DIRECTORY_SEPARATOR . 'applications' . DIRECTORY_SEPARATOR . $desktopFile;
            if (!is_file($path)) {
                throw new ValidationError(sprintf('Falta el launcher materializado %s', $path));
            }

            $desktopFiles[] = 'image-root/system-overlay/usr/share/applications/' . $desktopFile;
        }

        $filesystemManifestLines = $this->readFilesystemManifestLines(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest'
        );
        $desktopDefaults = $this->readRequiredJsonFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'desktop-defaults.json',
            'No se pudo leer desktop-defaults.json del live materializado'
        );
        $favorites = $this->validateFavorites($desktopDefaults);
        $dconfFavoritesPath = $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'dconf' . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'local.d' . DIRECTORY_SEPARATOR . '00-w4-home';
        $dconfFavorites = $this->readRequiredTextFile(
            $dconfFavoritesPath,
            'No se pudo leer el payload dconf de favoritos del live materializado'
        );
        $this->validateDconfFavorites($dconfFavorites, $favorites, $dconfFavoritesPath);

        return [
            'control_center_live_output_validation_schema_version' => 1,
            'kind' => 'control-center-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($liveSummary['W4_GENERATED_AT'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'launcher_count' => count($launchers),
            'pending_module_count' => count($pendingModules),
            'desktop_files' => $desktopFiles,
            'runtime_manifest' => 'image-root/system-overlay/etc/w4/control-center/gnome-launchers.json',
            'runtime_summary' => 'image-root/system-overlay/etc/w4/control-center/gnome-launchers-summary.txt',
            'filesystem_packages' => [
                'gnome-control-center' => in_array('gnome-control-center', $filesystemManifestLines, true),
                'gnome-software' => in_array('gnome-software', $filesystemManifestLines, true),
            ],
            'favorites' => [
                'declared' => $favorites,
                'runtime_defaults' => 'image-root/system-overlay/etc/w4/desktop-defaults.json',
                'runtime_dconf' => 'image-root/system-overlay/etc/dconf/db/local.d/00-w4-home',
            ],
            'live_summary' => [
                'live_user' => (string) ($liveSummary['W4_LIVE_USER'] ?? ''),
                'live_hostname' => (string) ($liveSummary['W4_LIVE_HOSTNAME'] ?? ''),
                'default_target' => (string) ($liveSummary['W4_DEFAULT_TARGET'] ?? ''),
            ],
            'summary_excerpt' => $this->summaryExcerpt($summary),
        ];
    }

    /**
     * @param array<string, mixed> $validation
     */
    public function renderSummaryText(array $validation): string
    {
        $desktopFiles = is_array($validation['desktop_files'] ?? null) ? $validation['desktop_files'] : [];
        $packages = is_array($validation['filesystem_packages'] ?? null) ? $validation['filesystem_packages'] : [];
        $favorites = is_array($validation['favorites'] ?? null) ? $validation['favorites'] : [];
        $favoriteEntries = is_array($favorites['declared'] ?? null) ? $favorites['declared'] : [];
        $liveSummary = is_array($validation['live_summary'] ?? null) ? $validation['live_summary'] : [];

        $lines = [
            sprintf('Control Center live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Live output: %s', (string) ($validation['live_output_dir'] ?? 'build/live-output/...')),
            sprintf('Launchers: %d', (int) ($validation['launcher_count'] ?? 0)),
            sprintf('Pending modules: %d', (int) ($validation['pending_module_count'] ?? 0)),
            sprintf('Target: %s', (string) ($liveSummary['default_target'] ?? '')),
            sprintf('Live user: %s', (string) ($liveSummary['live_user'] ?? '')),
            sprintf('Live host: %s', (string) ($liveSummary['live_hostname'] ?? '')),
            '',
            sprintf('filesystem.manifest -> gnome-control-center: %s', !empty($packages['gnome-control-center']) ? 'yes' : 'no'),
            sprintf('filesystem.manifest -> gnome-software: %s', !empty($packages['gnome-software']) ? 'yes' : 'no'),
            '',
            'Favorites:',
            '',
        ];

        foreach ($favoriteEntries as $favoriteEntry) {
            if (is_string($favoriteEntry)) {
                $lines[] = sprintf('- %s', $favoriteEntry);
            }
        }

        $lines[] = '';
        $lines[] = 'Desktop files:';
        foreach ($desktopFiles as $desktopFile) {
            if (is_string($desktopFile)) {
                $lines[] = sprintf('- %s', $desktopFile);
            }
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
     * @return list<string>
     */
    private function readFilesystemManifestLines(string $path): array
    {
        if (!is_file($path)) {
            throw new ValidationError(sprintf('No se pudo leer filesystem.manifest: %s', $path));
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new ValidationError(sprintf('No se pudo leer filesystem.manifest: %s', $path));
        }

        $packages = [];
        $lines = preg_split('/\r?\n/', $raw) ?: [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $parts = preg_split('/\s+/', $line);
            if ($parts === false || $parts === []) {
                continue;
            }

            $packages[] = $parts[0];
        }

        return array_values(array_unique($packages));
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

    private function summaryExcerpt(string $summary): string
    {
        $summary = trim(str_replace(["\r\n", "\r"], "\n", $summary));
        if ($summary === '') {
            return '';
        }

        $lines = preg_split('/\n/', $summary) ?: [];
        $excerpt = array_slice($lines, 0, 4);

        return implode("\n", $excerpt);
    }

    /**
     * @param array<string, mixed> $desktopDefaults
     * @return list<string>
     */
    private function validateFavorites(array $desktopDefaults): array
    {
        if (($desktopDefaults['kind'] ?? null) !== 'desktop-defaults') {
            throw new ValidationError('desktop-defaults.json no tiene un kind soportado');
        }

        $favorites = $desktopDefaults['favorites'] ?? null;
        if (!is_array($favorites)) {
            throw new ValidationError('desktop-defaults.json no declara una lista de favorites');
        }

        $normalizedFavorites = [];
        foreach ($favorites as $favorite) {
            if (!is_string($favorite) || $favorite === '') {
                throw new ValidationError('desktop-defaults.json contiene un favorito invalido');
            }

            $normalizedFavorites[] = $favorite;
        }

        foreach ([
            'w4-control-center-home-home.desktop',
            'w4-control-center-home-updates.desktop',
        ] as $requiredFavorite) {
            if (!in_array($requiredFavorite, $normalizedFavorites, true)) {
                throw new ValidationError(sprintf('Falta el favorito requerido %s en desktop-defaults.json', $requiredFavorite));
            }
        }

        if (in_array('org.gnome.Software.desktop', $normalizedFavorites, true)) {
            throw new ValidationError('desktop-defaults.json mantiene org.gnome.Software.desktop como favorito legacy');
        }

        return $normalizedFavorites;
    }

    /**
     * @param list<string> $favorites
     */
    private function validateDconfFavorites(string $dconfDefaults, array $favorites, string $path): void
    {
        foreach ($favorites as $favorite) {
            $needle = sprintf("'%s'", $favorite);
            if (!str_contains($dconfDefaults, $needle)) {
                throw new ValidationError(sprintf('El payload dconf no contiene el favorito %s: %s', $favorite, $path));
            }
        }

        if (str_contains($dconfDefaults, "'org.gnome.Software.desktop'")) {
            throw new ValidationError(sprintf('El payload dconf mantiene org.gnome.Software.desktop como favorito legacy: %s', $path));
        }
    }
}
