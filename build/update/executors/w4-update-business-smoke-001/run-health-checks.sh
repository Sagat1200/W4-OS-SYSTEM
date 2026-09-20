#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
STORE_DIR="${W4_UPDATE_STORE_DIR:-${SCRIPT_DIR}/store}"
RESULT_PATH="${W4_UPDATE_HEALTH_RESULT_PATH:-${STORE_DIR}/health-check-results.json}"
TEST_FILE_PATH="${W4_UPDATE_TEST_FILE_PATH:-/home/w4admin/update-proof.txt}"
KERNEL_PACKAGE="${W4_UPDATE_KERNEL_PACKAGE:-linux-image-amd64}"

declare -a REQUIRED_HEALTH_CHECKS=('boot-entry-present' 'cryptroot-unlock' 'root-subvolume-mounted' 'test-file-present')
declare -A CHECK_RESULTS=()

run_check() {
  local check_name="$1"

  case "${check_name}" in
    boot-entry-present)
      [[ -s /boot/grub/grub.cfg || -s /boot/efi/EFI/BOOT/BOOTX64.EFI ]]
      ;;
    cryptroot-unlock)
      findmnt -n -o SOURCE / | grep -Eq '^/dev/mapper/|cryptroot'
      ;;
    root-subvolume-mounted)
      findmnt -n -o OPTIONS / | grep -q 'subvol='
      ;;
    test-file-present)
      [[ -e "${TEST_FILE_PATH}" ]]
      ;;
    dpkg-consistent)
      ! dpkg --audit 2>/dev/null | grep -q .
      ;;
    kernel-package-installed)
      dpkg-query -W -f='${Status}' "${KERNEL_PACKAGE}" 2>/dev/null | grep -q 'install ok installed'
      ;;
    *)
      return 2
      ;;
  esac
}

overall_status="ok"
mkdir -p "${STORE_DIR}"

for check_name in "${REQUIRED_HEALTH_CHECKS[@]}"; do
  if run_check "${check_name}"; then
    CHECK_RESULTS["${check_name}"]="ok"
    continue
  fi

  exit_code="$?"
  if [[ "${exit_code}" == "2" ]]; then
    CHECK_RESULTS["${check_name}"]="unsupported"
  else
    CHECK_RESULTS["${check_name}"]="failed"
  fi

  overall_status="failed"
done

{
  printf '{
'
  printf '  "health_check_result_schema_version": 1,
'
  printf '  "kind": "health-check-results",
'
  printf '  "status": "%s",
' "${overall_status}"
  printf '  "results": {
'

  index=0
  total="${#REQUIRED_HEALTH_CHECKS[@]}"
  for check_name in "${REQUIRED_HEALTH_CHECKS[@]}"; do
    index=$((index + 1))
    separator=','
    if (( index == total )); then
      separator=''
    fi
    printf '    "%s": "%s"%s
' "${check_name}" "${CHECK_RESULTS[${check_name}]}" "${separator}"
  done

  printf '  }
'
  printf '}
'
} > "${RESULT_PATH}"

if [[ "${overall_status}" == "ok" ]]; then
  exit 0
fi

exit 1
