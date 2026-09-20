#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ENGINE_ROOT="C:\\W4\\Packages\\W4-OS SYSTEM"
STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}/store}"
FAIL_STAGE="${W4_UPDATE_FAIL_STAGE:-}"
ADVANCE_SCRIPT="${ENGINE_ROOT}/scripts/advance_update_operation.php"
RECONCILE_SCRIPT="${ENGINE_ROOT}/scripts/reconcile_update_operation.php"
ARTIFACT_PATH="${STORE_DIR}/offline-application.json"
HEALTH_CHECK_PATH="${STORE_DIR}/health-checks.required.txt"

OPERATION_ID="w4-update-smoke-001"
TARGET_VERSION="1.0.1-lab"
SNAPSHOT_NAME="pre-update-w4-update-smoke-001"
BOOT_MODE="uefi"
KERNEL_PACKAGE="linux-image-amd64"

declare -a INSTALL_PACKAGES=('w4-recovery-tools')
declare -a UPGRADE_PACKAGES=('w4-base-meta' 'w4-home-meta' 'linux-image-amd64')
declare -a REMOVE_PACKAGES=()
declare -a REQUIRED_HEALTH_CHECKS=('boot-entry-present' 'cryptroot-unlock' 'root-subvolume-mounted' 'test-file-present')

log() {
  echo "[w4-update] $*" >&2
}

advance_stage() {
  local next_stage="$1"
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

write_offline_artifact() {
  mkdir -p "${STORE_DIR}"
  cat > "${ARTIFACT_PATH}" <<'EOF'
{
  "offline_application_schema_version": 1,
  "kind": "offline-application",
  "operation_id": "w4-update-smoke-001",
  "target_version": "1.0.1-lab",
  "snapshot_name": "pre-update-w4-update-smoke-001",
  "boot_mode": "uefi",
  "kernel_package": "linux-image-amd64"
}
EOF

  printf '%s
' "${REQUIRED_HEALTH_CHECKS[@]}" > "${HEALTH_CHECK_PATH}"
}

log "Iniciando aplicacion offline para ${OPERATION_ID}"
advance_stage "downloading"
maybe_fail "downloading"

advance_stage "ready"
maybe_fail "ready"

advance_stage "prepared"
maybe_fail "prepared"

write_offline_artifact

advance_stage "applying_offline"
maybe_fail "applying_offline"

advance_stage "pending_health"
maybe_fail "pending_health"

log "Aplicacion offline completada; la operacion queda en pending_health"
log "Tras reiniciar, ejecutar reconcile-after-reboot.sh con la salud observada"
