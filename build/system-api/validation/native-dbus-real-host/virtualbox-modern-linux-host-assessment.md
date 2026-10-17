# VirtualBox Modern Linux Host Assessment

Fecha: 2026-10-17

## Objetivo

Contrastar la vertical D-Bus nativa en un Linux moderno real disponible desde este host Windows, evitando las limitaciones del WSL local con `Ubuntu 20.04` y `PHP 7.4`.

## Host materializado

- Hypervisor: `VirtualBox 7.2.8`
- VM efimera: `W4-OS-Server-DbusModern`
- Base: ISO `build/iso-output/w4-os-server/w4-os-server-live-amd64.iso`
- Acceso SSH temporal: `127.0.0.1:2240`
- Usuario live: `w4live`

## Identidad del sistema

- `PRETTY_NAME="W4 OS Server"`
- Base reportada: `Debian GNU/Linux 13 (trixie)`
- Kernel: `6.12.107+deb13-amd64`
- Estado del sistema: `systemctl is-system-running -> running`
- Bus de sistema: activo y consultable con `busctl --system list`

## Runtime PHP observado

- `PHP 8.4.24 (cli)`
- `phpize` disponible tras instalar `php8.4-dev`
- `pecl` disponible tras instalar `php-pear`

## Paquetes instalados para el contraste

```bash
sudo apt-get update
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y \
  php8.4-dev php-pear libdbus-1-dev pkg-config build-essential autoconf
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y libxml2-dev
```

## Resultado del intento PECL

### Intento 1: bloqueo en `configure`

El primer `pecl install channel://pecl.php.net/DBus-0.1.1` no fallo por el host moderno ni por `PHP 8.4`, sino por una dependencia faltante:

```text
checking for libxml-2.0 >= 2.9.4... no
configure: error: Package requirements (libxml-2.0 >= 2.9.4) were not met:
Package 'libxml-2.0', required by 'virtual:world', not found
```

Se resolvio instalando `libxml2-dev`.

### Intento 2: bloqueo real en `make`

Tras instalar `libxml2-dev`, el build avanzo hasta `make` y cayo por incompatibilidad real del codigo fuente de `DBus 0.1.1` con la API moderna de `PHP 8.4`:

```text
error: expected ';', ',' or ')' before 'TSRMLS_DC'
error: expected ')' before 'TSRMLS_CC'
error: too few arguments to function 'zend_parse_parameters'
error: implicit declaration of function 'dbus_double_initialize'
make: *** [Makefile:208: dbus.lo] Error 1
ERROR: `make' failed
```

La traza muestra referencias directas a APIs heredadas como:

- `TSRMLS_DC`
- `TSRMLS_CC`
- `zend_object_store_get_object`
- firmas antiguas de `zend_parse_parameters`

## Conclusión operativa

El bloqueo principal de la vertical D-Bus nativa ya no es el host. En un Linux moderno real con:

- `Debian 13`
- `PHP 8.4.24`
- toolchain de compilacion
- headers `dbus` y `libxml2`

la extension `DBus 0.1.1 (beta)` sigue fallando en `make` por incompatibilidad de codigo con la API moderna de PHP.

## Implicación para el proyecto

La siguiente decision ya no es "buscar un mejor Linux", sino una de estas rutas:

1. parchear o forkear `pecl/DBus` para compatibilidad con `PHP 8.4`
2. validar si existe una rama o fork mantenido compatible con PHP moderno
3. revaluar si el backend D-Bus nativo debe depender de esta extension legacy o migrar a otra estrategia

## Evidencia local

- `build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-attempt.log`
- `build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-pecl-dbus-retry-libxml2.log`
- `build/system-api/validation/native-dbus-real-host/virtualbox-modern-linux-native-dbus-extension-build-evidence.json`
