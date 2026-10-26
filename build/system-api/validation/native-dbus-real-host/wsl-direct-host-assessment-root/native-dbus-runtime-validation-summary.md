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
- Next action: `start-native-runtime-service`
- Report path: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json`
- Handoff path: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-runtime-validation-preparation.txt`

## Host Compatibility

- Detected OS family: `Linux`
- Detected PHP version: `8.4.3`
- Required PHP constraint: `^8.4`
- Required PHP version: `8.4.0`

## Native D-Bus Extension Installation

- Installation state: `buildable`
- PECL available: `yes`
- Installation build phase: `make`
- Installation blocking reason: `n/a`
- Installation next action: `observe-runtime-backend`
- Installation reason: `build-complete`
- Installation failure signature: ``
- Installation log path: `build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log`

## Recommended Next Actions

- `start-native-runtime-service`

## Preparation Plan

1. `materialize-system-bus-socket` - Confirmar que el socket del system bus exista y que el servicio D-Bus del sistema este activo.

```bash
sudo systemctl status dbus
test -S /run/dbus/system_bus_socket && echo system-bus-socket-ready
```

1. `retry-native-bus-ownership` - Reintentar el ownership real del bus solo cuando el host confirme extension PECL dbus, daemon, socket y tooling del sistema disponibles.

```bash
printf '%s\n' "Preserva la evidencia actual si solo vas a reinspeccionar el mismo intento de compilacion."
test -f "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json" && cp "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json" "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).json" || true
test -f "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log" && cp "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log" "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).log" || true
printf '%s\n' "Despues de un build real nuevo, elimina el JSON previo para forzar una rematerializacion limpia en el siguiente rerun."
cd "/opt/w4/w4-os-system" && rm -f "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment-root/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
```

