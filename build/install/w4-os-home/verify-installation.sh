#!/usr/bin/env bash
set -euo pipefail

TARGET_ROOT="${W4_TARGET_ROOT:-/mnt/w4-install-target}"
TARGET_DISK='/dev/sda'
EXPECTED_HOSTNAME='w4-home-vm'
EXPECTED_USER='w4'
CRYPT_NAME='cryptroot'

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

echo "Verificacion local completada para ${TARGET_ROOT}"
