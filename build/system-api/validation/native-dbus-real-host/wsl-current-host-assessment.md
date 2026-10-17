# Current WSL Host Assessment

Fecha: 2026-10-17

## Objetivo

Contrastar si el host Linux realmente disponible en este entorno Windows puede ejecutar el paquete `native-dbus-real-host` y servir como candidato para la validacion final del ownership de `org.w4.System1`.

## Host detectado

- Runtime Linux disponible: `WSL2`
- Distro: `Ubuntu 20.04.6 LTS (Focal Fossa)`
- Usuario actual: `sagat1200`
- Repo visible desde WSL: `/mnt/c/W4/Packages/W4-OS SYSTEM`

## Comandos ejecutados

```bash
wsl -l -v
wsl -e bash -lc 'cat /etc/os-release'
wsl -e bash -lc 'php --version'
wsl -e bash -lc 'id -un; sudo -n whoami; echo rc:$?'
wsl --cd "/mnt/c/W4/Packages/W4-OS SYSTEM" bash -lc 'pwd; php scripts/run_native_dbus_real_host_validation.php --format text --target-os-family Linux --target-repo-root "/mnt/c/W4/Packages/W4-OS SYSTEM"'
```

## Hallazgos

### 1. La distro disponible sigue siendo Ubuntu 20.04

El host Linux local no es todavia un candidato homologado moderno. La base actual sigue siendo `Ubuntu 20.04.6`.

### 2. El runtime PHP disponible es 7.4.3

El repo exige `PHP 8.4+`, pero el host WSL actual publica:

```text
PHP 7.4.3-4ubuntu2.29
```

### 3. El runner del repo no puede ejecutarse en este host

Al intentar lanzar el runner real desde WSL sobre el repo montado en `/mnt/c`, el host devuelve:

```text
PHP Parse error: syntax error, unexpected ',' in /mnt/c/W4/Packages/W4-OS SYSTEM/scripts/run_native_dbus_real_host_validation.php on line 43
```

Esto confirma que el runtime del host actual no soporta la sintaxis moderna ya usada por el proyecto.

### 4. `sudo` no esta disponible sin interaccion

El sondeo directo devolvio:

```text
sudo: a password is required
rc:1
```

Eso significa que este agente no puede homologar el host automaticamente desde aqui sin credenciales interactivas.

## Conclusión operativa

Este WSL actual no sirve como host final de contraste para la vertical D-Bus nativa porque combina:

1. `Ubuntu 20.04`
2. `PHP 7.4.3`
3. `sudo` con password requerida

El bloqueo deja de ser teorico: ya esta probado con evidencia real que el runner del repo no puede ejecutarse en este host.

## Siguiente paso recomendado

Mover el contraste final a un Linux moderno homologado con:

- `PHP 8.4+`
- `phpize`
- `pecl`
- toolchain de compilacion
- dependencias D-Bus
- acceso suficiente para instalar paquetes o una imagen ya preparada

El expediente operativo a reutilizar sigue siendo:

- `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-handoff.md`
- `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-prepare.sh`
- `build/system-api/validation/native-dbus-real-host/native-dbus-runtime-validation-preparation.txt`
