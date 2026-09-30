#!/usr/bin/env bash
set -euo pipefail

PROFILE_ENV="/etc/w4/profile.env"
LIVE_USER_DEFAULT="w4live"
LIVE_HOSTNAME_DEFAULT="w4-server-live"
PROFILE_ID="w4-os-server"

if [[ -f "${PROFILE_ENV}" ]]; then
  # shellcheck disable=SC1091
  . "${PROFILE_ENV}"
fi

is_live_mode() {
  if [[ "${W4_FORCE_LIVE:-0}" == "1" ]]; then
    return 0
  fi

  if [[ -e /run/live/medium ]] || [[ -e /lib/live/mount/medium ]]; then
    return 0
  fi

  if grep -Eq '(^| )boot=live( |$)|(^| )w4.live=1( |$)' /proc/cmdline 2>/dev/null; then
    return 0
  fi

  return 1
}

if ! is_live_mode; then
  exit 0
fi

LIVE_USER="${W4_LIVE_USER:-${LIVE_USER_DEFAULT}}"
LIVE_HOSTNAME="${W4_LIVE_HOSTNAME:-${LIVE_HOSTNAME_DEFAULT}}"

printf '%s\n' "${LIVE_HOSTNAME}" > /etc/hostname
mkdir -p /run/w4
printf '%s\n' "live" > /run/w4/session-mode

if ! id "${LIVE_USER}" >/dev/null 2>&1; then
  useradd -m -s /bin/bash "${LIVE_USER}"
fi

usermod -aG sudo,audio,video,plugdev,netdev "${LIVE_USER}" >/dev/null 2>&1 || true

mkdir -p /etc/systemd/system/getty@tty1.service.d
cat > /etc/systemd/system/getty@tty1.service.d/autologin.conf <<EOF
[Service]
ExecStart=
ExecStart=-/sbin/agetty --autologin ${LIVE_USER} --noclear %I \$TERM
EOF

mkdir -p /etc/w4
cat > /etc/w4/live-state.env <<EOF
W4_PROFILE_ID="${W4_PROFILE_ID:-${PROFILE_ID}}"
W4_LIVE_USER="${LIVE_USER}"
W4_LIVE_HOSTNAME="${LIVE_HOSTNAME}"
W4_LIVE_PREPARED_AT="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
EOF
