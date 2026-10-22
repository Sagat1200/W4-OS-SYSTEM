#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${W4_SYSTEM_API_REMOTE_ROOT_DIR:-/home/w4live/w4-os-system}"
PHP_BINARY_PATH="${W4_SYSTEM_API_REMOTE_PHP_BINARY:-/usr/bin/php}"
DBUS_MODULE_PATH="${W4_SYSTEM_API_REMOTE_DBUS_MODULE:-/home/w4live/dbus-0.1.1/modules/dbus.so}"
ADAPTER_SCRIPT_PATH="${W4_SYSTEM_API_REMOTE_ADAPTER_SCRIPT:-$ROOT_DIR/scripts/system_api_dbus_runtime_adapter.php}"
ARTIFACTS_DIR="${W4_SYSTEM_API_REMOTE_ARTIFACTS_DIR:-$ROOT_DIR/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/direct-serve}"

mkdir -p "$ARTIFACTS_DIR"

STDOUT_PATH="$ARTIFACTS_DIR/serve-native-runtime-service.stdout.json"
STDERR_PATH="$ARTIFACTS_DIR/serve-native-runtime-service.stderr.log"
EXIT_CODE_PATH="$ARTIFACTS_DIR/serve-native-runtime-service.exit_code"

export W4_SYSTEM_API_RUNTIME_BACKEND="${W4_SYSTEM_API_RUNTIME_BACKEND:-native-dbus-extension}"
export W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS="${W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS:-5}"
export W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER="$ARTIFACTS_DIR/w4-system-api-native-runtime.ready"
export W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE="$ARTIFACTS_DIR/w4-system-api-native-runtime-status.json"
export W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE="$ARTIFACTS_DIR/w4-system-api-native-runtime.pid"

rm -f \
    "$STDOUT_PATH" \
    "$STDERR_PATH" \
    "$EXIT_CODE_PATH" \
    "$W4_SYSTEM_API_NATIVE_RUNTIME_READY_MARKER" \
    "$W4_SYSTEM_API_NATIVE_RUNTIME_STATUS_FILE" \
    "$W4_SYSTEM_API_NATIVE_RUNTIME_PID_FILE"

if "$PHP_BINARY_PATH" -d "extension=$DBUS_MODULE_PATH" "$ADAPTER_SCRIPT_PATH" ServeNativeRuntimeService >"$STDOUT_PATH" 2>"$STDERR_PATH"; then
    EXIT_CODE=0
else
    EXIT_CODE=$?
fi

printf '%s\n' "$EXIT_CODE" >"$EXIT_CODE_PATH"
cat "$STDOUT_PATH"

exit "$EXIT_CODE"
