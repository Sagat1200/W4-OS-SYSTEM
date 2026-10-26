# Native D-Bus Runtime Handoff

- Target OS family: `Linux`
- Target repo root: `/opt/w4/w4-os-system`
- Current status: `ownership-validated`
- Blocking reason: `n/a`
- Preparation script: `build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-preparation.txt`

## Target Host Workflow

1. Prepare the target host with the generated checklist or shell script.
2. Re-run the native validation commands from the repository root on the target host.
3. Verify that `org.w4.System1` is visible on the system bus after ownership succeeds.

## Evidence Policy

- Reutiliza `--evidence-dir build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host` mientras sigas diagnosticando el mismo intento de compilacion.
- Si generas un nuevo intento real de compilacion, actualiza o regenera `native-dbus-extension-build-evidence.json` antes del siguiente rerun.

## Preparation Shortcuts

```bash
cd "/opt/w4/w4-os-system" && cat "build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-preparation.txt"
cd "/opt/w4/w4-os-system" && chmod +x "build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-prepare.sh" && bash "build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host/native-dbus-runtime-validation-prepare.sh"
```

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


## Re-run Commands

```bash
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/test-launch-process-owned-host
```

## Post-Install Verification

```bash
php --ri dbus
busctl --system list | grep org.w4.System1 || true
busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager
```

