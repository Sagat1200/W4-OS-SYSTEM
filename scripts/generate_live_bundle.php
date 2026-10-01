<?php

declare(strict_types=1);

use W4\OS\Support\ValidationError;

require_once __DIR__ . '/lib/ManifestToolkit.php';

$rootDir = dirname(__DIR__);
$defaultInputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'inputs';
$defaultOverlayDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'overlays';
$defaultOutputDir = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'live';

/**
 * @return array<string, mixed>
 */
function readJsonFile(string $path): array
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

    return $data;
}

function removeDirectory(string $path): void
{
    if (!is_dir($path)) {
        return;
    }

    $items = scandir($path);
    if ($items === false) {
        throw new ValidationError(sprintf('No se pudo listar la carpeta %s', $path));
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $itemPath = $path . DIRECTORY_SEPARATOR . $item;
        if (is_dir($itemPath) && !is_link($itemPath)) {
            removeDirectory($itemPath);
            continue;
        }

        if (!unlink($itemPath)) {
            throw new ValidationError(sprintf('No se pudo eliminar %s', $itemPath));
        }
    }

    if (!rmdir($path)) {
        throw new ValidationError(sprintf('No se pudo eliminar la carpeta %s', $path));
    }
}

function copyDirectory(string $sourceDir, string $targetDir): void
{
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta %s', $targetDir));
    }

    $items = scandir($sourceDir);
    if ($items === false) {
        throw new ValidationError(sprintf('No se pudo listar la carpeta %s', $sourceDir));
    }

    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $sourcePath = $sourceDir . DIRECTORY_SEPARATOR . $item;
        $targetPath = $targetDir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($sourcePath) && !is_link($sourcePath)) {
            copyDirectory($sourcePath, $targetPath);
            continue;
        }

        if (!is_dir(dirname($targetPath)) && !mkdir(dirname($targetPath), 0777, true) && !is_dir(dirname($targetPath))) {
            throw new ValidationError(sprintf('No se pudo crear la carpeta %s', dirname($targetPath)));
        }

        if (!copy($sourcePath, $targetPath)) {
            throw new ValidationError(sprintf('No se pudo copiar %s hacia %s', $sourcePath, $targetPath));
        }
    }
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
}

/**
 * @param array<string, mixed> $overlayManifest
 */
function validateOverlayManifest(array $overlayManifest, string $sourcePath, string $expectedProfileId): void
{
    if (($overlayManifest['overlay_schema_version'] ?? null) !== 1) {
        throw new ValidationError(sprintf('%s: overlay_schema_version debe ser 1', basename($sourcePath)));
    }

    if (($overlayManifest['kind'] ?? null) !== 'system-overlay') {
        throw new ValidationError(sprintf('%s: kind debe ser system-overlay', basename($sourcePath)));
    }

    if (($overlayManifest['profile_id'] ?? null) !== $expectedProfileId) {
        throw new ValidationError(sprintf('%s: profile_id no coincide con el build input', basename($sourcePath)));
    }
}

/**
 * @param array<string, mixed> $buildInput
 * @param array<string, mixed> $overlayManifest
 * @return array<string, string>
 */
function liveVariables(array $buildInput, array $overlayManifest): array
{
    /** @var array<string, mixed> $branding */
    $branding = is_array($overlayManifest['branding'] ?? null) ? $overlayManifest['branding'] : [];
    /** @var array<string, mixed> $editionPolicy */
    $editionPolicy = is_array($overlayManifest['edition_policy'] ?? null) ? $overlayManifest['edition_policy'] : [];

    $profileId = (string) $buildInput['profile_id'];
    $profileName = (string) $buildInput['profile_name'];
    $edition = (string) ($branding['edition'] ?? str_replace('W4 OS ', '', $profileName));
    $liveUser = (string) ($branding['live_user'] ?? 'w4live');
    $liveHostname = (string) ($branding['live_hostname'] ?? ($profileId . '-live'));
    $distributionName = (string) ($branding['distribution_name'] ?? 'W4 OS');
    $defaultTarget = (string) ($editionPolicy['default_target'] ?? 'multi-user.target');
    $volumeId = strtoupper(str_replace(['-', ' '], '_', $profileId . '_LIVE'));

    return [
        'profile_id' => $profileId,
        'profile_name' => $profileName,
        'edition' => $edition,
        'live_user' => $liveUser,
        'live_hostname' => $liveHostname,
        'distribution_name' => $distributionName,
        'default_target' => $defaultTarget,
        'volume_id' => substr($volumeId, 0, 32),
    ];
}

/**
 * @param array<string, string> $vars
 */
function buildDiskInfo(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", sprintf(
        "%s %s Live (%s)\n",
        $vars['distribution_name'],
        $vars['edition'],
        $vars['profile_id']
    ));
}

/**
 * @param array<string, string> $vars
 */
function buildReadme(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<TXT
W4 OS Live Bundle

Perfil: {$vars['profile_name']}
Edicion: {$vars['edition']}
Host live: {$vars['live_hostname']}
Usuario live: {$vars['live_user']}

Este bundle compone una estructura live a partir de:
- rootfs ensamblado
- overlay de sistema aplicado
- pila live-boot/live-config instalada en copia temporal

El resultado incluye:
- kernel e initrd listos para arranque live
- filesystem.squashfs
- manifest de paquetes
- grub.cfg base para UEFI/GRUB

Si el host dispone de xorriso y grub-mkstandalone, la siguiente etapa sera empaquetar ISO arrancable.
TXT);
}

/**
 * @param array<string, string> $vars
 */
function buildGrubCfg(array $vars): string
{
    return str_replace(["\r\n", "\r"], "\n", <<<CFG
set default=0
set timeout=5

menuentry "{$vars['distribution_name']} {$vars['edition']} (Live)" {
    linux /live/vmlinuz boot=live components username={$vars['live_user']} hostname={$vars['live_hostname']} quiet splash
    initrd /live/initrd
}

menuentry "{$vars['distribution_name']} {$vars['edition']} (Live, Safe Graphics)" {
    linux /live/vmlinuz boot=live components nomodeset username={$vars['live_user']} hostname={$vars['live_hostname']}
    initrd /live/initrd
}
CFG);
}

/**
 * @param array<string, string> $vars
 */
function buildComposeScript(array $vars): string
{
    $profileId = $vars['profile_id'];
    $profileName = $vars['profile_name'];
    $liveUser = $vars['live_user'];
    $liveHostname = $vars['live_hostname'];

    $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="${1:-}"
OUTPUT_DIR="${2:-}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILES_DIR="${SCRIPT_DIR}/files"
OVERLAY_FILES_DIR="${FILES_DIR}/system-overlay"
PROFILE_ID="%PROFILE_ID%"
PROFILE_NAME="%PROFILE_NAME%"
LIVE_USER="%LIVE_USER%"
LIVE_HOSTNAME="%LIVE_HOSTNAME%"
DIST_NAME="%DIST_NAME%"
EDITION="%EDITION%"
WORK_ROOTFS_DEFAULT="/var/tmp/w4-os-system/${PROFILE_ID}/live-work-rootfs"
WORK_ROOTFS="${W4_LIVE_WORK_ROOTFS:-${WORK_ROOTFS_DEFAULT}}"
STAGE_OUTPUT_DEFAULT="/var/tmp/w4-os-system/${PROFILE_ID}/live-output-stage"
STAGE_OUTPUT_DIR="${W4_LIVE_STAGE_OUTPUT_DIR:-${STAGE_OUTPUT_DEFAULT}}"
PREPARE_LIVE_STACK="${W4_PREPARE_LIVE_STACK:-1}"

ensure_debian_keyring_in_rootfs() {
  local rootfs_dir="${1}"
  local target_dir="${rootfs_dir}/usr/share/keyrings"
  local target_keyring="${target_dir}/debian-archive-keyring.gpg"
  local compatibility_keyring="${target_dir}/debian-archive-current.gpg"

  if [[ -f "${target_keyring}" ]]; then
    ln -sf debian-archive-keyring.gpg "${compatibility_keyring}"
    return 0
  fi

  mkdir -p "${target_dir}"

  if [[ -f /usr/share/keyrings/debian-archive-current.gpg ]] && command -v gpg >/dev/null 2>&1; then
    gpg --no-default-keyring --keyring /usr/share/keyrings/debian-archive-current.gpg --export > "${target_keyring}"
    ln -sf debian-archive-keyring.gpg "${compatibility_keyring}"
    return 0
  fi

  if [[ -f /usr/share/keyrings/debian-archive-keyring.gpg ]]; then
    install -m 0644 /usr/share/keyrings/debian-archive-keyring.gpg "${target_keyring}"
    ln -sf debian-archive-keyring.gpg "${compatibility_keyring}"
    return 0
  fi

  if [[ -f /usr/share/keyrings/debian-archive-current.gpg ]]; then
    install -m 0644 /usr/share/keyrings/debian-archive-current.gpg "${target_keyring}"
    ln -sf debian-archive-keyring.gpg "${compatibility_keyring}"
    return 0
  fi

  echo "ERROR: no se encontro un keyring Debian utilizable en el host Linux." >&2
  exit 1
}

normalize_debian_sources_keyring() {
  local rootfs_dir="${1}"

  if [[ -f "${rootfs_dir}/etc/apt/sources.list" ]]; then
    sed -i 's|/usr/share/keyrings/debian-archive-current.gpg|/usr/share/keyrings/debian-archive-keyring.gpg|g' "${rootfs_dir}/etc/apt/sources.list"
    sed -E -i 's|/var/tmp/w4-os-system/[^ ]*/bootstrap-keyring/debian-host-bootstrap-keyring.gpg|/usr/share/keyrings/debian-archive-keyring.gpg|g' "${rootfs_dir}/etc/apt/sources.list"
  fi

  if [[ -d "${rootfs_dir}/etc/apt/sources.list.d" ]]; then
    find "${rootfs_dir}/etc/apt/sources.list.d" -maxdepth 1 -type f -name '*.list' -exec \
      sed -i 's|/usr/share/keyrings/debian-archive-current.gpg|/usr/share/keyrings/debian-archive-keyring.gpg|g' {} +
    find "${rootfs_dir}/etc/apt/sources.list.d" -maxdepth 1 -type f -name '*.list' -exec \
      sed -E -i 's|/var/tmp/w4-os-system/[^ ]*/bootstrap-keyring/debian-host-bootstrap-keyring.gpg|/usr/share/keyrings/debian-archive-keyring.gpg|g' {} +
  fi
}

refresh_apt_indices() {
  local rootfs_dir="${1}"
  local attempt=1
  local max_attempts=3

  while (( attempt <= max_attempts )); do
    echo "==> apt-get update (intento ${attempt}/${max_attempts})"
    chroot "${rootfs_dir}" env DEBIAN_FRONTEND=noninteractive apt-get clean
    rm -rf "${rootfs_dir}/var/lib/apt/lists/"*
    mkdir -p "${rootfs_dir}/var/lib/apt/lists/partial"

    if chroot "${rootfs_dir}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 update; then
      return 0
    fi

    attempt=$((attempt + 1))
  done

  echo "ERROR: apt-get update fallo despues de varios intentos." >&2
  exit 1
}

install_live_stack() {
  local rootfs_dir="${1}"
  local attempt=1
  local max_attempts=3

  refresh_apt_indices "${rootfs_dir}"

  while (( attempt <= max_attempts )); do
    echo "==> apt-get install live-boot/live-config (intento ${attempt}/${max_attempts})"
    chroot "${rootfs_dir}" env DEBIAN_FRONTEND=noninteractive apt-get clean
    rm -f "${rootfs_dir}/var/cache/apt/archives/"*.deb
    mkdir -p "${rootfs_dir}/var/cache/apt/archives/partial"

    if chroot "${rootfs_dir}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 install -y live-boot live-config; then
      return 0
    fi

    refresh_apt_indices "${rootfs_dir}"
    attempt=$((attempt + 1))
  done

  echo "ERROR: la instalacion de live-boot/live-config fallo despues de varios intentos." >&2
  exit 1
}

cleanup() {
  umount -lf "${WORK_ROOTFS}/proc" 2>/dev/null || true
  umount -lf "${WORK_ROOTFS}/sys" 2>/dev/null || true
  umount -lf "${WORK_ROOTFS}/dev" 2>/dev/null || true
}

unmount_work_rootfs() {
  umount -lf "${WORK_ROOTFS}/proc" 2>/dev/null || true
  umount -lf "${WORK_ROOTFS}/sys" 2>/dev/null || true
  umount -lf "${WORK_ROOTFS}/dev" 2>/dev/null || true
}

apply_system_overlay() {
  local rootfs_dir="${1}"

  if [[ ! -d "${OVERLAY_FILES_DIR}" ]]; then
    echo "ERROR: el bundle live no incluye files/system-overlay" >&2
    exit 1
  fi

  rsync -aHAX "${OVERLAY_FILES_DIR}/" "${rootfs_dir}/"

  mkdir -p "${rootfs_dir}/etc/w4" "${rootfs_dir}/usr/local/lib/w4" "${rootfs_dir}/var/lib/w4"
  chown root:root "${rootfs_dir}" "${rootfs_dir}/etc" "${rootfs_dir}/usr" "${rootfs_dir}/usr/local" "${rootfs_dir}/usr/local/lib" 2>/dev/null || true
  chmod 0755 "${rootfs_dir}" "${rootfs_dir}/etc" "${rootfs_dir}/usr" "${rootfs_dir}/usr/local" "${rootfs_dir}/usr/local/lib" 2>/dev/null || true
  chown -R root:root \
    "${rootfs_dir}/etc/hostname" \
    "${rootfs_dir}/etc/hosts" \
    "${rootfs_dir}/etc/issue" \
    "${rootfs_dir}/etc/issue.net" \
    "${rootfs_dir}/etc/motd" \
    "${rootfs_dir}/etc/w4" \
    "${rootfs_dir}/etc/default" \
    "${rootfs_dir}/etc/ufw" \
    "${rootfs_dir}/etc/systemd" \
    "${rootfs_dir}/etc/skel" \
    "${rootfs_dir}/usr/local/lib/w4" \
    "${rootfs_dir}/var/lib/w4" 2>/dev/null || true

  [[ -f "${rootfs_dir}/usr/local/lib/w4/w4-firstboot.sh" ]] && chmod 0755 "${rootfs_dir}/usr/local/lib/w4/w4-firstboot.sh"
  [[ -f "${rootfs_dir}/usr/local/lib/w4/w4-live-prep.sh" ]] && chmod 0755 "${rootfs_dir}/usr/local/lib/w4/w4-live-prep.sh"
  [[ -d "${rootfs_dir}/etc/default" ]] && chmod 0755 "${rootfs_dir}/etc/default"
  [[ -d "${rootfs_dir}/etc/ufw" ]] && chmod 0755 "${rootfs_dir}/etc/ufw"
  [[ -f "${rootfs_dir}/etc/default/ufw" ]] && chmod 0644 "${rootfs_dir}/etc/default/ufw"
  [[ -f "${rootfs_dir}/etc/ufw/ufw.conf" ]] && chmod 0644 "${rootfs_dir}/etc/ufw/ufw.conf"

  mkdir -p "${rootfs_dir}/etc/systemd/system/multi-user.target.wants"
  if [[ -f "${rootfs_dir}/etc/systemd/system/w4-firstboot.service" ]]; then
    ln -sfn ../w4-firstboot.service "${rootfs_dir}/etc/systemd/system/multi-user.target.wants/w4-firstboot.service"
  fi
  if [[ -f "${rootfs_dir}/etc/systemd/system/w4-live-prep.service" ]]; then
    ln -sfn ../w4-live-prep.service "${rootfs_dir}/etc/systemd/system/multi-user.target.wants/w4-live-prep.service"
  fi

  printf '%s\n' "${PROFILE_ID}" > "${rootfs_dir}/var/lib/w4/system-overlay-profile"
  printf '%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "${rootfs_dir}/var/lib/w4/system-overlay-applied-at"
}

prepare_live_identity() {
  local rootfs_dir="${1}"
  local profile_env="${rootfs_dir}/etc/w4/profile.env"
  local os_release_overlay="${rootfs_dir}/etc/w4/os-release.env"
  local target_os_release="${rootfs_dir}/etc/os-release"
  local pretty_name="${DIST_NAME} ${EDITION}"
  local distro_name="${DIST_NAME}"
  local distro_id="w4"
  local distro_home="https://www.debian.org/"
  local distro_support="https://www.debian.org/support"
  local distro_bug="https://bugs.debian.org/"
  local version_id=""
  local version_codename=""
  local debian_version_full=""
  local original_name=""
  local original_pretty=""
  local original_id=""

  rm -f "${rootfs_dir}/var/lib/w4/firstboot-complete"

  if [[ -f "${profile_env}" ]]; then
    # shellcheck disable=SC1090
    . "${profile_env}"
    pretty_name="${W4_PROFILE_NAME:-${pretty_name}}"
    distro_name="${W4_DISTRIBUTION_NAME:-${distro_name}}"
  fi

  if [[ -f "${os_release_overlay}" ]]; then
    # shellcheck disable=SC1090
    . "${os_release_overlay}"
    pretty_name="${W4_OS_PRETTY_NAME:-${pretty_name}}"
    distro_name="${W4_OS_NAME:-${distro_name}}"
    distro_id="${W4_OS_ID:-${distro_id}}"
  fi

  if [[ -f "${target_os_release}" ]]; then
    version_id="$(sed -n 's/^VERSION_ID=//p' "${target_os_release}" | head -n 1 | tr -d '"')"
    version_codename="$(sed -n 's/^VERSION_CODENAME=//p' "${target_os_release}" | head -n 1 | tr -d '"')"
    debian_version_full="$(sed -n 's/^DEBIAN_VERSION_FULL=//p' "${target_os_release}" | head -n 1 | tr -d '"')"
    original_name="$(sed -n 's/^NAME=//p' "${target_os_release}" | head -n 1 | tr -d '"')"
    original_pretty="$(sed -n 's/^PRETTY_NAME=//p' "${target_os_release}" | head -n 1 | tr -d '"')"
    original_id="$(sed -n 's/^ID=//p' "${target_os_release}" | head -n 1 | tr -d '"')"
  fi

  cat > "${target_os_release}" <<EOF
PRETTY_NAME="${pretty_name}"
NAME="${distro_name}"
VERSION_ID="${version_id}"
VERSION="${version_id}${version_codename:+ (${version_codename})}"
VERSION_CODENAME="${version_codename}"
ID="${distro_id}"
ID_LIKE="debian"
HOME_URL="${distro_home}"
SUPPORT_URL="${distro_support}"
BUG_REPORT_URL="${distro_bug}"
DEBIAN_VERSION_FULL="${debian_version_full}"
W4_BASE_PRETTY_NAME="${original_pretty}"
W4_BASE_NAME="${original_name}"
W4_BASE_ID="${original_id}"
W4_EDITION="${EDITION}"
W4_PROFILE_ID="${PROFILE_ID}"
W4_LIVE_HOSTNAME="${LIVE_HOSTNAME}"
EOF
}

trap cleanup EXIT

if [[ -z "${ROOTFS_DIR}" ]]; then
  echo "ERROR: debe indicar el rootfs de origen" >&2
  exit 1
fi

if [[ ! -d "${ROOTFS_DIR}" ]]; then
  echo "ERROR: no existe el rootfs de origen: ${ROOTFS_DIR}" >&2
  exit 1
fi

if [[ -z "${OUTPUT_DIR}" ]]; then
  OUTPUT_DIR="${SCRIPT_DIR}/output"
fi

if ! command -v rsync >/dev/null 2>&1; then
  echo "ERROR: rsync no esta disponible en este entorno Linux." >&2
  exit 1
fi

if ! command -v mksquashfs >/dev/null 2>&1; then
  echo "ERROR: mksquashfs no esta disponible en este entorno Linux." >&2
  exit 1
fi

echo "==> W4 OS System live composition"
echo "Profile: ${PROFILE_NAME}"
echo "Rootfs: ${ROOTFS_DIR}"
echo "Output final: ${OUTPUT_DIR}"
echo "Output stage: ${STAGE_OUTPUT_DIR}"
echo "Work rootfs: ${WORK_ROOTFS}"

rm -rf "${STAGE_OUTPUT_DIR}" "${WORK_ROOTFS}"
mkdir -p "${STAGE_OUTPUT_DIR}/image-root/live" "${STAGE_OUTPUT_DIR}/image-root/boot/grub" "${STAGE_OUTPUT_DIR}/image-root/.disk" "${STAGE_OUTPUT_DIR}/metadata"

echo "==> Preparando copia de trabajo del rootfs"
rsync -aHAX --delete "${ROOTFS_DIR}/" "${WORK_ROOTFS}/"

echo "==> Aplicando overlay de sistema en la copia de trabajo"
apply_system_overlay "${WORK_ROOTFS}"

echo "==> Asegurando keyring Debian dentro de la copia de trabajo"
ensure_debian_keyring_in_rootfs "${WORK_ROOTFS}"
normalize_debian_sources_keyring "${WORK_ROOTFS}"

if [[ "${PREPARE_LIVE_STACK}" == "1" ]]; then
  echo "==> Instalando pila live-boot/live-config en la copia de trabajo"
  mount --bind /dev "${WORK_ROOTFS}/dev"
  mount -t proc proc "${WORK_ROOTFS}/proc"
  mount -t sysfs sysfs "${WORK_ROOTFS}/sys"

  install_live_stack "${WORK_ROOTFS}"
fi

echo "==> Ajustando identidad live y estado de primer arranque"
prepare_live_identity "${WORK_ROOTFS}"
unmount_work_rootfs

KERNEL_SRC="$(find "${WORK_ROOTFS}/boot" -maxdepth 1 -type f -name 'vmlinuz-*' | sort | tail -n 1)"
INITRD_SRC="$(find "${WORK_ROOTFS}/boot" -maxdepth 1 -type f -name 'initrd.img-*' | sort | tail -n 1)"

if [[ -z "${KERNEL_SRC}" ]] || [[ -z "${INITRD_SRC}" ]]; then
  echo "ERROR: no se encontraron kernel/initrd en la copia de trabajo." >&2
  exit 1
fi

echo "==> Copiando estructura base de imagen live"
cp -a "${FILES_DIR}/." "${STAGE_OUTPUT_DIR}/image-root/"
cp "${KERNEL_SRC}" "${STAGE_OUTPUT_DIR}/image-root/live/vmlinuz"
cp "${INITRD_SRC}" "${STAGE_OUTPUT_DIR}/image-root/live/initrd"

echo "==> Generando manifest de paquetes"
chroot "${WORK_ROOTFS}" dpkg-query -W --showformat='${Package} ${Version}\n' > "${STAGE_OUTPUT_DIR}/image-root/live/filesystem.manifest"

echo "==> Calculando tamano del filesystem"
du -sx --block-size=1 "${WORK_ROOTFS}" | cut -f1 > "${STAGE_OUTPUT_DIR}/image-root/live/filesystem.size"

echo "==> Generando filesystem.squashfs"
mksquashfs "${WORK_ROOTFS}" "${STAGE_OUTPUT_DIR}/image-root/live/filesystem.squashfs" \
  -comp xz \
  -wildcards \
  -e boot/* var/cache/apt/archives/* var/lib/apt/lists/* tmp/* var/tmp/*

echo "==> Generando checksums"
( cd "${STAGE_OUTPUT_DIR}/image-root" && find . -type f -print0 | sort -z | xargs -0 sha256sum ) > "${STAGE_OUTPUT_DIR}/metadata/SHA256SUMS"

cat > "${STAGE_OUTPUT_DIR}/metadata/live-summary.env" <<EOF
W4_PROFILE_ID="${PROFILE_ID}"
W4_PROFILE_NAME="${PROFILE_NAME}"
W4_LIVE_USER="${LIVE_USER}"
W4_LIVE_HOSTNAME="${LIVE_HOSTNAME}"
W4_DEFAULT_TARGET="${W4_DEFAULT_TARGET:-%DEFAULT_TARGET%}"
W4_KERNEL_BASENAME="$(basename "${KERNEL_SRC}")"
W4_INITRD_BASENAME="$(basename "${INITRD_SRC}")"
W4_PREPARED_LIVE_STACK="${PREPARE_LIVE_STACK}"
W4_IMAGE_ROOT="${OUTPUT_DIR}/image-root"
W4_STAGE_IMAGE_ROOT="${STAGE_OUTPUT_DIR}/image-root"
W4_GENERATED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF

if command -v xorriso >/dev/null 2>&1 && command -v grub-mkstandalone >/dev/null 2>&1; then
  printf '%s\n' "ISO_TOOLING_AVAILABLE=1" >> "${STAGE_OUTPUT_DIR}/metadata/live-summary.env"
else
  printf '%s\n' "ISO_TOOLING_AVAILABLE=0" >> "${STAGE_OUTPUT_DIR}/metadata/live-summary.env"
fi

echo "==> Sincronizando artefactos finales al workspace"
rm -rf "${OUTPUT_DIR}/image-root" "${OUTPUT_DIR}/metadata"
mkdir -p "${OUTPUT_DIR}"
rsync -a --delete "${STAGE_OUTPUT_DIR}/" "${OUTPUT_DIR}/"

echo "==> Live bundle preparado"
echo "Resultado: ${OUTPUT_DIR}/image-root"
BASH;

    $script = str_replace(
        ['%PROFILE_ID%', '%PROFILE_NAME%', '%LIVE_USER%', '%LIVE_HOSTNAME%', '%DEFAULT_TARGET%', '%DIST_NAME%', '%EDITION%'],
        [$profileId, $profileName, $liveUser, $liveHostname, $vars['default_target'], $vars['distribution_name'], $vars['edition']],
        $script
    );

    return str_replace(["\r\n", "\r"], "\n", $script);
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $inputPath = null;
    $overlayManifestPath = null;
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

            case '--overlay-manifest':
                $overlayManifestPath = $value;
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

    $buildInput = readJsonFile($inputPath);
    validateBuildInput($buildInput, $inputPath);

    /** @var string $resolvedProfileId */
    $resolvedProfileId = $buildInput['profile_id'];
    if ($profileId !== null && $profileId !== $resolvedProfileId) {
        throw new ValidationError('El perfil indicado no coincide con el build input proporcionado');
    }

    if ($overlayManifestPath === null) {
        $overlayManifestPath = $defaultOverlayDir . DIRECTORY_SEPARATOR . $resolvedProfileId . DIRECTORY_SEPARATOR . 'overlay-manifest.json';
    }

    $overlayManifest = readJsonFile($overlayManifestPath);
    validateOverlayManifest($overlayManifest, $overlayManifestPath, $resolvedProfileId);

    if ($outputPath === null) {
        $outputPath = $defaultOutputDir . DIRECTORY_SEPARATOR . $resolvedProfileId;
    }

    if (!is_dir($outputPath) && !mkdir($outputPath, 0777, true) && !is_dir($outputPath)) {
        throw new ValidationError(sprintf('No se pudo crear la carpeta de salida: %s', $outputPath));
    }

    $vars = liveVariables($buildInput, $overlayManifest);
    $overlayDir = dirname($overlayManifestPath);
    $overlayFilesDir = $overlayDir . DIRECTORY_SEPARATOR . 'files';

    if (!is_dir($overlayFilesDir)) {
        throw new ValidationError(sprintf('No existe el payload de overlay: %s', $overlayFilesDir));
    }

    $files = [
        'files/.disk/info' => buildDiskInfo($vars),
        'files/README-LIVE.txt' => buildReadme($vars),
        'files/boot/grub/grub.cfg' => buildGrubCfg($vars),
        'compose-live.sh' => buildComposeScript($vars),
    ];

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

    $overlayPayloadOutputDir = $outputPath . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'system-overlay';
    removeDirectory($overlayPayloadOutputDir);
    copyDirectory($overlayFilesDir, $overlayPayloadOutputDir);

    $liveManifest = [
        'live_bundle_schema_version' => 1,
        'kind' => 'live-bundle',
        'profile_id' => $buildInput['profile_id'],
        'profile_name' => $buildInput['profile_name'],
        'base_manifest_id' => $buildInput['base_manifest_id'],
        'source_build_input' => basename($inputPath),
        'source_overlay_manifest' => basename($overlayManifestPath),
        'source_overlay_payload' => 'files/system-overlay',
        'branding' => $vars,
        'edition_policy' => $overlayManifest['edition_policy'] ?? [],
        'live_stack' => [
            'packages' => ['live-boot', 'live-config'],
            'bootloader_ready' => false,
            'iso_tooling_required' => ['xorriso', 'grub-mkstandalone'],
        ],
        'generated_files' => array_merge(array_keys($files), ['files/system-overlay/']),
        'next_steps' => [
            'componer image-root y filesystem.squashfs',
            'validar live-boot en VM',
            'instalar tooling de ISO si aplica',
            'empaquetar ISO arrancable',
        ],
    ];

    $manifestPath = $outputPath . DIRECTORY_SEPARATOR . 'live-manifest.json';
    if (file_put_contents($manifestPath, json_encode($liveManifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n") === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $manifestPath));
    }

    printJson([
        'status' => 'ok',
        'profile_id' => $resolvedProfileId,
        'output_directory' => $outputPath,
        'generated_files' => array_merge(['live-manifest.json'], array_keys($files), ['files/system-overlay/']),
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
