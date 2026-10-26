# Native D-Bus Runtime Validation

- Status: `ownership-validated`
- Exit code: `0`
- Ownership validated: `yes`
- Coupling ready: `no`
- Startup acknowledged: `yes`
- Preparation target OS family: `Linux`
- Preparation target repo root: `/opt/w4/w4-os-system`
- Host compatibility: `compatible`
- Native D-Bus extension installation: `installed`
- Blocking reason: `n/a`
- Next action: `runtime-service-active`
- Report path: `build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `n/a`
- Handoff path: `build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-preparation.txt`

## Host Compatibility

- Detected OS family: `Linux`
- Detected PHP version: `8.4.3`
- Required PHP constraint: `^8.4`
- Required PHP version: `8.4.0`

## Native D-Bus Extension Installation

- Installation state: `installed`
- PECL available: `no`
- Installation build phase: `unknown`
- Installation blocking reason: `n/a`
- Installation next action: `observe-runtime-backend`
- Installation reason: `n/a`
- Installation failure signature: `n/a`
- Installation log path: `n/a`

## Recommended Next Actions

- `start-native-runtime-service`
- `observe-native-runtime-service-state`
- `runtime-service-active`
- `inspect-native-runtime-coupling`

## Preparation Plan

1. `inspect-native-runtime-artifacts` - Revisar los artefactos del runtime nativo y confirmar que startup, status y pid quedaron materializados antes del ownership.

```bash
ls -la "build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host\runtime-artifacts"
cat "build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host\runtime-artifacts\w4-system-api-native-runtime-status.json"
```

1. `capture-release-evidence` - Conservar el reporte y los artefactos como evidencia para el cierre de la validacion V1 del runtime D-Bus nativo.

```bash
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux
```

