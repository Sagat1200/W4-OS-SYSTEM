# Native D-Bus PECL Fork Workspace

- Target PHP: `8.4`
- PECL package: `DBus-0.1.1`
- Scope: preparar el fork transitorio autorizado para V1 sin perder la evidencia del build real.

## Imported Evidence

- Build log: `C:/W4/Packages/W4-OS SYSTEM/build/system-api/native-dbus-extension-fork/evidence/native-dbus-build.log`
- Structured evidence: `C:/W4/Packages/W4-OS SYSTEM/build/system-api/native-dbus-extension-fork/evidence/native-dbus-extension-build-evidence.json`
- Imported source tree: `C:/W4/Packages/W4-OS SYSTEM/build/system-api/native-dbus-extension-fork/upstream/imported-source`
- Editable fork tree: `C:/W4/Packages/W4-OS SYSTEM/build/system-api/native-dbus-extension-fork/fork/current`

## Patch Targets

1. Revisar y reemplazar macros legacy `TSRMLS_*` incompatibles con PHP 8.4.
2. Revisar llamadas a `zend_parse_parameters` y ajustar la firma segun la API moderna.
3. Sustituir el acceso legacy a objetos (`zend_object_store_get_object`) por la ruta compatible con PHP moderno.
4. Documentar cada ajuste incremental en `patches/` antes de reintentar `phpize`, `configure` y `make`.

## Bootstrap Patch Status

- `0002-php84-object-compat-bootstrap-header.patch` ya introduce en `php_dbus.h` la capa minima de compatibilidad para `PHP >= 8`, incluyendo `dbus_object_type`, `DBUS_GET_OBJECT`, `DBUS_ZEND_OBJECT_INIT_RETURN` y soporte explicito para `PHP 8.4`.
- `0003-php84-object-compat-bootstrap-runtime.patch` ya mueve en `dbus.c` la primera capa de fabricas de objetos, handlers y `get_properties` desde `zend_object_value` hacia `zend_object*`, y ahora tambien cubre `dbus_instantiate`, `Dbus::__construct`, `DbusObject::__construct`, `Dbus::createProxy`, `DbusSignal::__construct`, `addWatch()`, `php_dbus_do_method_call()`, `php_dbus_fetch_child_type()`, `php_dbus_fetch_child_type_as_string()`, `dbus_append_var()` y la ola principal de wrappers instanciados durante `php_dbus_to_zval()`.
- Este bootstrap todavia no completa el port. El source conserva varios puntos legacy fuera de la capa de objetos, especialmente llamadas directas a `zend_object_store_get_object`, `ALLOC_ZVAL`, `MAKE_STD_ZVAL` y rutas de inicializacion/metodos que siguen escritas para PHP 5.x.

## Next Runtime Hotspots

1. Revisar el ownership/memoria de `php_dbus_to_zval()` y de los property builders para confirmar que cada `zval` temporal queda liberado o retenido con la semantica correcta bajo `PHP 8.4`.
2. Reducir mas la capa transitoria `DBUS_ZEND_MAKE_STD_ZVAL` alli donde ya exista una ruta directa con `array_init()` o builders modernos, de modo que el diff del fork no cargue deuda evitable.
3. Releer `DICT_ENTRY`, `STRUCT` y los wrappers de propiedades contra la referencia moderna para detectar si todavia quedan firmas temporales o ownership ambiguo.
4. Solo despues de eso volver a correr `phpize`, `./configure` y `make` sobre un host Linux homologado para comprobar en que warning/error cae el siguiente corte real.

## Current Increment

- Se limpio el residuo textual del macro legacy `DBUS_FETCH_OBJECT` dentro de `dbus.c`, con lo que el scan del fork deja de reportar una definicion obsoleta que ya no representa el runtime activo.
- `DBUS_ZEND_MAKE_STD_ZVAL` ya no apunta a memoria de stack temporal: ahora reserva un `zval` real y lo inicializa con `DBUS_ZEND_INIT_ZVAL`, evitando punteros colgantes en los property builders mientras seguimos cerrando el port.
- `php_dbus_to_zval()` ya libera el contenedor `zval*` despues de insertarlo en arrays/structs, corrige el caso `DBUS_TYPE_DICT_ENTRY` para no perder el `return_value` reservado y alinea las keys string/object-path con la referencia moderna.
- `php_dbus_handle_reply()` ya libera el contenedor `zval*` en los caminos `always_array` y retorno unico, y deja de descartar silenciosamente `key` temporales cuando esos valores no se reutilizan.
- `php_dbus_variant_obj` vuelve a declarar `signature`, alineandose con el upstream moderno y con los helpers/destructores del propio fork que ya esperaban ese campo durante el marshaling del tipo `VARIANT`.
- `php_dbus.h` ya incorpora helpers de compatibilidad de hash/string (`DBUS_ZVAL_STRING`, `DBUS_ZEND_HASH_UPDATE`, `DBUS_ZEND_HASH_GET_CURRENT_DATA_CHECK`, `DBUS_ZEND_HASH_GET_CURRENT_KEY_INFO`) y `dbus.c` los usa en callbacks, builders de propiedades y marshaling de arrays/dicts/structs.
- El lookup de callbacks ya deja atras `zend_hash_find`, `zend_hash_exists`, `add_next_index_string(..., 0)` y la ruta vieja de iteracion sobre `HashTable`, ademas de liberar `key`, `lcname` y `class_name` temporales en el mismo bloque.
- `php_dbus.h` ya expone tambien `DBUS_ZEND_HASH_ADD_PTR`, y `Dbus::registerObject()` pasa a usar interfaz opcional nula, `size_t` en longitudes y `zend_hash_str_add_ptr` via helper en lugar del `zend_hash_add()` legacy.
- `Dbus::requestName()` y `Dbus::addWatch()` ya normalizan sus longitudes de parseo a `size_t`, reduciendo otro foco de incompatibilidad silenciosa con la firma moderna de `zend_parse_*`.
- `php_dbus_do_method_call()` ya se acerca al contrato moderno del upstream: usa `zval` en stack para `params` y `callback`, invoca `call_user_function()` en `PHP >= 8` y deja el fallback antiguo solo para ramas pre-PHP 8.
- `DbusSignal::send()` y `DbusSet::__construct()` ya abandonan los buffers `zval ***` para `zend_get_parameters_array_ex`, mientras el carril booleano del marshaling pasa a `IS_TRUE`/`IS_FALSE` y `zval_is_true()`.

## Execution Loop

1. Importar o actualizar el codigo fuente upstream dentro de `upstream/`.
2. Trabajar el patch activo dentro de `fork/current/` y registrar la evidencia del cambio.
3. Repetir `phpize`, `./configure` y `make`, guardando logs frescos en `evidence/`.
4. Regenerar el reporte del runner para confirmar si el bloqueo se movio o quedo resuelto.

