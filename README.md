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
- laboratorios reales de `Update y recovery` en `W4-OS-Home-Test` y `W4-OS-Business-Test`, ya capaces de persistir `failed`, `last_error` y eventos durables cuando APT no resuelve los paquetes W4 esperados,
- recorridos exitosos end-to-end de `Update y recovery` ya validados en `W4-OS-Business-Test` y `W4-OS-Home-Test` hasta `pending_health`, reboot, health checks y `confirmed`,
- `php-cli` formalizado en el baseline base para sostener el runtime actual del coordinador de `Update y recovery`,
- `btrfs-progs` formalizado en el baseline base para sostener snapshots Btrfs durante `MX-004`,
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

Las ejecuciones reales en VM ya confirmaron primero la persistencia correcta del fallo cuando APT no encontraba `w4-recovery-tools`, `w4-base-meta`, `w4-home-meta` o `w4-business-meta`; despues, con el repositorio APT W4 de laboratorio, tanto `W4-OS-Business-Test` como `W4-OS-Home-Test` ya completaron el recorrido de `MX-004` hasta `pending_health`, reboot, health checks y `confirmed`. El laboratorio tambien expuso que el sistema instalado necesitaba `btrfs-progs` para materializar snapshots Btrfs, por lo que ese prerequisito ya fue absorbido en el baseline base junto con `php-cli`, propagado a `build-input`, recompilado dentro de `rootfs` y verificado ya en `filesystem.manifest` de `live` e `iso` para Home y Business. Ademas, el repositorio APT de update ya no depende de un catalogo hardcodeado: ahora deriva `w4-base-meta`, `w4-desktop-meta`, `w4-home-meta`, `w4-business-meta` y `w4-recovery-tools` desde los manifests y perfiles reales del proyecto, deja trazada su procedencia en `package-sources.json` y ya expone un layout `dists/<channel>` que el runner offline puede priorizar automaticamente como source recomendada antes de caer al modo plano de laboratorio.

En la operacion de laboratorio vigente, el uso recomendado ya es cargar `repo.env` del bundle reconstruido y exportar `W4_UPDATE_APT_SOURCE_MODE=dists` junto con `W4_UPDATE_APT_SOURCE_LINE_DISTS_LOCAL` antes de ejecutar `run-update-offline.sh`. Los bundles vigentes de `w4-update-smoke-003` y `w4-update-business-smoke-001` ya quedaron regenerados con ese contrato operativo.

Para reducir pasos manuales en la siguiente validacion real, el bundle del ejecutor ahora tambien expone `run-update-with-repo-env.sh`: ese wrapper carga `repo.env`, deriva la source APT desde la ruta real del repositorio copiado al sistema objetivo y luego delega en `run-update-offline.sh`. Con ello, la operacion en VM puede lanzarse apuntando solo a `--repo-dir /ruta/al/repositorio`.

La validacion real de Home usando ya el layout `dists` tambien quedo rehecha sobre `W4-OS-Home-Test`: el flujo avanzo de nuevo hasta `pending_health`, se reinicio la VM, se desbloqueo LUKS y luego `run-health-checks.sh` + `reconcile-after-reboot.sh` volvieron a cerrar `operation.json.stage=confirmed`, con evidencia descargada en `build/update/validation/w4-update-smoke-003-dists-home/`. Ese mismo recorrido ya quedo repetido tambien en `W4-OS-Business-Test` usando los entrypoints PHP nuevos, con evidencia en `build/update/validation/w4-update-business-smoke-001-dists-business-php/`. Para repetir ese camino desde el host se incorporaron dos helpers operativos en PHP: `scripts/run_update_validation_via_paramiko.php` para la fase de copia + aplicacion + reboot, y `scripts/complete_pending_health_via_paramiko.php` para cerrar una operacion ya desbloqueada en `pending_health`. Con ello, `scripts/` vuelve a quedar alineado con la convencion del proyecto: entrypoints en PHP aunque el transporte de laboratorio siga apoyandose en la toolchain local de Paramiko. Ademas, el bundle del repositorio APT ya quedo preparado para firma GPG opcional: `generate_update_repository_bundle.php` publica `apt-source.signed.list.template`, exporta `W4_REPOSITORY_KEYRING_RELATIVE_PATH` y `W4_UPDATE_APT_SOURCE_LINE_SIGNED_*` en `repo.env`, y el wrapper `run-update-with-repo-env.sh` usa automaticamente `signed-by=` cuando el keyring del repositorio existe. Ese circuito ya puede ejecutarse de forma automatizada desde Windows con `scripts/run_update_repository_bundle_in_wsl.php`, que ahora soporta `--signing-mode gpg`, `--gpg-key-id`, `--gpg-homedir` y `--generate-lab-key`; la evidencia generada en `build/update/repository-output/w4-main-2026-09-20T180000Z-signed-auto/` ya incluye `InRelease`, `Release.gpg` y `keyrings/w4-update-archive-keyring.gpg`. Sobre esa misma salida firmada ya se completo una revalidacion real de `MX-004` en `W4-OS-Home-Test` hasta `stage=confirmed`, con evidencia en `build/update/validation/w4-update-smoke-003-signed-home/`. Durante ese cierre se absorbieron dos detalles operativos del laboratorio firmado: el helper host->VM ahora sincroniza automaticamente el reloj remoto antes de `apt-get` cuando detecta un desfase grande, y copia el repositorio a una ruta publica temporal en `/var/tmp/...` para que `_apt` y `sqv` puedan leer sin bloquearse por permisos del `home` del usuario remoto.

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
