#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_ENGINE_ROOT="$(cd -- "${SCRIPT_DIR}/../../../.." 2>/dev/null && pwd || true)"
ENGINE_ROOT="${W4_UPDATE_ENGINE_ROOT:-${DEFAULT_ENGINE_ROOT}}"
STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}/store}"
STAGING_DIR="${W4_UPDATE_STAGING_DIR:-/var/lib/w4-update/staging/w4-update-smoke-003}"
SNAPSHOT_PARENT="${W4_UPDATE_SNAPSHOT_PARENT:-/.snapshots}"
ROOT_MOUNT="${W4_UPDATE_ROOT_MOUNT:-/}"
ROOT_SUBVOLUME="${W4_UPDATE_ROOT_SUBVOLUME:-@}"
EXECUTE_MODE="${W4_UPDATE_EXECUTE:-0}"
APPLY_MODE="${W4_UPDATE_APPLY_MODE:-live-apt}"
FAIL_STAGE="${W4_UPDATE_FAIL_STAGE:-}"
APT_SOURCE_MODE="${W4_UPDATE_APT_SOURCE_MODE:-auto}"
APT_SOURCE_LINE="${W4_UPDATE_APT_SOURCE_LINE:-}"
APT_SOURCE_LINE_DISTS="${W4_UPDATE_APT_SOURCE_LINE_DISTS:-}"
APT_SOURCE_FILE="${W4_UPDATE_APT_SOURCE_FILE:-}"
APT_SOURCE_TARGET=""
APT_SOURCE_INSTALLED="0"
ADVANCE_SCRIPT="${ENGINE_ROOT}/scripts/advance_update_operation.php"
ARTIFACT_PATH="${STORE_DIR}/offline-application.json"
SNAPSHOT_MANIFEST_PATH="${STORE_DIR}/snapshot-manifest.json"
STAGING_MANIFEST_PATH="${STORE_DIR}/staging-manifest.json"
HEALTH_CHECK_PATH="${STORE_DIR}/health-checks.required.txt"

OPERATION_ID="w4-update-smoke-003"
TARGET_VERSION="1.0.1-lab"
SNAPSHOT_NAME="pre-update-w4-update-smoke-003"
SNAPSHOT_PATH="/.snapshots/pre-update-w4-update-smoke-003"
BOOT_MODE="uefi"
KERNEL_PACKAGE="linux-image-amd64"
TEST_FILE_PATH="/home/w4/update-proof.txt"
REQUIRED_SPACE_KIB="524288"
CURRENT_STAGE="planned"
FAILURE_PERSISTED="0"
APT_SOURCE_TARGET="/etc/apt/sources.list.d/w4-update-${OPERATION_ID}.list"

declare -a INSTALL_PACKAGES=('w4-recovery-tools')
declare -a UPGRADE_PACKAGES=('w4-base-meta' 'w4-home-meta' 'linux-image-amd64')
declare -a REMOVE_PACKAGES=()
declare -a REQUIRED_HEALTH_CHECKS=('boot-entry-present' 'cryptroot-unlock' 'root-subvolume-mounted' 'test-file-present')

log() {
  echo "[w4-update] $*" >&2
}

die() {
  log "ERROR: $*"
  exit 1
}

require_command() {
  local command_name="$1"
  command -v "${command_name}" >/dev/null 2>&1 || die "falta el comando requerido: ${command_name}"
}

ensure_engine_scripts() {
  [[ -n "${ENGINE_ROOT}" ]] || die "ENGINE_ROOT no resolvio una ruta valida; defina W4_UPDATE_ENGINE_ROOT"
  [[ -f "${ADVANCE_SCRIPT}" ]] || die "no se encontro ${ADVANCE_SCRIPT}; defina W4_UPDATE_ENGINE_ROOT o ejecute dentro del repo"
}

cleanup_temporary_apt_source() {
  if [[ "${APT_SOURCE_INSTALLED}" == "1" && -f "${APT_SOURCE_TARGET}" ]]; then
    rm -f "${APT_SOURCE_TARGET}"
  fi
}

configure_temporary_apt_source() {
  local source_content=""

  if [[ -n "${APT_SOURCE_FILE}" ]]; then
    [[ -f "${APT_SOURCE_FILE}" ]] || die "no se encontro el archivo de source APT temporal: ${APT_SOURCE_FILE}"
    source_content="$(<"${APT_SOURCE_FILE}")"
  else
    case "${APT_SOURCE_MODE}" in
      auto)
        if [[ -n "${APT_SOURCE_LINE_DISTS}" ]]; then
          source_content="${APT_SOURCE_LINE_DISTS}"
        elif [[ -n "${APT_SOURCE_LINE}" ]]; then
          source_content="${APT_SOURCE_LINE}"
        else
          return
        fi
        ;;
      dists)
        if [[ -n "${APT_SOURCE_LINE_DISTS}" ]]; then
          source_content="${APT_SOURCE_LINE_DISTS}"
        elif [[ -n "${APT_SOURCE_LINE}" ]]; then
          log "W4_UPDATE_APT_SOURCE_LINE_DISTS no esta definido; se usara la source plana como fallback"
          source_content="${APT_SOURCE_LINE}"
        else
          return
        fi
        ;;
      flat)
        if [[ -n "${APT_SOURCE_LINE}" ]]; then
          source_content="${APT_SOURCE_LINE}"
        elif [[ -n "${APT_SOURCE_LINE_DISTS}" ]]; then
          log "W4_UPDATE_APT_SOURCE_LINE no esta definido; se usara la source dists como fallback"
          source_content="${APT_SOURCE_LINE_DISTS}"
        else
          return
        fi
        ;;
      *)
        die "W4_UPDATE_APT_SOURCE_MODE no soportado: ${APT_SOURCE_MODE}"
        ;;
    esac
  fi

  [[ -n "${source_content}" ]] || die "la source APT temporal esta vacia"
  printf '%s
' "${source_content}" > "${APT_SOURCE_TARGET}"
  APT_SOURCE_INSTALLED="1"
  log "Source APT temporal instalada en ${APT_SOURCE_TARGET}"
}

persist_failure() {
  local exit_code="$1"
  local failed_stage="${CURRENT_STAGE:-unknown}"

  if [[ "${FAILURE_PERSISTED}" == "1" ]]; then
    return
  fi

  FAILURE_PERSISTED="1"

  if [[ ! -f "${STORE_DIR}/operation.json" ]]; then
    log "No se pudo persistir el fallo porque falta ${STORE_DIR}/operation.json"
    return
  fi

  php "${ADVANCE_SCRIPT}" \
    --store-dir "${STORE_DIR}" \
    --stage failed \
    --component "update-offline-executor" \
    --error-code "command-failed" \
    --error-message "La etapa ${failed_stage} fallo con exit_code=${exit_code}" \
    --detail "failed_stage=${failed_stage}" \
    --detail "exit_code=${exit_code}" \
    || true
}

handle_error() {
  local exit_code="$1"
  local line_number="$2"
  log "La etapa ${CURRENT_STAGE} fallo en la linea ${line_number} con exit_code=${exit_code}"
  persist_failure "${exit_code}"
  exit "${exit_code}"
}

advance_stage() {
  local next_stage="$1"
  CURRENT_STAGE="${next_stage}"
  php "${ADVANCE_SCRIPT}" --store-dir "${STORE_DIR}" --stage "${next_stage}" --component "update-offline-executor"
}

fail_stage() {
  local failed_stage="$1"
  php "${ADVANCE_SCRIPT}" --store-dir "${STORE_DIR}" --stage "failed" --component "update-offline-executor" --error-code "injected-failure" --error-message "Fallo inyectado en la etapa ${failed_stage}" --detail "failed_stage=${failed_stage}"
}

maybe_fail() {
  local stage_name="$1"
  if [[ -n "${FAIL_STAGE}" && "${FAIL_STAGE}" == "${stage_name}" ]]; then
    log "Inyectando fallo en ${stage_name}"
    fail_stage "${stage_name}"
    exit 1
  fi
}

run_or_describe() {
  if [[ "${EXECUTE_MODE}" == "1" ]]; then
    "$@"
    return
  fi

  log "[check-only] $(printf '%q ' "$@")"
}

check_free_space() {
  mkdir -p "${STAGING_DIR}"
  local available_kib
  available_kib="$(df -Pk "${STAGING_DIR}" | awk 'NR==2 {print $4}')"
  [[ -n "${available_kib}" ]] || die "no se pudo calcular el espacio libre de ${STAGING_DIR}"

  if (( available_kib < REQUIRED_SPACE_KIB )); then
    die "espacio insuficiente en staging: disponible=${available_kib} KiB requerido=${REQUIRED_SPACE_KIB} KiB"
  fi
}

stage_packages() {
  mkdir -p "${STAGING_DIR}"
  cat > "${STAGING_MANIFEST_PATH}" <<EOF
{
  "staging_manifest_schema_version": 1,
  "kind": "staging-manifest",
  "operation_id": "w4-update-smoke-003",
  "staging_directory": "${STAGING_DIR}",
  "estimated_space_kib": 524288,
  "install_count": 1,
  "upgrade_count": 3,
  "remove_count": 0
}
EOF

  if [[ "${EXECUTE_MODE}" != "1" ]]; then
    if [[ -n "${APT_SOURCE_LINE}" || -n "${APT_SOURCE_FILE}" ]]; then
      log "Modo check-only: se detecto una source APT temporal pero no se aplicara"
    fi
    log "Modo check-only: se omite apt-get update y descarga de paquetes"
    return
  fi

  require_command apt-get
  export DEBIAN_FRONTEND=noninteractive
  configure_temporary_apt_source
  apt-get update

  local packages_to_stage=()
  packages_to_stage+=("${INSTALL_PACKAGES[@]}")
  packages_to_stage+=("${UPGRADE_PACKAGES[@]}")
  if (( ${#packages_to_stage[@]} > 0 )); then
    apt-get -o Dir::Cache::Archives="${STAGING_DIR}" -y --download-only install "${packages_to_stage[@]}"
  fi
}

create_snapshot() {
  mkdir -p "${STORE_DIR}"

  if [[ "${EXECUTE_MODE}" == "1" ]]; then
    if command -v snapper >/dev/null 2>&1; then
      snapper --no-dbus create --type single --description "W4 update ${OPERATION_ID}" --userdata "operation_id=${OPERATION_ID}"
    elif command -v btrfs >/dev/null 2>&1; then
      mkdir -p "${SNAPSHOT_PARENT}"
      btrfs subvolume snapshot -r "${ROOT_MOUNT}" "${SNAPSHOT_PARENT}/${SNAPSHOT_NAME}"
    else
      die "no se encontro snapper ni btrfs para crear snapshot"
    fi
  else
    if ! command -v snapper >/dev/null 2>&1 && ! command -v btrfs >/dev/null 2>&1; then
      log "Modo check-only: no se detecto snapper ni btrfs; se conserva la advertencia para laboratorio"
    fi
  fi

  cat > "${SNAPSHOT_MANIFEST_PATH}" <<EOF
{
  "snapshot_manifest_schema_version": 1,
  "kind": "snapshot-manifest",
  "operation_id": "w4-update-smoke-003",
  "snapshot_name": "pre-update-w4-update-smoke-003",
  "snapshot_path": "${SNAPSHOT_PARENT}/${SNAPSHOT_NAME}",
  "root_mount": "${ROOT_MOUNT}",
  "root_subvolume": "${ROOT_SUBVOLUME}"
}
EOF
}

apply_packages() {
  if [[ "${EXECUTE_MODE}" != "1" ]]; then
    log "Modo check-only: se omite aplicacion real de paquetes"
    return
  fi

  require_command apt-get
  export DEBIAN_FRONTEND=noninteractive

  if [[ "${APPLY_MODE}" != "live-apt" ]]; then
    die "APPLY_MODE no soportado por esta etapa: ${APPLY_MODE}"
  fi

  local packages_to_install=()
  packages_to_install+=("${INSTALL_PACKAGES[@]}")
  packages_to_install+=("${UPGRADE_PACKAGES[@]}")
  if (( ${#packages_to_install[@]} > 0 )); then
    apt-get -o Dir::Cache::Archives="${STAGING_DIR}" -y install "${packages_to_install[@]}"
  fi

  if (( ${#REMOVE_PACKAGES[@]} > 0 )); then
    apt-get -y remove "${REMOVE_PACKAGES[@]}"
  fi
}

write_offline_artifact() {
  mkdir -p "${STORE_DIR}"
  cat > "${ARTIFACT_PATH}" <<'EOF'
{
  "offline_application_schema_version": 1,
  "kind": "offline-application",
  "operation_id": "w4-update-smoke-003",
  "target_version": "1.0.1-lab",
  "snapshot_name": "pre-update-w4-update-smoke-003",
  "boot_mode": "uefi",
  "kernel_package": "linux-image-amd64",
  "test_file_path": "/home/w4/update-proof.txt",
  "staging_directory": "/var/lib/w4-update/staging/w4-update-smoke-003"
}
EOF

  printf '%s
' "${REQUIRED_HEALTH_CHECKS[@]}" > "${HEALTH_CHECK_PATH}"
}

trap 'handle_error $? $LINENO' ERR
trap cleanup_temporary_apt_source EXIT

ensure_engine_scripts
require_command php

log "Iniciando aplicacion offline para ${OPERATION_ID}"
advance_stage "downloading"
maybe_fail "downloading"
check_free_space
stage_packages

advance_stage "ready"
maybe_fail "ready"

advance_stage "prepared"
maybe_fail "prepared"
create_snapshot

advance_stage "applying_offline"
maybe_fail "applying_offline"
apply_packages
write_offline_artifact

advance_stage "pending_health"
maybe_fail "pending_health"

log "Aplicacion offline completada; la operacion queda en pending_health"
log "Tras reiniciar, ejecutar run-health-checks.sh y luego reconcile-after-reboot.sh"
