#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
TARGET_ROOT="${W4_TARGET_ROOT:-/mnt/w4-install-target}"
TARGET_DISK='/dev/sda'
EXPECTED_HOSTNAME='w4-server-vm'
EXPECTED_USER='w4admin'
CRYPT_NAME='cryptroot'
ROOT_SUBVOLUME='@'
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
