<?php

declare(strict_types=1);

use W4\OS\Support\ValidationError;
require_once __DIR__ . '/lib/InstallerToolkit.php';

$rootDir = dirname(__DIR__);
$defaultBundleRoot = $rootDir . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . 'install';

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

    if (!is_array($data)) {
        throw new ValidationError(sprintf('JSON invalido en %s: la raiz debe ser un objeto o arreglo JSON', $path));
    }

    return $data;
}

/**
 * @param array<string, mixed> $data
 */
function writeJsonFile(string $path, array $data): void
{
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        throw new ValidationError(sprintf('No se pudo serializar JSON para %s', $path));
    }

    if (file_put_contents($path, $json . PHP_EOL) === false) {
        throw new ValidationError(sprintf('No se pudo escribir el archivo %s', $path));
    }
}

function shellLiteral(string $value): string
{
    return "'" . str_replace("'", "'\"'\"'", $value) . "'";
}

function consoleKeymapForLayout(string $keyboardLayout): string
{
    return match ($keyboardLayout) {
        'latam' => 'la-latin1',
        'es' => 'es',
        'us' => 'us',
        'uk' => 'uk',
        'de' => 'de',
        'fr' => 'fr',
        'it' => 'it',
        'br' => 'br-abnt2',
        default => $keyboardLayout,
    };
}

function normalizeLf(string $content): string
{
    return str_replace(["\r\n", "\r"], "\n", $content);
}

/**
 * @param array<string, mixed> $plan
 */
function validateInstallationPlan(array $plan, string $sourcePath): void
{
    if (($plan['installation_plan_schema_version'] ?? null) !== 1) {
        throw new ValidationError(sprintf('%s: installation_plan_schema_version debe ser 1', basename($sourcePath)));
    }

    if (($plan['kind'] ?? null) !== 'installation-plan') {
        throw new ValidationError(sprintf('%s: kind debe ser installation-plan', basename($sourcePath)));
    }

    foreach (['profile_id', 'profile_name', 'base_manifest_id'] as $field) {
        $value = $plan[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new ValidationError(sprintf('%s: falta el campo %s', basename($sourcePath), $field));
        }
    }

    foreach (['plan_binding', 'identity', 'security', 'storage', 'execution'] as $field) {
        if (!is_array($plan[$field] ?? null)) {
            throw new ValidationError(sprintf('%s: %s debe ser un objeto', basename($sourcePath), $field));
        }
    }
}

/**
 * @param array<string, mixed> $bundleManifest
 */
function validateInstallationBundle(array $bundleManifest, string $sourcePath): void
{
    if (($bundleManifest['installation_bundle_schema_version'] ?? null) !== 1) {
        throw new ValidationError(sprintf('%s: installation_bundle_schema_version debe ser 1', basename($sourcePath)));
    }

    if (($bundleManifest['kind'] ?? null) !== 'installation-bundle') {
        throw new ValidationError(sprintf('%s: kind debe ser installation-bundle', basename($sourcePath)));
    }
}

/**
 * @param array<string, mixed> $plan
 * @return array{name:string,mountpoint:string}
 */
function rootSubvolume(array $plan): array
{
    $subvolumes = $plan['storage']['btrfs']['subvolumes'] ?? null;
    if (!is_array($subvolumes)) {
        throw new ValidationError('El plan no contiene storage.btrfs.subvolumes');
    }

    foreach ($subvolumes as $subvolume) {
        if (is_array($subvolume) && (($subvolume['mountpoint'] ?? null) === '/')) {
            return [
                'name' => (string) $subvolume['name'],
                'mountpoint' => '/',
            ];
        }
    }

    throw new ValidationError('El plan no contiene un subvolumen raiz con mountpoint "/"');
}

/**
 * @param array<string, mixed> $plan
 */
function buildApplyScript(array $plan): string
{
    $disk = $plan['plan_binding']['selected_disk'];
    $selector = $plan['plan_binding']['selector'];
    $identity = $plan['identity'];
    $user = $identity['user'];
    $storage = $plan['storage'];
    $encryption = $plan['security']['encryption'];
    $btrfs = $storage['btrfs'];
    $rootSubvolume = rootSubvolume($plan);

    $espSizeMib = (int) round(((int) $storage['partitions'][0]['size_bytes']) / 1048576);
    $bootSizeMib = (int) round(((int) $storage['partitions'][1]['size_bytes']) / 1048576);

    $subvolumeCreateLines = [];
    $subvolumeMountLines = [];
    $fstabLines = [];

    foreach ($btrfs['subvolumes'] as $subvolume) {
        if (!is_array($subvolume)) {
            continue;
        }

        $name = (string) ($subvolume['name'] ?? '');
        $mountpoint = (string) ($subvolume['mountpoint'] ?? '');
        if ($name === '' || $mountpoint === '') {
            continue;
        }

        $subvolumeCreateLines[] = sprintf('btrfs subvolume create "${STAGING_MOUNT}/%s"', $name);

        $fstabLines[] = sprintf(
            'UUID=${BTRFS_UUID} %s btrfs defaults,%s,subvol=%s 0 0',
            $mountpoint,
            implode(',', $btrfs['mount_options']),
            $name
        );

        if ($mountpoint === '/') {
            continue;
        }

        $targetDirectory = rtrim('${TARGET_ROOT}' . $mountpoint, '/');
        if ($targetDirectory === '') {
            $targetDirectory = '${TARGET_ROOT}';
        }

        $subvolumeMountLines[] = sprintf('mkdir -p "%s"', $targetDirectory);
        $subvolumeMountLines[] = sprintf(
            'mount -o %s,subvol=%s "/dev/mapper/${CRYPT_NAME}" "%s"',
            implode(',', $btrfs['mount_options']),
            $name,
            $targetDirectory
        );
    }

    $fstabBody = implode("\n", array_map(
        static fn (string $line): string => $line,
        array_merge(
            $fstabLines,
            [
                'UUID=${BOOT_UUID} /boot ext4 defaults 0 2',
                'UUID=${ESP_UUID} /boot/efi vfat umask=0077 0 1',
            ]
        )
    ));

    $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

# Algunas live sessions no incluyen rutas sbin en PATH.
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:${PATH:-}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLAN_JSON="${SCRIPT_DIR}/installation-plan.json"
PROFILE_NAME=%PROFILE_NAME%
TARGET_DISK=%TARGET_DISK%
EXPECTED_SERIAL=%EXPECTED_SERIAL%
EXPECTED_WWID=%EXPECTED_WWID%
EXPECTED_BY_PATH=%EXPECTED_BY_PATH%
EXPECTED_SIZE_BYTES=%EXPECTED_SIZE_BYTES%
SIZE_TOLERANCE_BYTES='1048576'
HOSTNAME_VALUE=%HOSTNAME%
LOCALE_VALUE=%LOCALE%
KEYBOARD_VALUE=%KEYBOARD%
CONSOLE_KEYMAP_VALUE=%CONSOLE_KEYMAP%
USERNAME_VALUE=%USERNAME%
DISPLAY_NAME_VALUE=%DISPLAY_NAME%
PASSWORD_SOURCE=%PASSWORD_SOURCE%
PASSPHRASE_SOURCE=%PASSPHRASE_SOURCE%
CRYPT_NAME=%CRYPT_NAME%
ROOT_LABEL=%ROOT_LABEL%
ESP_LABEL=%ESP_LABEL%
BOOT_LABEL=%BOOT_LABEL%
ROOT_SUBVOLUME=%ROOT_SUBVOLUME%
ESP_SIZE_MIB=%ESP_SIZE_MIB%
BOOT_SIZE_MIB=%BOOT_SIZE_MIB%
TARGET_ROOT="${W4_TARGET_ROOT:-/mnt/w4-install-target}"
STAGING_ROOT="${W4_STAGING_ROOT:-/mnt/w4-install-staging}"
STAGING_MOUNT="${W4_STAGING_MOUNT:-/mnt/w4-install-staging-subvol}"
EXECUTE_MODE="${W4_INSTALL_EXECUTE:-0}"
SOURCE_ROOTFS="${W4_INSTALL_SOURCE_ROOTFS:-}"
SOURCE_SQUASHFS="${W4_INSTALL_SOURCE_SQUASHFS:-}"
DISK_PASSPHRASE_FILE="${W4_DISK_PASSPHRASE_FILE:-}"
LOCAL_USER_PASSWORD_FILE="${W4_LOCAL_USER_PASSWORD_FILE:-}"

log() {
  echo "[w4-install] $*" >&2
}

warn() {
  echo "WARN: $*" >&2
}

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

require_command() {
  if ! command -v "${1}" >/dev/null 2>&1; then
    fail "falta el comando requerido: ${1}"
  fi
}

has_command() {
  command -v "${1}" >/dev/null 2>&1
}

part_path() {
  local disk="${1}"
  local number="${2}"

  if [[ "${disk}" == *"nvme"* || "${disk}" == *"mmcblk"* || "${disk}" == *"loop"* ]]; then
    printf '%sp%s' "${disk}" "${number}"
    return 0
  fi

  printf '%s%s' "${disk}" "${number}"
}

udev_value() {
  local device="${1}"
  local key="${2}"

  udevadm info --query=property --name="${device}" | awk -F= -v key="${key}" '$1 == key { print $2; exit }'
}

assert_selector() {
  local device="${1}"
  local current_serial current_serial_short current_serial_full current_wwid current_by_path current_size size_delta

  current_serial_short="$(udev_value "${device}" "ID_SERIAL_SHORT")"
  current_serial_full="$(udev_value "${device}" "ID_SERIAL")"
  current_serial="${current_serial_short}"
  if [[ -z "${current_serial}" ]]; then
    current_serial="${current_serial_full}"
  fi

  current_wwid="$(udev_value "${device}" "ID_WWN")"
  if [[ -z "${current_wwid}" ]]; then
    current_wwid="$(udev_value "${device}" "ID_WWN_WITH_EXTENSION")"
  fi

  current_by_path="$(udev_value "${device}" "ID_PATH")"
  current_size="$(lsblk -bndo SIZE "${device}" | tr -d '[:space:]')"

  if [[ -n "${EXPECTED_SERIAL}" \
    && "${EXPECTED_SERIAL}" != "${current_serial}" \
    && "${EXPECTED_SERIAL}" != "${current_serial_short}" \
    && "${EXPECTED_SERIAL}" != "${current_serial_full}" ]]; then
    fail "el disco ya no coincide con el serial esperado"
  fi

  if [[ -n "${EXPECTED_WWID}" && "${EXPECTED_WWID}" != "${current_wwid}" ]]; then
    fail "el disco ya no coincide con el WWID esperado"
  fi

  if [[ -n "${EXPECTED_BY_PATH}" && "${EXPECTED_BY_PATH}" != "${current_by_path}" ]]; then
    fail "el disco ya no coincide con el path esperado"
  fi

  if [[ -z "${current_size}" || ! "${current_size}" =~ ^[0-9]+$ ]]; then
    fail "no se pudo determinar el tamaño actual del disco"
  fi

  size_delta=$(( EXPECTED_SIZE_BYTES - current_size ))
  if (( size_delta < 0 )); then
    size_delta=$(( -size_delta ))
  fi

  if (( size_delta > SIZE_TOLERANCE_BYTES )); then
    fail "el disco ya no coincide con el tamaño esperado"
  fi
}

disk_has_signatures() {
  local device="${1}"
  local fstype

  if has_command wipefs; then
    if wipefs -n "${device}" 2>/dev/null | tail -n +2 | grep -q .; then
      return 0
    fi

    return 1
  fi

  if has_command blkid; then
    if blkid "${device}" >/dev/null 2>&1; then
      return 0
    fi

    return 1
  fi

  fstype="$(lsblk -dn -o FSTYPE "${device}" 2>/dev/null | tr -d '[:space:]')"
  if [[ -n "${fstype}" ]]; then
    return 0
  fi

  return 1
}

assert_empty_target() {
  local device="${1}"
  local ro

  ro="$(lsblk -dn -o RO "${device}")"
  if [[ "${ro}" == "1" ]]; then
    fail "el disco seleccionado es de solo lectura"
  fi

  if lsblk -nr -o NAME "${device}" | tail -n +2 | grep -q .; then
    fail "el disco seleccionado ya contiene particiones"
  fi

  if disk_has_signatures "${device}"; then
    fail "el disco seleccionado contiene firmas de filesystem"
  fi

  if [[ "${EXECUTE_MODE}" != "1" ]] && ! has_command wipefs && ! has_command blkid; then
    warn "sin wipefs ni blkid; la validacion check-only se apoyo en lsblk/FSTYPE y ausencia de particiones"
  fi
}

cleanup() {
  set +e

  for path in \
    "${TARGET_ROOT}/boot/efi" \
    "${TARGET_ROOT}/boot" \
    "${TARGET_ROOT}/home" \
    "${TARGET_ROOT}/var/log" \
    "${TARGET_ROOT}/var/cache" \
    "${TARGET_ROOT}/var/lib/w4" \
    "${TARGET_ROOT}/sys/firmware/efi/efivars" \
    "${TARGET_ROOT}/sys" \
    "${TARGET_ROOT}/proc" \
    "${TARGET_ROOT}/dev/pts" \
    "${TARGET_ROOT}/dev"; do
    if mountpoint -q "${path}"; then
      umount "${path}"
    fi
  done

  if mountpoint -q "${TARGET_ROOT}"; then
    umount "${TARGET_ROOT}"
  fi

  if mountpoint -q "${STAGING_MOUNT}"; then
    umount "${STAGING_MOUNT}"
  fi

  if [[ -e "/dev/mapper/${CRYPT_NAME}" ]]; then
    cryptsetup close "${CRYPT_NAME}" >/dev/null 2>&1 || true
  fi
}

prepare_source_root() {
  if [[ -n "${SOURCE_ROOTFS}" ]]; then
    [[ -d "${SOURCE_ROOTFS}" ]] || fail "W4_INSTALL_SOURCE_ROOTFS no apunta a una carpeta valida"
    if [[ ! -f "${SOURCE_ROOTFS}/var/lib/dpkg/status" ]]; then
      warn "W4_INSTALL_SOURCE_ROOTFS no incluye var/lib/dpkg; se intentara restaurar el estado de paquetes desde W4_INSTALL_SOURCE_SQUASHFS"
    fi
    printf '%s' "${SOURCE_ROOTFS}"
    return 0
  fi

  [[ -n "${SOURCE_SQUASHFS}" ]] || fail "debe indicar W4_INSTALL_SOURCE_ROOTFS o W4_INSTALL_SOURCE_SQUASHFS"
  [[ -f "${SOURCE_SQUASHFS}" ]] || fail "W4_INSTALL_SOURCE_SQUASHFS no existe"

  require_command unsquashfs
  rm -rf "${STAGING_ROOT}"
  mkdir -p "${STAGING_ROOT}"
  log "Extrayendo squashfs fuente"
  unsquashfs -f -d "${STAGING_ROOT}" "${SOURCE_SQUASHFS}" >/dev/null
  printf '%s' "${STAGING_ROOT}"
}

source_has_package_state() {
  local source_root="${1}"
  [[ -f "${source_root}/var/lib/dpkg/status" ]]
}

restore_target_package_state_from_source_root() {
  local source_root="${1}"

  log "Repoblando estado de paquetes desde el arbol fuente"
  mkdir -p "${TARGET_ROOT}/var/lib"

  if [[ -d "${source_root}/var/lib/apt" ]]; then
    rsync -aHAX --numeric-ids "${source_root}/var/lib/apt/" "${TARGET_ROOT}/var/lib/apt/"
  fi

  rsync -aHAX --numeric-ids "${source_root}/var/lib/dpkg/" "${TARGET_ROOT}/var/lib/dpkg/"
}

restore_target_package_state_from_squashfs() {
  [[ -n "${SOURCE_SQUASHFS}" ]] || fail "la fuente seleccionada no contiene var/lib/dpkg y no se indico W4_INSTALL_SOURCE_SQUASHFS para restaurarlo"
  [[ -f "${SOURCE_SQUASHFS}" ]] || fail "W4_INSTALL_SOURCE_SQUASHFS no existe"

  require_command unsquashfs
  log "Restaurando estado de paquetes desde squashfs fuente"
  mkdir -p "${TARGET_ROOT}/var/lib"
  unsquashfs -f -d "${TARGET_ROOT}" "${SOURCE_SQUASHFS}" var/lib/apt var/lib/dpkg >/dev/null
}

ensure_target_package_state() {
  if [[ -f "${TARGET_ROOT}/var/lib/dpkg/status" ]]; then
    return 0
  fi

  if source_has_package_state "${SOURCE_ROOT}"; then
    restore_target_package_state_from_source_root "${SOURCE_ROOT}"
  else
    restore_target_package_state_from_squashfs
  fi

  [[ -f "${TARGET_ROOT}/var/lib/dpkg/status" ]] || fail "la instalacion no dejo un estado dpkg utilizable en ${TARGET_ROOT}/var/lib/dpkg"
}

mount_chroot_support() {
  mkdir -p "${TARGET_ROOT}/dev/pts" "${TARGET_ROOT}/run" "${TARGET_ROOT}/run/lock"
  mount --bind /dev "${TARGET_ROOT}/dev"
  mount --bind /dev/pts "${TARGET_ROOT}/dev/pts"
  mount --bind /proc "${TARGET_ROOT}/proc"
  mount --bind /sys "${TARGET_ROOT}/sys"

  if [[ -d /sys/firmware/efi/efivars ]]; then
    mkdir -p "${TARGET_ROOT}/sys/firmware/efi/efivars"
    mount --bind /sys/firmware/efi/efivars "${TARGET_ROOT}/sys/firmware/efi/efivars"
  fi
}

chroot_has_command() {
  local command_name="${1}"
  chroot "${TARGET_ROOT}" /bin/bash -lc "command -v '${command_name}' >/dev/null 2>&1"
}

ensure_kernel_boot_artifacts() {
  local preferred_kernel_package=""
  local kernel_package_names=()
  local kernel_versions=()
  local kernel_version=""

  if compgen -G "${TARGET_ROOT}/boot/vmlinuz-*" >/dev/null 2>&1 \
    && compgen -G "${TARGET_ROOT}/boot/initrd.img-*" >/dev/null 2>&1; then
    return 0
  fi

  log "No se encontraron artefactos de kernel en /boot; reinstalando paquetes linux-image"
  chroot_has_command apt-get || fail "faltan artefactos de kernel en /boot y apt-get no existe en el sistema destino"
  chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get update || true
  log "Asegurando soporte initramfs para cryptroot"
  chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get install -y cryptsetup-initramfs

  if chroot "${TARGET_ROOT}" /bin/bash -lc "dpkg-query -W -f='\${db:Status-Abbrev} \${Package}\n' linux-image-amd64 2>/dev/null | grep '^ii ' >/dev/null 2>&1"; then
    preferred_kernel_package="linux-image-amd64"
  fi

  mapfile -t kernel_package_names < <(
    chroot "${TARGET_ROOT}" /bin/bash -lc "dpkg-query -W -f='\${db:Status-Abbrev} \${Package}\n' 'linux-image-[0-9]*' 2>/dev/null | awk '\$1 == \"ii\" { print \$2 }' | grep -v -- '-unsigned$' || true"
  )

  if [[ "${#kernel_package_names[@]}" -eq 0 ]]; then
    mapfile -t kernel_package_names < <(
      chroot "${TARGET_ROOT}" /bin/bash -lc "dpkg-query -W -f='\${db:Status-Abbrev} \${Package}\n' 'linux-image-[0-9]*' 2>/dev/null | awk '\$1 == \"ii\" { print \$2 }' || true"
    )
  fi

  if [[ -n "${preferred_kernel_package}" ]]; then
    log "Reinstalando metapaquete ${preferred_kernel_package}"
    chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get install -y --reinstall "${preferred_kernel_package}"
  fi

  if ! compgen -G "${TARGET_ROOT}/boot/vmlinuz-*" >/dev/null 2>&1; then
    if [[ "${#kernel_package_names[@]}" -eq 0 ]]; then
      fail "faltan artefactos de kernel en /boot y no se encontraron paquetes linux-image instalados"
    fi

    log "Reinstalando paquetes kernel especificos: ${kernel_package_names[*]}"
    chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get install -y --reinstall "${kernel_package_names[@]}"
  fi

  if ! compgen -G "${TARGET_ROOT}/boot/vmlinuz-*" >/dev/null 2>&1; then
    fail "la reinstalacion del kernel no genero vmlinuz en /boot"
  fi

  if ! compgen -G "${TARGET_ROOT}/boot/initrd.img-*" >/dev/null 2>&1; then
    chroot_has_command mkinitramfs || fail "la reinstalacion del kernel no genero initrd.img en /boot y mkinitramfs no existe en el sistema destino"
    mapfile -t kernel_versions < <(
      find "${TARGET_ROOT}/boot" -maxdepth 1 -type f -name 'vmlinuz-*' -printf '%f\n' | sed 's/^vmlinuz-//' | sort
    )

    if [[ "${#kernel_versions[@]}" -eq 0 ]]; then
      fail "no se encontraron versiones de kernel en /boot para regenerar initrd"
    fi

    for kernel_version in "${kernel_versions[@]}"; do
      if [[ ! -e "${TARGET_ROOT}/boot/initrd.img-${kernel_version}" ]]; then
        log "Generando initrd manual para ${kernel_version}"
        chroot "${TARGET_ROOT}" mkinitramfs -o "/boot/initrd.img-${kernel_version}" "${kernel_version}"
      fi
    done
  fi

  if ! compgen -G "${TARGET_ROOT}/boot/initrd.img-*" >/dev/null 2>&1; then
    fail "la reinstalacion del kernel no genero initrd.img en /boot"
  fi
}

ensure_directories() {
  mkdir -p \
    "${TARGET_ROOT}" \
    "${TARGET_ROOT}/boot" \
    "${TARGET_ROOT}/boot/efi" \
    "${TARGET_ROOT}/home" \
    "${TARGET_ROOT}/var/log" \
    "${TARGET_ROOT}/var/cache" \
    "${TARGET_ROOT}/var/lib/w4"
}

print_plan() {
  cat <<EOF
W4 OS Executor

Perfil: ${PROFILE_NAME}
Disco destino: ${TARGET_DISK}
Modo por defecto: check-only
Para ejecutar de verdad:
  export W4_INSTALL_EXECUTE=1
  export W4_INSTALL_SOURCE_ROOTFS=/ruta/rootfs   o W4_INSTALL_SOURCE_SQUASHFS=/ruta/filesystem.squashfs
  export W4_DISK_PASSPHRASE_FILE=/ruta/passphrase.txt
  export W4_LOCAL_USER_PASSWORD_FILE=/ruta/password.txt
  bash "${0}"
EOF
}

for cmd in lsblk udevadm awk grep sed tr tail; do
  require_command "${cmd}"
done

[[ -b "${TARGET_DISK}" ]] || fail "el disco objetivo no existe: ${TARGET_DISK}"

assert_selector "${TARGET_DISK}"
assert_empty_target "${TARGET_DISK}"

if [[ "${EXECUTE_MODE}" != "1" ]]; then
  print_plan
  exit 0
fi

for cmd in sgdisk partprobe blkid mkfs.vfat mkfs.ext4 cryptsetup mkfs.btrfs rsync mount umount chroot awk grep sed; do
  require_command "${cmd}"
done

[[ -n "${DISK_PASSPHRASE_FILE}" && -f "${DISK_PASSPHRASE_FILE}" ]] || fail "debe indicar W4_DISK_PASSPHRASE_FILE"
[[ -n "${LOCAL_USER_PASSWORD_FILE}" && -f "${LOCAL_USER_PASSWORD_FILE}" ]] || fail "debe indicar W4_LOCAL_USER_PASSWORD_FILE"

SOURCE_ROOT="$(prepare_source_root)"
trap cleanup EXIT

ESP_PART="$(part_path "${TARGET_DISK}" 1)"
BOOT_PART="$(part_path "${TARGET_DISK}" 2)"
ROOT_PART="$(part_path "${TARGET_DISK}" 3)"

log "Aplicando esquema GPT en ${TARGET_DISK}"
sgdisk --zap-all "${TARGET_DISK}"
if has_command wipefs; then
  wipefs -af "${TARGET_DISK}"
else
  warn "wipefs no esta disponible; se continua con sgdisk y recreacion completa del layout"
fi
sgdisk -og "${TARGET_DISK}"
sgdisk -n 1:1MiB:+"${ESP_SIZE_MIB}"MiB -t 1:ef00 -c 1:"${ESP_LABEL}" "${TARGET_DISK}"
sgdisk -n 2:0:+"${BOOT_SIZE_MIB}"MiB -t 2:8300 -c 2:"${BOOT_LABEL}" "${TARGET_DISK}"
sgdisk -n 3:0:0 -t 3:8309 -c 3:"W4-CRYPTROOT" "${TARGET_DISK}"
partprobe "${TARGET_DISK}"
udevadm settle
sleep 1

log "Formateando particiones"
mkfs.vfat -F 32 -n "${ESP_LABEL}" "${ESP_PART}"
mkfs.ext4 -F -L "${BOOT_LABEL}" "${BOOT_PART}"
cryptsetup luksFormat --batch-mode --type luks2 "${ROOT_PART}" "${DISK_PASSPHRASE_FILE}"
cryptsetup open "${ROOT_PART}" "${CRYPT_NAME}" --key-file "${DISK_PASSPHRASE_FILE}"
mkfs.btrfs -f -L "${ROOT_LABEL}" "/dev/mapper/${CRYPT_NAME}"

log "Creando subvolumenes Btrfs"
mkdir -p "${STAGING_MOUNT}"
mount "/dev/mapper/${CRYPT_NAME}" "${STAGING_MOUNT}"
%SUBVOLUME_CREATE_LINES%
umount "${STAGING_MOUNT}"

log "Montando layout destino"
ensure_directories
mount -o %ROOT_MOUNT_OPTIONS%,subvol="${ROOT_SUBVOLUME}" "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}"
%SUBVOLUME_MOUNT_LINES%
mkdir -p "${TARGET_ROOT}/boot"
mount "${BOOT_PART}" "${TARGET_ROOT}/boot"
mkdir -p "${TARGET_ROOT}/boot/efi"
mount "${ESP_PART}" "${TARGET_ROOT}/boot/efi"

log "Sincronizando sistema fuente"
rsync -aHAX --numeric-ids \
  --exclude=/dev/* \
  --exclude=/proc/* \
  --exclude=/sys/* \
  --exclude=/run/* \
  --exclude=/tmp/* \
  --exclude=/mnt/* \
  --exclude=/media/* \
  --exclude=lost+found \
  "${SOURCE_ROOT}/" "${TARGET_ROOT}/"

ensure_target_package_state

echo "${HOSTNAME_VALUE}" > "${TARGET_ROOT}/etc/hostname"
cat > "${TARGET_ROOT}/etc/hosts" <<EOF
127.0.0.1 localhost
127.0.1.1 ${HOSTNAME_VALUE}
EOF

mkdir -p "${TARGET_ROOT}/etc/default"
cat > "${TARGET_ROOT}/etc/default/keyboard" <<EOF
XKBMODEL="pc105"
XKBLAYOUT="${KEYBOARD_VALUE}"
XKBVARIANT=""
XKBOPTIONS=""
BACKSPACE="guess"
EOF
echo "LANG=${LOCALE_VALUE}" > "${TARGET_ROOT}/etc/default/locale"
cat > "${TARGET_ROOT}/etc/vconsole.conf" <<EOF
KEYMAP=${CONSOLE_KEYMAP_VALUE}
FONT=latarcyrheb-sun16
EOF
mkdir -p "${TARGET_ROOT}/etc/initramfs-tools/conf.d"
cat > "${TARGET_ROOT}/etc/initramfs-tools/conf.d/w4-keyboard" <<EOF
KEYMAP=${CONSOLE_KEYMAP_VALUE}
XKBLAYOUT=${KEYBOARD_VALUE}
EOF

if [[ -e /etc/resolv.conf ]]; then
  cp -L /etc/resolv.conf "${TARGET_ROOT}/etc/resolv.conf"
fi

ESP_UUID="$(blkid -s UUID -o value "${ESP_PART}")"
BOOT_UUID="$(blkid -s UUID -o value "${BOOT_PART}")"
ROOT_UUID="$(blkid -s UUID -o value "${ROOT_PART}")"
BTRFS_UUID="$(blkid -s UUID -o value "/dev/mapper/${CRYPT_NAME}")"

cat > "${TARGET_ROOT}/etc/crypttab" <<EOF
${CRYPT_NAME} UUID=${ROOT_UUID} none luks,discard
EOF

cat > "${TARGET_ROOT}/etc/fstab" <<EOF
%FSTAB_BODY%
EOF

if [[ -f "${TARGET_ROOT}/etc/locale.gen" ]]; then
  sed -i "s/^# *${LOCALE_VALUE} UTF-8/${LOCALE_VALUE} UTF-8/" "${TARGET_ROOT}/etc/locale.gen" || true
fi

mkdir -p "${TARGET_ROOT}/etc/systemd/system/multi-user.target.wants"
if [[ -f "${TARGET_ROOT}/lib/systemd/system/w4-firstboot.service" ]]; then
  ln -sf /lib/systemd/system/w4-firstboot.service "${TARGET_ROOT}/etc/systemd/system/multi-user.target.wants/w4-firstboot.service"
fi

if [[ -f "${TARGET_ROOT}/lib/systemd/system/w4-live-prep.service" ]]; then
  rm -f "${TARGET_ROOT}/etc/systemd/system/multi-user.target.wants/w4-live-prep.service"
fi

mount_chroot_support
ensure_kernel_boot_artifacts

if ! chroot "${TARGET_ROOT}" id -u "${USERNAME_VALUE}" >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" useradd -m -s /bin/bash -c "${DISPLAY_NAME_VALUE}" "${USERNAME_VALUE}"
fi

if chroot "${TARGET_ROOT}" getent group sudo >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" usermod -aG sudo "${USERNAME_VALUE}"
fi

USER_HOME="$(chroot "${TARGET_ROOT}" getent passwd "${USERNAME_VALUE}" | cut -d: -f6 || true)"
if [[ -n "${USER_HOME}" ]] && [[ "${USER_HOME}" == /* ]] && [[ "${USER_HOME}" != "/" ]]; then
  mkdir -p "${TARGET_ROOT}${USER_HOME}"
  if [[ -d "${TARGET_ROOT}/etc/skel" ]]; then
    cp -an "${TARGET_ROOT}/etc/skel/." "${TARGET_ROOT}${USER_HOME}/" 2>/dev/null || true
  fi
  chroot "${TARGET_ROOT}" chown -R "${USERNAME_VALUE}:${USERNAME_VALUE}" "${USER_HOME}"
fi

LOCAL_USER_PASSWORD="$(cat "${LOCAL_USER_PASSWORD_FILE}")"
printf '%s:%s\n' "${USERNAME_VALUE}" "${LOCAL_USER_PASSWORD}" | chroot "${TARGET_ROOT}" chpasswd

if chroot_has_command locale-gen; then
  chroot "${TARGET_ROOT}" locale-gen || true
fi

if chroot_has_command setupcon; then
  chroot "${TARGET_ROOT}" setupcon --save-only || warn "setupcon devolvio un error; se conserva la configuracion escrita en /etc/default/keyboard"
fi

if ! chroot_has_command grub-install; then
  log "grub-install no esta disponible; instalando paquetes EFI requeridos"
  chroot_has_command apt-get || fail "grub-install no esta disponible y apt-get tampoco existe en el sistema destino"
  chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get update
  chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get install -y grub-efi-amd64 grub-efi-amd64-bin grub2-common shim-signed efibootmgr
fi

chroot_has_command grub-install || fail "grub-install sigue sin estar disponible en el sistema destino"
log "Instalando GRUB EFI"
chroot "${TARGET_ROOT}" grub-install --target=x86_64-efi --efi-directory=/boot/efi --bootloader-id="W4 OS" --recheck
log "Instalando ruta UEFI de fallback"
chroot "${TARGET_ROOT}" grub-install --target=x86_64-efi --efi-directory=/boot/efi --removable --recheck

[[ -e "${TARGET_ROOT}/boot/efi/EFI/BOOT/BOOTX64.EFI" ]] || fail "no se genero la ruta UEFI de fallback BOOTX64.EFI"

if chroot_has_command update-initramfs; then
  chroot "${TARGET_ROOT}" update-initramfs -u -k all || warn "update-initramfs devolvio un error; se conserva el initrd ya generado en /boot"
fi

if chroot_has_command update-grub; then
  chroot "${TARGET_ROOT}" update-grub
fi

log "Instalacion preparada. Ejecute verify-installation.sh antes de reiniciar."
BASH;

    return str_replace(
        [
            '%PROFILE_NAME%',
            '%TARGET_DISK%',
            '%EXPECTED_SERIAL%',
            '%EXPECTED_WWID%',
            '%EXPECTED_BY_PATH%',
            '%EXPECTED_SIZE_BYTES%',
            '%HOSTNAME%',
            '%LOCALE%',
            '%KEYBOARD%',
            '%CONSOLE_KEYMAP%',
            '%USERNAME%',
            '%DISPLAY_NAME%',
            '%PASSWORD_SOURCE%',
            '%PASSPHRASE_SOURCE%',
            '%CRYPT_NAME%',
            '%ROOT_LABEL%',
            '%ESP_LABEL%',
            '%BOOT_LABEL%',
            '%ROOT_SUBVOLUME%',
            '%ESP_SIZE_MIB%',
            '%BOOT_SIZE_MIB%',
            '%SUBVOLUME_CREATE_LINES%',
            '%ROOT_MOUNT_OPTIONS%',
            '%SUBVOLUME_MOUNT_LINES%',
            '%FSTAB_BODY%',
        ],
        [
            shellLiteral((string) $plan['profile_name']),
            shellLiteral((string) $disk['device']),
            shellLiteral((string) ($selector['serial'] ?? '')),
            shellLiteral((string) ($selector['wwid'] ?? '')),
            shellLiteral((string) ($selector['by_path'] ?? '')),
            shellLiteral((string) $disk['size_bytes']),
            shellLiteral((string) $identity['hostname']),
            shellLiteral((string) $identity['locale']),
            shellLiteral((string) $identity['keyboard']),
            shellLiteral(consoleKeymapForLayout((string) $identity['keyboard'])),
            shellLiteral((string) $user['username']),
            shellLiteral((string) $user['display_name']),
            shellLiteral((string) $user['password_source']),
            shellLiteral((string) $encryption['passphrase_source']),
            shellLiteral((string) $storage['encryption']['mapping_name']),
            shellLiteral((string) $btrfs['label']),
            shellLiteral((string) $storage['partitions'][0]['label']),
            shellLiteral((string) $storage['partitions'][1]['label']),
            shellLiteral($rootSubvolume['name']),
            shellLiteral((string) $espSizeMib),
            shellLiteral((string) $bootSizeMib),
            implode("\n", $subvolumeCreateLines),
            implode(',', $btrfs['mount_options']),
            implode("\n", $subvolumeMountLines),
            $fstabBody,
        ],
        $script
    ) . "\n";
}

/**
 * @param array<string, mixed> $plan
 */
function buildVerificationScript(array $plan): string
{
    $disk = $plan['plan_binding']['selected_disk'];
    $user = $plan['identity']['user'];
    $identity = $plan['identity'];
    $rootSubvolume = rootSubvolume($plan);

    $script = <<<'BASH'
#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
TARGET_ROOT="${W4_TARGET_ROOT:-/mnt/w4-install-target}"
TARGET_DISK=%TARGET_DISK%
EXPECTED_HOSTNAME=%HOSTNAME%
EXPECTED_USER=%USERNAME%
CRYPT_NAME=%CRYPT_NAME%
ROOT_SUBVOLUME=%ROOT_SUBVOLUME%
PASSPHRASE_FILE="${W4_DISK_PASSPHRASE_FILE:-${SCRIPT_DIR}/runtime/disk-passphrase.txt}"
OPENED_CRYPTROOT=0
MOUNTED_TARGET=0

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

part_path() {
  local disk="${1}"
  local number="${2}"

  if [[ "${disk}" == *"nvme"* || "${disk}" == *"mmcblk"* || "${disk}" == *"loop"* ]]; then
    printf '%sp%s' "${disk}" "${number}"
    return 0
  fi

  printf '%s%s' "${disk}" "${number}"
}

ESP_PART="$(part_path "${TARGET_DISK}" 1)"
BOOT_PART="$(part_path "${TARGET_DISK}" 2)"
ROOT_PART="$(part_path "${TARGET_DISK}" 3)"

cleanup() {
  if [[ "${MOUNTED_TARGET}" == "1" ]]; then
    for path in \
      "${TARGET_ROOT}/boot/efi" \
      "${TARGET_ROOT}/boot" \
      "${TARGET_ROOT}"; do
      if mountpoint -q "${path}"; then
        umount "${path}"
      fi
    done
  fi

  if [[ "${OPENED_CRYPTROOT}" == "1" ]] && [[ -e "/dev/mapper/${CRYPT_NAME}" ]]; then
    cryptsetup close "${CRYPT_NAME}" || true
  fi
}

trap cleanup EXIT

mount_target_if_needed() {
  if mountpoint -q "${TARGET_ROOT}"; then
    return 0
  fi

  [[ -b "${ESP_PART}" ]] || fail "falta la particion ESP"
  [[ -b "${BOOT_PART}" ]] || fail "falta la particion /boot"
  [[ -b "${ROOT_PART}" ]] || fail "falta la particion cifrada"
  command -v cryptsetup >/dev/null 2>&1 || fail "cryptsetup no esta disponible para verificar el target desmontado"
  [[ -f "${PASSPHRASE_FILE}" ]] || fail "falta el archivo de passphrase para verificar el target desmontado: ${PASSPHRASE_FILE}"

  mkdir -p "${TARGET_ROOT}/boot/efi"

  if [[ ! -e "/dev/mapper/${CRYPT_NAME}" ]]; then
    cryptsetup open "${ROOT_PART}" "${CRYPT_NAME}" --key-file "${PASSPHRASE_FILE}"
    OPENED_CRYPTROOT=1
  fi

  mount -o subvol="${ROOT_SUBVOLUME}" "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}"
  mount "${BOOT_PART}" "${TARGET_ROOT}/boot"
  mount "${ESP_PART}" "${TARGET_ROOT}/boot/efi"
  MOUNTED_TARGET=1
}

mount_target_if_needed

[[ -d "${TARGET_ROOT}" ]] || fail "no existe el rootfs montado en ${TARGET_ROOT}"
[[ -b "${ESP_PART}" ]] || fail "falta la particion ESP"
[[ -b "${BOOT_PART}" ]] || fail "falta la particion /boot"
[[ -b "${ROOT_PART}" ]] || fail "falta la particion cifrada"
[[ -f "${TARGET_ROOT}/etc/fstab" ]] || fail "falta /etc/fstab"
[[ -f "${TARGET_ROOT}/etc/crypttab" ]] || fail "falta /etc/crypttab"
grep -q "${CRYPT_NAME}" "${TARGET_ROOT}/etc/crypttab" || fail "crypttab no referencia ${CRYPT_NAME}"
grep -q "${EXPECTED_HOSTNAME}" "${TARGET_ROOT}/etc/hostname" || fail "hostname no coincide"
grep -q "^${EXPECTED_USER}:" "${TARGET_ROOT}/etc/passwd" || fail "el usuario esperado no existe"
test -d "${TARGET_ROOT}/home" || fail "falta /home en el target"
test -d "${TARGET_ROOT}/boot/efi" || fail "falta /boot/efi en el target"
test -e "${TARGET_ROOT}/boot" || fail "falta /boot en el target"
test -f "${TARGET_ROOT}/boot/grub/grub.cfg" || fail "falta /boot/grub/grub.cfg"
test -e "${TARGET_ROOT}/boot/efi/EFI/BOOT/BOOTX64.EFI" || fail "falta la ruta UEFI de fallback BOOTX64.EFI"

echo "Verificacion local completada para ${TARGET_ROOT}"
BASH;

    return str_replace(
        ['%TARGET_DISK%', '%HOSTNAME%', '%USERNAME%', '%CRYPT_NAME%', '%ROOT_SUBVOLUME%'],
        [
            shellLiteral((string) $disk['device']),
            shellLiteral((string) $identity['hostname']),
            shellLiteral((string) $user['username']),
            shellLiteral((string) $plan['storage']['encryption']['mapping_name']),
            shellLiteral((string) $rootSubvolume['name']),
        ],
        $script
    ) . "\n";
}

/**
 * @param array<string, mixed> $plan
 */
function buildExecutorReadme(array $plan): string
{
    return str_replace(["\r\n", "\r"], "\n", sprintf(
        "W4 OS Installation Executor\n\n".
        "Perfil: %s\n".
        "Disco objetivo: %s\n".
        "Modo por defecto: verificacion sin escritura\n\n".
        "Archivos generados:\n".
        "- apply-installation.sh\n".
        "- verify-installation.sh\n".
        "- installation-executor.json\n\n".
        "Variables requeridas para ejecutar de verdad:\n".
        "- W4_INSTALL_EXECUTE=1\n".
        "- W4_INSTALL_SOURCE_ROOTFS=/ruta/rootfs   o   W4_INSTALL_SOURCE_SQUASHFS=/ruta/filesystem.squashfs\n".
        "- W4_DISK_PASSPHRASE_FILE=/ruta/passphrase.txt\n".
        "- W4_LOCAL_USER_PASSWORD_FILE=/ruta/password.txt\n\n".
        "El script revalida el disco antes de escribir, rechaza particiones o firmas existentes y esta pensado para disco vacio en una sesion live.\n",
        $plan['profile_name'],
        $plan['plan_binding']['selected_disk']['device']
    ));
}

/**
 * @param array<string, mixed> $plan
 * @return array<string, mixed>
 */
function createExecutorManifest(array $plan): array
{
    return [
        'installation_executor_schema_version' => 1,
        'kind' => 'installation-executor',
        'profile_id' => $plan['profile_id'],
        'selected_disk' => $plan['plan_binding']['selected_disk']['device'],
        'default_mode' => 'check-only',
        'required_env' => [
            'W4_INSTALL_EXECUTE',
            'W4_INSTALL_SOURCE_ROOTFS or W4_INSTALL_SOURCE_SQUASHFS',
            'W4_DISK_PASSPHRASE_FILE',
            'W4_LOCAL_USER_PASSWORD_FILE',
        ],
        'required_commands' => [
            'lsblk',
            'udevadm',
            'wipefs',
            'sgdisk',
            'partprobe',
            'mkfs.vfat',
            'mkfs.ext4',
            'cryptsetup',
            'mkfs.btrfs',
            'rsync',
            'chroot',
        ],
        'generated_scripts' => [
            'apply-installation.sh',
            'verify-installation.sh',
        ],
    ];
}

/**
 * @param array<string, mixed> $bundleManifest
 * @return array<string, mixed>
 */
function mergeExecutorArtifacts(array $bundleManifest): array
{
    $generatedArtifacts = $bundleManifest['generated_artifacts'] ?? [];
    if (!is_array($generatedArtifacts)) {
        $generatedArtifacts = [];
    }

    $generatedArtifacts = array_values(array_unique(array_merge(
        $generatedArtifacts,
        [
            'apply-installation.sh',
            'verify-installation.sh',
            'installation-executor.json',
            'INSTALLATION_EXECUTOR_README.txt',
        ]
    )));

    sort($generatedArtifacts);
    $bundleManifest['generated_artifacts'] = $generatedArtifacts;

    return $bundleManifest;
}

try {
    $arguments = $argv ?? [];
    $profileId = null;
    $bundleDir = null;
    $planPath = null;
    $bundleManifestPath = null;

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

            case '--bundle-dir':
                $bundleDir = $value;
                break;

            case '--plan':
                $planPath = $value;
                break;

            case '--bundle-manifest':
                $bundleManifestPath = $value;
                break;

            default:
                throw new ValidationError(sprintf('Argumento no soportado: %s', $argument));
        }
    }

    if ($profileId === null && $bundleDir === null && $planPath === null) {
        throw new ValidationError('Debe indicar --profile, --bundle-dir o --plan');
    }

    if ($bundleDir === null) {
        if ($planPath !== null) {
            $bundleDir = dirname($planPath);
        } else {
            $bundleDir = $defaultBundleRoot . DIRECTORY_SEPARATOR . $profileId;
        }
    }

    $planPath ??= $bundleDir . DIRECTORY_SEPARATOR . 'installation-plan.json';
    $bundleManifestPath ??= $bundleDir . DIRECTORY_SEPARATOR . 'installation-bundle.json';

    if (!is_file($planPath)) {
        throw new ValidationError(sprintf('No existe el plan de instalacion: %s', $planPath));
    }

    if (!is_file($bundleManifestPath)) {
        throw new ValidationError(sprintf('No existe el installation-bundle.json: %s', $bundleManifestPath));
    }

    $plan = readJsonFile($planPath);
    validateInstallationPlan($plan, $planPath);

    $bundleManifest = readJsonFile($bundleManifestPath);
    validateInstallationBundle($bundleManifest, $bundleManifestPath);

    $applyScriptPath = $bundleDir . DIRECTORY_SEPARATOR . 'apply-installation.sh';
    $verifyScriptPath = $bundleDir . DIRECTORY_SEPARATOR . 'verify-installation.sh';
    $executorManifestPath = $bundleDir . DIRECTORY_SEPARATOR . 'installation-executor.json';
    $executorReadmePath = $bundleDir . DIRECTORY_SEPARATOR . 'INSTALLATION_EXECUTOR_README.txt';

    if (file_put_contents($applyScriptPath, normalizeLf(buildApplyScript($plan))) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $applyScriptPath));
    }

    if (file_put_contents($verifyScriptPath, normalizeLf(buildVerificationScript($plan))) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $verifyScriptPath));
    }

    if (file_put_contents($executorReadmePath, normalizeLf(buildExecutorReadme($plan))) === false) {
        throw new ValidationError(sprintf('No se pudo escribir %s', $executorReadmePath));
    }

    @chmod($applyScriptPath, 0755);
    @chmod($verifyScriptPath, 0755);

    writeJsonFile($executorManifestPath, createExecutorManifest($plan));
    writeJsonFile($bundleManifestPath, mergeExecutorArtifacts($bundleManifest));

    printJson([
        'status' => 'ok',
        'bundle_dir' => $bundleDir,
        'plan' => $planPath,
        'apply_script' => $applyScriptPath,
        'verify_script' => $verifyScriptPath,
        'executor_manifest' => $executorManifestPath,
    ]);
    exit(0);
} catch (ValidationError $exception) {
    fwrite(STDERR, sprintf("ERROR: %s\n", $exception->getMessage()));
    exit(1);
}
