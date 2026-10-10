#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="${1:-./rootfs}"
BOOTSTRAP_KEYRING_DIR="/var/tmp/w4-os-system/w4-os-business/bootstrap-keyring"

prepare_host_debian_bootstrap_keyring() {
  local work_dir="${1}"
  local output_keyring="${work_dir}/debian-host-bootstrap-keyring.gpg"

  mkdir -p "${work_dir}"
  rm -f "${output_keyring}"

  if [[ -f /usr/share/keyrings/debian-archive-current.gpg ]] && command -v gpg >/dev/null 2>&1; then
    gpg --batch --no-default-keyring --keyring /usr/share/keyrings/debian-archive-current.gpg --export > "${output_keyring}"
    if [[ -s "${output_keyring}" ]]; then
      printf '%s
' "${output_keyring}"
      return 0
    fi
  fi

  if [[ -f /usr/share/keyrings/debian-archive-current.gpg ]]; then
    install -m 0644 /usr/share/keyrings/debian-archive-current.gpg "${output_keyring}"
    printf '%s
' "${output_keyring}"
    return 0
  fi

  if [[ -f /usr/share/keyrings/debian-archive-keyring.gpg ]]; then
    install -m 0644 /usr/share/keyrings/debian-archive-keyring.gpg "${output_keyring}"
    printf '%s
' "${output_keyring}"
    return 0
  fi

  echo "ERROR: no se encontro un keyring Debian utilizable para bootstrap en el host Linux." >&2
  exit 1
}

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

reset_rootfs_dir() {
  if [[ -z "${ROOTFS_DIR}" || "${ROOTFS_DIR}" == "/" ]]; then
    echo "ERROR: ROOTFS_DIR invalido para limpieza." >&2
    exit 1
  fi

  rm -rf "${ROOTFS_DIR}"
  mkdir -p "${ROOTFS_DIR}"
}

bootstrap_with_mmdebstrap() {
  mmdebstrap \
    --variant=minbase \
    --include=apparmor,apt,base-files,bash,btrfs-progs,ca-certificates,curl,dolphin,grub-efi-amd64,jq,konsole,linux-image-amd64,network-manager,os-prober,php-cli,pipewire,plasma-desktop,plasma-nm,plasma-workspace,sddm,shim-signed,sudo,systemd,systemsettings,ufw,xdg-desktop-portal,xdg-desktop-portal-kde \
    --aptopt='Acquire::Retries "3"' \
    trixie "${ROOTFS_DIR}" \
    "deb [signed-by=${HOST_BOOTSTRAP_KEYRING}] https://deb.debian.org/debian trixie main"
}

bootstrap_with_debootstrap() {
  debootstrap --keyring="${HOST_BOOTSTRAP_KEYRING}" --merged-usr --variant=minbase trixie "${ROOTFS_DIR}" https://deb.debian.org/debian/

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
  chroot "${ROOTFS_DIR}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 install -y apparmor apt base-files bash btrfs-progs ca-certificates curl dolphin grub-efi-amd64 jq konsole linux-image-amd64 network-manager os-prober php-cli pipewire plasma-desktop plasma-nm plasma-workspace sddm shim-signed sudo systemd systemsettings ufw xdg-desktop-portal xdg-desktop-portal-kde
}

cleanup() {
  umount -lf "${ROOTFS_DIR}/proc" 2>/dev/null || true
  umount -lf "${ROOTFS_DIR}/sys" 2>/dev/null || true
  umount -lf "${ROOTFS_DIR}/dev" 2>/dev/null || true
}

trap cleanup EXIT

echo "==> W4 OS System rootfs assembly"
echo "Profile: w4-os-business"
echo "Distribution: debian"
echo "Track: stable"
echo "Codename: trixie"
echo "Output: ${ROOTFS_DIR}"

if ! command -v mmdebstrap >/dev/null 2>&1 && ! command -v debootstrap >/dev/null 2>&1; then
  echo "ERROR: no hay ni mmdebstrap ni debootstrap disponibles en este entorno Linux." >&2
  exit 1
fi

mkdir -p "${ROOTFS_DIR}"
HOST_BOOTSTRAP_KEYRING="$(prepare_host_debian_bootstrap_keyring "${BOOTSTRAP_KEYRING_DIR}")"

echo "==> Repositorios declarados"
echo "  - debian-main"
echo "  - debian-security"
echo "  - w4-main"
echo "==> Keyring bootstrap: ${HOST_BOOTSTRAP_KEYRING}"

if command -v mmdebstrap >/dev/null 2>&1; then
  echo "==> Bootstrap base Debian con mmdebstrap"
  set +e
  bootstrap_with_mmdebstrap
  mmdebstrap_exit_code=$?
  set -e

  if [[ ${mmdebstrap_exit_code} -ne 0 ]]; then
    echo "WARN: mmdebstrap fallo con codigo ${mmdebstrap_exit_code}; limpiando rootfs parcial y reintentando con debootstrap." >&2
    cleanup
    reset_rootfs_dir

    echo "==> Bootstrap base Debian con debootstrap (fallback)"
    bootstrap_with_debootstrap
  fi
else
  echo "==> Bootstrap base Debian con debootstrap"
  bootstrap_with_debootstrap
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
echo "flatpak fwupd openvpn snapper wireguard-tools"

echo "==> Limpiando identidades del entorno"
rm -f "${ROOTFS_DIR}/etc/machine-id"
touch "${ROOTFS_DIR}/etc/machine-id"
rm -f "${ROOTFS_DIR}/var/lib/dbus/machine-id"

echo "==> Rootfs base ensamblado"
echo "Siguiente etapa: configuracion de primer inicio, instalador y formato de imagen."
