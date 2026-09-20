# W4 OS System

**W4 OS System** es la iniciativa de W4 para crear sistemas operativos derivados de Linux, pensados para ofrecer una experiencia moderna, mantenible y segura tanto para hogares como para organizaciones.

No se trata solo de personalizar un escritorio. El objetivo del proyecto es construir una base de sistema coherente, con identidad propia, instalada sobre fundamentos solidos de Linux y Debian, y preparada para evolucionar hacia una familia completa de sistemas operativos W4.

## Vision

W4 OS System busca desarrollar una plataforma capaz de reunir:

- una base comun reutilizable,
- una experiencia de escritorio clara y consistente,
- instalacion, actualizacion y recuperacion confiables,
- seguridad integrada desde el diseno,
- y la posibilidad de crear distintas ediciones segun el contexto de uso.

## Familia W4 OS

La arquitectura documental actual del proyecto plantea una familia construida sobre una base compartida:

- `W4 Linux Base`: nucleo comun de integracion del sistema.
- `W4 OS Home`: orientado a uso personal, familiar y domestico.
- `W4 OS Business`: orientado a administracion, control y operacion en entornos organizacionales.

## Que contiene este repositorio

Este repositorio ya combina base conceptual, tooling tecnico y artefactos de trabajo:

- documentacion de arquitectura,
- decisiones tecnicas y de producto,
- pipeline PHP versionado para manifiestos, build e instalacion,
- perfiles de edicion e instalacion,
- pruebas PHPUnit sobre la base reusable y los scripts CLI,
- artefactos generados de `build`, `live`, `iso` e instalacion,
- y documentos de seguimiento para llevar el proyecto de diseno a implementacion real.

## Principios del proyecto

- Construir sobre Linux con una base mantenible.
- Priorizar confiabilidad antes que complejidad innecesaria.
- Separar claramente documentacion, implementacion y evidencia tecnica.
- Diseñar para Home y Business sobre una base compartida.
- Avanzar con foco en instalacion, actualizacion y recuperacion antes de ampliar alcance.

## Estado actual

Actualmente, **W4 OS System** ya supero la fase puramente documental inicial y cuenta con evidencia tecnica real en varias capas del pipeline:

- manifiestos versionados y resolucion de perfiles,
- ensamblado de `rootfs`, `live` e `iso`,
- validacion de arranque UEFI en VM para Home y Business,
- instalacion destructiva end-to-end validada en VirtualBox para Home y Business con `GPT + ESP + /boot + LUKS2 + Btrfs`,
- primera base ejecutable para `Update y recovery` con `update-plan`, almacen durable, transiciones persistidas, reconciliacion post-reinicio y bundle offline con health checks,
- primer laboratorio real de `Update y recovery` en `W4-OS-Home-Test`, ya capaz de persistir `failed`, `last_error` y eventos durables cuando APT no resuelve los paquetes W4 esperados,
- base PHP estructurada como paquete Composer,
- y suite PHPUnit para toolkits y scripts principales.

La documentacion sigue siendo el contrato rector del proyecto, pero el repositorio ya contiene implementacion verificable y evidencia operativa para `Supply`, `Build` e `Instalacion`.

## Ruta inicial

La prioridad del proyecto es construir, en este orden:

1. decisiones base de plataforma,
2. supply, build y repositorios, ya materializados con artefactos reproducibles de trabajo,
3. instalacion funcional, ya validada de punta a punta en VM para Home y Business,
4. update y recovery, siguiente frente prioritario del proyecto,
5. baseline minima de seguridad,
6. experiencia de escritorio V1,
7. edicion Home utilizable,
8. piloto Business controlado.

## Siguiente ciclo

El siguiente ciclo tecnico recomendado corresponde a `MX-004 · Update y recovery` y se centra en:

- coordinador durable de actualizacion con `operation_id`,
- snapshot previo a aplicar cambios,
- ejecucion offline con registro de estados,
- health check post-arranque,
- y recovery manual probado con evidencia.

La base inicial de este frente ya existe en el repositorio mediante `src/Update/UpdateToolkit.php`, `scripts/generate_update_plan.php` y `scripts/prepare_update_operation.php`.

La segunda capa ya incorpora `scripts/advance_update_operation.php`, `scripts/reconcile_update_operation.php` y `scripts/generate_update_executor.php` para laboratorio y trazabilidad del coordinador.

La tercera capa ya genera un bundle offline con `run-update-offline.sh`, `run-health-checks.sh` y `reconcile-after-reboot.sh`, incluyendo `staging`, snapshot previo y checks locales verificables antes de confirmar la operacion.

La primera ejecucion real en VM ya confirmo la persistencia correcta del fallo cuando APT no encuentra `w4-recovery-tools`, `w4-base-meta` y `w4-home-meta`; el siguiente paso directo es resolver una fuente de paquetes W4 accesible o adaptar el smoke para completar el recorrido hasta snapshot, reboot y reconciliacion final.

## Documentacion clave

- `Docs/INDICE_W4_OS.md`
- `Docs/W4-OS/001_W4_OS_PROJECT_CONTEXT.md`
- `Docs/W4-OS/401_W4_OS_ARCHITECTURE_DECISION_RECORDS.md`
- `Docs/W4-OS/414_W4_OS_ROADMAP.md`
- `Docs/W4-OS/415_W4_OS_FINAL_ARCHITECTURE_OVERVIEW.md`
- `Docs/DEVELOPMENT/EXECUTIVE_IMPLEMENTATION_PLAN.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_MATRIX.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_VERSIONS.md`

## Enfoque

W4 OS System no nace como una simple distribucion derivada sin direccion. Nace como una propuesta para construir una linea de sistemas operativos W4 con arquitectura clara, criterio de evolucion y una implementacion que pueda sostenerse con el tiempo.
