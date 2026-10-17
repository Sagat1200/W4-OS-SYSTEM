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

## Preparation Shortcuts

```bash
cd "/srv/w4/os-system" && cat "build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-preparation.txt"
cd "/srv/w4/os-system" && chmod +x "build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-prepare.sh" && bash "build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-prepare.sh"
```

## Recommended Next Actions

- `inspect-native-dbus-build-make`
- `migrate-to-modern-linux-host`

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
printf '%s\n' "Ultimo motivo reportado: zend_object_store_get_object"
printf '%s\n' "Firma detectada del fallo: make: *** [Makefile:192: dbus.lo] Error 1"
test -f "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" && tail -n 80 "build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md" || echo "Build log no disponible: build/system-api/validation/native-dbus-real-host/wsl-ubuntu-operational-attempt.md"
cd "/srv/w4/os-system" && php -r "echo json_decode(file_get_contents('composer.json'), true)['require']['php'], PHP_EOL;"
```

1. `migrate-to-modern-linux-host` - Preparar o elegir un Linux moderno homologado con PHP `8.4+`, toolchain de compilacion y dependencias D-Bus antes de repetir el contraste nativo real.

```bash
lsb_release -a || cat /etc/os-release
php --version || true
printf '%s\n' "Objetivo: host Linux moderno con PHP 8.4+ y toolchain homologada para PECL dbus."
sudo apt-get update
sudo apt-cache policy php8.4-cli php8.4-dev php8.4-pear
sudo apt-get install -y software-properties-common ca-certificates lsb-release apt-transport-https gnupg || true
sudo apt-get install -y build-essential autoconf automake libtool re2c pkg-config libdbus-1-dev libxml2-dev dbus dbus-user-session
sudo apt-get install -y php8.4-cli php8.4-dev php8.4-pear
php --version
phpize --version || true
pecl version || true
busctl --version || true
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

