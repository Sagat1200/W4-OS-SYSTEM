# Native D-Bus PECL Fork Workspace

- Target PHP: `8.4`
- PECL package: `DBus-0.1.1`
- Scope: preparar el fork transitorio autorizado para V1 sin perder la evidencia del build real.

## Imported Evidence

- Build log: `C:/W4/Packages/W4-OS SYSTEM/build/system-api/native-dbus-extension-fork/evidence/native-dbus-build.log`
- Structured evidence: `C:/W4/Packages/W4-OS SYSTEM/build/system-api/native-dbus-extension-fork/evidence/native-dbus-extension-build-evidence.json`

## Patch Targets

1. Revisar y reemplazar macros legacy `TSRMLS_*` incompatibles con PHP 8.4.
2. Revisar llamadas a `zend_parse_parameters` y ajustar la firma segun la API moderna.
3. Sustituir el acceso legacy a objetos (`zend_object_store_get_object`) por la ruta compatible con PHP moderno.
4. Documentar cada ajuste incremental en `patches/` antes de reintentar `phpize`, `configure` y `make`.

## Execution Loop

1. Importar o actualizar el codigo fuente upstream dentro de `upstream/`.
2. Aplicar el patch activo dentro de `fork/` y registrar la evidencia del cambio.
3. Repetir `phpize`, `./configure` y `make`, guardando logs frescos en `evidence/`.
4. Regenerar el reporte del runner para confirmar si el bloqueo se movio o quedo resuelto.

## Pending Input

- Todavia no se importo el source upstream de `DBus 0.1.1`; ese es el siguiente paso tecnico dentro del workspace.

