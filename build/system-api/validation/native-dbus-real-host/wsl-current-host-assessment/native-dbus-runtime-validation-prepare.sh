#!/usr/bin/env bash
set -euo pipefail

# Native D-Bus runtime preparation helper for Linux
# Review each step before executing it on the target host.

# Step 1: install-native-dbus-extension
# Instalar y habilitar la extension PECL `dbus` para que PHP pueda pedir el bus name y exportar el objeto D-Bus.
sudo apt-get update
sudo apt-get install -y php-pear php-dev libdbus-1-dev pkg-config
sudo pecl install dbus
php --ri dbus

# Step 2: materialize-system-bus-socket
# Confirmar que el socket del system bus exista y que el servicio D-Bus del sistema este activo.
sudo systemctl status dbus
test -S /run/dbus/system_bus_socket && echo system-bus-socket-ready

