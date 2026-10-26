#!/usr/bin/env bash
set -euo pipefail

# Native D-Bus runtime preparation helper for Linux
# Review each step before executing it on the target host.

# Step 1: capture-release-evidence
# Conservar el reporte y los artefactos como evidencia para el cierre de la validacion V1 del runtime D-Bus nativo.
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux

