<?php

declare(strict_types=1);

use W4\OS\Support\ArtifactMetadataToolkit;
use W4\OS\Support\ValidationError as SupportValidationError;

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultInputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'inputs';
$defaultOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays';

/**
 * @return array<string, mixed>
 */
function readBuildInput(string $path): array
{
    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer el build input: %s', $path));
    }

    try {
        /** @var array<string, mixed> $data */
        $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('Build input invalido: %s', $exception->getMessage()));
    }

    return $data;
}

/**
 * @param array<string, mixed> $buildInput
 */
function validateBuildInput(array $buildInput, string $sourcePath): void
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

    foreach (['repositories', 'features'] as $field) {
        if (!is_array($buildInput[$field] ?? null)) {
            throw new ValidationError(sprintf('%s: %s debe ser una lista', basename($sourcePath), $field));
        }
    }
}

/**
 * @return array<string, mixed>
 */
function readEditionPolicy(string $rootDir, string $profileId): array
{
    $editionDirectory = str_replace('w4-os-', '', $profileId);
    $policyPath = $rootDir
        . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR . 'editions'
        . DIRECTORY_SEPARATOR . $editionDirectory
        . DIRECTORY_SEPARATOR . 'policy.json';

    if (!is_file($policyPath)) {
        return [];
    }

    $raw = file_get_contents($policyPath);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer la politica de edicion: %s', $policyPath));
    }

    try {
        /** @var array<string, mixed> $policy */
        $policy = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('Politica de edicion invalida: %s', $exception->getMessage()));
    }

    if (($policy['profile_id'] ?? null) !== $profileId) {
        throw new ValidationError(sprintf('La politica de edicion no corresponde al profile_id %s', $profileId));
    }

    return $policy;
}

/**
 * @return array<string, mixed>
 */
function readDesktopDefaults(string $rootDir, string $profileId): array
{
    $editionDirectory = str_replace('w4-os-', '', $profileId);
    $defaultsPath = $rootDir
        . DIRECTORY_SEPARATOR . 'config'
        . DIRECTORY_SEPARATOR . 'editions'
        . DIRECTORY_SEPARATOR . $editionDirectory
        . DIRECTORY_SEPARATOR . 'desktop-defaults.json';

    if (!is_file($defaultsPath)) {
        return [];
    }

    $raw = file_get_contents($defaultsPath);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer los defaults desktop: %s', $defaultsPath));
    }

    try {
        /** @var array<string, mixed> $defaults */
        $defaults = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $exception) {
        throw new ValidationError(sprintf('Defaults desktop invalidos: %s', $exception->getMessage()));
    }

    if (($defaults['schema_version'] ?? null) !== 1) {
        throw new ValidationError(sprintf('%s: schema_version debe ser 1', $defaultsPath));
    }

    if (($defaults['kind'] ?? null) !== 'desktop-defaults') {
        throw new ValidationError(sprintf('%s: kind debe ser desktop-defaults', $defaultsPath));
    }

    if (($defaults['profile_id'] ?? null) !== $profileId) {
        throw new ValidationError(sprintf('%s: profile_id debe ser %s', $defaultsPath, $profileId));
    }

    $desktop = $defaults['desktop'] ?? null;
    $application = $defaults['application'] ?? null;
    $theme = $defaults['theme'] ?? null;
    $wallpaper = $defaults['wallpaper'] ?? null;
    $favorites = $defaults['favorites'] ?? null;
    $branding = $defaults['branding'] ?? null;

    if (!is_array($desktop) || !is_array($application) || !is_array($theme) || !is_array($wallpaper) || !is_array($branding)) {
        throw new ValidationError(sprintf('%s: desktop, application, theme, wallpaper y branding deben ser objetos', $defaultsPath));
    }

    foreach (['shell', 'session', 'display_manager'] as $field) {
        $value = $desktop[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: desktop.%s debe ser un string no vacio', $defaultsPath, $field));
        }
    }

    foreach (['method', 'scope'] as $field) {
        $value = $application[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: application.%s debe ser un string no vacio', $defaultsPath, $field));
        }
    }

    foreach (['gtk', 'color_scheme', 'icon', 'cursor'] as $field) {
        $value = $theme[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: theme.%s debe ser un string no vacio', $defaultsPath, $field));
        }
    }

    foreach (['uri', 'asset_path'] as $field) {
        $value = $wallpaper[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: wallpaper.%s debe ser un string no vacio', $defaultsPath, $field));
        }
    }

    if (!is_array($favorites)) {
        throw new ValidationError(sprintf('%s: favorites debe ser una lista', $defaultsPath));
    }

    foreach ($favorites as $favorite) {
        if (!is_string($favorite) || $favorite === '') {
            throw new ValidationError(sprintf('%s: favorites debe contener strings no vacios', $defaultsPath));
        }
    }

    $scope = $branding['scope'] ?? null;
    if (!is_string($scope) || $scope === '') {
        throw new ValidationError(sprintf('%s: branding.scope debe ser un string no vacio', $defaultsPath));
    }

    $login = $branding['login'] ?? null;
    if (!is_array($login)) {
        throw new ValidationError(sprintf('%s: branding.login debe ser un objeto', $defaultsPath));
    }

    $mode = $login['mode'] ?? null;
    if (!is_string($mode) || $mode === '') {
        throw new ValidationError(sprintf('%s: branding.login.mode debe ser un string no vacio', $defaultsPath));
    }

    return $defaults;
}

/**
 * @param array<string, mixed> $buildInput
 * @param array<string, mixed> $editionPolicy
 * @return array<string, string>
 */
function overlayVariables(array $buildInput, array $editionPolicy): array
{
    $profileId = (string) $buildInput['profile_id'];
    $profileName = (string) $buildInput['profile_name'];
    $edition = (string) (($editionPolicy['branding']['edition'] ?? null) ?: str_replace('W4 OS ', '', $profileName));
    $roleCatalog = [
        'w4-os-home' => [
            'motd_role' => 'entorno orientado a escritorio personal',
        ],
        'w4-os-business' => [
            'motd_role' => 'entorno orientado a piloto empresarial',
        ],
        'w4-os-server' => [
            'motd_role' => 'entorno headless orientado a administracion remota y servicios',
        ],
    ];

    if (!array_key_exists($profileId, $roleCatalog)) {
        throw new ValidationError(sprintf('Perfil sin politica de overlay registrada: %s', $profileId));
    }

    $hostnamePrefix = (string) (($editionPolicy['branding']['hostname_prefix'] ?? null) ?: '');
    if ($hostnamePrefix === '') {
        throw new ValidationError(sprintf('La politica de edicion %s debe definir branding.hostname_prefix', $profileId));
    }

    $defaultTarget = (string) (($editionPolicy['boot']['default_target'] ?? null) ?: '');
    if ($defaultTarget === '') {
        throw new ValidationError(sprintf('La politica de edicion %s debe definir boot.default_target', $profileId));
    }

    $hostname = $hostnamePrefix;
    $liveHostname = $hostname . '-live';
    $liveUser = 'w4live';
    $motdRole = $roleCatalog[$profileId]['motd_role'];

    return [
        'profile_id' => $profileId,
        'profile_name' => $profileName,
        'edition' => $edition,
        'hostname' => $hostname,
        'live_hostname' => $liveHostname,
        'live_user' => $liveUser,
        'product_name' => 'W4 OS System',
        'distribution_name' => 'W4 OS',
        'motd_role' => $motdRole,
        'default_target' => $defaultTarget,
    ];
}

/**
 * @param array<string, string> $vars
 */
function buildProfileEnv(array $vars, string $features): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<ENV
W4_PRODUCT_NAME="{$vars['product_name']}"
W4_DISTRIBUTION_NAME="{$vars['distribution_name']}"
W4_PROFILE_ID="{$vars['profile_id']}"
W4_PROFILE_NAME="{$vars['profile_name']}"
W4_EDITION="{$vars['edition']}"
W4_HOSTNAME="{$vars['hostname']}"
W4_LIVE_HOSTNAME="{$vars['live_hostname']}"
W4_LIVE_USER="{$vars['live_user']}"
W4_DEFAULT_TARGET="{$vars['default_target']}"
W4_FEATURES="{$features}"
ENV);
}

/**
 * @param array<string, string> $vars
 */
function buildOsReleaseOverlay(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
W4_OS_ID="{$vars['profile_id']}"
W4_OS_PRETTY_NAME="{$vars['profile_name']}"
W4_OS_NAME="{$vars['distribution_name']}"
W4_OS_VENDOR="{$vars['product_name']}"
W4_OS_EDITION="{$vars['edition']}"
TXT);
}

/**
 * @param array<string, mixed> $desktopDefaults
 */
function buildDesktopDefaultsJson(array $desktopDefaults): string
{
    $json = json_encode($desktopDefaults, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new ValidationError('No se pudo serializar desktop-defaults.json');
    }

    return $json;
}

/**
 * @return array<string, mixed>
 */
function buildControlCenterLauncherOverlay(string $rootDir, string $profileId, array $desktopDefaults): array
{
    if ($profileId !== 'w4-os-home' || $desktopDefaults === []) {
        return [
            'status' => 'not-applicable',
            'files' => [],
        ];
    }

    try {
        $bundleDir = ensureControlCenterLauncherBundle($rootDir, $profileId);
        $bundleJsonPath = $bundleDir . DIRECTORY_SEPARATOR . 'control-center-gnome-launchers.json';
        $bundleSummaryPath = $bundleDir . DIRECTORY_SEPARATOR . 'control-center-gnome-launchers-summary.txt';
        $bundle = readOptionalJsonFile($bundleJsonPath);

        if ($bundle === null) {
            throw new ValidationError(sprintf('No se pudo leer el bundle de launchers GNOME: %s', $bundleJsonPath));
        }

        $summary = file_get_contents($bundleSummaryPath);
        if ($summary === false) {
            throw new ValidationError(sprintf('No se pudo leer el resumen de launchers GNOME: %s', $bundleSummaryPath));
        }

        $launchers = is_array($bundle['launchers'] ?? null) ? $bundle['launchers'] : [];
        $pendingModules = is_array($bundle['pending_modules'] ?? null) ? $bundle['pending_modules'] : [];
        $overlayRoot = $bundleDir . DIRECTORY_SEPARATOR . 'overlay-root';
        $overlayFiles = overlayFilesFromDirectory($overlayRoot);

        $files = [
            'files/etc/w4/control-center/gnome-launchers.json' => encodePrettyJson($bundle, 'No se pudo serializar el bundle de launchers GNOME'),
            'files/etc/w4/control-center/gnome-launchers-summary.txt' => normalizeLineEndings($summary),
        ];

        foreach ($overlayFiles as $relativePath => $contents) {
            $files['files/' . $relativePath] = $contents;
        }

        return [
            'status' => 'integrated',
            'bundle_dir' => relativePathFromRoot($rootDir, $bundleDir),
            'launcher_count' => count($launchers),
            'pending_module_count' => count($pendingModules),
            'runtime_manifest' => 'files/etc/w4/control-center/gnome-launchers.json',
            'runtime_summary' => 'files/etc/w4/control-center/gnome-launchers-summary.txt',
            'desktop_files' => array_map(
                static fn (string $path): string => 'files/' . str_replace(DIRECTORY_SEPARATOR, '/', $path),
                array_keys($overlayFiles)
            ),
            'files' => $files,
        ];
    } catch (ValidationError $exception) {
        return [
            'status' => 'skipped',
            'reason' => $exception->getMessage(),
            'files' => [],
        ];
    }
}

function ensureControlCenterLauncherBundle(string $rootDir, string $profileId): string
{
    $bundleDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'control-center-gnome-launchers' . DIRECTORY_SEPARATOR . $profileId;
    $bundleJsonPath = $bundleDir . DIRECTORY_SEPARATOR . 'control-center-gnome-launchers.json';
    $overlayRoot = $bundleDir . DIRECTORY_SEPARATOR . 'overlay-root';

    if (is_file($bundleJsonPath) && is_dir($overlayRoot)) {
        return $bundleDir;
    }

    runPhpGenerator($rootDir, 'generate_control_center_snapshot.php', ['--profile', $profileId, '--root-dir', $rootDir]);
    runPhpGenerator($rootDir, 'generate_control_center_ui_bundle.php', ['--profile', $profileId, '--root-dir', $rootDir]);
    runPhpGenerator($rootDir, 'generate_control_center_gnome_launchers.php', ['--profile', $profileId, '--root-dir', $rootDir]);

    if (!is_file($bundleJsonPath) || !is_dir($overlayRoot)) {
        throw new ValidationError(sprintf('El bundle de launchers GNOME no quedo disponible en %s', $bundleDir));
    }

    return $bundleDir;
}

/**
 * @param list<string> $arguments
 */
function runPhpGenerator(string $rootDir, string $scriptName, array $arguments): void
{
    $scriptPath = $rootDir . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . $scriptName;
    if (!is_file($scriptPath)) {
        throw new ValidationError(sprintf('No existe el generador requerido: %s', $scriptPath));
    }

    $command = array_merge([PHP_BINARY, $scriptPath], $arguments);
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];

    $process = proc_open($command, $descriptorSpec, $pipes, $rootDir);
    if (!is_resource($process)) {
        throw new ValidationError(sprintf('No se pudo ejecutar %s', $scriptName));
    }

    fclose($pipes[0]);
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    $exitCode = proc_close($process);
    if ($exitCode !== 0) {
        $details = trim((string) ($stderr !== false ? $stderr : ''));
        if ($details === '') {
            $details = trim((string) ($stdout !== false ? $stdout : ''));
        }

        throw new ValidationError(sprintf('Fallo %s: %s', $scriptName, $details));
    }
}

/**
 * @return array<string, string>
 */
function overlayFilesFromDirectory(string $directory): array
{
    if (!is_dir($directory)) {
        throw new ValidationError(sprintf('No existe el directorio de overlay: %s', $directory));
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
            continue;
        }

        $absolutePath = $fileInfo->getPathname();
        $relativePath = substr($absolutePath, strlen(rtrim($directory, DIRECTORY_SEPARATOR)) + 1);
        $contents = file_get_contents($absolutePath);
        if ($contents === false) {
            throw new ValidationError(sprintf('No se pudo leer el archivo de overlay %s', $absolutePath));
        }

        $files[str_replace(['/', '\\'], '/', $relativePath)] = $contents;
    }

    ksort($files);

    return $files;
}

/**
 * @return array<string, mixed>|null
 */
function readOptionalJsonFile(string $path): ?array
{
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false) {
        throw new ValidationError(sprintf('No se pudo leer %s', $path));
    }

    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        throw new ValidationError(sprintf('El archivo JSON no es valido: %s', $path));
    }

    return $decoded;
}

/**
 * @param array<string, mixed> $payload
 */
function encodePrettyJson(array $payload, string $errorMessage): string
{
    $encoded = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        throw new ValidationError($errorMessage);
    }

    return $encoded . "\n";
}

function normalizeLineEndings(string $contents): string
{
    return str_replace(["\r\n", "\r"], "\n", $contents);
}

function relativePathFromRoot(string $rootDir, string $path): string
{
    $normalizedRoot = rtrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rootDir), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $normalizedPath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);

    if (str_starts_with($normalizedPath, $normalizedRoot)) {
        return str_replace(DIRECTORY_SEPARATOR, '/', substr($normalizedPath, strlen($normalizedRoot)));
    }

    return str_replace(DIRECTORY_SEPARATOR, '/', $normalizedPath);
}

function buildDconfUserProfile(): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
user-db:user
system-db:local
TXT);
}

function buildDconfGdmProfile(): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
user-db:user
system-db:gdm
file-db:/usr/share/gdm/greeter-dconf-defaults
TXT);
}

function escapeGVariantString(string $value): string
{
    return str_replace(["\\", "'"], ["\\\\", "\\'"], $value);
}

/**
 * @param list<string> $items
 */
function renderGVariantStringArray(array $items): string
{
    $rendered = array_map(
        static fn (string $item): string => "'" . escapeGVariantString($item) . "'",
        $items
    );

    return '[' . implode(', ', $rendered) . ']';
}

/**
 * @param array<string, mixed> $desktopDefaults
 */
function buildUserDconfDefaults(array $desktopDefaults): string
{
    /** @var array<string, string> $theme */
    $theme = $desktopDefaults['theme'];
    /** @var array<string, string> $wallpaper */
    $wallpaper = $desktopDefaults['wallpaper'];
    /** @var list<string> $favorites */
    $favorites = $desktopDefaults['favorites'];

    $wallpaperUri = escapeGVariantString($wallpaper['uri']);
    $gtkTheme = escapeGVariantString($theme['gtk']);
    $colorScheme = escapeGVariantString($theme['color_scheme']);
    $iconTheme = escapeGVariantString($theme['icon']);
    $cursorTheme = escapeGVariantString($theme['cursor']);
    $favoriteApps = renderGVariantStringArray($favorites);

    return str_replace(["\r\n", "\r"], "\n", <<<TXT
[org/gnome/desktop/interface]
gtk-theme='{$gtkTheme}'
color-scheme='{$colorScheme}'
icon-theme='{$iconTheme}'
cursor-theme='{$cursorTheme}'

[org/gnome/desktop/background]
picture-uri='{$wallpaperUri}'
picture-uri-dark='{$wallpaperUri}'

[org/gnome/desktop/screensaver]
picture-uri='{$wallpaperUri}'

[org/gnome/shell]
favorite-apps={$favoriteApps}
TXT);
}

/**
 * @param array<string, mixed> $desktopDefaults
 */
function buildGdmDconfDefaults(array $desktopDefaults): string
{
    /** @var array<string, string> $theme */
    $theme = $desktopDefaults['theme'];
    $colorScheme = escapeGVariantString($theme['color_scheme']);

    return str_replace(["\r\n", "\r"], "\n", <<<TXT
[org/gnome/desktop/interface]
color-scheme='{$colorScheme}'
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildHomeWallpaperSvg(array $vars): string
{
    $title = htmlspecialchars($vars['profile_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $edition = htmlspecialchars($vars['edition'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return str_replace(["\r\n", "\r"], "\n", <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="3840" height="2160" viewBox="0 0 3840 2160" role="img" aria-labelledby="title desc">
  <title id="title">{$title} default wallpaper</title>
  <desc id="desc">Wallpaper base de {$edition} con identidad W4 y contraste alto.</desc>
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0b1020"/>
      <stop offset="55%" stop-color="#111c3a"/>
      <stop offset="100%" stop-color="#1d3d6f"/>
    </linearGradient>
    <radialGradient id="glow" cx="0.82" cy="0.2" r="0.7">
      <stop offset="0%" stop-color="#7dd3fc" stop-opacity="0.42"/>
      <stop offset="100%" stop-color="#7dd3fc" stop-opacity="0"/>
    </radialGradient>
  </defs>
  <rect width="3840" height="2160" fill="url(#bg)"/>
  <rect width="3840" height="2160" fill="url(#glow)"/>
  <circle cx="2940" cy="460" r="520" fill="#38bdf8" opacity="0.10"/>
  <circle cx="3180" cy="360" r="240" fill="#f8fafc" opacity="0.05"/>
  <path d="M0 1760C480 1600 820 1560 1180 1610C1530 1660 1840 1780 2160 1830C2570 1890 3000 1850 3840 1610V2160H0Z" fill="#020617" opacity="0.32"/>
  <g transform="translate(270 320)">
    <text x="0" y="0" font-family="Inter, Segoe UI, Arial, sans-serif" font-size="128" font-weight="700" fill="#f8fafc">W4</text>
    <text x="0" y="170" font-family="Inter, Segoe UI, Arial, sans-serif" font-size="64" font-weight="500" fill="#cbd5e1">{$title}</text>
    <text x="0" y="254" font-family="Inter, Segoe UI, Arial, sans-serif" font-size="38" font-weight="400" fill="#94a3b8">GNOME + GDM default route</text>
  </g>
</svg>
SVG);
}

/**
 * @param array<string, string> $vars
 */
function buildUfwDefaults(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
# W4 OS security baseline
IPV6=yes
DEFAULT_INPUT_POLICY="DROP"
DEFAULT_OUTPUT_POLICY="ACCEPT"
DEFAULT_FORWARD_POLICY="DROP"
DEFAULT_APPLICATION_POLICY="SKIP"
MANAGE_BUILTINS=no
IPT_SYSCTL=/etc/ufw/sysctl.conf
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildUfwConfig(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
# W4 OS security baseline
ENABLED=yes
LOGLEVEL=low
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildSecurityGrubDefaults(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
# W4 OS security baseline
GRUB_CMDLINE_LINUX_DEFAULT="\${GRUB_CMDLINE_LINUX_DEFAULT:+\${GRUB_CMDLINE_LINUX_DEFAULT} }apparmor=1 security=apparmor"
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildMotd(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
Bienvenido a {$vars['profile_name']}.

Esta imagen pertenece a {$vars['product_name']} y representa un {$vars['motd_role']}.
El sistema aplica identidad y tareas de primer inicio en el primer arranque.
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildIssue(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
{$vars['profile_name']} \n \l
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildIssueNet(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
{$vars['profile_name']}
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildHosts(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
127.0.0.1 localhost
127.0.1.1 {$vars['hostname']}

::1 localhost ip6-localhost ip6-loopback
ff02::1 ip6-allnodes
ff02::2 ip6-allrouters
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildSkelProfile(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
PROFILE_ID={$vars['profile_id']}
PROFILE_NAME={$vars['profile_name']}
EDITION={$vars['edition']}
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildFirstbootService(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<UNIT
[Unit]
Description=W4 OS first boot initialization ({$vars['edition']})
After=local-fs.target systemd-machine-id-commit.service
ConditionPathExists=!/var/lib/w4/firstboot-complete

[Service]
Type=oneshot
ExecStart=/usr/local/lib/w4/w4-firstboot.sh
RemainAfterExit=yes

[Install]
WantedBy={$vars['default_target']}
UNIT);
}

/**
 * @param array<string, string> $vars
 */
function buildLivePrepService(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<UNIT
[Unit]
Description=W4 OS live session preparation ({$vars['edition']})
After=local-fs.target systemd-machine-id-commit.service
Before=display-manager.service getty@tty1.service

[Service]
Type=oneshot
ExecStart=/usr/local/lib/w4/w4-live-prep.sh
RemainAfterExit=yes

[Install]
WantedBy={$vars['default_target']}
UNIT);
}

/**
 * @param array<string, string> $vars
 */
function buildFirstbootScript(array $vars): string
{
    $profileId = $vars['profile_id'];
    $hostname = $vars['hostname'];

    $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

STATE_DIR="/var/lib/w4"
STATE_FILE="${STATE_DIR}/firstboot-complete"
PROFILE_ENV="/etc/w4/profile.env"
DEFAULT_HOSTNAME="%DEFAULT_HOSTNAME%"
DEFAULT_TARGET="%DEFAULT_TARGET%"
PROFILE_ID="%PROFILE_ID%"

if [[ -f "${PROFILE_ENV}" ]]; then
  # shellcheck disable=SC1091
  . "${PROFILE_ENV}"
fi

if [[ -f "${STATE_FILE}" ]] && [[ "${W4_FIRSTBOOT_FORCE:-0}" != "1" ]]; then
  exit 0
fi

mkdir -p "${STATE_DIR}"

if [[ ! -s /etc/machine-id ]]; then
  systemd-machine-id-setup >/dev/null 2>&1 || dbus-uuidgen --ensure=/etc/machine-id >/dev/null 2>&1 || true
fi

TARGET_HOSTNAME="${W4_HOSTNAME:-${DEFAULT_HOSTNAME}}"
TARGET_DEFAULT="${W4_DEFAULT_TARGET:-${DEFAULT_TARGET}}"
CURRENT_HOSTNAME="$(cat /etc/hostname 2>/dev/null || true)"

if [[ -z "${CURRENT_HOSTNAME}" ]] || [[ "${CURRENT_HOSTNAME}" == "localhost" ]] || [[ "${CURRENT_HOSTNAME}" == "debian" ]]; then
  printf '%s\n' "${TARGET_HOSTNAME}" > /etc/hostname
fi

if command -v aa-enabled >/dev/null 2>&1; then
  aa-enabled >/dev/null 2>&1 || true
fi

if command -v systemctl >/dev/null 2>&1; then
  if [[ -n "${TARGET_DEFAULT}" ]]; then
    systemctl set-default "${TARGET_DEFAULT}" >/dev/null 2>&1 || true
  fi

  if systemctl list-unit-files apparmor.service >/dev/null 2>&1; then
    systemctl enable apparmor.service >/dev/null 2>&1 || true
    systemctl start apparmor.service >/dev/null 2>&1 || true
  fi
fi

if command -v ufw >/dev/null 2>&1; then
  ufw --force reset >/dev/null 2>&1 || true
  ufw default deny incoming >/dev/null 2>&1 || true
  ufw default allow outgoing >/dev/null 2>&1 || true
  ufw --force enable >/dev/null 2>&1 || true
fi

if command -v dconf >/dev/null 2>&1 && [[ -d /etc/dconf/db ]]; then
  dconf update >/dev/null 2>&1 || true
fi

mkdir -p /etc/w4
cat > /etc/w4/firstboot-state.env <<EOF
W4_PROFILE_ID="${W4_PROFILE_ID:-${PROFILE_ID}}"
W4_HOSTNAME_APPLIED="${TARGET_HOSTNAME}"
W4_DEFAULT_TARGET_APPLIED="${TARGET_DEFAULT}"
W4_FIRSTBOOT_COMPLETED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF

printf '%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "${STATE_FILE}"
BASH;

    $script = str_replace(
        ['%DEFAULT_HOSTNAME%', '%DEFAULT_TARGET%', '%PROFILE_ID%'],
        [$hostname, $vars['default_target'], $profileId],
        $script
    );

    return str_replace(["\r\n", "\r"], "\n", $script);
}

/**
 * @param array<string, string> $vars
 */
function buildLivePrepScript(array $vars): string
{
    $liveUser = $vars['live_user'];
    $liveHostname = $vars['live_hostname'];
    $profileId = $vars['profile_id'];

    $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

PROFILE_ENV="/etc/w4/profile.env"
LIVE_USER_DEFAULT="%LIVE_USER%"
LIVE_HOSTNAME_DEFAULT="%LIVE_HOSTNAME%"
PROFILE_ID="%PROFILE_ID%"

if [[ -f "${PROFILE_ENV}" ]]; then
  # shellcheck disable=SC1091
  . "${PROFILE_ENV}"
fi

is_live_mode() {
  if [[ "${W4_FORCE_LIVE:-0}" == "1" ]]; then
    return 0
  fi

  if [[ -e /run/live/medium ]] || [[ -e /lib/live/mount/medium ]]; then
    return 0
  fi

  if grep -Eq '(^| )boot=live( |$)|(^| )w4.live=1( |$)' /proc/cmdline 2>/dev/null; then
    return 0
  fi

  return 1
}

if ! is_live_mode; then
  exit 0
fi

LIVE_USER="${W4_LIVE_USER:-${LIVE_USER_DEFAULT}}"
LIVE_HOSTNAME="${W4_LIVE_HOSTNAME:-${LIVE_HOSTNAME_DEFAULT}}"

printf '%s\n' "${LIVE_HOSTNAME}" > /etc/hostname
mkdir -p /run/w4
printf '%s\n' "live" > /run/w4/session-mode

if ! id "${LIVE_USER}" >/dev/null 2>&1; then
  useradd -m -s /bin/bash "${LIVE_USER}"
fi

usermod -aG sudo,audio,video,plugdev,netdev "${LIVE_USER}" >/dev/null 2>&1 || true

mkdir -p /etc/systemd/system/getty@tty1.service.d
cat > /etc/systemd/system/getty@tty1.service.d/autologin.conf <<EOF
[Service]
ExecStart=
ExecStart=-/sbin/agetty --autologin ${LIVE_USER} --noclear %I \$TERM
EOF

mkdir -p /etc/w4
cat > /etc/w4/live-state.env <<EOF
W4_PROFILE_ID="${W4_PROFILE_ID:-${PROFILE_ID}}"
W4_LIVE_USER="${LIVE_USER}"
W4_LIVE_HOSTNAME="${LIVE_HOSTNAME}"
W4_LIVE_PREPARED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF

if command -v dconf >/dev/null 2>&1 && [[ -d /etc/dconf/db ]]; then
  dconf update >/dev/null 2>&1 || true
fi
BASH;

    $script = str_replace(
        ['%LIVE_USER%', '%LIVE_HOSTNAME%', '%PROFILE_ID%'],
        [$liveUser, $liveHostname, $profileId],
        $script
    );

    return str_replace(["\r\n", "\r"], "\n", $script);
}

/**
 * @param array<string, string> $vars
 */
function buildApplyScript(array $vars): string
{
    $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="${1:-}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
OVERLAY_DIR="${SCRIPT_DIR}/files"

if [[ -z "${ROOTFS_DIR}" ]]; then
  echo "ERROR: debe indicar el rootfs de destino" >&2
  exit 1
fi

if [[ ! -d "${ROOTFS_DIR}" ]]; then
  echo "ERROR: no existe el rootfs de destino: ${ROOTFS_DIR}" >&2
  exit 1
fi

mkdir -p "${ROOTFS_DIR}/etc/w4" "${ROOTFS_DIR}/usr/local/lib/w4" "${ROOTFS_DIR}/usr/share/applications" "${ROOTFS_DIR}/var/lib/w4"
cp -a "${OVERLAY_DIR}/." "${ROOTFS_DIR}/"

chown root:root "${ROOTFS_DIR}" "${ROOTFS_DIR}/etc" "${ROOTFS_DIR}/usr" "${ROOTFS_DIR}/usr/local" "${ROOTFS_DIR}/usr/local/lib" "${ROOTFS_DIR}/usr/share" || true
chown -R root:root \
  "${ROOTFS_DIR}/etc/hostname" \
  "${ROOTFS_DIR}/etc/hosts" \
  "${ROOTFS_DIR}/etc/issue" \
  "${ROOTFS_DIR}/etc/issue.net" \
  "${ROOTFS_DIR}/etc/motd" \
  "${ROOTFS_DIR}/etc/dconf" \
  "${ROOTFS_DIR}/etc/w4" \
  "${ROOTFS_DIR}/etc/default" \
  "${ROOTFS_DIR}/etc/systemd" \
  "${ROOTFS_DIR}/etc/skel" \
  "${ROOTFS_DIR}/usr/share/applications" \
  "${ROOTFS_DIR}/usr/local/lib/w4" \
  "${ROOTFS_DIR}/usr/share/w4" \
  "${ROOTFS_DIR}/var/lib/w4" || true

chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/w4-firstboot.sh"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/w4-live-prep.sh"

DEFAULT_TARGET="%DEFAULT_TARGET%"

mkdir -p "${ROOTFS_DIR}/etc/systemd/system/${DEFAULT_TARGET}.wants"
ln -sfn ../w4-firstboot.service "${ROOTFS_DIR}/etc/systemd/system/${DEFAULT_TARGET}.wants/w4-firstboot.service"
ln -sfn ../w4-live-prep.service "${ROOTFS_DIR}/etc/systemd/system/${DEFAULT_TARGET}.wants/w4-live-prep.service"

printf '%s\n' "%PROFILE_ID%" > "${ROOTFS_DIR}/var/lib/w4/system-overlay-profile"
printf '%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "${ROOTFS_DIR}/var/lib/w4/system-overlay-applied-at"

echo "Overlay aplicado a %PROFILE_NAME% en ${ROOTFS_DIR}"
BASH;

    $script = str_replace(
        ['%DEFAULT_TARGET%', '%PROFILE_ID%', '%PROFILE_NAME%'],
        [$vars['default_target'], $vars['profile_id'], $vars['profile_name']],
        $script
    );

    return str_replace(["\r\n", "\r"], "\n", $script);
}

/**
 * @param array<string, mixed> $buildInput
 * @param array<string, string> $vars
 * @param array<string, mixed> $desktopDefaults
 * @return array<string, string>
 */
function buildOverlayFiles(array $buildInput, array $vars, array $desktopDefaults): array
{
    $features = implode(',', $buildInput['features']);

    $files = [
        'files/etc/hostname' => $vars['hostname'] . "\n",
        'files/etc/hosts' => buildHosts($vars) . "\n",
        'files/etc/default/grub.d/50-w4-security.cfg' => buildSecurityGrubDefaults($vars) . "\n",
        'files/etc/default/ufw' => buildUfwDefaults($vars) . "\n",
        'files/etc/issue' => buildIssue($vars) . "\n",
        'files/etc/issue.net' => buildIssueNet($vars) . "\n",
        'files/etc/motd' => buildMotd($vars) . "\n",
        'files/etc/ufw/ufw.conf' => buildUfwConfig($vars) . "\n",
        'files/etc/default/w4-live' => sprintf("W4_LIVE_USER=%s\nW4_LIVE_HOSTNAME=%s\n", $vars['live_user'], $vars['live_hostname']),
        'files/etc/w4/profile.env' => buildProfileEnv($vars, $features) . "\n",
        'files/etc/w4/os-release.env' => buildOsReleaseOverlay($vars) . "\n",
        'files/etc/skel/.config/w4-os/profile.env' => buildSkelProfile($vars) . "\n",
        'files/etc/systemd/system/w4-firstboot.service' => buildFirstbootService($vars) . "\n",
        'files/etc/systemd/system/w4-live-prep.service' => buildLivePrepService($vars) . "\n",
        'files/usr/local/lib/w4/w4-firstboot.sh' => buildFirstbootScript($vars) . "\n",
        'files/usr/local/lib/w4/w4-live-prep.sh' => buildLivePrepScript($vars) . "\n",
        'apply-overlay.sh' => buildApplyScript($vars) . "\n",
    ];

    if ($desktopDefaults !== []) {
        $files['files/etc/w4/desktop-defaults.json'] = buildDesktopDefaultsJson($desktopDefaults) . "\n";
        $files['files/etc/dconf/profile/user'] = buildDconfUserProfile() . "\n";
        $files['files/etc/dconf/profile/gdm'] = buildDconfGdmProfile() . "\n";
        $files['files/etc/dconf/db/local.d/00-w4-home'] = buildUserDconfDefaults($desktopDefaults) . "\n";
        $files['files/etc/dconf/db/gdm.d/00-w4-login'] = buildGdmDconfDefaults($desktopDefaults) . "\n";
        $files['files/usr/share/w4/branding/home/wallpapers/w4-home-default.svg'] = buildHomeWallpaperSvg($vars) . "\n";
    }

    return $files;
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $inputPath = null;
    $outputPath = null;

    for ($index = 1, $count = count($arguments); $index < $count; $index++) {
        $argument = $arguments[$index];

        if (!isset($arguments[$index + 1]) || $arguments[$index + 1] === '') {
            throw new ValidationError(sprintf('Falta el valor para %s', $argument));
        }

        $value = $arguments[++$index];

        switch ($argument) {
            case '--profile':
                $profileId = $value;
                break;

            case '--input':
                $inputPath = $value;
                break;

            case '--output':
                $outputPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null && $inputPath === null) {
        throw new ValidationError('Debe indicar --profile o --input');
    }

    if ($inputPath === null) {
        $inputPath = $defaultInputDir . DIRECTORY_SEPARATOR . $profileId . '.build-input.json';
    }

    $buildInput = readBuildInput($inputPath);
    validateBuildInput($buildInput, $inputPath);

    /** @var string $resolvedProfileId */
    $resolvedProfileId = $buildInput['profile_id'];
    if ($profileId !== null && $profileId !== $resolvedProfileId) {
        throw new ValidationError('El perfil indicado no coincide con el build input proporcionado');
    }

    if ($outputPath === null) {
        $outputPath = $defaultOutputDir . DIRECTORY_SEPARATOR . $resolvedProfileId;
    }

    if (!is_dir($outputPath) && !mkdir($outputPath, 0777, true) && !is_dir($outputPath)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputPath));
    }

    $editionPolicy = readEditionPolicy($rootDir, $resolvedProfileId);
    $desktopDefaults = readDesktopDefaults($rootDir, $resolvedProfileId);
    $vars = overlayVariables($buildInput, $editionPolicy);
    $files = buildOverlayFiles($buildInput, $vars, $desktopDefaults);
    $controlCenterLaunchers = buildControlCenterLauncherOverlay($rootDir, $resolvedProfileId, $desktopDefaults);
    if (is_array($controlCenterLaunchers['files'] ?? null) && $controlCenterLaunchers['files'] !== []) {
        /** @var array<string, string> $launcherFiles */
        $launcherFiles = $controlCenterLaunchers['files'];
        $files = array_merge($files, $launcherFiles);
    }

    foreach ($files as $relativePath => $contents) {
        $targetPath = $outputPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $parentDir = dirname($targetPath);
        if (!is_dir($parentDir) && !mkdir($parentDir, 0777, true) && !is_dir($parentDir)) {
            throw new ValidationError(sprintf('No se pudo crear la carpeta %s', $parentDir));
        }

        if (file_put_contents($targetPath, $contents) === false) {
            throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $targetPath));
        }
    }

    $metadataToolkit = new ArtifactMetadataToolkit();
    $overlayManifestData = [
        'base_manifest_id' => $buildInput['base_manifest_id'],
        'source_build_input' => basename($inputPath),
        'branding' => [
            'product_name' => $vars['product_name'],
            'distribution_name' => $vars['distribution_name'],
            'edition' => $vars['edition'],
            'hostname' => $vars['hostname'],
            'live_hostname' => $vars['live_hostname'],
            'live_user' => $vars['live_user'],
        ],
        'edition_policy' => [
            'path' => sprintf('config/editions/%s/policy.json', str_replace('w4-os-', '', $resolvedProfileId)),
            'default_target' => $vars['default_target'],
        ],
        'features' => $buildInput['features'],
        'services' => [
            'w4-firstboot.service',
            'w4-live-prep.service',
        ],
        'security' => [
            'apparmor' => [
                'bootloader_defaults' => 'files/etc/default/grub.d/50-w4-security.cfg',
                'activation' => 'firstboot-enables-service',
            ],
            'firewall' => [
                'tool' => 'ufw',
                'defaults' => 'deny-incoming-allow-outgoing',
                'config_files' => [
                    'files/etc/default/ufw',
                    'files/etc/ufw/ufw.conf',
                ],
            ],
        ],
        'generated_files' => $metadataToolkit->normalizeGeneratedFiles(array_keys($files)),
        'next_steps' => [
            'aplicar overlay al rootfs',
            'verificar primer inicio',
            'integrar entorno live con la imagen final',
            'probar boot en VM',
        ],
    ];

    if ($desktopDefaults !== []) {
        $overlayManifestData['desktop_defaults'] = [
            'path' => sprintf('config/editions/%s/desktop-defaults.json', str_replace('w4-os-', '', $resolvedProfileId)),
            'application_method' => $desktopDefaults['application']['method'],
            'scope' => $desktopDefaults['application']['scope'],
            'runtime_file' => 'files/etc/w4/desktop-defaults.json',
            'dconf_profiles' => [
                'files/etc/dconf/profile/user',
                'files/etc/dconf/profile/gdm',
            ],
            'dconf_databases' => [
                'files/etc/dconf/db/local.d/00-w4-home',
                'files/etc/dconf/db/gdm.d/00-w4-login',
            ],
            'assets' => [
                'files/usr/share/w4/branding/home/wallpapers/w4-home-default.svg',
            ],
        ];
    }

    if (($controlCenterLaunchers['status'] ?? null) === 'integrated') {
        $overlayManifestData['control_center_gnome_launchers'] = [
            'status' => 'integrated',
            'bundle_dir' => $controlCenterLaunchers['bundle_dir'],
            'launcher_count' => $controlCenterLaunchers['launcher_count'],
            'pending_module_count' => $controlCenterLaunchers['pending_module_count'],
            'runtime_manifest' => $controlCenterLaunchers['runtime_manifest'],
            'runtime_summary' => $controlCenterLaunchers['runtime_summary'],
            'desktop_files' => $controlCenterLaunchers['desktop_files'],
        ];
    } elseif (($controlCenterLaunchers['status'] ?? null) === 'skipped') {
        $overlayManifestData['control_center_gnome_launchers'] = [
            'status' => 'skipped',
            'reason' => (string) ($controlCenterLaunchers['reason'] ?? 'unknown'),
        ];
    }

    $overlayManifest = $metadataToolkit->createManifest(
        'overlay_schema_version',
        'system-overlay',
        (string) $buildInput['profile_id'],
        (string) $buildInput['profile_name'],
        $overlayManifestData
    );

    $manifestPath = $outputPath . DIRECTORY_SEPARATOR . 'overlay-manifest.json';
    if (file_put_contents($manifestPath, json_encode($overlayManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n") === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $manifestPath));
    }

    printJson($metadataToolkit->createSuccessPayload(
        (string) $buildInput['profile_id'],
        [
        'output_directory' => $outputPath,
        'generated_files' => $metadataToolkit->normalizeGeneratedFiles(array_merge(['overlay-manifest.json'], array_keys($files))),
        ]
    ));
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
