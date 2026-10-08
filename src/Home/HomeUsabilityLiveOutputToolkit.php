<?php

declare(strict_types=1);

namespace W4\OS\Home;

use W4\OS\Support\ValidationError;

final class HomeUsabilityLiveOutputToolkit
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
            throw new ValidationError(sprintf('Home utilizable requiere graphical.target y obtuvo %s', $defaultTarget));
        }

        $filesystemPackages = $this->readFilesystemManifestLines(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'live' . DIRECTORY_SEPARATOR . 'filesystem.manifest'
        );

        $requiredPackages = [
            'firefox-esr',
            'libreoffice',
            'nautilus',
            'gnome-control-center',
            'gnome-software',
        ];

        $recommendedPackages = [
            'evince',
            'vlc',
        ];

        $requiredPackageStatus = $this->packageStatus($filesystemPackages, $requiredPackages, true);
        $recommendedPackageStatus = $this->packageStatus($filesystemPackages, $recommendedPackages, false);

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
        $this->validateDconfFavorites($dconfFavorites, $favorites['required'], $dconfFavoritesPath);

        $launcherManifest = $this->readRequiredJsonFile(
            $resolvedLiveOutputDir . DIRECTORY_SEPARATOR . 'image-root' . DIRECTORY_SEPARATOR . 'system-overlay' . DIRECTORY_SEPARATOR . 'etc' . DIRECTORY_SEPARATOR . 'w4' . DIRECTORY_SEPARATOR . 'control-center' . DIRECTORY_SEPARATOR . 'gnome-launchers.json',
            'No se pudo leer el manifest runtime de launchers GNOME de Control Center'
        );

        if (($launcherManifest['kind'] ?? null) !== 'control-center-gnome-launchers') {
            throw new ValidationError('gnome-launchers.json no tiene un kind soportado');
        }

        $launcherIds = $this->launcherIds($launcherManifest);
        foreach (['home', 'updates'] as $requiredLauncherId) {
            if (!in_array($requiredLauncherId, $launcherIds, true)) {
                throw new ValidationError(sprintf('Falta el launcher requerido %s en gnome-launchers.json', $requiredLauncherId));
            }
        }

        return [
            'home_usability_live_output_validation_schema_version' => 1,
            'kind' => 'home-usability-live-output-validation',
            'profile_id' => $profileId,
            'generated_at' => (string) ($liveSummary['W4_GENERATED_AT'] ?? gmdate('c')),
            'live_output_dir' => $this->relativePath($resolvedLiveOutputDir),
            'required_packages' => $requiredPackageStatus,
            'recommended_packages' => $recommendedPackageStatus,
            'favorites' => [
                'declared' => $favorites['declared'],
                'required' => $favorites['required'],
                'runtime_defaults' => 'image-root/system-overlay/etc/w4/desktop-defaults.json',
                'runtime_dconf' => 'image-root/system-overlay/etc/dconf/db/local.d/00-w4-home',
            ],
            'visible_routes' => [
                [
                    'id' => 'browser',
                    'label' => 'Navegacion',
                    'status' => $requiredPackageStatus['present']['firefox-esr'] ? 'available' : 'missing',
                    'package' => 'firefox-esr',
                    'favorite' => 'firefox-esr.desktop',
                ],
                [
                    'id' => 'documents',
                    'label' => 'Documentos',
                    'status' => $requiredPackageStatus['present']['libreoffice'] ? 'available' : 'missing',
                    'package' => 'libreoffice',
                    'favorite' => 'org.libreoffice.LibreOffice.StartCenter.desktop',
                ],
                [
                    'id' => 'files',
                    'label' => 'Archivos',
                    'status' => $requiredPackageStatus['present']['nautilus'] ? 'available' : 'missing',
                    'package' => 'nautilus',
                    'favorite' => 'org.gnome.Nautilus.desktop',
                ],
                [
                    'id' => 'settings',
                    'label' => 'W4 Settings',
                    'status' => in_array('home', $launcherIds, true) ? 'available' : 'missing',
                    'launcher_id' => 'home',
                    'favorite' => 'w4-control-center-home-home.desktop',
                ],
                [
                    'id' => 'updates',
                    'label' => 'Actualizaciones',
                    'status' => in_array('updates', $launcherIds, true) && $requiredPackageStatus['present']['gnome-software'] ? 'available' : 'missing',
                    'launcher_id' => 'updates',
                    'favorite' => 'w4-control-center-home-updates.desktop',
                ],
            ],
            'deferred_capabilities' => [
                'pdf-viewer',
                'multimedia',
                'home-onboarding',
                'external-backup-guidance',
                'accessibility-baseline',
            ],
            'control_center' => [
                'runtime_manifest' => 'image-root/system-overlay/etc/w4/control-center/gnome-launchers.json',
                'launcher_ids' => $launcherIds,
            ],
            'live_summary' => [
                'live_user' => (string) ($liveSummary['W4_LIVE_USER'] ?? ''),
                'live_hostname' => (string) ($liveSummary['W4_LIVE_HOSTNAME'] ?? ''),
                'default_target' => $defaultTarget,
            ],
        ];
    }

    /**
     * @param array<string, mixed> $validation
     */
    public function renderSummaryText(array $validation): string
    {
        $requiredPackages = is_array($validation['required_packages'] ?? null) ? $validation['required_packages'] : [];
        $recommendedPackages = is_array($validation['recommended_packages'] ?? null) ? $validation['recommended_packages'] : [];
        $favorites = is_array($validation['favorites'] ?? null) ? $validation['favorites'] : [];
        $favoriteEntries = is_array($favorites['declared'] ?? null) ? $favorites['declared'] : [];
        $visibleRoutes = is_array($validation['visible_routes'] ?? null) ? $validation['visible_routes'] : [];
        $liveSummary = is_array($validation['live_summary'] ?? null) ? $validation['live_summary'] : [];

        $lines = [
            sprintf('Home utilizable live output · %s', (string) ($validation['profile_id'] ?? 'unknown')),
            sprintf('Live output: %s', (string) ($validation['live_output_dir'] ?? 'build/live-output/...')),
            sprintf('Target: %s', (string) ($liveSummary['default_target'] ?? '')),
            sprintf('Live user: %s', (string) ($liveSummary['live_user'] ?? '')),
            sprintf('Live host: %s', (string) ($liveSummary['live_hostname'] ?? '')),
            '',
            'Required packages:',
        ];

        foreach ((array) ($requiredPackages['present'] ?? []) as $package => $present) {
            $lines[] = sprintf('- %s: %s', (string) $package, $present ? 'yes' : 'no');
        }

        $lines[] = '';
        $lines[] = 'Recommended packages:';
        foreach ((array) ($recommendedPackages['present'] ?? []) as $package => $present) {
            $lines[] = sprintf('- %s: %s', (string) $package, $present ? 'yes' : 'no');
        }

        $lines[] = '';
        $lines[] = 'Favorites:';
        foreach ($favoriteEntries as $favoriteEntry) {
            if (is_string($favoriteEntry)) {
                $lines[] = sprintf('- %s', $favoriteEntry);
            }
        }

        $lines[] = '';
        $lines[] = 'Visible routes:';
        foreach ($visibleRoutes as $route) {
            if (!is_array($route)) {
                continue;
            }

            $lines[] = sprintf(
                '- %s: %s',
                (string) ($route['label'] ?? $route['id'] ?? 'route'),
                (string) ($route['status'] ?? 'unknown')
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

    /**
     * @param list<string> $filesystemPackages
     * @param list<string> $expectedPackages
     * @return array{declared:list<string>,present:array<string,bool>,missing:list<string>,strict:bool}
     */
    private function packageStatus(array $filesystemPackages, array $expectedPackages, bool $strict): array
    {
        $present = [];
        $missing = [];

        foreach ($expectedPackages as $package) {
            $isPresent = in_array($package, $filesystemPackages, true);
            $present[$package] = $isPresent;

            if (!$isPresent) {
                $missing[] = $package;
            }
        }

        if ($strict && $missing !== []) {
            throw new ValidationError(sprintf(
                'Faltan paquetes requeridos del baseline utilizable de Home: %s',
                implode(', ', $missing)
            ));
        }

        return [
            'declared' => $expectedPackages,
            'present' => $present,
            'missing' => $missing,
            'strict' => $strict,
        ];
    }

    /**
     * @param array<string, mixed> $desktopDefaults
     * @return array{declared:list<string>,required:list<string>}
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

        $declaredFavorites = [];
        foreach ($favorites as $favorite) {
            if (!is_string($favorite) || $favorite === '') {
                throw new ValidationError('desktop-defaults.json contiene un favorito invalido');
            }

            $declaredFavorites[] = $favorite;
        }

        $requiredFavorites = [
            'org.gnome.Nautilus.desktop',
            'firefox-esr.desktop',
            'org.libreoffice.LibreOffice.StartCenter.desktop',
            'w4-control-center-home-home.desktop',
            'w4-control-center-home-updates.desktop',
        ];

        foreach ($requiredFavorites as $requiredFavorite) {
            if (!in_array($requiredFavorite, $declaredFavorites, true)) {
                throw new ValidationError(sprintf('Falta el favorito requerido %s en desktop-defaults.json', $requiredFavorite));
            }
        }

        if (in_array('org.gnome.Software.desktop', $declaredFavorites, true)) {
            throw new ValidationError('desktop-defaults.json mantiene org.gnome.Software.desktop como favorito legacy');
        }

        return [
            'declared' => $declaredFavorites,
            'required' => $requiredFavorites,
        ];
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

    /**
     * @param array<string, mixed> $launcherManifest
     * @return list<string>
     */
    private function launcherIds(array $launcherManifest): array
    {
        $launcherIds = [];
        $launchers = $launcherManifest['launchers'] ?? null;
        if (!is_array($launchers)) {
            return $launcherIds;
        }

        foreach ($launchers as $launcher) {
            if (!is_array($launcher)) {
                continue;
            }

            $id = trim((string) ($launcher['id'] ?? ''));
            if ($id === '') {
                continue;
            }

            $launcherIds[] = $id;
        }

        return array_values(array_unique($launcherIds));
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
