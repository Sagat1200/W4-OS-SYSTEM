#!/usr/bin/env bash
set -euo pipefail

STATE_DIR="/var/lib/w4"
STATE_FILE="${STATE_DIR}/firstboot-complete"
PROFILE_ENV="/etc/w4/profile.env"
DEFAULT_HOSTNAME="w4-home"
DEFAULT_TARGET="graphical.target"
PROFILE_ID="w4-os-home"

if [[ -f "${PROFILE_ENV}" ]]; then
  # shellcheck disable=SC1091
  . "${PROFILE_ENV}"
fi

if [[ -f "${STATE_FILE}" ]] && [[ "${W4_FIRSTBOOT_FORCE:-0}" != "1" ]]; then
  exit 0
fi

mkdir -p "${STATE_DIR}"

if [[ ! -s /etc/machine-id ]]; then
  systemd-machine-id-setup >/dev/null 2>&1 || dbus-uuidgen --ensure=/etc/machine-id >/dev/null 2>&1 || true
fi

TARGET_HOSTNAME="${W4_HOSTNAME:-${DEFAULT_HOSTNAME}}"
TARGET_DEFAULT="${W4_DEFAULT_TARGET:-${DEFAULT_TARGET}}"
CURRENT_HOSTNAME="$(cat /etc/hostname 2>/dev/null || true)"

if [[ -z "${CURRENT_HOSTNAME}" ]] || [[ "${CURRENT_HOSTNAME}" == "localhost" ]] || [[ "${CURRENT_HOSTNAME}" == "debian" ]]; then
  printf '%s\n' "${TARGET_HOSTNAME}" > /etc/hostname
fi

if command -v aa-enabled >/dev/null 2>&1; then
  aa-enabled >/dev/null 2>&1 || true
fi

if command -v systemctl >/dev/null 2>&1; then
  if [[ -n "${TARGET_DEFAULT}" ]]; then
    systemctl set-default "${TARGET_DEFAULT}" >/dev/null 2>&1 || true
  fi

  if systemctl list-unit-files apparmor.service >/dev/null 2>&1; then
    systemctl enable apparmor.service >/dev/null 2>&1 || true
    systemctl start apparmor.service >/dev/null 2>&1 || true
  fi
fi

if command -v ufw >/dev/null 2>&1; then
  ufw --force reset >/dev/null 2>&1 || true
  ufw default deny incoming >/dev/null 2>&1 || true
  ufw default allow outgoing >/dev/null 2>&1 || true
  ufw --force enable >/dev/null 2>&1 || true
fi

if command -v dconf >/dev/null 2>&1 && [[ -d /etc/dconf/db ]]; then
  dconf update >/dev/null 2>&1 || true
fi

mkdir -p /etc/w4
cat > /etc/w4/firstboot-state.env <<EOF
W4_PROFILE_ID="${W4_PROFILE_ID:-${PROFILE_ID}}"
W4_HOSTNAME_APPLIED="${TARGET_HOSTNAME}"
W4_DEFAULT_TARGET_APPLIED="${TARGET_DEFAULT}"
W4_FIRSTBOOT_COMPLETED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF

printf '%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "${STATE_FILE}"
