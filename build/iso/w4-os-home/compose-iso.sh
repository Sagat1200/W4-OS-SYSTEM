#!/usr/bin/env bash
set -euo pipefail

IMAGE_ROOT_DIR="${1:-}"
OUTPUT_DIR="${2:-}"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROFILE_ID="w4-os-home"
PROFILE_NAME="W4 OS Home"
ISO_FILENAME="w4-os-home-live-amd64.iso"
VOLUME_ID="W4_OS_HOME_LIVE"
ISO_STAGE_DIR_DEFAULT="/var/tmp/w4-os-system/${PROFILE_ID}/iso-stage"
ISO_STAGE_DIR="${W4_ISO_STAGE_DIR:-${ISO_STAGE_DIR_DEFAULT}}"

require_file() {
  local path="${1}"
  if [[ ! -f "${path}" ]]; then
    echo "ERROR: falta el archivo requerido: ${path}" >&2
    exit 1
  fi
}

if [[ -z "${IMAGE_ROOT_DIR}" ]]; then
  echo "ERROR: debe indicar el image-root de origen" >&2
  exit 1
fi

if [[ ! -d "${IMAGE_ROOT_DIR}" ]]; then
  echo "ERROR: no existe el image-root de origen: ${IMAGE_ROOT_DIR}" >&2
  exit 1
fi

if [[ -z "${OUTPUT_DIR}" ]]; then
  OUTPUT_DIR="${SCRIPT_DIR}/output"
fi

if ! command -v xorriso >/dev/null 2>&1; then
  echo "ERROR: xorriso no esta disponible en este entorno Linux." >&2
  exit 1
fi

echo "==> W4 OS System ISO composition"
echo "Profile: ${PROFILE_NAME}"
echo "Image root: ${IMAGE_ROOT_DIR}"
echo "Output final: ${OUTPUT_DIR}"
echo "ISO stage: ${ISO_STAGE_DIR}"

require_file "${IMAGE_ROOT_DIR}/boot/grub/grub.cfg"
require_file "${IMAGE_ROOT_DIR}/live/vmlinuz"
require_file "${IMAGE_ROOT_DIR}/live/initrd"
require_file "${IMAGE_ROOT_DIR}/live/filesystem.squashfs"

rm -rf "${ISO_STAGE_DIR}"
mkdir -p "${ISO_STAGE_DIR}/image-root" "${ISO_STAGE_DIR}/metadata"
rsync -a --delete "${IMAGE_ROOT_DIR}/" "${ISO_STAGE_DIR}/image-root/"

if command -v grub-mkrescue >/dev/null 2>&1; then
  echo "==> Generando ISO con grub-mkrescue"
  grub-mkrescue -o "${ISO_STAGE_DIR}/${ISO_FILENAME}" "${ISO_STAGE_DIR}/image-root"
else
  if ! command -v grub-mkstandalone >/dev/null 2>&1; then
    echo "ERROR: no se encontro ni grub-mkrescue ni grub-mkstandalone." >&2
    exit 1
  fi

  if ! command -v mformat >/dev/null 2>&1 || ! command -v mmd >/dev/null 2>&1 || ! command -v mcopy >/dev/null 2>&1; then
    echo "ERROR: faltan herramientas mtools (mformat/mmd/mcopy) para construir EFI fallback." >&2
    exit 1
  fi

  if ! command -v mkfs.vfat >/dev/null 2>&1; then
    echo "ERROR: mkfs.vfat no esta disponible para construir EFI fallback." >&2
    exit 1
  fi

  echo "==> Generando ISO UEFI con xorriso + grub-mkstandalone"
  mkdir -p "${ISO_STAGE_DIR}/work/EFI/BOOT" "${ISO_STAGE_DIR}/image-root/EFI"

  cat > "${ISO_STAGE_DIR}/work/grub-embed.cfg" <<'EOF'
search --file --set=root /live/filesystem.squashfs
set prefix=($root)/boot/grub
configfile /boot/grub/grub.cfg
EOF

  grub-mkstandalone \
    -O x86_64-efi \
    -o "${ISO_STAGE_DIR}/work/EFI/BOOT/BOOTX64.EFI" \
    "boot/grub/grub.cfg=${ISO_STAGE_DIR}/work/grub-embed.cfg"

  truncate -s 20M "${ISO_STAGE_DIR}/image-root/EFI/efiboot.img"
  mkfs.vfat "${ISO_STAGE_DIR}/image-root/EFI/efiboot.img" >/dev/null
  mmd -i "${ISO_STAGE_DIR}/image-root/EFI/efiboot.img" ::/EFI ::/EFI/BOOT
  mcopy -i "${ISO_STAGE_DIR}/image-root/EFI/efiboot.img" "${ISO_STAGE_DIR}/work/EFI/BOOT/BOOTX64.EFI" ::/EFI/BOOT/BOOTX64.EFI

  xorriso -as mkisofs \
    -iso-level 3 \
    -full-iso9660-filenames \
    -volid "${VOLUME_ID}" \
    -eltorito-alt-boot \
    -e EFI/efiboot.img \
    -no-emul-boot \
    -isohybrid-gpt-basdat \
    -output "${ISO_STAGE_DIR}/${ISO_FILENAME}" \
    "${ISO_STAGE_DIR}/image-root"
fi

echo "==> Generando metadata ISO"
sha256sum "${ISO_STAGE_DIR}/${ISO_FILENAME}" > "${ISO_STAGE_DIR}/metadata/SHA256SUMS"

cat > "${ISO_STAGE_DIR}/metadata/iso-summary.env" <<EOF
W4_PROFILE_ID="${PROFILE_ID}"
W4_PROFILE_NAME="${PROFILE_NAME}"
W4_ISO_FILENAME="${ISO_FILENAME}"
W4_VOLUME_ID="${VOLUME_ID}"
W4_IMAGE_ROOT="${IMAGE_ROOT_DIR}"
W4_STAGE_DIR="${ISO_STAGE_DIR}"
W4_GENERATED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF

echo "==> Sincronizando artefactos ISO al workspace"
rm -rf "${OUTPUT_DIR}"
mkdir -p "${OUTPUT_DIR}"
rsync -a --delete "${ISO_STAGE_DIR}/" "${OUTPUT_DIR}/"

echo "==> ISO preparada"
echo "Resultado: ${OUTPUT_DIR}/${ISO_FILENAME}"