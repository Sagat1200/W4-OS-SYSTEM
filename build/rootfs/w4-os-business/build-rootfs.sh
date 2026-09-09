#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="${1:-./rootfs}"

echo "==> W4 OS System rootfs assembly"
echo "Profile: w4-os-business"
echo "Distribution: debian"
echo "Track: stable"
echo "Output: ${ROOTFS_DIR}"

if ! command -v debootstrap >/dev/null 2>&1; then
  echo "ERROR: debootstrap no esta disponible en este entorno Linux." >&2
  exit 1
fi

mkdir -p "${ROOTFS_DIR}"
mkdir -p "${ROOTFS_DIR}/boot"
mkdir -p "${ROOTFS_DIR}/boot/efi"
mkdir -p "${ROOTFS_DIR}/dev"
mkdir -p "${ROOTFS_DIR}/etc"
mkdir -p "${ROOTFS_DIR}/etc/apt"
mkdir -p "${ROOTFS_DIR}/etc/apt/sources.list.d"
mkdir -p "${ROOTFS_DIR}/etc/default"
mkdir -p "${ROOTFS_DIR}/etc/systemd/system"
mkdir -p "${ROOTFS_DIR}/home"
mkdir -p "${ROOTFS_DIR}/proc"
mkdir -p "${ROOTFS_DIR}/root"
mkdir -p "${ROOTFS_DIR}/run"
mkdir -p "${ROOTFS_DIR}/sys"
mkdir -p "${ROOTFS_DIR}/tmp"
mkdir -p "${ROOTFS_DIR}/usr"
mkdir -p "${ROOTFS_DIR}/usr/local"
mkdir -p "${ROOTFS_DIR}/var"
mkdir -p "${ROOTFS_DIR}/var/cache/apt"
mkdir -p "${ROOTFS_DIR}/var/lib/apt"
mkdir -p "${ROOTFS_DIR}/var/lib/dpkg"
mkdir -p "${ROOTFS_DIR}/var/log"
mkdir -p "${ROOTFS_DIR}/var/tmp"

echo "==> Repositorios declarados"
echo "  - debian-main"
echo "  - debian-security"
echo "  - w4-main"

echo "==> Bootstrap base Debian"
debootstrap --variant=minbase stable "${ROOTFS_DIR}" http://deb.debian.org/debian/

echo "==> Instalacion de paquetes requeridos"
chroot "${ROOTFS_DIR}" apt-get update
chroot "${ROOTFS_DIR}" apt-get install -y apt base-files bash ca-certificates curl grub-efi-amd64 jq linux-image-amd64 network-manager os-prober pipewire shim-signed sudo systemd xdg-desktop-portal

echo "==> Paquetes recomendados sugeridos"
echo "flatpak fwupd openvpn snapper wireguard-tools"

echo "==> Limpiando identidades del entorno"
rm -f "${ROOTFS_DIR}/etc/machine-id"
touch "${ROOTFS_DIR}/etc/machine-id"
rm -f "${ROOTFS_DIR}/var/lib/dbus/machine-id"

echo "==> Rootfs base ensamblado"
echo "Siguiente etapa: configuracion de primer inicio, instalador y formato de imagen."
