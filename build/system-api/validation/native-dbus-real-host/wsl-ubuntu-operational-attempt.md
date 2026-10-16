# Contraste real WSL Ubuntu 20.04

Fecha: 2026-10-16

## Objetivo

Intentar el contraste real de `System API` D-Bus nativa sobre un host Linux disponible en este entorno (`WSL Ubuntu 20.04`), preparando capacidades reales antes del ownership de `org.w4.System1`.

## Acciones ejecutadas

1. Confirmacion de distro Linux disponible:
   - `wsl.exe -l -v`
   - resultado: `Ubuntu` disponible en `WSL2`

2. Sondeo inicial del host Linux:
   - `dbus-daemon` y `busctl` presentes
   - `php` y `pecl` ausentes
   - `/run/dbus/system_bus_socket` ausente

3. Preparacion real del host:
   - `apt-get update`
   - `apt-get install -y php-cli php-dev php-pear libdbus-1-dev pkg-config dbus`
   - `apt-get install -y libxml2-dev`

4. Arranque del system bus:
   - `dbus-daemon --system --fork --nopidfile --print-address`
   - resultado: socket del system bus materializado en `/run/dbus/system_bus_socket`

## Resultado operativo

El host Linux quedo parcialmente preparado:

- `dbus-daemon`: listo
- `busctl`: listo
- `system bus socket`: listo
- `php`: instalado, pero en version `7.4.3`
- `pecl`: instalado
- `extension dbus`: no instalada

## Bloqueadores reales encontrados

### 1. El repositorio ya requiere PHP 8+

Al intentar ejecutar el runner del propio repo dentro de WSL:

- `php scripts/run_native_dbus_real_host_validation.php --format markdown ...`

el host Linux devuelve:

- `PHP Parse error: syntax error, unexpected ',' in scripts/run_native_dbus_real_host_validation.php on line 43`

La causa es que el repo usa sintaxis moderna (`match`) y el PHP disponible en esta imagen Ubuntu es `7.4`, mientras el host Windows del workspace corre `PHP 8.4.3`.

### 2. `pecl/dbus-0.1.1` no compila sobre este PHP/stack

Se intento:

- `pecl install dbus`
- `pecl install channel://pecl.php.net/dbus-0.1.1`

Hallazgos:

- el canal estable no existe; solo hay release `beta`
- tras instalar `libxml2-dev`, la compilacion sigue fallando en `make`
- el error queda ligado a incompatibilidad de la extension con la API usada por este PHP:
  - referencias a `zend_object_store_get_object(...)`
  - fallo final: `make: *** [Makefile:192: dbus.lo] Error 1`

## Lectura honesta

Este entorno ya permitio validar que el contraste real no esta bloqueado solo por ausencia de paquetes del sistema. Se avanzo hasta un bloqueo de compatibilidad mas concreto:

1. la distro Linux disponible no tiene un PHP 8 utilizable listo desde los repos configurados,
2. y la extension `pecl/dbus-0.1.1` no compila con el stack PHP actualmente instalado en esa imagen.

## Siguiente paso recomendado

Mover el contraste real final a uno de estos hosts:

1. un Linux mas reciente con PHP 8.x listo para el repo y una ruta viable para `dbus`,
2. o una imagen Ubuntu/Debian homologada para V1 donde se pueda fijar explicitamente la combinacion `PHP + pecl dbus`.

Mientras tanto, este intento deja probado que:

- el bus del sistema puede materializarse en Linux,
- pero el cierre real del ownership sigue bloqueado por compatibilidad de runtime/extension, no solo por preparacion superficial del host.
