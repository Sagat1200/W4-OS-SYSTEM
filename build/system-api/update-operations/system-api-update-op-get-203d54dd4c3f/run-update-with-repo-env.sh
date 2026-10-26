#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
REPOSITORY_DIR="${W4_UPDATE_REPOSITORY_DIR:-}"
REPOSITORY_ENV_FILE="${W4_UPDATE_REPOSITORY_ENV_FILE:-}"
declare -a OFFLINE_ARGS=()

log() {
  echo "[w4-update-repo-env] $*" >&2
}

die() {
  log "ERROR: $*"
  exit 1
}

while (($# > 0)); do
  case "$1" in
    --repo-dir)
      [[ $# -ge 2 ]] || die "falta el valor para --repo-dir"
      REPOSITORY_DIR="$2"
      shift 2
      ;;
    --repo-env)
      [[ $# -ge 2 ]] || die "falta el valor para --repo-env"
      REPOSITORY_ENV_FILE="$2"
      shift 2
      ;;
    --)
      shift
      OFFLINE_ARGS+=("$@")
      break
      ;;
    *)
      OFFLINE_ARGS+=("$1")
      shift
      ;;
  esac
done

if [[ -z "${REPOSITORY_ENV_FILE}" && -n "${REPOSITORY_DIR}" ]]; then
  REPOSITORY_ENV_FILE="${REPOSITORY_DIR}/repo.env"
fi

if [[ -n "${REPOSITORY_ENV_FILE}" ]]; then
  [[ -f "${REPOSITORY_ENV_FILE}" ]] || die "no se encontro repo.env: ${REPOSITORY_ENV_FILE}"
  set -a
  # shellcheck disable=SC1090
  source "${REPOSITORY_ENV_FILE}"
  set +a
fi

if [[ -z "${REPOSITORY_DIR}" && -n "${REPOSITORY_ENV_FILE}" ]]; then
  REPOSITORY_DIR="$(cd -- "$(dirname -- "${REPOSITORY_ENV_FILE}")" && pwd)"
fi

[[ -n "${REPOSITORY_DIR}" ]] || die "defina --repo-dir, W4_UPDATE_REPOSITORY_DIR o --repo-env"
[[ -d "${REPOSITORY_DIR}" ]] || die "no se encontro el directorio del repositorio: ${REPOSITORY_DIR}"

REPOSITORY_DIR="$(cd -- "${REPOSITORY_DIR}" && pwd)"
CHANNEL="${W4_REPOSITORY_CHANNEL:-${W4_UPDATE_REPOSITORY_CHANNEL:-testing}}"
REPOSITORY_URI_PATH="${REPOSITORY_DIR//%/%25}"
REPOSITORY_URI_PATH="${REPOSITORY_URI_PATH// /%20}"
REPOSITORY_URI="file:${REPOSITORY_URI_PATH}"
KEYRING_RELATIVE_PATH="${W4_REPOSITORY_KEYRING_RELATIVE_PATH:-keyrings/w4-update-archive-keyring.gpg}"
KEYRING_PATH="${W4_UPDATE_APT_KEYRING_PATH:-}"

if [[ -z "${KEYRING_PATH}" && -n "${KEYRING_RELATIVE_PATH}" ]]; then
  KEYRING_PATH="${REPOSITORY_DIR}/${KEYRING_RELATIVE_PATH}"
fi

export W4_UPDATE_APT_SOURCE_MODE="${W4_UPDATE_APT_SOURCE_MODE:-${W4_UPDATE_APT_SOURCE_MODE_DEFAULT:-dists}}"
export W4_UPDATE_APT_CHECK_DATE="${W4_UPDATE_APT_CHECK_DATE:-0}"
if [[ -z "${W4_UPDATE_APT_SOURCE_LINE_SIGNED:-}" && -n "${KEYRING_PATH}" && -f "${KEYRING_PATH}" ]]; then
  export W4_UPDATE_APT_SOURCE_LINE_SIGNED="deb [signed-by=${KEYRING_PATH}] ${REPOSITORY_URI} ${CHANNEL} main"
fi
if [[ -z "${W4_UPDATE_APT_SOURCE_LINE_DISTS:-}" && -n "${W4_UPDATE_APT_SOURCE_LINE_SIGNED:-}" ]]; then
  export W4_UPDATE_APT_SOURCE_LINE_DISTS="${W4_UPDATE_APT_SOURCE_LINE_SIGNED}"
fi
export W4_UPDATE_APT_SOURCE_LINE_DISTS="${W4_UPDATE_APT_SOURCE_LINE_DISTS:-deb [trusted=yes] ${REPOSITORY_URI} ${CHANNEL} main}"
export W4_UPDATE_APT_SOURCE_LINE="${W4_UPDATE_APT_SOURCE_LINE:-deb [trusted=yes] ${REPOSITORY_URI} ./}"

log "Usando repositorio APT desde ${REPOSITORY_DIR}"
log "Canal: ${CHANNEL}"
log "Modo APT: ${W4_UPDATE_APT_SOURCE_MODE}"

exec "${SCRIPT_DIR}/run-update-offline.sh" "${OFFLINE_ARGS[@]}"
