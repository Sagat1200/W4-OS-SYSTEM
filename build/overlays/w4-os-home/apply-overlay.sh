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

chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/w4-firstboot.sh"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/w4-live-prep.sh"

mkdir -p "${ROOTFS_DIR}/etc/systemd/system/multi-user.target.wants"
ln -sfn ../w4-firstboot.service "${ROOTFS_DIR}/etc/systemd/system/multi-user.target.wants/w4-firstboot.service"
ln -sfn ../w4-live-prep.service "${ROOTFS_DIR}/etc/systemd/system/multi-user.target.wants/w4-live-prep.service"

printf '%s\n' "w4-os-home" > "${ROOTFS_DIR}/var/lib/w4/system-overlay-profile"
printf '%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "${ROOTFS_DIR}/var/lib/w4/system-overlay-applied-at"

echo "Overlay aplicado a W4 OS Home en ${ROOTFS_DIR}"
