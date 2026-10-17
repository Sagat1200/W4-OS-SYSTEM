# PHP 8.4 Upstream Reference

## Context

- `fork/current/` sigue reflejando el source legacy de `DBus 0.1.1`.
- `upstream/official-master-reference/pecl-dbus-master/` contiene una referencia moderna del upstream oficial.
- `patches/0001-buildroot-php8-reference.patch` ya esta disponible como patch de referencia para `PHP 8`.

## Key Deltas Observed

1. `php_dbus.h` en el upstream moderno define compatibilidad para `PHP >= 80000` con:
   - `TSRMLS_*` como macros vacias,
   - `dbus_object_type` apuntando a `zend_object`,
   - helpers `DBUS_GET_OBJECT` y `DBUS_ZVAL_GET_OBJECT`,
   - inicializacion de propiedades diferenciada para `PHP >= 8.4`.

2. `dbus.c` reemplaza el viejo `zend_object_value` por `zend_object*` en los constructores de objetos.

3. Las funciones `*_get_properties` pasan de recibir `zval *object` a `dbus_object_type *object`.

4. El upstream moderno encapsula la inicializacion de objetos y handlers en macros reutilizables en vez de depender de `zend_objects_store_put`.

## Recommended First Port Step

1. Introducir en `fork/current/php_dbus.h` una capa minima de compatibilidad inspirada en el upstream moderno:
   - macros `TSRMLS_*` para `PHP >= 80000`,
   - `dbus_object_type`,
   - helpers de acceso a objeto.

2. Migrar en `fork/current/dbus.c` solo la superficie minima que hoy bloquea el parser de `PHP 8.4`:
   - prototipos y forward declarations de `zend_object_value` hacia `zend_object*`,
   - firmas `*_get_properties`,
   - helpers de alloc/init/free para objetos.

3. Aplicar despues el patch de referencia de Buildroot por partes, no como import ciego, para mantener trazabilidad clara del fork transitorio V1.

## Local References

- `upstream/imported-source/dbus.c`
- `upstream/official-master-reference/pecl-dbus-master/php_dbus.h`
- `upstream/official-master-reference/pecl-dbus-master/dbus.c`
- `patches/0001-buildroot-php8-reference.patch`
- `patches/series.txt`
