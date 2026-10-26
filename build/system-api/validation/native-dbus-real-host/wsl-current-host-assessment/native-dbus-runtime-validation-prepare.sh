#!/usr/bin/env bash
set -euo pipefail

# Native D-Bus runtime preparation helper for Linux
# Review each step before executing it on the target host.

# Step 1: inspect-native-runtime-artifacts
# Revisar los artefactos del runtime nativo y confirmar que startup, status y pid quedaron materializados antes del ownership.
ls -la "C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-current-host-assessment\runtime-artifacts"
cat "C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-current-host-assessment\runtime-artifacts\w4-system-api-native-runtime-status.json"

