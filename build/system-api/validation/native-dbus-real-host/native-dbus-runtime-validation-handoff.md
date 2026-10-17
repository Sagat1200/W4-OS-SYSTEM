# Native D-Bus Runtime Handoff

- Target OS family: `Linux`
- Target repo root: `/srv/w4/os-system`
- Current status: `blocked`
- Blocking reason: `native-dbus-extension-build-failed`
- Preparation script: `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-preparation.txt`

## Target Host Workflow

1. Prepare the target host with the generated checklist or shell script.
2. Re-run the native validation commands from the repository root on the target host.
3. Verify that `org.w4.System1` is visible on the system bus after ownership succeeds.

## Evidence Policy

- Reutiliza `--evidence-dir build/system-api/validation/native-dbus-real-host` mientras sigas diagnosticando el mismo intento de compilacion.
- Conserva `--native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json` cuando el rerun solo valida o reinspecciona la evidencia ya capturada.
- Si vuelves a ejecutar una compilacion real de `pecl dbus`, regenera `native-dbus-extension-build-evidence.json` antes del siguiente rerun para no mezclar evidencia vieja con un intento nuevo.
- Conserva tambien `--native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md` cuando el rerun deba rematerializar el JSON estructurado desde el log crudo del intento real.

## Re-run Commands

```bash
printf '%s\n' "Preserva la evidencia actual si solo vas a reinspeccionar el mismo intento de compilacion."
test -f "build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json" && cp "build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json" "build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).json" || true
test -f "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" && cp "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md.before-rerun.$(date -u +%Y%m%dT%H%M%SZ).log" || true
printf '%s\n' "Despues de un build real nuevo, elimina el JSON previo para forzar una rematerializacion limpia en el siguiente rerun."
cd "/srv/w4/os-system" && rm -f "build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host --native-dbus-build-evidence-json-path build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json --native-dbus-build-log-path build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md
```

## Post-Install Verification

```bash
php --ri dbus
busctl --system list | grep org.w4.System1 || true
busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager
```

