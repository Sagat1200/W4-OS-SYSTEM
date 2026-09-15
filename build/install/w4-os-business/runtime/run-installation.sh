#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="\${SCRIPT_DIR}/install.env"

[[ -f "\${ENV_FILE}" ]] || {
  echo "ERROR: falta \${ENV_FILE}" >&2
  exit 1
}

# shellcheck disable=SC1090
source "\${ENV_FILE}"
bash "${SCRIPT_DIR}/../apply-installation.sh"
