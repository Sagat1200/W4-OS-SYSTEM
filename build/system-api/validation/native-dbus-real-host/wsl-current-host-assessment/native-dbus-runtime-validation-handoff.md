# Native D-Bus Runtime Handoff

- Target OS family: `Linux`
- Target repo root: `/mnt/c/W4/Packages/W4-OS SYSTEM`
- Current status: `blocked`
- Blocking reason: `linux-php-runtime-too-old`
- Preparation script: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-preparation.txt`

## Target Host Workflow

1. Prepare the target host with the generated checklist or shell script.
2. Re-run the native validation commands from the repository root on the target host.
3. Verify that `org.w4.System1` is visible on the system bus after ownership succeeds.

## Evidence Policy

- Reutiliza `--evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment` mientras sigas diagnosticando el mismo intento de compilacion.
- Si generas un nuevo intento real de compilacion, actualiza o regenera `native-dbus-extension-build-evidence.json` antes del siguiente rerun.

## Preparation Shortcuts

```bash
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && cat "build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-preparation.txt"
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && chmod +x "build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh" && bash "build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh"
```

## Recommended Next Actions

- `upgrade-runtime-php`
- `migrate-to-modern-linux-host`

## Preparation Plan

1. `upgrade-runtime-php` - El host Linux publica PHP `7.4.3-4ubuntu2.29`, pero el repo exige `^8.4`. Homologar un runtime PHP moderno antes de volver a intentar PECL `dbus` y el ownership real.

```bash
php --version
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php -r "echo json_decode(file_get_contents('composer.json'), true)['require']['php'], PHP_EOL;"
lsb_release -a || cat /etc/os-release
sudo apt-cache policy php8.4-cli php8.4-dev php8.4-pear
sudo apt-get update
sudo apt-get install -y php8.4-cli php8.4-dev php8.4-pear
php --version
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
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php -r "echo json_decode(file_get_contents('composer.json'), true)['require']['php'], PHP_EOL;"
```

1. `rerun-native-runtime-validation` - Reejecutar el runner despues de homologar el runtime PHP del host Linux para confirmar si el siguiente bloqueo ya es exclusivamente D-Bus.

```bash
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
```


## Re-run Commands

```bash
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format markdown --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
cd "/mnt/c/W4/Packages/W4-OS SYSTEM" && php scripts/run_native_dbus_real_host_validation.php --format json --target-os-family Linux --evidence-dir build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment
```

## Post-Install Verification

```bash
php --ri dbus
busctl --system list | grep org.w4.System1 || true
busctl --system introspect org.w4.System1 /org/w4/System1 org.w4.System1.Manager
```

