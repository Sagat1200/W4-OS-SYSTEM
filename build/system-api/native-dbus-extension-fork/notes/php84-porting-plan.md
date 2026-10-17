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

1. Limpiar el residuo textual del macro legacy `DBUS_FETCH_OBJECT` para que el scan del fork no siga marcando una definicion obsoleta aunque ya este sobreescrita.
2. Revisar si conviene migrar `DBUS_ZEND_MAKE_STD_ZVAL` de los property builders a helpers mas modernos o si ya es suficiente como capa de compatibilidad transitoria.
3. Volver sobre `php_dbus_to_zval()` para cerrar detalles de ownership/memoria y alinearlo mejor con la referencia moderna, especialmente en `DICT_ENTRY`, `STRUCT` y firmas temporales.
4. Solo despues de eso volver a revisar la fase de marshaling/unmarshaling para cerrar warnings y errores de compilacion adicionales.

## Execution Loop

1. Importar o actualizar el codigo fuente upstream dentro de `upstream/`.
2. Trabajar el patch activo dentro de `fork/current/` y registrar la evidencia del cambio.
3. Repetir `phpize`, `./configure` y `make`, guardando logs frescos en `evidence/`.
4. Regenerar el reporte del runner para confirmar si el bloqueo se movio o quedo resuelto.

