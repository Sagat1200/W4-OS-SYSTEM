# Native D-Bus Runtime Handoff

- Target OS family: `Linux`
- Target repo root: `/opt/w4/w4-os-system`
- Current status: `validation-failed`
- Blocking reason: `native-dbus-system-bus-policy-denied`
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
- `install-native-dbus-system-bus-policy`

## Preparation Plan

1. `install-native-dbus-system-bus-policy` - Instalar o corregir la policy del system bus para permitir que el runtime reclame `org.w4.System1` y exporte `/org/w4/System1`.

```bash
grep -R "org.w4.System1" /etc/dbus-1/system.d /usr/share/dbus-1/system.d 2>/dev/null || true
sudo tee /etc/dbus-1/system.d/org.w4.System1.conf >/dev/null <<'EOF'
<!DOCTYPE busconfig PUBLIC "-//freedesktop//DTD D-Bus Bus Configuration 1.0//EN"
 "http://www.freedesktop.org/standards/dbus/1.0/busconfig.dtd">
<busconfig>
  <policy user="root">
    <allow own="org.w4.System1"/>
    <allow send_destination="org.w4.System1"/>
  </policy>
  <policy context="default">
    <allow send_destination="org.w4.System1"/>
  </policy>
</busconfig>
EOF
sudo systemctl restart dbus >/dev/null 2>&1 || sudo service dbus restart >/dev/null 2>&1 || true
busctl --system list | grep org.w4.System1 || true
```

1. `retry-native-bus-ownership` - Reintentar el ownership real del bus solo cuando el host confirme extension PECL dbus, daemon, socket y tooling del sistema disponibles.

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

