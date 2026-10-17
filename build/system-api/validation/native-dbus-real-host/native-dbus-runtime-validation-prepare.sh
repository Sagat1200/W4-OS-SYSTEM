#!/usr/bin/env bash
set -euo pipefail

# Native D-Bus runtime preparation helper for Linux
# Review each step before executing it on the target host.

# Step 1: inspect-native-dbus-build-make
# La extension `dbus` ya avanzo hasta `make`, pero la compilacion fallo. Conviene inspeccionar el toolchain C y la evidencia del error de compilacion antes de seguir insistiendo con PECL.
php --version
phpize --version || true
lsb_release -a || cat /etc/os-release
pecl version
pecl channel-info pecl.php.net || true
pecl channel-update pecl.php.net || true
pecl remote-info pecl/dbus || true
pecl remote-info channel://pecl.php.net/dbus-0.1.1
pecl search dbus || true
make --version || true
gcc --version || true
printf '%s\\n' "Ultimo motivo reportado: zend_object_store_get_object"
printf '%s\\n' "Firma detectada del fallo: make: *** [Makefile:192: dbus.lo] Error 1"
test -f "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" && tail -n 80 "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" || echo "Build log no disponible: build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md"
cd "/srv/w4/os-system" && php -r "echo json_decode(file_get_contents('composer.json'), true)['require']['php'], PHP_EOL;"

# Step 2: migrate-to-modern-linux-host
# Preparar o elegir un Linux moderno homologado con PHP `8.4+`, toolchain de compilacion y dependencias D-Bus antes de repetir el contraste nativo real.
lsb_release -a || cat /etc/os-release
php --version || true
printf '%s\\n' "Objetivo: host Linux moderno con PHP 8.4+ y toolchain homologada para PECL dbus."
sudo apt-get update
sudo apt-cache policy php8.4-cli php8.4-dev php8.4-pear
sudo apt-get install -y software-properties-common ca-certificates lsb-release apt-transport-https gnupg || true
sudo apt-get install -y build-essential autoconf automake libtool re2c pkg-config libdbus-1-dev libxml2-dev dbus dbus-user-session
sudo apt-get install -y php8.4-cli php8.4-dev php8.4-pear
php --version
phpize --version || true
pecl version || true
busctl --version || true
cd "/srv/w4/os-system" && php -r "echo json_decode(file_get_contents('composer.json'), true)['require']['php'], PHP_EOL;"

# Step 3: rerun-native-runtime-validation
# Reejecutar el runner despues de corregir la instalacion o compilacion de la extension `dbus` para confirmar si el ownership ya puede progresar.
printf '%s\n' "Preserva la evidencia actual si solo vas a reinspeccionar el mismo intento de compilacion."
test -f "build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json" && cp "build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json" "build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).json" || true
test -f "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" && cp "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).log" || true
printf '%s\n' "Despues de un build real nuevo, elimina el JSON previo para forzar una rematerializacion limpia en el siguiente rerun."
cd "/srv/w4/os-system" && rm -f "build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md

