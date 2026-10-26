# Native D-Bus Runtime Validation

- Status: `ownership-validated`
- Exit code: `0`
- Ownership validated: `yes`
- Coupling ready: `yes`
- Startup acknowledged: `yes`
- Preparation target OS family: `Linux`
- Preparation target repo root: `/opt/w4/w4-os-system`
- Host compatibility: `compatible`
- Native D-Bus extension installation: `buildable`
- Blocking reason: `n/a`
- Next action: `runtime-service-active`
- Report path: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json`
- Handoff path: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-preparation.txt`

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
- `observe-native-runtime-service-state`
- `materialize-native-bus-ownership`
- `runtime-service-active`

## Preparation Plan

1. `capture-release-evidence` - Conservar el reporte y los artefactos como evidencia para el cierre de la validacion V1 del runtime D-Bus nativo.

```bash
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux
```

