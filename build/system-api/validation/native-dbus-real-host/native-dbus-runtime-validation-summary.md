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
- Next action: `inspect-native-dbus-build-stack`
- Report path: `C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\native-dbus-extension-build-evidence.json`
- Handoff path: `C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `C:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\native-dbus-runtime-validation-preparation.txt`

## Host Compatibility

- Detected OS family: `Windows`
- Detected PHP version: `8.4.3`
- Required PHP constraint: `^8.4`
- Required PHP version: `8.4.0`

## Native D-Bus Extension Installation

- Installation state: `build-failed`
- PECL available: `yes`
- Installation blocking reason: `native-dbus-extension-build-failed`
- Installation next action: `inspect-native-dbus-build-stack`
- Installation reason: `zend_object_store_get_object`
- Installation log path: `c:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-ubuntu-operational-attempt.md`

## Recommended Next Actions

- `inspect-native-dbus-build-stack`
- `migrate-to-modern-linux-host`
- `install-native-dbus-extension`

## Preparation Plan

1. `inspect-native-dbus-build-stack` - La extension `dbus` ya fue intentada pero la compilacion o instalacion fallo. Conviene inspeccionar el stack y la evidencia del build antes de seguir insistiendo con PECL.

```bash
php --version
phpize --version || true
lsb_release -a || cat /etc/os-release
pecl version
pecl remote-info channel://pecl.php.net/dbus-0.1.1
printf '%s
' "Ultimo motivo reportado: zend_object_store_get_object"
test -f "c:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-ubuntu-operational-attempt.md" && tail -n 80 "c:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-ubuntu-operational-attempt.md" || echo "Build log no disponible: c:\W4\Packages\W4-OS SYSTEM\build\system-api\validation\native-dbus-real-host\wsl-ubuntu-operational-attempt.md"
cd "/srv/w4/os-system" && php -r "echo json_decode(file_get_contents('composer.json'), true)['require']['php'], PHP_EOL;"
```

1. `rerun-native-runtime-validation` - Reejecutar el runner despues de corregir la instalacion o compilacion de la extension `dbus` para confirmar si el ownership ya puede progresar.

```bash
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux
cd "/srv/w4/os-system" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux
```

