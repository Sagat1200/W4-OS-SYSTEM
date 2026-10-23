#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${W4_SYSTEM_API_REMOTE_ROOT_DIR:-/home/w4live/w4-os-system}"
ARTIFACTS_DIR="${W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR:-$ROOT_DIR/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/busctl-request-name}"
BUS_NAME="${W4_SYSTEM_API_REMOTE_BUS_NAME:-org.w4.System1}"

mkdir -p "$ARTIFACTS_DIR"

run_command() {
  local name="$1"
  shift

  local stdout_path="$ARTIFACTS_DIR/$name.stdout"
  local stderr_path="$ARTIFACTS_DIR/$name.stderr"
  local exit_code_path="$ARTIFACTS_DIR/$name.exit_code"

  rm -f "$stdout_path" "$stderr_path" "$exit_code_path"

  if "$@" >"$stdout_path" 2>"$stderr_path"; then
    exit_code=0
  else
    exit_code=$?
  fi

  printf '%s\n' "$exit_code" >"$exit_code_path"
}

run_command id id
run_command busctl-name-has-owner \
  busctl --system call org.freedesktop.DBus /org/freedesktop/DBus org.freedesktop.DBus NameHasOwner s "$BUS_NAME"
run_command busctl-list-queued-owners \
  busctl --system call org.freedesktop.DBus /org/freedesktop/DBus org.freedesktop.DBus ListQueuedOwners s "$BUS_NAME"
run_command busctl-request-name \
  busctl --system call org.freedesktop.DBus /org/freedesktop/DBus org.freedesktop.DBus RequestName su "$BUS_NAME" 0
run_command busctl-get-name-owner \
  busctl --system call org.freedesktop.DBus /org/freedesktop/DBus org.freedesktop.DBus GetNameOwner s "$BUS_NAME"

cat <<EOF
{
  "ok": true,
  "artifacts_dir": "$ARTIFACTS_DIR",
  "bus_name": "$BUS_NAME"
}
EOF
