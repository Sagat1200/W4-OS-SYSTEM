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
- `0011-php84-wait-loop-signal-shutdown-stabilization.patch` estabiliza `waitLoop()` en `PHP 8.4`: para señales entrantes deja de materializar `DbusSignal` en el carril `PHP >= 8`, libera el `DBusMessage` dentro del propio metodo y cierra tambien la liberacion del mensaje tras el dispatch de method calls, eliminando el `segfault` que aparecia al volver de `waitLoop()` o al destruir el valor devuelto en probes reales sobre `BUS_SYSTEM`.
- `0012-php84-add-missing-arginfo-declarations.patch` añade arginfo explicito para los metodos publicos del modulo (`Dbus`, `DbusObject`, `DbusSignal`, `DbusArray`, `DbusDict`, `DbusVariant`, `DbusSet`, `DbusStruct`, `DbusObjectPath` y los wrappers escalares), conectando esas firmas a las tablas `PHP_ME(...)` para que `PHP 8.4` deje de emitir warnings `Missing arginfo` al cargar `dbus.so`.
- `0013-php84-wrapper-destructor-ownership-hardening.patch` corrige deuda de destructor/ownership que la smoke dedicada de wrappers destapo fuera del carril principal del runtime: `DbusSignal` deja de hacer `dbus_message_unref()` sobre `NULL`, y `DbusArray`, `DbusDict`, `DbusVariant` y `DbusStruct` pasan a liberar sus `zval` con `zval_ptr_dtor(...)` en lugar de mezclar refcount manual, `zend_array_destroy()` y `efree()` sobre memoria cuya vida ya pertenece al motor.

Estado real del fork tras el ultimo contraste:

- El source activo ya completa `phpize`, `./configure` y `make` en `Debian 13 + PHP 8.4.24`, y deja `modules/dbus.so` materializado en la VM moderna usada para el contraste real.
- La reflexion del modulo cargado confirma la clase `Dbus`, la constante `BUS_SYSTEM` y los metodos `requestName`, `registerObject` y `waitLoop`, por lo que el port ya cruzo el umbral de compatibilidad minima de API visible.
- El contraste mas reciente en la VM moderna confirma ademas que `waitLoop(250)` ya devuelve `true`, sobrevive a `unset($connection)` y deja de disparar el `general protection fault`/`segfault` del shutdown legacy cuando el modulo recompilado se carga en `PHP 8.4`.
- El corte `0012` elimina el ruido `Missing arginfo` al cargar el modulo recompilado en la VM moderna: `php -d extension=/home/w4live/dbus-0.1.1/modules/dbus.so -m 2>&1 | grep -i arginfo` ya no devuelve resultados.
- La smoke homologada `build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_wrapper_smoke.php` ya deja `ok = true` en `guest-artifacts/wrapper-smoke/check-native-dbus-wrapper-smoke.json`, y despues del mismo rebuild siguen verdes `probe_native_dbus_heap_after_request_name.php` y `check_native_dbus_launch_process.php`.
- Los toggles experimentales `W4_DBUS_CONNECTION_MODE`, `W4_DBUS_SKIP_OBJECTS_HASH_INIT` y `W4_DBUS_SKIP_OBJECT_PROPERTIES_INIT` se conservan en el source activo como instrumentacion de laboratorio. No forman parte del contrato estable del runtime ni del baseline de produccion; su rol queda acotado a contrastes controlados cuando haga falta aislar constructor/layout/ownership.
- Siguen abiertos el warning legacy `AC_PROG_LIBTOOL is obsolete` y el `#warning` historico sobre `signature` en el carril `struct`.

