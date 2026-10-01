<?php

declare(strict_types=1);

use W4\OS\Support\ArtifactMetadataToolkit;

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

mkdir -p "${ROOTFS_DIR}/etc/w4" "${ROOTFS_DIR}/usr/local/lib/w4" "${ROOTFS_DIR}/var/lib/w4"
cp -a "${OVERLAY_DIR}/." "${ROOTFS_DIR}/"

chown root:root "${ROOTFS_DIR}" "${ROOTFS_DIR}/etc" "${ROOTFS_DIR}/usr" "${ROOTFS_DIR}/usr/local" "${ROOTFS_DIR}/usr/local/lib" || true
chown -R root:root \
  "${ROOTFS_DIR}/etc/hostname" \
  "${ROOTFS_DIR}/etc/hosts" \
  "${ROOTFS_DIR}/etc/issue" \
  "${ROOTFS_DIR}/etc/issue.net" \
  "${ROOTFS_DIR}/etc/motd" \
  "${ROOTFS_DIR}/etc/w4" \
  "${ROOTFS_DIR}/etc/default" \
  "${ROOTFS_DIR}/etc/systemd" \
  "${ROOTFS_DIR}/etc/skel" \
  "${ROOTFS_DIR}/usr/local/lib/w4" \
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
 * @return array<string, string>
 */
function buildOverlayFiles(array $buildInput, array $vars): array
{
    $features = implode(',', $buildInput['features']);

    return [
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
    $vars = overlayVariables($buildInput, $editionPolicy);
    $files = buildOverlayFiles($buildInput, $vars);

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
    $overlayManifest = $metadataToolkit->createManifest(
        'overlay_schema_version',
        'system-overlay',
        (string) $buildInput['profile_id'],
        (string) $buildInput['profile_name'],
        [
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
        ]
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
