# Native D-Bus Runtime Validation

- Status: `blocked`
- Exit code: `3`
- Ownership validated: `no`
- Coupling ready: `no`
- Startup acknowledged: `no`
- Preparation target OS family: `Linux`
- Preparation target repo root: `/opt/w4/w4-os-system`
- Host compatibility: `compatible`
- Native D-Bus extension installation: `buildable`
- Blocking reason: `missing-runtime-capabilities`
- Next action: `install-native-dbus-extension`
- Report path: `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json`
- Handoff path: `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-preparation.txt`

## Host Compatibility

- Detected OS family: `Windows`
- Detected PHP version: `8.4.3`
- Required PHP constraint: `^8.4`
- Required PHP version: `8.4.0`

## Native D-Bus Extension Installation

- Installation state: `buildable`
- PECL available: `yes`
- Installation build phase: `make`
- Installation blocking reason: `n/a`
- Installation next action: `install-native-dbus-extension`
- Installation reason: `build-complete`
- Installation failure signature: ``
- Installation log path: `build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log`

## Recommended Next Actions

- `install-native-dbus-extension`

## Preparation Plan

1. `install-native-dbus-extension` - Instalar y habilitar la extension PECL `dbus` para que PHP pueda pedir el bus name y exportar el objeto D-Bus.

```bash
sudo apt-get update
sudo apt-get install -y php-pear php-dev libdbus-1-dev pkg-config
sudo pecl install dbus
php --ri dbus
```

1. `install-dbus-daemon` - Asegurar que `dbus-daemon` este instalado y disponible en el host.

```bash
sudo apt-get update
sudo apt-get install -y dbus
dbus-daemon --version
```

1. `materialize-system-bus-socket` - Confirmar que el socket del system bus exista y que el servicio D-Bus del sistema este activo.

```bash
sudo systemctl status dbus
test -S /run/dbus/system_bus_socket && echo system-bus-socket-ready
```

1. `install-dbus-client-tooling` - Instalar al menos una herramienta de cliente D-Bus (`busctl` o `gdbus`) para contrastar el runtime desde el host.

```bash
sudo apt-get update
sudo apt-get install -y systemd dbus
busctl --version || gdbus --version
```

1. `retry-native-bus-ownership` - Reintentar el ownership real del bus solo cuando el host confirme extension PECL dbus, daemon, socket y tooling del sistema disponibles.

```bash
printf '%s\n' "Preserva la evidencia actual si solo vas a reinspeccionar el mismo intento de compilacion."
test -f "build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json" && cp "build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json" "build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).json" || true
test -f "build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log" && cp "build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log" "build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).log" || true
printf '%s\n' "Despues de un build real nuevo, elimina el JSON previo para forzar una rematerializacion limpia en el siguiente rerun."
cd "/opt/w4/w4-os-system" && rm -f "build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log
```

