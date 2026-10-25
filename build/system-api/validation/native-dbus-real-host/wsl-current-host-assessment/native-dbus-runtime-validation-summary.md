# Native D-Bus Runtime Validation

- Status: `blocked`
- Exit code: `3`
- Ownership validated: `no`
- Coupling ready: `no`
- Startup acknowledged: `no`
- Preparation target OS family: `Linux`
- Preparation target repo root: `/mnt/c/W4/Packages/W4-OS SYSTEM`
- Host compatibility: `compatible`
- Native D-Bus extension installation: `unknown`
- Blocking reason: `missing-runtime-capabilities`
- Next action: `install-native-dbus-extension`
- Report path: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `n/a`
- Handoff path: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-preparation.txt`

## Host Compatibility

- Detected OS family: `Linux`
- Detected PHP version: `8.4.3`
- Required PHP constraint: `^8.4`
- Required PHP version: `8.4.0`

## Native D-Bus Extension Installation

- Installation state: `unknown`
- PECL available: `yes`
- Installation build phase: `unknown`
- Installation blocking reason: `n/a`
- Installation next action: `install-native-dbus-extension`
- Installation reason: `n/a`
- Installation failure signature: `n/a`
- Installation log path: `n/a`

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

1. `materialize-system-bus-socket` - Confirmar que el socket del system bus exista y que el servicio D-Bus del sistema este activo.

```bash
sudo systemctl status dbus
test -S /run/dbus/system_bus_socket && echo system-bus-socket-ready
```

