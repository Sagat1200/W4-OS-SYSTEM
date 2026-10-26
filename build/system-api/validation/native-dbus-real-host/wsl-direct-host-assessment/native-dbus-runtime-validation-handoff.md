# Native D-Bus Runtime Handoff

- Target OS family: `Linux`
- Target repo root: `/opt/w4/w4-os-system`
- Current status: `ownership-validated`
- Blocking reason: `n/a`
- Preparation script: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-preparation.txt`

## Target Host Workflow

1. Prepare the target host with the generated checklist or shell script.
2. Re-run the native validation commands from the repository root on the target host.
3. Verify that `org.w4.System1` is visible on the system bus after ownership succeeds.

## Evidence Policy

- Reutiliza `--evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment` mientras sigas diagnosticando el mismo intento de compilacion.
- Conserva `--native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json` cuando el rerun solo valida o reinspecciona la evidencia ya capturada.
- Si vuelves a ejecutar una compilacion real de `pecl dbus`, regenera `native-dbus-extension-build-evidence.json` antes del siguiente rerun para no mezclar evidencia vieja con un intento nuevo.
- Conserva tambien `--native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log` cuando el rerun deba rematerializar el JSON estructurado desde el log crudo del intento real.

## Preparation Shortcuts

```bash
cd "/opt/w4/w4-os-system" && cat "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-preparation.txt"
cd "/opt/w4/w4-os-system" && chmod +x "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-prepare.sh" && bash "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-runtime-validation-prepare.sh"
```

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


## Re-run Commands

```bash
printf '%s\n' "Preserva la evidencia actual si solo vas a reinspeccionar el mismo intento de compilacion."
test -f "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json" && cp "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json" "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).json" || true
test -f "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log" && cp "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log" "build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).log" || true
printf '%s\n' "Despues de un build real nuevo, elimina el JSON previo para forzar una rematerializacion limpia en el siguiente rerun."
cd "/opt/w4/w4-os-system" && rm -f "build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
cd "/opt/w4/w4-os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/wsl-direct-host-assessment/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/native-dbus-extension-fork/evidence/php84-build-attempt-current.log
```

## Post-Install Verification

```bash
php --ri dbus
busctl --system list | grep org.w4.System1 || true
busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager
```

