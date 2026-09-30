#!/usr/bin/env bash
set -euo pipefail

OUTPUT_DIR="${1:-}"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
SNAPSHOT_ID="w4-main-server-p0"
CHANNEL="testing"
TARGET_VERSION="0.1.0-server-p0"
PACKAGE_SET="server"
WORK_ROOT="${W4_UPDATE_REPO_WORK_ROOT:-/var/tmp/w4-os-system/update-repositories/${SNAPSHOT_ID}}"
REPO_DIR="${WORK_ROOT}/repo"
PKG_DIR="${WORK_ROOT}/packages"
POOL_DIR="${REPO_DIR}/pool/main/w4"
DISTS_DIR="${REPO_DIR}/dists/${CHANNEL}/main/binary-amd64"
KEYRING_DIR="${REPO_DIR}/keyrings"
SIGNING_MODE="${W4_UPDATE_REPO_SIGNING_MODE:-unsigned}"
SIGNING_KEY_ID="${W4_UPDATE_REPO_GPG_KEY_ID:-}"
SIGNING_HOMEDIR="${W4_UPDATE_REPO_GPG_HOMEDIR:-}"
SIGNING_PASSPHRASE="${W4_UPDATE_REPO_GPG_PASSPHRASE:-}"
SIGNING_KEYRING_NAME="${W4_UPDATE_REPO_SIGNING_KEYRING_NAME:-w4-update-archive-keyring.gpg}"
SIGNING_KEYRING_PATH="${KEYRING_DIR}/${SIGNING_KEYRING_NAME}"
RELEASE_PATH="${REPO_DIR}/dists/${CHANNEL}/Release"
INRELEASE_PATH="${REPO_DIR}/dists/${CHANNEL}/InRelease"
RELEASE_GPG_PATH="${REPO_DIR}/dists/${CHANNEL}/Release.gpg"

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
if [[ "${SIGNING_MODE}" != "unsigned" ]]; then
  require_command gpg
fi

rm -rf "${REPO_DIR}" "${PKG_DIR}"
mkdir -p "${REPO_DIR}" "${PKG_DIR}" "${POOL_DIR}" "${DISTS_DIR}" "${KEYRING_DIR}"

build_package "w4-base-meta" "0.1.0-server-p0" "all" "apparmor, apt, base-files, bash, btrfs-progs, ca-certificates, grub-efi-amd64, linux-image-amd64, network-manager, php-cli, shim-signed, sudo, systemd, ufw" "W4 OS base metapackage derived from the base manifest" "base"
build_package "w4-recovery-tools" "0.1.0-server-p0" "all" "bash, btrfs-progs" "W4 OS recovery tools package for update validation" "base"
build_package "w4-server-meta" "0.1.0-server-p0" "all" "w4-base-meta, cryptsetup-initramfs, curl, iproute2, jq, openssh-server, systemd-sysv, systemd-timesyncd" "W4 OS Server headless metapackage derived from the server edition profile" "server"

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

cat > "${RELEASE_PATH}" <<EOF
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

if [[ "${SIGNING_MODE}" == "gpg" ]]; then
  [[ -n "${SIGNING_KEY_ID}" ]] || die "W4_UPDATE_REPO_GPG_KEY_ID es obligatorio cuando W4_UPDATE_REPO_SIGNING_MODE=gpg"

  gpg_args=(--batch --yes)
  if [[ -n "${SIGNING_HOMEDIR}" ]]; then
    gpg_args+=(--homedir "${SIGNING_HOMEDIR}")
  fi
  if [[ -n "${SIGNING_PASSPHRASE}" ]]; then
    gpg_args+=(--pinentry-mode loopback --passphrase "${SIGNING_PASSPHRASE}")
  fi

  rm -f "${INRELEASE_PATH}" "${RELEASE_GPG_PATH}" "${SIGNING_KEYRING_PATH}" "${SIGNING_KEYRING_PATH}.asc"
  gpg "${gpg_args[@]}" --armor --output "${RELEASE_GPG_PATH}" --detach-sign --local-user "${SIGNING_KEY_ID}" "${RELEASE_PATH}"
  gpg "${gpg_args[@]}" --clearsign --output "${INRELEASE_PATH}" --local-user "${SIGNING_KEY_ID}" "${RELEASE_PATH}"
  gpg "${gpg_args[@]}" --output "${SIGNING_KEYRING_PATH}" --export "${SIGNING_KEY_ID}"
  gpg "${gpg_args[@]}" --armor --output "${SIGNING_KEYRING_PATH}.asc" --export "${SIGNING_KEY_ID}"
fi

cat > "${REPO_DIR}/apt-source.list.template" <<'EOF'
deb [trusted=yes] file:__W4_REPO_ROOT__ ./
EOF

cat > "${REPO_DIR}/apt-source.dists.list.template" <<EOF
deb [trusted=yes] file:__W4_REPO_ROOT__ ${CHANNEL} main
EOF

cat > "${REPO_DIR}/apt-source.signed.list.template" <<EOF
deb [signed-by=__W4_REPO_ROOT__/keyrings/${SIGNING_KEYRING_NAME}] file:__W4_REPO_ROOT__ ${CHANNEL} main
EOF

default_source_line_template="deb [trusted=yes] file:__W4_REPO_ROOT__ ${CHANNEL} main"
default_source_line_local="deb [trusted=yes] file:${OUTPUT_DIR} ${CHANNEL} main"
if [[ "${SIGNING_MODE}" == "gpg" ]]; then
  default_source_line_template="deb [signed-by=__W4_REPO_ROOT__/keyrings/${SIGNING_KEYRING_NAME}] file:__W4_REPO_ROOT__ ${CHANNEL} main"
  default_source_line_local="deb [signed-by=${OUTPUT_DIR}/keyrings/${SIGNING_KEYRING_NAME}] file:${OUTPUT_DIR} ${CHANNEL} main"
fi

cat > "${REPO_DIR}/repo.env" <<EOF
W4_REPOSITORY_SNAPSHOT_ID="${SNAPSHOT_ID}"
W4_REPOSITORY_CHANNEL="${CHANNEL}"
W4_REPOSITORY_TARGET_VERSION="${TARGET_VERSION}"
W4_REPOSITORY_SIGNING_MODE="${SIGNING_MODE}"
W4_REPOSITORY_KEYRING_RELATIVE_PATH="keyrings/${SIGNING_KEYRING_NAME}"
W4_UPDATE_APT_SOURCE_MODE_DEFAULT="dists"
W4_UPDATE_APT_SOURCE_LINE_DEFAULT_TEMPLATE="${default_source_line_template}"
W4_UPDATE_APT_SOURCE_LINE_DEFAULT_LOCAL="${default_source_line_local}"
W4_UPDATE_APT_SOURCE_LINE_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ ./"
W4_UPDATE_APT_SOURCE_LINE_LOCAL="deb [trusted=yes] file:${OUTPUT_DIR} ./"
W4_UPDATE_APT_SOURCE_LINE_DISTS_TEMPLATE="deb [trusted=yes] file:__W4_REPO_ROOT__ ${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_DISTS_LOCAL="deb [trusted=yes] file:${OUTPUT_DIR} ${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_SIGNED_TEMPLATE="deb [signed-by=__W4_REPO_ROOT__/keyrings/${SIGNING_KEYRING_NAME}] file:__W4_REPO_ROOT__ ${CHANNEL} main"
W4_UPDATE_APT_SOURCE_LINE_SIGNED_LOCAL="deb [signed-by=${OUTPUT_DIR}/keyrings/${SIGNING_KEYRING_NAME}] file:${OUTPUT_DIR} ${CHANNEL} main"
EOF

cat > "${REPO_DIR}/package-sources.json" <<EOF
{
  "w4-base-meta": "w4-linux-base.packages.required",
  "w4-recovery-tools": "update-recovery-tooling",
  "w4-server-meta": "w4-os-server"
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
- dists/${CHANNEL}/InRelease (cuando SIGNING_MODE=gpg)
- dists/${CHANNEL}/Release.gpg (cuando SIGNING_MODE=gpg)
- apt-source.list.template
- apt-source.dists.list.template
- apt-source.signed.list.template
- package-sources.json
- repo.env

Uso de laboratorio:
1. copiar este directorio al sistema objetivo
2. definir preferentemente:
   W4_UPDATE_APT_SOURCE_MODE=dists
   W4_UPDATE_APT_SOURCE_LINE_SIGNED="deb [signed-by=/ruta/al/repositorio/keyrings/${SIGNING_KEYRING_NAME}] file:/ruta/al/repositorio ${CHANNEL} main"
3. si el repo aun no fue firmado, usar como fallback:
   W4_UPDATE_APT_SOURCE_LINE_DISTS="deb [trusted=yes] file:/ruta/al/repositorio ${CHANNEL} main"
4. ejecutar run-update-offline.sh con W4_UPDATE_EXECUTE=1

Firma opcional:
- exportar W4_UPDATE_REPO_SIGNING_MODE=gpg y W4_UPDATE_REPO_GPG_KEY_ID antes de ejecutar build-repo.sh
- opcionalmente definir W4_UPDATE_REPO_GPG_HOMEDIR y W4_UPDATE_REPO_GPG_PASSPHRASE
- el repositorio firmado exporta el keyring publico a keyrings/${SIGNING_KEYRING_NAME}

Origen declarativo:
- los metapaquetes se derivan de los manifests/perfiles reales del repositorio
- package-sources.json resume la fuente usada para cada paquete

Compatibilidad:
- el runner ya prioriza la source dists en modo auto, que es la ruta recomendada para futuras pruebas
- se conserva la source plana para el laboratorio actual: deb [trusted=yes] file:/ruta ./
- ademas se publica una estructura tipo APT bajo dists/${CHANNEL} y puede firmarse con GPG para preparar una fuente W4 mas cercana a produccion

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
