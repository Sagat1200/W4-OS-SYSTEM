# Native D-Bus Runtime Validation

- Status: `blocked`
- Exit code: `3`
- Ownership validated: `no`
- Coupling ready: `no`
- Startup acknowledged: `no`
- Preparation target OS family: `Linux`
- Preparation target repo root: `/mnt/c/W4/Packages/W4-OS SYSTEM`
- Host compatibility: `blocked`
- Native D-Bus extension installation: `unknown`
- Blocking reason: `linux-php-runtime-too-old`
- Next action: `upgrade-runtime-php`
- Report path: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-report.json`
- Native D-Bus build evidence path: `n/a`
- Handoff path: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-handoff.md`
- Preparation shell script: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-prepare.sh`
- Preparation checklist: `build/system-api/validation/native-dbus-real-host/wsl-current-host-assessment/native-dbus-runtime-validation-preparation.txt`

## Host Compatibility

- Detected OS family: `Linux`
- Detected PHP version: `7.4.3-4ubuntu2.29`
- Required PHP constraint: `^8.4`
- Required PHP version: `8.4.0`

## Native D-Bus Extension Installation

- Installation state: `unknown`
- PECL available: `yes`
- Installation build phase: `unknown`
- Installation blocking reason: `n/a`
- Installation next action: `install-native-dbus-extension`
- Installation reason: `n/a`
- Installation failure signature: `n/a`
- Installation log path: `n/a`

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

