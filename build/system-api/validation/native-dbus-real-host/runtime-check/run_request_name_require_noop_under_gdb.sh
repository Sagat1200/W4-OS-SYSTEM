#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${W4_SYSTEM_API_REMOTE_ROOT_DIR:-/home/w4live/w4-os-system}"
PHP_BINARY_PATH="${W4_SYSTEM_API_REMOTE_PHP_BINARY:-/usr/bin/php}"
DBUS_MODULE_PATH="${W4_SYSTEM_API_REMOTE_DBUS_MODULE:-$ROOT_DIR/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so}"
PROBE_SCRIPT_PATH="${W4_SYSTEM_API_REMOTE_HEAP_PROBE_SCRIPT:-$ROOT_DIR/build/system-api/validation/native-dbus-real-host/runtime-check/probe_native_dbus_heap_after_request_name.php}"
ARTIFACTS_DIR="${W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR:-$ROOT_DIR/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/heap-after-request-name}"
SCENARIO_NAME="${W4_SYSTEM_API_HEAP_PROBE_SCENARIO:-request_name_then_require_noop}"
LOG_BASENAME="${W4_SYSTEM_API_HEAP_PROBE_LOG_BASENAME:-gdb-$SCENARIO_NAME.log}"
LOG_PATH="$ARTIFACTS_DIR/$LOG_BASENAME"

mkdir -p "$ARTIFACTS_DIR"

exec gdb -batch \
  -ex "set pagination off" \
  -ex "run" \
  -ex "bt" \
  -ex "quit" \
  --args "$PHP_BINARY_PATH" \
    -d "extension=$DBUS_MODULE_PATH" \
    "$PROBE_SCRIPT_PATH" \
    --scenario="$SCENARIO_NAME" \
  >"$LOG_PATH" 2>&1
