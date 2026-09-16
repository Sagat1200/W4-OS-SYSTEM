# W4 OS · Development Guidelines

Guia operativa para trabajar durante el desarrollo del proyecto sin perder trazabilidad, foco tecnico ni coherencia con la documentacion de arquitectura.

## Objetivo

Establecer reglas comunes para que cada ciclo de desarrollo deje:

- avance tecnico real,
- evidencia verificable,
- documentacion actualizada,
- impacto visible en la planificacion.

## Reglas generales

1. No presentar una capacidad como implementada solo porque existe un documento.
2. No iniciar una iniciativa P1 o P2 mientras una dependencia P0 siga bloqueada sin aceptacion explicita.
3. Toda decision transversal debe reflejarse en ADR o en el documento canonico correspondiente.
4. Todo cambio tecnico relevante debe actualizar el conjunto de documentos de desarrollo.
5. Ningun cierre es valido sin evidencia.

## Secuencia obligatoria por ciclo

Por cada ciclo de trabajo:

1. revisar `EXECUTIVE_IMPLEMENTATION_PLAN.md`,
2. revisar `DEVELOPMENT_TABLE.md`,
3. actualizar `DEVELOPMENT_MATRIX.md` con el estado real,
4. ejecutar implementacion o prueba,
5. registrar el cambio en `DEVELOPMENT_VERSIONS.md`,
6. ajustar estas guidelines si se incorporo una nueva norma de trabajo.

## Definicion de evidencia valida

Se considera evidencia valida cualquiera de los siguientes elementos, segun el tipo de tarea:

- commit o conjunto de cambios identificable,
- build reproducible,
- artefacto firmado,
- log de prueba o validacion,
- captura o reporte tecnico de un flujo ejecutado,
- ADR aprobado,
- checklist de QA completado,
- resultado de prueba negativa o de recuperacion.

No se considera evidencia suficiente:

- una intencion,
- una afirmacion verbal,
- una captura aislada de UI sin prueba funcional asociada,
- un documento sin implementacion o validacion.

## Reglas para actualizar documentos de desarrollo

### `DEVELOPMENT_TABLE.md`

Actualizar cuando cambie:

- prioridad,
- orden de ejecucion,
- riesgo principal,
- primer entregable tecnico,
- decision pendiente relevante.

### `DEVELOPMENT_MATRIX.md`

Actualizar en cada ciclo si cambia:

- estado,
- responsable,
- evidencia,
- siguiente accion,
- riesgo activo.

### `DEVELOPMENT_VERSIONS.md`

Actualizar cuando exista:

- cambio de plan,
- hito tecnico,
- nueva version documental,
- candidato de release.

### `EXECUTIVE_IMPLEMENTATION_PLAN.md`

Actualizar cuando cambie:

- alcance V1,
- fases H0-H5,
- criterio de salida,
- ruta priorizada.

## Reglas de priorizacion

- `P0`: bloquea arquitectura base, imagen minima o MVP recuperable.
- `P1`: necesario para V1 despues de cerrar dependencias P0.
- `P2`: importante para pilotos, crecimiento o formalizacion final.

Cuando exista conflicto, prevalece siempre:

1. recuperacion de sistema,
2. integridad del artefacto,
3. seguridad minima,
4. mantenibilidad,
5. amplitud funcional.

## Reglas de calidad

- Toda nueva capacidad debe definir su responsable funcional.
- Toda capacidad persistente debe declarar su politica de datos, migracion y recuperacion.
- Todo servicio privilegiado debe explicitar su frontera de confianza.
- Toda funcionalidad Business debe probar aislamiento entre organizaciones.
- Todo cambio de UX que afecte seguridad o recovery requiere prueba negativa.
- Todo rootfs o bundle live basado en Debian debe incluir un keyring OpenPGP compatible con APT y `sqv`, evitando depender de keyboxes del host o de rutas `signed-by` no reproducibles.
- Toda composicion pesada en WSL debe ejecutarse preferentemente sobre almacenamiento nativo Linux y sincronizar el resultado final al workspace solo al cierre del proceso.
- Toda imagen live debe limpiar marcadores de primer arranque persistentes antes de generar `filesystem.squashfs` y exponer branding coherente en `/etc/os-release`.
- Toda validacion UEFI en Hyper-V debe registrar explicitamente si la ISO arranca con Secure Boot activado o requiere desactivarlo; por ahora las ISOs W4 se validan con Secure Boot desactivado.
- Todo perfil de instalacion unattended debe usar un selector de disco estable e inequívoco, referenciar secretos por origen externo en vez de persistirlos y revalidar el destino justo antes de escribir en disco.
- Mientras no exista un ejecutor privilegiado validado, el MVP de instalacion solo puede generar y probar planes declarativos para discos vacios; redimensionamiento, preservacion y ejecucion destructiva quedan fuera de alcance.
- Todo inventario real de discos usado por la fase de instalacion debe conservar evidencia de solo lectura, firmas detectadas y, cuando exista, al menos un identificador estable reutilizable por el selector unattended; si el entorno no expone esa identidad, el plan destructivo debe bloquearse y volver a recolectarse desde la sesion live objetivo.
- Todo ejecutor privilegiado generado para instalacion debe arrancar en modo `check-only` por defecto, exigir secretos por archivo externo y declarar de forma explicita la fuente del sistema a desplegar antes de permitir escritura en disco.
- Todo bundle preparado desde inventario real debe conservar tanto el perfil base como un perfil derivado con el selector efectivo del disco objetivo, para que la evidencia de deteccion y la evidencia de ejecucion sigan siendo auditables por separado.
- Toda prueba `check-only` del instalador sobre la ISO live debe tolerar entornos minimos sin `wipefs` o `blkid`, degradando la deteccion de firmas a `lsblk` y ausencia de particiones; una corrida destructiva real sigue requiriendo utilidades suficientes para revalidar y materializar el layout completo.
- Toda revalidacion de tamaño de disco en `check-only` debe normalizar la salida del host y aceptar solo una tolerancia minima de hasta `1 MiB`, manteniendo obligatoria la coincidencia por identificador estable (`serial`, `wwid` o `by_path`) antes de permitir cualquier etapa posterior.
- Toda logica PHP reutilizable del sistema de build, instalacion y automatizacion debe residir en `src/` bajo namespace `W4\\OS\\...` y quedar alineada con `composer.json`; los archivos en `scripts/` deben actuar como entrypoints delgados compatibles con Composer y no como ubicacion primaria de librerias compartidas.
- Todo cambio relevante en clases bajo `src/` debe ir acompañado por pruebas PHPUnit en `tests/` y ejecutarse con `composer test` o `vendor/bin/phpunit` como parte de la verificacion local del ciclo.
- Toda preparacion de ejecucion destructiva del instalador debe materializarse en `build/install/<perfil>/runtime/` mediante tooling versionado, con fuente del sistema explicitada en `install.env`, runners separados para `check-only` y ejecucion real, y secretos efimeros (`disk-passphrase.txt`, `local-user-password.txt`) fuera del seguimiento de Git.
- Toda transferencia del instalador hacia una VM live debe generarse como un payload autocontenido en `build/install-transfer/<perfil>/`, reescribiendo `runtime/install.env` para rutas internas del paquete y evitando declarar secretos que no hayan sido copiados efectivamente.
- Toda comprobacion de binarios dentro del sistema destino debe ejecutarse con un shell real dentro del `chroot` (por ejemplo `chroot ... /bin/bash -lc "command -v ..."`) y nunca con `chroot ... command -v`, porque `command` es un builtin del shell y genera falsos negativos en la validacion de dependencias del instalador.

## Regla de bloqueo

Detener avance y replanificar cuando ocurra cualquiera de estas condiciones:

- un ADR base entra en conflicto con la implementacion iniciada,
- el prototipo contradice el contrato documental de recovery,
- una prueba demuestra perdida de datos no aceptada,
- un entregable no tiene responsable,
- se descubre que el alcance anunciado supera la capacidad real del proyecto.

## Criterio de cierre de trabajo

Una tarea solo puede marcarse como cerrada cuando:

1. existe artefacto tecnico o decision aprobada,
2. existe evidencia verificable,
3. la matriz fue actualizada,
4. el registro de versiones fue actualizado si aplica,
5. el cambio no deja inconsistencia entre documentos de desarrollo.

## Estado inicial de adopcion

Estas guidelines entran en vigor desde `2026-09-09` y aplican a los ciclos de desarrollo posteriores a la creacion de la base documental de `Docs/DEVELOPMENT`.
