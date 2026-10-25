#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
DEFAULT_ENGINE_ROOT="$(cd -- "${SCRIPT_DIR}/../../../.." 2>/dev/null && pwd || true)"
ENGINE_ROOT="${W4_UPDATE_ENGINE_ROOT:-${DEFAULT_ENGINE_ROOT}}"
STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}/store}"
OBSERVED_STAGE="${W4_UPDATE_OBSERVED_STAGE:-}"
DETAIL="${W4_UPDATE_RECONCILE_DETAIL:-post-reboot-check}"
ERROR_CODE="${W4_UPDATE_ERROR_CODE:-health-check-failed}"
ERROR_MESSAGE="${W4_UPDATE_ERROR_MESSAGE:-La validacion post-arranque fallo}"
AUTO_HEALTHCHECK="${W4_UPDATE_AUTO_HEALTHCHECK:-1}"
HEALTH_SCRIPT="${SCRIPT_DIR}/run-health-checks.sh"
HEALTH_RESULT_PATH="${STORE_DIR}/health-check-results.json"

if [[ -z "${OBSERVED_STAGE}" && "${AUTO_HEALTHCHECK}" == "1" ]]; then
  if "${HEALTH_SCRIPT}"; then
    OBSERVED_STAGE="confirmed"
    DETAIL="${DETAIL};health=ok"
  else
    OBSERVED_STAGE="failed"
    DETAIL="${DETAIL};health=failed"
  fi
fi

OBSERVED_STAGE="${OBSERVED_STAGE:-confirmed}"

COMMAND=(
  php "${ENGINE_ROOT}/scripts/reconcile_update_operation.php"
  --store-dir "${STORE_DIR}"
  --observed-stage "${OBSERVED_STAGE}"
  --component "update-post-boot-check"
  --detail "${DETAIL}"
  --detail "health_result=${HEALTH_RESULT_PATH}"
)

if [[ "${OBSERVED_STAGE}" == "failed" ]]; then
  COMMAND+=(
    --error-code "${ERROR_CODE}"
    --error-message "${ERROR_MESSAGE}"
  )
fi

"${COMMAND[@]}"
