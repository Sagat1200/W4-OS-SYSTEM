#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="${1:-./rootfs}"

cleanup() {
  umount -lf "${ROOTFS_DIR}/proc" 2>/dev/null || true
  umount -lf "${ROOTFS_DIR}/sys" 2>/dev/null || true
  umount -lf "${ROOTFS_DIR}/dev" 2>/dev/null || true
}

trap cleanup EXIT

echo "==> W4 OS System rootfs assembly"
echo "Profile: w4-os-home"
echo "Distribution: debian"
echo "Track: stable"
echo "Output: ${ROOTFS_DIR}"

if ! command -v mmdebstrap >/dev/null 2>&1 && ! command -v debootstrap >/dev/null 2>&1; then
  echo "ERROR: no hay ni mmdebstrap ni debootstrap disponibles en este entorno Linux." >&2
  exit 1
fi

mkdir -p "${ROOTFS_DIR}"

echo "==> Repositorios declarados"
echo "  - debian-main"
echo "  - debian-security"
echo "  - w4-main"

if command -v mmdebstrap >/dev/null 2>&1; then
  echo "==> Bootstrap base Debian con mmdebstrap"
  mmdebstrap \
    --variant=minbase \
    --include=apt,base-files,bash,ca-certificates,firefox-esr,grub-efi-amd64,libreoffice,linux-image-amd64,network-manager,os-prober,pipewire,shim-signed,sudo,systemd,xdg-desktop-portal \
    stable "${ROOTFS_DIR}" \
    "deb [signed-by=/usr/share/keyrings/debian-archive-current.gpg] http://deb.debian.org/debian stable main"
else
  echo "==> Bootstrap base Debian con debootstrap"
  debootstrap --merged-usr --variant=minbase stable "${ROOTFS_DIR}" http://deb.debian.org/debian/

  echo "==> Asegurando estructura base de directorios"
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

  echo "==> Montando pseudo-filesystems para chroot"
  mount --bind /dev "${ROOTFS_DIR}/dev"
  mount -t proc proc "${ROOTFS_DIR}/proc"
  mount -t sysfs sysfs "${ROOTFS_DIR}/sys"

  echo "==> Instalacion de paquetes requeridos"
  chroot "${ROOTFS_DIR}" env DEBIAN_FRONTEND=noninteractive apt-get update
  chroot "${ROOTFS_DIR}" env DEBIAN_FRONTEND=noninteractive apt-get install -y apt base-files bash ca-certificates firefox-esr grub-efi-amd64 libreoffice linux-image-amd64 network-manager os-prober pipewire shim-signed sudo systemd xdg-desktop-portal
fi

echo "==> Asegurando estructura base de directorios"
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

echo "==> Paquetes recomendados sugeridos"
echo "evince flatpak fwupd snapper vlc"

echo "==> Limpiando identidades del entorno"
rm -f "${ROOTFS_DIR}/etc/machine-id"
touch "${ROOTFS_DIR}/etc/machine-id"
rm -f "${ROOTFS_DIR}/var/lib/dbus/machine-id"

echo "==> Rootfs base ensamblado"
echo "Siguiente etapa: configuracion de primer inicio, instalador y formato de imagen."
