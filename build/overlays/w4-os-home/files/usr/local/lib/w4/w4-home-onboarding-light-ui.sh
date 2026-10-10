#!/usr/bin/env bash
set -euo pipefail

PROFILE_ENV="/etc/w4/profile.env"
BUNDLE_DIR="/usr/share/w4/home-onboarding"
ENTRY_FILE="${BUNDLE_DIR}/index.html"
STATE_DIR="${HOME:-/tmp}/.config/w4"
STATE_FILE="${STATE_DIR}/home-onboarding-light-ui-seen"
SESSION_GUARD="/run/w4/home-onboarding-light-ui-launched"

if [[ -f "${PROFILE_ENV}" ]]; then
  # shellcheck disable=SC1091
  . "${PROFILE_ENV}"
fi

has_onboarding_feature() {
  case ",${W4_FEATURES:-}," in
    *,home-onboarding,*) return 0 ;;
    *) return 1 ;;
  esac
}

is_candidate_session() {
  if [[ "${W4_HOME_ONBOARDING_FORCE:-0}" == "1" ]]; then
    return 0
  fi

  if [[ -f /run/w4/session-mode ]] && grep -qx 'live' /run/w4/session-mode 2>/dev/null; then
    return 0
  fi

  if [[ -f /etc/w4/firstboot-state.env ]]; then
    return 0
  fi

  return 1
}

open_entry() {
  local entry_uri="file://${ENTRY_FILE}"

  if command -v xdg-open >/dev/null 2>&1; then
    xdg-open "${entry_uri}" >/dev/null 2>&1 &
    return 0
  fi

  if command -v gio >/dev/null 2>&1; then
    gio open "${entry_uri}" >/dev/null 2>&1 &
    return 0
  fi

  if command -v firefox-esr >/dev/null 2>&1; then
    firefox-esr "${entry_uri}" >/dev/null 2>&1 &
    return 0
  fi

  return 1
}

if ! has_onboarding_feature; then
  exit 0
fi

if ! is_candidate_session; then
  exit 0
fi

if [[ ! -f "${ENTRY_FILE}" ]]; then
  exit 0
fi

if [[ -f "${STATE_FILE}" ]] && [[ "${W4_HOME_ONBOARDING_FORCE:-0}" != "1" ]]; then
  exit 0
fi

if [[ -f "${SESSION_GUARD}" ]] && [[ "${W4_HOME_ONBOARDING_FORCE:-0}" != "1" ]]; then
  exit 0
fi

mkdir -p "${STATE_DIR}" /run/w4
printf '%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "${SESSION_GUARD}"

if open_entry; then
  printf '%s\n' "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > "${STATE_FILE}"
fi
