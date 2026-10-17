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
- `0003-php84-object-compat-bootstrap-runtime.patch` ya mueve en `dbus.c` la primera capa de fabricas de objetos, handlers y `get_properties` desde `zend_object_value` hacia `zend_object*`, dejando el bootstrap de objetos alineado con el upstream moderno.
- Este bootstrap todavia no completa el port. El source conserva varios puntos legacy fuera de la capa de objetos, especialmente llamadas directas a `zend_object_store_get_object`, `ALLOC_ZVAL`, `MAKE_STD_ZVAL` y rutas de inicializacion/metodos que siguen escritas para PHP 5.x.

## Next Runtime Hotspots

1. Reemplazar los accesos restantes a `zend_object_store_get_object` por `DBUS_ZVAL_GET_OBJECT` o `DBUS_GET_OBJECT` segun el tipo de entrada.
2. Modernizar `dbus_instantiate` y los caminos que aun usan `ALLOC_ZVAL` o `MAKE_STD_ZVAL`.
3. Ajustar constructores y metodos principales (`Dbus::__construct`, `DbusObject::__construct`, `createProxy`, `DbusSignal::__construct`) para que usen la nueva capa de objetos de forma consistente.
4. Solo despues de eso volver a revisar la fase de marshaling/unmarshaling para cerrar warnings y errores de compilacion adicionales.

## Execution Loop

1. Importar o actualizar el codigo fuente upstream dentro de `upstream/`.
2. Trabajar el patch activo dentro de `fork/current/` y registrar la evidencia del cambio.
3. Repetir `phpize`, `./configure` y `make`, guardando logs frescos en `evidence/`.
4. Regenerar el reporte del runner para confirmar si el bloqueo se movio o quedo resuelto.

