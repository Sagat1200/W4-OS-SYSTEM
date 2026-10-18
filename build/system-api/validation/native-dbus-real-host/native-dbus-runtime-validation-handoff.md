# Native D-Bus Runtime Handoff

- Target OS family: `Linux`
- Target repo root: `/opt/w4/w4-os-system`
- Current status: `blocked`
- Blocking reason: `missing-runtime-capabilities`
- Preparation script: `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-preparation.txt`

## Target Host Workflow

1. Prepare the target host with the generated checklist or shell script.
2. Re-run the native validation commands from the repository root on the target host.
3. Verify that `org.w4.System1` is visible on the system bus after ownership succeeds.

## Evidence Policy

- Reutiliza `--evidence-dir build/system-api/validation/native-dbus-real-host` mientras sigas diagnosticando el mismo intento de compilacion.
- Conserva `--native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json` cuando el rerun solo valida o reinspecciona la evidencia ya capturada.
- Si vuelves a ejecutar una compilacion real de `pecl dbus`, regenera `native-dbus-extension-build-evidence.json` antes del siguiente rerun para no mezclar evidencia vieja con un intento nuevo.
- Conserva tambien `--native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log` cuando el rerun deba rematerializar el JSON estructurado desde el log crudo del intento real.

## Preparation Shortcuts

```bash
cd "/opt/w4/w4-os-system" && cat "build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-preparation.txt"
cd "/opt/w4/w4-os-system" && chmod +x "build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-prepare.sh" && bash "build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-prepare.sh"
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


## Re-run Commands

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

## Post-Install Verification

```bash
php --ri dbus
busctl --system list | grep org.w4.System1 || true
busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager
```

