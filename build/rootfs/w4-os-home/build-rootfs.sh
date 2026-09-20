#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="${1:-./rootfs}"

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
  fi

  if [[ -d "${rootfs_dir}/etc/apt/sources.list.d" ]]; then
    find "${rootfs_dir}/etc/apt/sources.list.d" -maxdepth 1 -type f -name '*.list' -exec \
      sed -i 's|/usr/share/keyrings/debian-archive-current.gpg|/usr/share/keyrings/debian-archive-keyring.gpg|g' {} +
  fi
}

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
    --include=apt,base-files,bash,ca-certificates,firefox-esr,grub-efi-amd64,libreoffice,linux-image-amd64,network-manager,os-prober,php-cli,pipewire,shim-signed,sudo,systemd,xdg-desktop-portal \
    --aptopt='Acquire::Retries "3"' \
    stable "${ROOTFS_DIR}" \
    "deb [signed-by=/usr/share/keyrings/debian-archive-keyring.gpg] http://deb.debian.org/debian stable main"
else
  echo "==> Bootstrap base Debian con debootstrap"
  debootstrap --merged-usr --variant=minbase stable "${ROOTFS_DIR}" http://deb.debian.org/debian/

  echo "==> Asegurando keyring Debian dentro del rootfs"
  ensure_debian_keyring_in_rootfs "${ROOTFS_DIR}"
  normalize_debian_sources_keyring "${ROOTFS_DIR}"

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
  chroot "${ROOTFS_DIR}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 update
  chroot "${ROOTFS_DIR}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 install -y apt base-files bash ca-certificates firefox-esr grub-efi-amd64 libreoffice linux-image-amd64 network-manager os-prober php-cli pipewire shim-signed sudo systemd xdg-desktop-portal
fi

echo "==> Asegurando keyring Debian dentro del rootfs"
ensure_debian_keyring_in_rootfs "${ROOTFS_DIR}"
normalize_debian_sources_keyring "${ROOTFS_DIR}"

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
