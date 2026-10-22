#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="${W4_SYSTEM_API_REMOTE_ROOT_DIR:-/home/w4live/w4-os-system}"
SOURCE_DIR="${W4_SYSTEM_API_DBUS_FORK_SOURCE_DIR:-$ROOT_DIR/build/system-api/native-dbus-extension-fork/fork/current/dbus-0.1.1}"
TARGET_DIR="${W4_SYSTEM_API_DBUS_FORK_TARGET_DIR:-/home/w4live/dbus-0.1.1}"
OUTPUT_PATH="${W4_SYSTEM_API_REMOTE_DBUS_MODULE:-$TARGET_DIR/modules/dbus.so}"
APT_PACKAGES=(
  php8.4-dev
  build-essential
  pkg-config
  autoconf
  automake
  libtool
  libdbus-1-dev
  libxml2-dev
)

if [[ ! -d "$SOURCE_DIR" ]]; then
  printf '{\n  "ok": false,\n  "error": "dbus-fork-source-missing",\n  "source_dir": "%s"\n}\n' "$SOURCE_DIR"
  exit 1
fi

sudo -n apt-get update
sudo -n DEBIAN_FRONTEND=noninteractive apt-get install -y "${APT_PACKAGES[@]}"

rm -rf "$TARGET_DIR"
mkdir -p "$(dirname "$TARGET_DIR")"
cp -a "$SOURCE_DIR" "$TARGET_DIR"

cd "$TARGET_DIR"
phpize
./configure
make -j"$(nproc)"

if [[ ! -f "$OUTPUT_PATH" ]]; then
  printf '{\n  "ok": false,\n  "error": "dbus-module-build-missing-output",\n  "output_path": "%s"\n}\n' "$OUTPUT_PATH"
  exit 1
fi

php -d "extension=$OUTPUT_PATH" -m >/tmp/w4-dbus-module-check.stdout 2>/tmp/w4-dbus-module-check.stderr || true

printf '{\n'
printf '  "ok": true,\n'
printf '  "source_dir": "%s",\n' "$SOURCE_DIR"
printf '  "target_dir": "%s",\n' "$TARGET_DIR"
printf '  "output_path": "%s",\n' "$OUTPUT_PATH"
printf '  "module_check_stdout_path": "%s",\n' "/tmp/w4-dbus-module-check.stdout"
printf '  "module_check_stderr_path": "%s"\n' "/tmp/w4-dbus-module-check.stderr"
printf '}\n'
