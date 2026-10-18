# Patch Queue

Este directorio guarda los parches incrementales del fork temporal de `DBus-0.1.1`.

Convencion sugerida:

- `0001-*.patch` para compatibilidad de compilacion basica.
- `0002-*.patch` para limpieza de warnings o ajustes de API.
- `series.txt` si despues se necesita un orden explicito.

Estado actual:

- `0004-php84-macro-cleanup-and-zval-allocation.patch` ya documenta la limpieza del macro legacy duplicado `DBUS_FETCH_OBJECT` y la correccion de `DBUS_ZEND_MAKE_STD_ZVAL`, que ahora asigna un `zval` real en vez de dejar un puntero a stack temporal.
- `0005-php84-zval-ownership-followup.patch` documenta el cleanup de ownership en `php_dbus_to_zval()` y `php_dbus_handle_reply()`, incluyendo liberacion del contenedor `zval*` tras insertar en arrays, correccion del caso `DBUS_TYPE_DICT_ENTRY` y alineacion de keys string/object-path con la referencia moderna.
- `0006-php84-variant-signature-struct-alignment.patch` alinea `php_dbus_variant_obj` con el upstream moderno, reintroduciendo el campo `signature` que el runtime ya consulta y libera en helpers de marshaling y destructores.

