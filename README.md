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
- `W4 OS Server`: composicion headless en bootstrap para administracion remota y servicios sobre la misma base comun.

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
- endurecimiento tecnico reciente del pipeline de instalacion para rechazar discos `read_only`, resolver el subvolumen raiz por `mountpoint=/` en `verify-installation.sh`, estabilizar el contrato JSON de los CLI ante raices escalares y normalizar permisos base del target instalado,
- primera base ejecutable para `Update y recovery` con `update-plan`, almacen durable, transiciones persistidas, reconciliacion post-reinicio y bundle offline con health checks,
- laboratorios reales de `Update y recovery` en `W4-OS-Home-Test` y `W4-OS-Business-Test`, ya capaces de persistir `failed`, `last_error` y eventos durables cuando APT no resuelve los paquetes W4 esperados,
- recorridos exitosos end-to-end de `Update y recovery` ya validados en `W4-OS-Business-Test` y `W4-OS-Home-Test` hasta `pending_health`, reboot, health checks y `confirmed`,
- endurecimiento tecnico reciente de `UpdateToolkit` para estabilizar el contrato JSON de los CLI, alinear `apply_mode=live-apt`, iniciar `health-report.json` en `planned` y persistir metadata real del snapshot cuando el backend es `snapper`,
- `php-cli` formalizado en el baseline base para sostener el runtime actual del coordinador de `Update y recovery`,
- `btrfs-progs` formalizado en el baseline base para sostener snapshots Btrfs durante `MX-004`,
- apertura tecnica de `W4 OS Server` con Base sin desktop obligatorio, perfil Server headless, politica inicial separada, propagacion de Debian `trixie` como codename efectivo y publisher capaz de generar `--package-set server`,
- instalador MVP de `W4 OS Server` con perfil unattended, inventario Hyper-V de laboratorio y bundle `build/install/w4-os-server`,
- `W4 OS Server` materializado en WSL con rootfs Debian `trixie`, arbol live, ISO `build/iso-output/w4-os-server/w4-os-server-live-amd64.iso` y manifiesto sin paquetes desktop prohibidos,
- gate PHPUnit de artefacto Server para verificar checksum de ISO, metadata y ausencia de paquetes desktop prohibidos,
- preflight y smoke live de `W4 OS Server` en VirtualBox EFI con autologin `w4live`, identidad Server, SSH activo y disco desechable visible,
- instalacion de `W4 OS Server` en VM VirtualBox desechable con LUKS2, Btrfs, subvolumen `/srv`, primer boot cifrado y SSH validado con `w4admin`,
- baseline runtime de `W4 OS Server` ejecutado en VM instalada, con fix aplicado para que el live bundle transporte/aplique `system-overlay` antes del squashfs y el instalador preserve `w4-firstboot.service` en el target,
- reinstalacion fresca de `W4 OS Server` desde ISO regenerada con `w4-firstboot.service` activo, UFW habilitado con `DEFAULT_INPUT_POLICY=DROP`, contrato SSH Server formalizado y baseline runtime en `9 passed`, `0 failed`, `1 skipped`,
- decision `C-100` aplicada y materializada para que la ISO Server incorpore nativamente las herramientas de instalacion VM (`gdisk`, `parted`, `dosfstools`, `e2fsprogs`, `squashfs-tools`) y elimine el bootstrap temporal por APT; la ISO autocontenida vigente queda en SHA256 `b5fbb8915e6af8760298c9e5b3b7f9eb797f20e1ddabd4c51fd2c6db925c3f21`,
- instalacion VM repetida desde la ISO Server autocontenida sin ejecutar bootstrap temporal por APT, con `INSTALL_EXIT=0`, primer boot LUKS correcto, `w4-firstboot.service` activo, UFW en `DEFAULT_INPUT_POLICY=DROP` y baseline runtime en `9 passed`, `0 failed`, `1 skipped`,
- base PHP estructurada como paquete Composer,
- y suite PHPUnit para toolkits y scripts principales.

La documentacion sigue siendo el contrato rector del proyecto, pero el repositorio ya contiene implementacion verificable y evidencia operativa para `Supply`, `Build` e `Instalacion`.
Tambien queda explicitado que la especificacion amplia de `W4-Linux-Base` describe en varias areas una arquitectura objetivo mas avanzada que el estado tecnico hoy validado en este repositorio; la comparacion trazable entre ambas capas se registra en `Docs/DEVELOPMENT/W4_LINUX_BASE_GAP_MATRIX.md`.
Como ajuste inmediato derivado de esa revision, `manifests/w4-linux-base.manifest.json` ya declara de forma explicita que `w4-linux-base` es base reusable de Home, Business y Server.
Tambien quedaron materializadas politicas explicitas para `Home` y `Business` en `config/editions/home/policy.json` y `config/editions/business/policy.json`, alineando la capa declarativa de las tres ediciones aunque la paridad funcional completa siga pendiente.
Ademas, `generate_system_overlay.php` ya consume esa capa de politica para derivar `hostname_prefix` y `boot.default_target`, y `w4-firstboot.sh` aplica el target por defecto declarado en cada edicion.

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

El siguiente ciclo tecnico activo corresponde a `MX-006 · Escritorio oficial` y se centra en:

- comparar KDE y GNOME como candidatos reales de V1,
- medir impacto en ISO, memoria base, accesibilidad y mantenimiento,
- fijar el escritorio oficial unico para Home,
- traducir esa decision al alcance de `shell`, branding, onboarding y apps base,
- y desbloquear de forma ordenada `MX-007`, `MX-008` y `MX-009`.

El siguiente paso operativo es ejecutar la matriz comparativa y aprobar el ADR del escritorio oficial antes de abrir implementacion UX.

La referencia historica de `MX-004 · Update y recovery` se centro en:

- coordinador durable de actualizacion con `operation_id`,
- snapshot previo a aplicar cambios,
- ejecucion offline con registro de estados,
- health check post-arranque,
- y recovery manual probado con evidencia.

La base inicial de este frente ya existe en el repositorio mediante `src/Update/UpdateToolkit.php`, `scripts/generate_update_plan.php` y `scripts/prepare_update_operation.php`.

La segunda capa ya incorpora `scripts/advance_update_operation.php`, `scripts/reconcile_update_operation.php` y `scripts/generate_update_executor.php` para laboratorio y trazabilidad del coordinador.

La tercera capa ya genera un bundle offline con `run-update-offline.sh`, `run-health-checks.sh` y `reconcile-after-reboot.sh`, incluyendo `staging`, snapshot previo y checks locales verificables antes de confirmar la operacion.

Las ejecuciones reales en VM ya confirmaron primero la persistencia correcta del fallo cuando APT no encontraba `w4-recovery-tools`, `w4-base-meta`, `w4-home-meta` o `w4-business-meta`; despues, con el repositorio APT W4 de laboratorio, tanto `W4-OS-Business-Test` como `W4-OS-Home-Test` ya completaron el recorrido de `MX-004` hasta `pending_health`, reboot, health checks y `confirmed`.

El laboratorio tambien expuso que el sistema instalado necesitaba `btrfs-progs` para materializar snapshots Btrfs, por lo que ese prerequisito ya fue absorbido en el baseline base junto con `php-cli`, propagado a `build-input`, recompilado dentro de `rootfs` y verificado ya en `filesystem.manifest` de `live` e `iso` para Home y Business. Ademas, el repositorio APT de update ya no depende de un catalogo hardcodeado: ahora deriva `w4-base-meta`, `w4-desktop-meta`, `w4-home-meta`, `w4-business-meta` y `w4-recovery-tools` desde los manifests y perfiles reales del proyecto, deja trazada su procedencia en `package-sources.json` y ya expone un layout `dists/<channel>` que el runner offline puede priorizar automaticamente como source recomendada antes de caer al modo plano de laboratorio.

En la operacion de laboratorio vigente, el uso recomendado ya es cargar `repo.env` del bundle reconstruido y exportar `W4_UPDATE_APT_SOURCE_MODE=dists` junto con `W4_UPDATE_APT_SOURCE_LINE_DISTS_LOCAL` antes de ejecutar `run-update-offline.sh`. Los bundles vigentes de `w4-update-smoke-003` y `w4-update-business-smoke-001` ya quedaron regenerados con ese contrato operativo.

Para reducir pasos manuales en la siguiente validacion real, el bundle del ejecutor ahora tambien expone `run-update-with-repo-env.sh`: ese wrapper carga `repo.env`, deriva la source APT desde la ruta real del repositorio copiado al sistema objetivo y luego delega en `run-update-offline.sh`. Con ello, la operacion en VM puede lanzarse apuntando solo a `--repo-dir /ruta/al/repositorio`.

La validacion real de Home usando ya el layout `dists` tambien quedo rehecha sobre `W4-OS-Home-Test`: el flujo avanzo de nuevo hasta `pending_health`, se reinicio la VM, se desbloqueo LUKS y luego `run-health-checks.sh` + `reconcile-after-reboot.sh` volvieron a cerrar `operation.json.stage=confirmed`, con evidencia descargada en `build/update/validation/w4-update-smoke-003-dists-home/`.

Ese mismo recorrido ya quedo repetido tambien en `W4-OS-Business-Test` usando los entrypoints PHP nuevos, con evidencia en `build/update/validation/w4-update-business-smoke-001-dists-business-php/`. Para repetir ese camino desde el host se incorporaron dos helpers operativos en PHP: `scripts/run_update_validation_via_paramiko.php` para la fase de copia + aplicacion + reboot, y `scripts/complete_pending_health_via_paramiko.php` para cerrar una operacion ya desbloqueada en `pending_health`.

Con ello, `scripts/` vuelve a quedar alineado con la convencion del proyecto: entrypoints en PHP aunque el transporte de laboratorio siga apoyandose en la toolchain local de Paramiko.

Ademas, el bundle del repositorio APT ya quedo preparado para firma GPG opcional: `generate_update_repository_bundle.php` publica `apt-source.signed.list.template`, exporta `W4_REPOSITORY_KEYRING_RELATIVE_PATH` y `W4_UPDATE_APT_SOURCE_LINE_SIGNED_*` en `repo.env`, y el wrapper `run-update-with-repo-env.sh` usa automaticamente `signed-by=` cuando el keyring del repositorio existe. Ese circuito ya puede ejecutarse de forma automatizada desde Windows con `scripts/run_update_repository_bundle_in_wsl.php`, que ahora soporta `--signing-mode gpg`, `--signing-profile`, `--gpg-key-id`, `--gpg-homedir`, `--gpg-secret-key-file`, `--gpg-ownertrust-file` y `--generate-lab-key`; con ello, el build firmado puede consumir tanto una clave efimera de laboratorio como material GPG persistente exportado en archivos desde el host Windows sin preprovisionar manualmente el homedir dentro de WSL.

La evidencia generada en `build/update/repository-output/w4-main-2026-09-20T180000Z-signed-auto/` ya incluye `InRelease`, `Release.gpg` y `keyrings/w4-update-archive-keyring.gpg`. Sobre esa misma salida firmada ya se completaron revalidaciones reales de `MX-004` tanto en `W4-OS-Home-Test` como en `W4-OS-Business-Test`, ambas hasta `stage=confirmed`, con evidencia en `build/update/validation/w4-update-smoke-003-signed-home/` y `build/update/validation/w4-update-business-smoke-001-signed-business/`.

Durante ese cierre se absorbieron varios detalles operativos del laboratorio firmado: el helper host->VM ahora sincroniza automaticamente el reloj remoto antes de `apt-get` cuando detecta un desfase grande, copia el repositorio a una ruta publica temporal en `/var/tmp/...` para que `_apt` y `sqv` puedan leer sin bloquearse por permisos del `home` remoto, y expone `--unlock-wait` para retrasar la inyeccion de la passphrase LUKS cuando la VM tarda mas en presentar el prompt post-reboot. En Business, ademas, quedo revalidado que conviene mantener la VM arrancando desde disco y con la ISO live desacoplada antes de repetir este flujo firmado. A partir de ahi, el laboratorio ya dio el siguiente paso real hacia publicacion: se genero un homedir persistente de firma en WSL (`/var/tmp/w4-os-system/signing-w4`) con la identidad `W4-Update-Prod`, se reconstruyo el repositorio firmado en `build/update/repository-output/w4-main-2026-09-20T180000Z-signed-prod/` y tanto `W4-OS-Home-Test` como `W4-OS-Business-Test` ya volvieron a consumir ese repo hasta `stage=confirmed`, con `health-report.status=ok` y evidencia descargada en `build/update/validation/w4-update-smoke-003-signed-prod-home/` y `build/update/validation/w4-update-business-smoke-001-signed-prod-business/`.

Esa promocion ya quedo reflejada tambien en el runner firmado: `--signing-profile prod` fija por convenio `W4-Update-Prod`, `/var/tmp/w4-os-system/signing-w4` y una salida `-signed-prod`, reduciendo el margen de error al reconstruir/publicar el repo oficial. Sobre esa base, el proyecto ya cuenta tambien con `scripts/publish_update_repository.php`, un entrypoint oficial que encapsula la ruta productiva, valida que la salida publicada contenga `InRelease`, `Release.gpg`, `keyrings/w4-update-archive-keyring.gpg` y un `repo.env` alineado con `signed-by=`, y deja `publication-manifest.json` como evidencia autocontenida de la publicacion.

Ese publisher oficial ya fue reejecutado sobre el bundle vigente y, a continuacion, Home y Business volvieron a consumir exactamente esa salida republicada hasta `stage=confirmed`, con nueva evidencia descargada en `build/update/validation/w4-update-smoke-003-signed-prod-home-rerun/` y `build/update/validation/w4-update-business-smoke-001-signed-prod-business-rerun/`. Durante esa revalidacion final se confirmo de nuevo el patron operativo del prompt LUKS: ambos sistemas arrancan correctamente desde disco, pero cuando se parte de una VM apagada hace falta un primer desbloqueo manual previo al acceso SSH; despues, el helper sigue siendo capaz de completar la corrida completa hasta health checks y reconciliacion, y en Business puede seguir apareciendo el snapshot sobrante `pre-update-w4-update-business-smoke-001`, que conviene limpiar antes de reintentar la operacion si ya existe. La repeticion del mismo timing sobre el prompt LUKS en ambas ediciones tambien dejo reforzado el helper host->VM: `run_update_validation_via_paramiko.php` ya incorpora `--unlock-retry-interval` y `--unlock-retries` para reinyectar automaticamente la passphrase mientras SSH aun no vuelve de forma estable. Sobre esa base, `UpdateToolkit` quedo ahora endurecido en su contrato interno: los CLI de update ya rechazan JSON validos con raiz escalar mediante `ValidationError`, el `health-report.json` inicial nace alineado con `operation.json.stage=planned`, el `update-plan` publica `apply_mode=live-apt` como valor realmente soportado por el ejecutor y el `snapshot-manifest.json` del runner offline ya distingue `snapshot_backend`, `snapshot_reference` y `snapshot_path` real cuando la captura se hace con `snapper`.

## Documentacion clave

- `Docs/INDICE_W4_OS.md`
- `Docs/W4-OS/001_W4_OS_PROJECT_CONTEXT.md`
- `Docs/W4-OS/401_W4_OS_ARCHITECTURE_DECISION_RECORDS.md`
- `Docs/W4-OS/414_W4_OS_ROADMAP.md`
- `Docs/W4-OS/415_W4_OS_FINAL_ARCHITECTURE_OVERVIEW.md`
- `Docs/DEVELOPMENT/EXECUTIVE_IMPLEMENTATION_PLAN.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_MATRIX.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_VERSIONS.md`
- `Docs/DEVELOPMENT/W4_LINUX_BASE_GAP_MATRIX.md`

## Enfoque

W4 OS System no nace como una simple distribucion derivada sin direccion. Nace como una propuesta para construir una linea de sistemas operativos W4 con arquitectura clara, criterio de evolucion y una implementacion que pueda sostenerse con el tiempo.

## Estado reciente

Tras confirmar que las instalaciones previas no incorporaban completamente el hardening nuevo de `MX-005`, el ciclo ya avanzo por Home y Business con recompilacion de artefactos (`rootfs`, `overlay`, `live`, `iso`), payloads de instalacion regenerados y secretos temporales limpios. En Business, la nueva ISO `build/iso-output/w4-os-business/w4-os-business-live-amd64.iso` quedo emitida con checksum `c386d548262d443de40665e61295047be715b9b097262db02d19b114a25d8c09`, y el `filesystem.manifest` confirma `apparmor`, `ufw`, `php-cli` y `btrfs-progs`.

Durante la reinstalacion fresca de `Home` aparecio un bloqueo nuevo: al forzar `W4_INSTALL_SOURCE_ROOTFS` para evitar descomprimir el squashfs completo dentro de la live, el target podia quedar sin `var/lib/dpkg`, rompiendo el `apt-get install -y cryptsetup-initramfs` dentro del `chroot`. Ese hueco ya fue absorbido en `generate_installation_executor.php`: el instalador ahora detecta cuando la fuente directa no trae estado de paquetes y repuebla `var/lib/apt` y `var/lib/dpkg` desde `W4_INSTALL_SOURCE_SQUASHFS`. En el mismo cierre se reforzo tambien el fallback del kernel: si reinstalar `linux-image-amd64` no devuelve `vmlinuz-*`, el instalador fuerza despues el paquete versionado (`linux-image-6.12.107+deb13-amd64` en la corrida actual) antes de regenerar `initrd`.

Con esos guardrails materializados, `Home` y `Business` ya fueron reinstalados desde artefactos endurecidos y ambos completaron el primer boot cifrado con login local. La revalidacion runtime de `MX-005` por Paramiko ya dejo reportes frescos en `build/security/validation/w4-os-home/security-baseline-report.json` y `build/security/validation/w4-os-business/security-baseline-report.json`, ambos con `9` controles aprobados, `0` fallidos y `1` omitido por depender de la evidencia firmada de `MX-004`. El baseline ahora incluye tambien `critical-filesystem-permissions`, `firewall-default-deny-incoming` y `apparmor-enforced-profiles`, verificando permisos criticos, UFW habilitado por configuracion con `DEFAULT_INPUT_POLICY=DROP` y perfiles AppArmor en modo enforce. Para transportar el bundle se usa una excepcion temporal de laboratorio (`openssh-server`, `ssh` iniciado sin quedar habilitado y regla `ufw allow 22/tcp`), retirada al terminar cada corrida; el playbook quedo documentado en `Docs/DEVELOPMENT/COMANDOS_DE_OPERACION_PSHELL_VB.md`.
