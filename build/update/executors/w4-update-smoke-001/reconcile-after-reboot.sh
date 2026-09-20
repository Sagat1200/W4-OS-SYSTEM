#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
ENGINE_ROOT="C:\\W4\\Packages\\W4-OS SYSTEM"
STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}/store}"
OBSERVED_STAGE="${W4_UPDATE_OBSERVED_STAGE:-confirmed}"
DETAIL="${W4_UPDATE_RECONCILE_DETAIL:-post-reboot-check}"
ERROR_CODE="${W4_UPDATE_ERROR_CODE:-health-check-failed}"
ERROR_MESSAGE="${W4_UPDATE_ERROR_MESSAGE:-La validacion post-arranque fallo}"

COMMAND=(
  php "${ENGINE_ROOT}/scripts/reconcile_update_operation.php"
  --store-dir "${STORE_DIR}"
  --observed-stage "${OBSERVED_STAGE}"
  --component "update-post-boot-check"
  --detail "${DETAIL}"
)

if [[ "${OBSERVED_STAGE}" == "failed" ]]; then
  COMMAND+=(
    --error-code "${ERROR_CODE}"
    --error-message "${ERROR_MESSAGE}"
  )
fi

"${COMMAND[@]}"
