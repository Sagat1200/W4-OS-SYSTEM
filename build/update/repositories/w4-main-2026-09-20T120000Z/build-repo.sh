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
require_command gzip
require_command sha256sum

rm -rf "${REPO_DIR}" "${PKG_DIR}"
mkdir -p "${REPO_DIR}" "${PKG_DIR}" "${POOL_DIR}"

build_package "w4-base-meta" "1.0.1-lab" "all" "apt, bash, ca-certificates, php-cli" "W4 OS base metapackage for update validation" "base"
build_package "w4-recovery-tools" "1.0.1-lab" "all" "bash" "W4 OS recovery tools package for update validation" "base"
build_package "w4-home-meta" "1.0.1-lab" "all" "w4-base-meta" "W4 OS Home metapackage for update validation" "home"
build_package "w4-business-meta" "1.0.1-lab" "all" "w4-base-meta" "W4 OS Business metapackage for update validation" "business"

(
  cd "${REPO_DIR}"
  dpkg-scanpackages --multiversion pool /dev/null > Packages
  gzip -kf Packages
  sha256sum Packages Packages.gz > SHA256SUMS
)

cat > "${REPO_DIR}/apt-source.list.template" <<'EOF'
deb [trusted=yes] file:__W4_REPO_ROOT__ ./
EOF

cat > "${REPO_DIR}/repo.env" <<EOF
W4_REPOSITORY_SNAPSHOT_ID="${SNAPSHOT_ID}"
W4_REPOSITORY_CHANNEL="${CHANNEL}"
W4_REPOSITORY_TARGET_VERSION="${TARGET_VERSION}"
W4_UPDATE_APT_SOURCE_LINE_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ ./"
W4_UPDATE_APT_SOURCE_LINE_LOCAL="deb [trusted=yes] file:${OUTPUT_DIR} ./"
EOF

cat > "${REPO_DIR}/REPOSITORY_README.txt" <<EOF
W4 OS Update Repository (lab)

Contenido:
- pool/main/w4/*.deb
- Packages
- Packages.gz
- SHA256SUMS
- apt-source.list.template
- repo.env

Uso de laboratorio:
1. copiar este directorio al sistema objetivo
2. definir W4_UPDATE_APT_SOURCE_LINE con una source tipo:
   deb [trusted=yes] file:/ruta/al/repositorio ./
3. ejecutar run-update-offline.sh con W4_UPDATE_EXECUTE=1

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
