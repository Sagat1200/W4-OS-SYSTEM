#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLAN_JSON="${SCRIPT_DIR}/installation-plan.json"
PROFILE_NAME='W4 OS Business'
TARGET_DISK='/dev/sda'
EXPECTED_SERIAL='hyperv-vm-disk-business-001'
EXPECTED_WWID='wwid-hyperv-vm-disk-business-001'
EXPECTED_BY_PATH='pci-0000:00:07.0-scsi-0:0:0:0'
EXPECTED_SIZE_BYTES='68719476736'
HOSTNAME_VALUE='w4-business-vm'
LOCALE_VALUE='es_DO.UTF-8'
KEYBOARD_VALUE='latam'
USERNAME_VALUE='w4admin'
DISPLAY_NAME_VALUE='W4 Administrator'
PASSWORD_SOURCE='secret://install/business-local-password'
PASSPHRASE_SOURCE='secret://install/business-disk-passphrase'
CRYPT_NAME='cryptroot'
ROOT_LABEL='W4-SYSTEM'
ESP_LABEL='W4-ESP'
BOOT_LABEL='W4-BOOT'
ROOT_SUBVOLUME='@'
ESP_SIZE_MIB='512'
BOOT_SIZE_MIB='2048'
TARGET_ROOT="${W4_TARGET_ROOT:-/mnt/w4-install-target}"
STAGING_ROOT="${W4_STAGING_ROOT:-/mnt/w4-install-staging}"
STAGING_MOUNT="${W4_STAGING_MOUNT:-/mnt/w4-install-staging-subvol}"
EXECUTE_MODE="${W4_INSTALL_EXECUTE:-0}"
SOURCE_ROOTFS="${W4_INSTALL_SOURCE_ROOTFS:-}"
SOURCE_SQUASHFS="${W4_INSTALL_SOURCE_SQUASHFS:-}"
DISK_PASSPHRASE_FILE="${W4_DISK_PASSPHRASE_FILE:-}"
LOCAL_USER_PASSWORD_FILE="${W4_LOCAL_USER_PASSWORD_FILE:-}"

log() {
  echo "[w4-install] $*"
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
  local current_serial current_wwid current_by_path current_size

  current_serial="$(udev_value "${device}" "ID_SERIAL_SHORT")"
  if [[ -z "${current_serial}" ]]; then
    current_serial="$(udev_value "${device}" "ID_SERIAL")"
  fi

  current_wwid="$(udev_value "${device}" "ID_WWN")"
  if [[ -z "${current_wwid}" ]]; then
    current_wwid="$(udev_value "${device}" "ID_WWN_WITH_EXTENSION")"
  fi

  current_by_path="$(udev_value "${device}" "ID_PATH")"
  current_size="$(lsblk -bndo SIZE "${device}")"

  if [[ -n "${EXPECTED_SERIAL}" && "${EXPECTED_SERIAL}" != "${current_serial}" ]]; then
    fail "el disco ya no coincide con el serial esperado"
  fi

  if [[ -n "${EXPECTED_WWID}" && "${EXPECTED_WWID}" != "${current_wwid}" ]]; then
    fail "el disco ya no coincide con el WWID esperado"
  fi

  if [[ -n "${EXPECTED_BY_PATH}" && "${EXPECTED_BY_PATH}" != "${current_by_path}" ]]; then
    fail "el disco ya no coincide con el path esperado"
  fi

  if [[ "${EXPECTED_SIZE_BYTES}" != "${current_size}" ]]; then
    fail "el disco ya no coincide con el tamaño esperado"
  fi
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

  if wipefs -n "${device}" 2>/dev/null | tail -n +2 | grep -q .; then
    fail "el disco seleccionado contiene firmas de filesystem"
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
    "${TARGET_ROOT}/run" \
    "${TARGET_ROOT}/sys" \
    "${TARGET_ROOT}/proc" \
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

mount_chroot_support() {
  mount --bind /dev "${TARGET_ROOT}/dev"
  mount --bind /proc "${TARGET_ROOT}/proc"
  mount --bind /sys "${TARGET_ROOT}/sys"
  mount --bind /run "${TARGET_ROOT}/run"
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

for cmd in lsblk udevadm wipefs sgdisk partprobe blkid mkfs.vfat mkfs.ext4 cryptsetup mkfs.btrfs rsync mount umount chroot awk grep sed; do
  require_command "${cmd}"
done

[[ -b "${TARGET_DISK}" ]] || fail "el disco objetivo no existe: ${TARGET_DISK}"

assert_selector "${TARGET_DISK}"
assert_empty_target "${TARGET_DISK}"

if [[ "${EXECUTE_MODE}" != "1" ]]; then
  print_plan
  exit 0
fi

[[ -n "${DISK_PASSPHRASE_FILE}" && -f "${DISK_PASSPHRASE_FILE}" ]] || fail "debe indicar W4_DISK_PASSPHRASE_FILE"
[[ -n "${LOCAL_USER_PASSWORD_FILE}" && -f "${LOCAL_USER_PASSWORD_FILE}" ]] || fail "debe indicar W4_LOCAL_USER_PASSWORD_FILE"

SOURCE_ROOT="$(prepare_source_root)"
trap cleanup EXIT

ESP_PART="$(part_path "${TARGET_DISK}" 1)"
BOOT_PART="$(part_path "${TARGET_DISK}" 2)"
ROOT_PART="$(part_path "${TARGET_DISK}" 3)"

log "Aplicando esquema GPT en ${TARGET_DISK}"
sgdisk --zap-all "${TARGET_DISK}"
wipefs -af "${TARGET_DISK}"
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
btrfs subvolume create "${STAGING_MOUNT}/@"
btrfs subvolume create "${STAGING_MOUNT}/@home"
btrfs subvolume create "${STAGING_MOUNT}/@log"
btrfs subvolume create "${STAGING_MOUNT}/@cache"
btrfs subvolume create "${STAGING_MOUNT}/@inventory"
umount "${STAGING_MOUNT}"

log "Montando layout destino"
ensure_directories
mount -o compress=zstd,noatime,subvol="${ROOT_SUBVOLUME}" "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}"
mkdir -p "${TARGET_ROOT}/home"
mount -o compress=zstd,noatime,subvol=@home "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}/home"
mkdir -p "${TARGET_ROOT}/var/log"
mount -o compress=zstd,noatime,subvol=@log "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}/var/log"
mkdir -p "${TARGET_ROOT}/var/cache"
mount -o compress=zstd,noatime,subvol=@cache "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}/var/cache"
mkdir -p "${TARGET_ROOT}/var/lib/w4"
mount -o compress=zstd,noatime,subvol=@inventory "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}/var/lib/w4"
mount "${BOOT_PART}" "${TARGET_ROOT}/boot"
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

echo "${HOSTNAME_VALUE}" > "${TARGET_ROOT}/etc/hostname"
cat > "${TARGET_ROOT}/etc/hosts" <<EOF
127.0.0.1 localhost
127.0.1.1 ${HOSTNAME_VALUE}
EOF

mkdir -p "${TARGET_ROOT}/etc/default"
cat > "${TARGET_ROOT}/etc/default/keyboard" <<EOF
XKBLAYOUT="${KEYBOARD_VALUE}"
EOF
echo "LANG=${LOCALE_VALUE}" > "${TARGET_ROOT}/etc/default/locale"

ESP_UUID="$(blkid -s UUID -o value "${ESP_PART}")"
BOOT_UUID="$(blkid -s UUID -o value "${BOOT_PART}")"
ROOT_UUID="$(blkid -s UUID -o value "${ROOT_PART}")"
BTRFS_UUID="$(blkid -s UUID -o value "/dev/mapper/${CRYPT_NAME}")"

cat > "${TARGET_ROOT}/etc/crypttab" <<EOF
${CRYPT_NAME} UUID=${ROOT_UUID} none luks,discard
EOF

cat > "${TARGET_ROOT}/etc/fstab" <<EOF
UUID=${BTRFS_UUID} / btrfs defaults,compress=zstd,noatime,subvol=@ 0 0
UUID=${BTRFS_UUID} /home btrfs defaults,compress=zstd,noatime,subvol=@home 0 0
UUID=${BTRFS_UUID} /var/log btrfs defaults,compress=zstd,noatime,subvol=@log 0 0
UUID=${BTRFS_UUID} /var/cache btrfs defaults,compress=zstd,noatime,subvol=@cache 0 0
UUID=${BTRFS_UUID} /var/lib/w4 btrfs defaults,compress=zstd,noatime,subvol=@inventory 0 0
UUID=${BOOT_UUID} /boot ext4 defaults 0 2
UUID=${ESP_UUID} /boot/efi vfat umask=0077 0 1
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

if ! chroot "${TARGET_ROOT}" id -u "${USERNAME_VALUE}" >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" useradd -m -s /bin/bash -c "${DISPLAY_NAME_VALUE}" "${USERNAME_VALUE}"
fi

if chroot "${TARGET_ROOT}" getent group sudo >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" usermod -aG sudo "${USERNAME_VALUE}"
fi

LOCAL_USER_PASSWORD="$(cat "${LOCAL_USER_PASSWORD_FILE}")"
printf '%s:%s\n' "${USERNAME_VALUE}" "${LOCAL_USER_PASSWORD}" | chroot "${TARGET_ROOT}" chpasswd

if chroot "${TARGET_ROOT}" command -v locale-gen >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" locale-gen || true
fi

if chroot "${TARGET_ROOT}" command -v grub-install >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" grub-install --target=x86_64-efi --efi-directory=/boot/efi --bootloader-id="W4 OS" --recheck
fi

if chroot "${TARGET_ROOT}" command -v update-initramfs >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" update-initramfs -u -k all
fi

if chroot "${TARGET_ROOT}" command -v update-grub >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" update-grub
fi

log "Instalacion preparada. Ejecute verify-installation.sh antes de reiniciar."
