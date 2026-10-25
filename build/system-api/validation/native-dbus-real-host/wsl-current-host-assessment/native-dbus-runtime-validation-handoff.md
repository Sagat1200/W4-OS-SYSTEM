# Native D-Bus Runtime Handoff

- Target OS family: `Linux`
- Target repo root: `/mnt/c/W4/Packages/W4-OS SYSTEM`
- Current status: `blocked`
- Blocking reason: `missing-runtime-capabilities`
- Preparation script: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-preparation.txt`

## Target Host Workflow

1. Prepare the target host with the generated checklist or shell script.
2. Re-run the native validation commands from the repository root on the target host.
3. Verify that `org.w4.System1` is visible on the system bus after ownership succeeds.

## Evidence Policy

- Reutiliza `--evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment` mientras sigas diagnosticando el mismo intento de compilacion.
- Si generas un nuevo intento real de compilacion, actualiza o regenera `native-dbus-extension-build-evidence.json` antes del siguiente rerun.

## Preparation Shortcuts

```bash
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && cat "build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-preparation.txt"
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && chmod +x "build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh" && bash "build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh"
```

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


## Re-run Commands

```bash
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
```

## Post-Install Verification

```bash
php --ri dbus
busctl --system list | grep org.w4.System1 || true
busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager
```

