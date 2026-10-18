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
- `0007-php84-hash-and-callback-compat-followup.patch` trae helpers de compatibilidad de hash/string desde el upstream moderno y los aplica al carril de callbacks, builders de propiedades y marshaling de arrays/dicts/structs, reduciendo el uso de firmas Zend pre-PHP 7 que todavia seguian en el source activo.
- `0008-php84-parameter-length-and-registerobject-alignment.patch` normaliza longitudes a `size_t` en rutas de parseo que seguian con `int`, y alinea `Dbus::registerObject()` con el upstream moderno usando interfaz opcional nula y `zend_hash_str_add_ptr` via helper de compatibilidad.
- `0009-php84-bool-and-parameter-array-modernization.patch` mueve el runtime de callbacks y los buffers de `zend_get_parameters_array_ex` hacia el contrato moderno del upstream, y deja el carril booleano alineado con `IS_TRUE`/`IS_FALSE`.
- `0010-php84-append-parameters-hash-iteration-cleanup.patch` limpia `php_dbus_append_parameters()` para que itere `HashTable` con `zval *` y los helpers `DBUS_ZEND_HASH_GET_CURRENT_DATA_CHECK_EX`, dejando atras la firma vieja con `zval **entry`.

