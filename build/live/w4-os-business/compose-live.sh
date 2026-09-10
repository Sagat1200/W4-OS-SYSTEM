#!/usr/bin/env bash
set -euo pipefail

ROOTFS_DIR="${1:-}"
OUTPUT_DIR="${2:-}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILES_DIR="${SCRIPT_DIR}/files"
PROFILE_ID="w4-os-business"
PROFILE_NAME="W4 OS Business"
LIVE_USER="w4live"
LIVE_HOSTNAME="w4-business-live"
WORK_ROOTFS_DEFAULT="/var/tmp/w4-os-system/${PROFILE_ID}/live-work-rootfs"
WORK_ROOTFS="${W4_LIVE_WORK_ROOTFS:-${WORK_ROOTFS_DEFAULT}}"
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
  fi

  if [[ -d "${rootfs_dir}/etc/apt/sources.list.d" ]]; then
    find "${rootfs_dir}/etc/apt/sources.list.d" -maxdepth 1 -type f -name '*.list' -exec \
      sed -i 's|/usr/share/keyrings/debian-archive-current.gpg|/usr/share/keyrings/debian-archive-keyring.gpg|g' {} +
  fi
}

cleanup() {
  umount -lf "${WORK_ROOTFS}/proc" 2>/dev/null || true
  umount -lf "${WORK_ROOTFS}/sys" 2>/dev/null || true
  umount -lf "${WORK_ROOTFS}/dev" 2>/dev/null || true
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
echo "Output: ${OUTPUT_DIR}"
echo "Work rootfs: ${WORK_ROOTFS}"

rm -rf "${OUTPUT_DIR}/image-root" "${OUTPUT_DIR}/metadata" "${WORK_ROOTFS}"
mkdir -p "${OUTPUT_DIR}/image-root/live" "${OUTPUT_DIR}/image-root/boot/grub" "${OUTPUT_DIR}/image-root/.disk" "${OUTPUT_DIR}/metadata"

echo "==> Preparando copia de trabajo del rootfs"
rsync -aHAX --delete "${ROOTFS_DIR}/" "${WORK_ROOTFS}/"

echo "==> Asegurando keyring Debian dentro de la copia de trabajo"
ensure_debian_keyring_in_rootfs "${WORK_ROOTFS}"
normalize_debian_sources_keyring "${WORK_ROOTFS}"

if [[ "${PREPARE_LIVE_STACK}" == "1" ]]; then
  echo "==> Instalando pila live-boot/live-config en la copia de trabajo"
  mount --bind /dev "${WORK_ROOTFS}/dev"
  mount -t proc proc "${WORK_ROOTFS}/proc"
  mount -t sysfs sysfs "${WORK_ROOTFS}/sys"

  chroot "${WORK_ROOTFS}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 update
  chroot "${WORK_ROOTFS}" env DEBIAN_FRONTEND=noninteractive apt-get -o Acquire::Retries=3 install -y live-boot live-config
fi

KERNEL_SRC="$(find "${WORK_ROOTFS}/boot" -maxdepth 1 -type f -name 'vmlinuz-*' | sort | tail -n 1)"
INITRD_SRC="$(find "${WORK_ROOTFS}/boot" -maxdepth 1 -type f -name 'initrd.img-*' | sort | tail -n 1)"

if [[ -z "${KERNEL_SRC}" ]] || [[ -z "${INITRD_SRC}" ]]; then
  echo "ERROR: no se encontraron kernel/initrd en la copia de trabajo." >&2
  exit 1
fi

echo "==> Copiando estructura base de imagen live"
cp -a "${FILES_DIR}/." "${OUTPUT_DIR}/image-root/"
cp "${KERNEL_SRC}" "${OUTPUT_DIR}/image-root/live/vmlinuz"
cp "${INITRD_SRC}" "${OUTPUT_DIR}/image-root/live/initrd"

echo "==> Generando manifest de paquetes"
chroot "${WORK_ROOTFS}" dpkg-query -W --showformat='${Package} ${Version}\n' > "${OUTPUT_DIR}/image-root/live/filesystem.manifest"

echo "==> Calculando tamano del filesystem"
du -sx --block-size=1 "${WORK_ROOTFS}" | cut -f1 > "${OUTPUT_DIR}/image-root/live/filesystem.size"

echo "==> Generando filesystem.squashfs"
mksquashfs "${WORK_ROOTFS}" "${OUTPUT_DIR}/image-root/live/filesystem.squashfs" \
  -comp xz \
  -wildcards \
  -e boot/* var/cache/apt/archives/* var/lib/apt/lists/* tmp/* var/tmp/*

echo "==> Generando checksums"
( cd "${OUTPUT_DIR}/image-root" && find . -type f -print0 | sort -z | xargs -0 sha256sum ) > "${OUTPUT_DIR}/metadata/SHA256SUMS"

cat > "${OUTPUT_DIR}/metadata/live-summary.env" <<EOF
W4_PROFILE_ID="${PROFILE_ID}"
W4_PROFILE_NAME="${PROFILE_NAME}"
W4_LIVE_USER="${LIVE_USER}"
W4_LIVE_HOSTNAME="${LIVE_HOSTNAME}"
W4_KERNEL_BASENAME="$(basename "${KERNEL_SRC}")"
W4_INITRD_BASENAME="$(basename "${INITRD_SRC}")"
W4_PREPARED_LIVE_STACK="${PREPARE_LIVE_STACK}"
W4_IMAGE_ROOT="${OUTPUT_DIR}/image-root"
W4_GENERATED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF

if command -v xorriso >/dev/null 2>&1 && command -v grub-mkstandalone >/dev/null 2>&1; then
  printf '%s\n' "ISO_TOOLING_AVAILABLE=1" >> "${OUTPUT_DIR}/metadata/live-summary.env"
else
  printf '%s\n' "ISO_TOOLING_AVAILABLE=0" >> "${OUTPUT_DIR}/metadata/live-summary.env"
fi

echo "==> Live bundle preparado"
echo "Resultado: ${OUTPUT_DIR}/image-root"