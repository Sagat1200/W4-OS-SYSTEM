#!/usr/bin/env bash
set -euo pipefail

# Native D-Bus runtime preparation helper for Linux
# Review each step before executing it on the target host.

# Step 1: materialize-system-bus-socket
# Confirmar que el socket del system bus exista y que el servicio D-Bus del sistema este activo.
sudo systemctl status dbus
test -S /run/dbus/system_bus_socket && echo system-bus-socket-ready

# Step 2: retry-native-bus-ownership
# Reintentar el ownership real del bus solo cuando el host confirme extension PECL dbus, daemon, socket y tooling del sistema disponibles.
printf '%s\n' "Preserva la evidencia actual si solo vas a reinspeccionar el mismo intento de compilacion."
test -f "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json" && cp "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json" "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).json" || true
test -f "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log" && cp "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log" "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).log" || true
printf '%s\n' "Despues de un build real nuevo, elimina el JSON previo para forzar una rematerializacion limpia en el siguiente rerun."
cd "/opt/w4/w4-os-system" && rm -f "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log

