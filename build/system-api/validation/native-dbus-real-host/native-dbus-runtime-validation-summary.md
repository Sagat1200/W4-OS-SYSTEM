# Native D-Bus Runtime Validation

- Status: `blocked`
- Exit code: `3`
- Ownership validated: `no`
- Coupling ready: `no`
- Startup acknowledged: `no`
- Preparation target OS family: `Linux`
- Preparation target repo root: `/srv/w4/os-system`
- Host compatibility: `compatible`
- Native D-Bus extension installation: `build-failed`
- Blocking reason: `native-dbus-extension-build-failed`
- Next action: `inspect-native-dbus-build-make`
- Report path: `build/system-api/validation/native-dbus-real-host\native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `build/system-api/validation/native-dbus-real-host/native-dbus-extension-build-evidence.json`
- Handoff path: `build/system-api/validation/native-dbus-real-host\native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `build/system-api/validation/native-dbus-real-host\native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host\native-dbus-runtime-validation-preparation.txt`

## Host Compatibility

- Detected OS family: `Windows`
- Detected PHP version: `8.4.3`
- Required PHP constraint: `^8.4`
- Required PHP version: `8.4.0`

## Native D-Bus Extension Installation

- Installation state: `build-failed`
- PECL available: `yes`
- Installation build phase: `make`
- Installation blocking reason: `native-dbus-extension-build-failed`
- Installation next action: `inspect-native-dbus-build-make`
- Installation reason: `zend_object_store_get_object`
- Installation failure signature: `make: *** [Makefile:192: dbus.lo] Error 1`
- Installation log path: `c:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-ubuntu-operational-attempt.md`

## Recommended Next Actions

- `inspect-native-dbus-build-make`
- `migrate-to-modern-linux-host`
- `install-native-dbus-extension`

## Preparation Plan

1. `inspect-native-dbus-build-make` - La extension `dbus` ya avanzo hasta `make`, pero la compilacion fallo. Conviene inspeccionar el toolchain C y la evidencia del error de compilacion antes de seguir insistiendo con PECL.

```bash
php --version
phpize --version || true
lsb_release -a || cat /etc/os-release
pecl version
pecl channel-info pecl.php.net || true
pecl channel-update pecl.php.net || true
pecl remote-info pecl/dbus || true
pecl remote-info channel://pecl.php.net/dbus-0.1.1
pecl search dbus || true
make --version || true
gcc --version || true
printf '%s
' "Ultimo motivo reportado: zend_object_store_get_object"
printf '%s
' "Firma detectada del fallo: make: *** [Makefile:192: dbus.lo] Error 1"
test -f "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" && tail -n 80 "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" || echo "Build log no disponible: build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md"
cd "/srv/w4/os-system" && php -r "echo json_decode(file_get_contents('composer.json'), true)['require']['php'], PHP_EOL;"
```

1. `rerun-native-runtime-validation` - Reejecutar el runner despues de corregir la instalacion o compilacion de la extension `dbus` para confirmar si el ownership ya puede progresar.

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

