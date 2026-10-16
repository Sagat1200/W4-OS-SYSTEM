#!/usr/bin/env bash
set -euo pipefail

# Native D-Bus runtime preparation helper for Windows
# Review each step before executing it on the target host.

# Step 1: install-native-dbus-extension
# Instalar y habilitar la extension PECL `dbus` para que PHP pueda pedir el bus name y exportar el objeto D-Bus.
echo "Resolver manualmente la capacidad faltante `php-dbus-runtime-adapter` para el sistema operativo `Windows`."

# Step 2: install-dbus-daemon
# Asegurar que `dbus-daemon` este instalado y disponible en el host.
echo "Resolver manualmente la capacidad faltante `dbus-daemon` para el sistema operativo `Windows`."

# Step 3: materialize-system-bus-socket
# Confirmar que el socket del system bus exista y que el servicio D-Bus del sistema este activo.
echo "Resolver manualmente la capacidad faltante `system-bus-socket` para el sistema operativo `Windows`."

# Step 4: install-dbus-client-tooling
# Instalar al menos una herramienta de cliente D-Bus (`busctl` o `gdbus`) para contrastar el runtime desde el host.
echo "Resolver manualmente la capacidad faltante `dbus-client-tooling` para el sistema operativo `Windows`."

# Step 5: retry-native-bus-ownership
# Reintentar el ownership real del bus solo cuando el host confirme extension PECL dbus, daemon, socket y tooling del sistema disponibles.
echo "php scripts/run_native_dbus_real_host_validation.php --format text"

