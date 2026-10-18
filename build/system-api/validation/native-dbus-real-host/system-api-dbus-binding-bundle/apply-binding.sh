#!/usr/bin/env bash
set -euo pipefail

if [[ $# -lt 1 ]]; then
  echo "Uso: $0 <rootfs-dir>" >&2
  exit 1
fi

ROOTFS_DIR="$1"
BUNDLE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
FILES_DIR="${BUNDLE_DIR}/files"

install -d "${ROOTFS_DIR}/usr/share/w4/system-api"
install -d "${ROOTFS_DIR}/usr/share/dbus-1/system-services"
install -d "${ROOTFS_DIR}/etc/dbus-1/system.d"
install -d "${ROOTFS_DIR}/etc/systemd/system"
install -d "${ROOTFS_DIR}/etc/w4"
install -d "${ROOTFS_DIR}/usr/local/lib/w4/system-api"
install -d "${ROOTFS_DIR}/usr/local/bin"

cp -f "${FILES_DIR}/usr/share/w4/system-api/org.w4.System1.Manager.xml" "${ROOTFS_DIR}/usr/share/w4/system-api/org.w4.System1.Manager.xml"
cp -f "${FILES_DIR}/usr/share/dbus-1/system-services/org.w4.System1.service" "${ROOTFS_DIR}/usr/share/dbus-1/system-services/org.w4.System1.service"
cp -f "${FILES_DIR}/etc/dbus-1/system.d/org.w4.System1.conf" "${ROOTFS_DIR}/etc/dbus-1/system.d/org.w4.System1.conf"
cp -f "${FILES_DIR}/etc/systemd/system/w4-system-api.service" "${ROOTFS_DIR}/etc/systemd/system/w4-system-api.service"
cp -f "${FILES_DIR}/etc/w4/system-api-binding.env" "${ROOTFS_DIR}/etc/w4/system-api-binding.env"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php"
cp -f "${FILES_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php" "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-bridge" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-adapter" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-adapter"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-host" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-host"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-launcher" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-launcher"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness"
cp -f "${FILES_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose" "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-bridge.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-adapter.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-host.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-launcher.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-bridge-readiness.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/lib/w4/system-api/w4-system-api-dbus-runtime-diagnose.php"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-adapter"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-host"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-launcher"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-bridge-readiness"
chmod 0755 "${ROOTFS_DIR}/usr/local/bin/w4-system-api-dbus-runtime-diagnose"

# The service remains disabled until the real D-Bus bridge exists in the target runtime.
