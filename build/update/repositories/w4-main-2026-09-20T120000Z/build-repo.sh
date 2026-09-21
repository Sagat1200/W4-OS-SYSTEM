#!/usr/bin/env bash
set -euo pipefail

OUTPUT_DIR="${1:-}"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SNAPSHOT_ID="w4-main-2026-09-20T120000Z"
CHANNEL="testing"
TARGET_VERSION="1.0.1-lab"
PACKAGE_SET="both"
WORK_ROOT="${W4_UPDATE_REPO_WORK_ROOT:-/var/tmp/w4-os-system/update-repositories/${SNAPSHOT_ID}}"
REPO_DIR="${WORK_ROOT}/repo"
PKG_DIR="${WORK_ROOT}/packages"
POOL_DIR="${REPO_DIR}/pool/main/w4"
DISTS_DIR="${REPO_DIR}/dists/${CHANNEL}/main/binary-amd64"

if [[ -z "${OUTPUT_DIR}" ]]; then
  OUTPUT_DIR="${SCRIPT_DIR}/output"
fi

log() {
  echo "[w4-update-repo] $*" >&2
}

die() {
  log "ERROR: $*"
  exit 1
}

require_command() {
  local command_name="$1"
  command -v "${command_name}" >/dev/null 2>&1 || die "falta el comando requerido: ${command_name}"
}

build_package() {
  local name="$1"
  local version="$2"
  local architecture="$3"
  local depends="$4"
  local description="$5"
  local profile_scope="$6"

  local package_root="${PKG_DIR}/${name}"
  local output_path="${POOL_DIR}/${name}_${version}_${architecture}.deb"

  rm -rf "${package_root}"
  mkdir -p "${package_root}/DEBIAN" "${package_root}/usr/share/w4/packages"

  cat > "${package_root}/DEBIAN/control" <<EOF
Package: ${name}
Version: ${version}
Section: metapackages
Priority: optional
Architecture: ${architecture}
Maintainer: W4 OS System <w4@example.invalid>
Description: ${description}
EOF

  if [[ -n "${depends}" ]]; then
    printf 'Depends: %s
' "${depends}" >> "${package_root}/DEBIAN/control"
  fi

  cat > "${package_root}/usr/share/w4/packages/${name}.json" <<EOF
{
  "package_name": "${name}",
  "version": "${version}",
  "repository_snapshot_id": "${SNAPSHOT_ID}",
  "channel": "${CHANNEL}",
  "package_set": "${PACKAGE_SET}",
  "profile_scope": "${profile_scope}"
}
EOF

  dpkg-deb --build "${package_root}" "${output_path}" >/dev/null
}

log "Preparando repositorio APT W4 de laboratorio"
log "Snapshot: ${SNAPSHOT_ID}"
log "Canal: ${CHANNEL}"
log "Version objetivo: ${TARGET_VERSION}"
log "Package set: ${PACKAGE_SET}"

require_command dpkg-deb
require_command dpkg-scanpackages
require_command date
require_command gzip
require_command stat
require_command sha256sum

rm -rf "${REPO_DIR}" "${PKG_DIR}"
mkdir -p "${REPO_DIR}" "${PKG_DIR}" "${POOL_DIR}" "${DISTS_DIR}"

build_package "w4-base-meta" "1.0.1-lab" "all" "apt, base-files, bash, btrfs-progs, ca-certificates, grub-efi-amd64, linux-image-amd64, network-manager, os-prober, php-cli, shim-signed, sudo, systemd" "W4 OS base metapackage derived from the base manifest" "base"
build_package "w4-desktop-meta" "1.0.1-lab" "all" "pipewire, xdg-desktop-portal" "W4 OS desktop metapackage derived from shared edition requirements" "desktop"
build_package "w4-recovery-tools" "1.0.1-lab" "all" "bash, btrfs-progs" "W4 OS recovery tools package for update validation" "base"
build_package "w4-home-meta" "1.0.1-lab" "all" "w4-base-meta, w4-desktop-meta, firefox-esr, libreoffice" "W4 OS Home metapackage derived from the home edition profile" "home"
build_package "w4-business-meta" "1.0.1-lab" "all" "w4-base-meta, w4-desktop-meta, curl, jq" "W4 OS Business metapackage derived from the business edition profile" "business"

(
  cd "${REPO_DIR}"
  dpkg-scanpackages --multiversion pool /dev/null > Packages
  gzip -kf Packages
  sha256sum Packages Packages.gz > SHA256SUMS
)

cp "${REPO_DIR}/Packages" "${DISTS_DIR}/Packages"
cp "${REPO_DIR}/Packages.gz" "${DISTS_DIR}/Packages.gz"

packages_sha256="$(sha256sum "${DISTS_DIR}/Packages" | awk '{print $1}')"
packages_gz_sha256="$(sha256sum "${DISTS_DIR}/Packages.gz" | awk '{print $1}')"
packages_size="$(stat -c %s "${DISTS_DIR}/Packages")"
packages_gz_size="$(stat -c %s "${DISTS_DIR}/Packages.gz")"
release_date="$(LC_ALL=C date -Ru)"

cat > "${REPO_DIR}/dists/${CHANNEL}/Release" <<EOF
Origin: W4 OS
Label: W4 OS
Suite: ${CHANNEL}
Codename: ${CHANNEL}
Date: ${release_date}
Architectures: amd64
Components: main
Description: W4 OS update repository generated from repository manifests
SHA256:
 ${packages_sha256} ${packages_size} main/binary-amd64/Packages
 ${packages_gz_sha256} ${packages_gz_size} main/binary-amd64/Packages.gz
EOF

cat > "${REPO_DIR}/apt-source.list.template" <<'EOF'
deb [trusted=yes] file:__W4_REPO_ROOT__ ./
EOF

cat > "${REPO_DIR}/apt-source.dists.list.template" <<EOF
deb [trusted=yes] file:__W4_REPO_ROOT__ ${CHANNEL} main
EOF

cat > "${REPO_DIR}/repo.env" <<EOF
W4_REPOSITORY_SNAPSHOT_ID="${SNAPSHOT_ID}"
W4_REPOSITORY_CHANNEL="${CHANNEL}"
W4_REPOSITORY_TARGET_VERSION="${TARGET_VERSION}"
W4_UPDATE_APT_SOURCE_MODE_DEFAULT="dists"
W4_UPDATE_APT_SOURCE_LINE_DEFAULT_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ ${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_DEFAULT_LOCAL="deb [trusted=yes] file:${OUTPUT_DIR} ${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ ./"
W4_UPDATE_APT_SOURCE_LINE_LOCAL="deb [trusted=yes] file:${OUTPUT_DIR} ./"
W4_UPDATE_APT_SOURCE_LINE_DISTS_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ ${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_DISTS_LOCAL="deb [trusted=yes] file:${OUTPUT_DIR} ${CHANNEL} main"
EOF

cat > "${REPO_DIR}/package-sources.json" <<EOF
{
  "w4-base-meta": "w4-linux-base.packages.required",
  "w4-desktop-meta": "intersection(w4-os-home,w4-os-business).packages.required",
  "w4-recovery-tools": "update-recovery-tooling",
  "w4-home-meta": "w4-os-home",
  "w4-business-meta": "w4-os-business"
}
EOF

cat > "${REPO_DIR}/REPOSITORY_README.txt" <<EOF
W4 OS Update Repository (lab)

Contenido:
- pool/main/w4/*.deb
- Packages
- Packages.gz
- SHA256SUMS
- dists/${CHANNEL}/main/binary-amd64/Packages
- dists/${CHANNEL}/main/binary-amd64/Packages.gz
- dists/${CHANNEL}/Release
- apt-source.list.template
- apt-source.dists.list.template
- package-sources.json
- repo.env

Uso de laboratorio:
1. copiar este directorio al sistema objetivo
2. definir preferentemente:
   W4_UPDATE_APT_SOURCE_MODE=dists
   W4_UPDATE_APT_SOURCE_LINE_DISTS="deb [trusted=yes] file:/ruta/al/repositorio ${CHANNEL} main"
3. ejecutar run-update-offline.sh con W4_UPDATE_EXECUTE=1

Origen declarativo:
- los metapaquetes se derivan de los manifests/perfiles reales del repositorio
- package-sources.json resume la fuente usada para cada paquete

Compatibilidad:
- el runner ya prioriza la source dists en modo auto, que es la ruta recomendada para futuras pruebas
- se conserva la source plana para el laboratorio actual: deb [trusted=yes] file:/ruta ./
- ademas se publica una estructura tipo APT bajo dists/${CHANNEL} para preparar una fuente W4 mas cercana a produccion

Snapshot: ${SNAPSHOT_ID}
Canal: ${CHANNEL}
Version objetivo: ${TARGET_VERSION}
Package set: ${PACKAGE_SET}
EOF

rm -rf "${OUTPUT_DIR}"
mkdir -p "${OUTPUT_DIR}"
cp -a "${REPO_DIR}/." "${OUTPUT_DIR}/"

log "Repositorio listo en ${OUTPUT_DIR}"
echo "Resultado: ${OUTPUT_DIR}"
