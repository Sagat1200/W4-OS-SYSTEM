# W4 OS · Development Versions

Registro de versiones del trabajo de desarrollo, util para relacionar decisiones, documentos, artefactos y cambios de alcance.

## Objetivo

Mantener trazabilidad entre:

- version documental,
- version del plan de implementacion,
- hitos tecnicos,
- cambios relevantes de alcance o prioridad.

## Convencion recomendada

- `DOC-x.y`: cambios en documentacion de desarrollo y gobernanza.
- `PLAN-x.y`: cambios en plan ejecutivo o prioridades.
- `TECH-x.y`: hitos tecnicos o entregables de implementacion.
- `REL-x.y`: candidatos de release o versiones publicables.

## Estado actual

| Tipo | Version | Fecha | Estado | Descripcion |
| --- | --- | --- | --- | --- |
| DOC | DOC-0.2 | 2026-09-09 | Activa | Base de gestion creada y README introductorio inicial publicado |
| PLAN | PLAN-0.1 | 2026-09-09 | Activa | Ruta ejecutiva inicial para V1 basada en la coleccion W4 OS |
| TECH | TECH-1.16 | 2026-09-15 | Activa | Runtime de instalacion preparado para Home y Business con fuente auto-detectada, runners controlados y secretos efimeros gitignored |
| REL | REL-0.0 | 2026-09-09 | Base | Sin candidato de release registrado |

## Historial

| Fecha | Tipo | Version | Cambio | Impacto | Referencias |
| --- | --- | --- | --- | --- | --- |
| 2026-09-09 | DOC | DOC-0.1 | Alta inicial de documentos `DEVELOPMENT_*` y `EXECUTIVE_IMPLEMENTATION_PLAN` | Se establece la base operativa del proyecto para seguimiento de desarrollo | `DEVELOPMENT_TABLE.md`, `DEVELOPMENT_MATRIX.md`, `DEVELOPMENT_VERSIONS.md`, `DEVELOPMENT_GUIDELINES.md`, `EXECUTIVE_IMPLEMENTATION_PLAN.md` |
| 2026-09-09 | DOC | DOC-0.2 | Creacion del `README.md` introductorio del proyecto `W4 OS System` | Se define una presentacion base del repositorio con vision, familia de productos y documentos clave | `README.md` |
| 2026-09-09 | PLAN | PLAN-0.1 | Definicion inicial del orden de implementacion V1 | Se prioriza H0-H5 con foco en MVP recuperable | `DEVELOPMENT_TABLE.md`, `EXECUTIVE_IMPLEMENTATION_PLAN.md` |
| 2026-09-09 | TECH | TECH-0.1 | Creacion del sistema inicial de manifiestos y validador | El proyecto dispone ya de una fuente versionada para base, Home y Business, y de una verificacion automatica de consistencia | `manifests/w4-linux-base.manifest.json`, `manifests/w4-os-home.profile.json`, `manifests/w4-os-business.profile.json`, `scripts/validate_manifests.php` |
| 2026-09-09 | TECH | TECH-0.2 | Creacion del pipeline minimo de exportacion de build | El proyecto ya puede validar perfiles y generar artefactos `build-input` consumibles por la siguiente etapa del sistema de build | `scripts/lib/ManifestToolkit.php`, `scripts/generate_build_input.php`, `build/inputs/w4-os-home.build-input.json`, `build/inputs/w4-os-business.build-input.json` |
| 2026-09-09 | TECH | TECH-0.3 | Creacion de la etapa de ensamblado de raiz | El proyecto ya puede generar bundles de `rootfs` con metadata, listas de paquetes y script de ejecucion Linux preparado para `debootstrap` | `scripts/generate_rootfs_bundle.php`, `build/rootfs/w4-os-home/rootfs-manifest.json`, `build/rootfs/w4-os-home/build-rootfs.sh`, `build/rootfs/w4-os-business/rootfs-manifest.json`, `build/rootfs/w4-os-business/build-rootfs.sh` |
| 2026-09-09 | TECH | TECH-0.4 | Adaptacion del ensamblado a WSL2 | El proyecto ya puede verificar Ubuntu WSL2, convertir rutas Windows/WSL y preparar la ejecucion del `rootfs`; el unico bloqueo actual detectado es la ausencia de `debootstrap` | `scripts/run_rootfs_in_wsl.php` |
| 2026-09-09 | TECH | TECH-0.5 | Inicio del ensamblado real del rootfs en WSL2 | El proyecto ya ejecuta el perfil `w4-os-home` dentro de Ubuntu WSL2 usando `mmdebstrap` y un keyring Debian actualizado por HTTPS; la instalacion del rootfs sigue en progreso y ya supera la fase de bootstrap base | `scripts/generate_rootfs_bundle.php`, `scripts/run_rootfs_in_wsl.php` |
| 2026-09-09 | TECH | TECH-0.6 | Finalizacion del primer rootfs Home y arranque de Business | `w4-os-home` ya completo con Debian 13, kernel, GRUB y paquetes clave; el mismo flujo corregido ya se relanza para `w4-os-business` | `scripts/generate_rootfs_bundle.php`, `scripts/run_rootfs_in_wsl.php` |
| 2026-09-09 | TECH | TECH-0.7 | Finalizacion del ciclo de rootfs para Home y Business | Ambas ediciones ya se ensamblan realmente en Ubuntu WSL2: Home validado con `452` paquetes y Business con `298`, incluyendo kernel, GRUB y paquetes base de cada perfil | `scripts/generate_rootfs_bundle.php`, `scripts/run_rootfs_in_wsl.php` |
| 2026-09-10 | TECH | TECH-0.8 | Integracion de overlay de sistema y primer inicio | Home y Business ya cuentan con overlay reproducible de identidad, `w4-firstboot.service`, `w4-live-prep.service`, scripts validados y estado de primer inicio ejecutado dentro del rootfs | `scripts/generate_system_overlay.php`, `scripts/run_overlay_in_wsl.php`, `build/overlays/w4-os-home/overlay-manifest.json`, `build/overlays/w4-os-business/overlay-manifest.json` |
| 2026-09-10 | TECH | TECH-0.9 | Normalizacion de keyring Debian para rootfs y live | El pipeline ahora exporta un keyring OpenPGP valido desde `debian-archive-current.gpg`, reescribe `sources.list` hacia `debian-archive-keyring.gpg` y deja la composicion live de Home avanzando mas alla de `apt-get update` hasta `mksquashfs` | `scripts/generate_rootfs_bundle.php`, `scripts/generate_live_bundle.php`, `build/rootfs/w4-os-home/build-rootfs.sh`, `build/rootfs/w4-os-business/build-rootfs.sh`, `build/live/w4-os-home/compose-live.sh`, `build/live/w4-os-business/compose-live.sh` |
| 2026-09-10 | TECH | TECH-1.0 | Cierre del bundle live reproducible para Home y Business | La composicion live ya produce `image-root`, `filesystem.squashfs`, `filesystem.manifest`, `filesystem.size`, `SHA256SUMS` y metadata final para ambos perfiles. El flujo ahora usa staging nativo WSL, desmonta pseudo-filesystems antes de comprimir y normaliza ownership del overlay a `root:root` | `scripts/generate_live_bundle.php`, `scripts/generate_system_overlay.php`, `scripts/run_live_bundle_in_wsl.php`, `scripts/run_overlay_in_wsl.php`, `build/live-output/w4-os-home`, `build/live-output/w4-os-business` |
| 2026-09-10 | TECH | TECH-1.1 | Composicion ISO UEFI para Home y Business | El proyecto ya genera `w4-os-home-live-amd64.iso` y `w4-os-business-live-amd64.iso` con checksum final desde una nueva etapa PHP `iso bundle`, usando `xorriso` y `grub-mkrescue` en Ubuntu WSL | `scripts/generate_iso_bundle.php`, `scripts/run_iso_bundle_in_wsl.php`, `build/iso/w4-os-home`, `build/iso/w4-os-business`, `build/iso-output/w4-os-home`, `build/iso-output/w4-os-business` |
| 2026-09-10 | TECH | TECH-1.2 | Correccion de identidad live tras validacion Hyper-V | La validacion de Home en Hyper-V permitio corregir dos detalles del pipeline live: la limpieza de `firstboot-complete` antes de empaquetar `filesystem.squashfs` y la publicacion de branding W4 en `/etc/os-release`. La ISO Home fue regenerada con ambos ajustes aplicados | `scripts/generate_system_overlay.php`, `scripts/generate_live_bundle.php`, `build/overlays/w4-os-home`, `build/live-output/w4-os-home`, `build/iso-output/w4-os-home/w4-os-home-live-amd64.iso` |
| 2026-09-11 | TECH | TECH-1.3 | Cierre de validacion VM para Home y Business | Las dos ISOs fueron validadas en Hyper-V con `w4-firstboot.service` y `w4-live-prep.service` exitosos, branding W4 en `os-release` y ajuste adicional del overlay para reemplazar el banner TTY heredado de Debian. Se documento tambien que la validacion actual requiere Secure Boot desactivado | `scripts/generate_system_overlay.php`, `build/iso-output/w4-os-home/w4-os-home-live-amd64.iso`, `build/iso-output/w4-os-business/w4-os-business-live-amd64.iso`, evidencia Hyper-V Home/Business |
| 2026-09-11 | TECH | TECH-1.4 | Primer artefacto tecnico de instalacion para VM vacia | El proyecto ya puede generar bundles `installation-plan` para Home y Business a partir de un `build-input`, un perfil de instalacion versionado y un inventario de discos. El generador valida selector estable, rechaza coincidencias ambiguas, bloquea el medio instalador y limita el MVP a discos vacios con layout `GPT + ESP + /boot + LUKS2 + Btrfs` | `scripts/lib/InstallerToolkit.php`, `scripts/generate_installation_plan.php`, `installer-profiles/w4-os-home.vm-install.json`, `installer-profiles/w4-os-business.vm-install.json`, `examples/install/hyperv-home-empty-disk.inventory.json`, `examples/install/hyperv-business-empty-disk.inventory.json`, `build/install/w4-os-home`, `build/install/w4-os-business` |
| 2026-09-12 | TECH | TECH-1.5 | Colector real de inventario de discos para la fase de instalacion | El proyecto ya puede recolectar inventario real de discos desde Linux local o desde una distribucion WSL invocada desde Windows, validarlo contra el esquema de instalacion y reutilizarlo como evidencia tecnica. La prueba real sobre Ubuntu WSL mostro ademas que ese entorno no expone identificadores estables suficientes para el selector unattended destructivo actual, y el planificador rechazo correctamente ese inventario al no poder enlazarlo con el perfil `w4-os-home` | `scripts/generate_disk_inventory.php`, `scripts/run_disk_inventory_in_wsl.php`, `build/install-inventory/hyperv-wsl-scan.json`, prueba de `scripts/generate_installation_plan.php` contra inventario WSL |
| 2026-09-12 | TECH | TECH-1.6 | Primer ejecutor privilegiado del bundle de instalacion | El proyecto ya puede transformar un `installation-plan` en un ejecutor versionado con `apply-installation.sh`, `verify-installation.sh` y un manifiesto de ejecucion. El flujo generado revalida el disco, prepara el layout `GPT + ESP + /boot + LUKS2 + Btrfs`, admite `rootfs` o `filesystem.squashfs` como fuente y arranca en modo `check-only` hasta recibir secretos externos y aprobacion explicita de ejecucion. Los scripts Home y Business quedaron validados con `bash -n` dentro de WSL | `scripts/generate_installation_executor.php`, `build/install/w4-os-home/apply-installation.sh`, `build/install/w4-os-home/verify-installation.sh`, `build/install/w4-os-home/installation-executor.json`, `build/install/w4-os-business/apply-installation.sh`, `build/install/w4-os-business/verify-installation.sh`, `build/install/w4-os-business/installation-executor.json` |
| 2026-09-12 | TECH | TECH-1.7 | Preparador de bundle hacia la prueba `check-only` | El proyecto ya puede tomar un `disk-inventory`, seleccionar el disco vacio escribible, derivar un perfil de instalacion con el selector real del hardware detectado, regenerar el `installation-plan` y volver a emitir el ejecutor dentro del mismo bundle. Home y Business quedaron validados con los inventarios Hyper-V de ejemplo, y la ruta negativa confirma el bloqueo correcto cuando el inventario WSL no ofrece un disco apto para prueba destructiva | `scripts/prepare_installation_bundle.php`, `build/install/w4-os-home/installation-profile.derived.json`, `build/install/w4-os-home/CHECK_ONLY_PREPARATION.txt`, `build/install/w4-os-business/installation-profile.derived.json`, `build/install/w4-os-business/CHECK_ONLY_PREPARATION.txt`, prueba negativa con `build/install-inventory/hyperv-wsl-scan.json` |
| 2026-09-12 | TECH | TECH-1.8 | Adaptacion de `check-only` a la ISO live minima y captura real de Hyper-V | El ejecutor ahora permite validar el binding del disco en modo `check-only` aunque la ISO live no incluya `wipefs` ni `blkid`, degradando la verificacion de firmas a `lsblk` y ausencia de particiones. Ademas, el repo ya conserva un inventario real manual de Hyper-V para Home con `/dev/sda` como destino y `/dev/sr0` como medio live, listo para regenerar el bundle con el selector efectivo de esa VM | `scripts/generate_installation_executor.php`, `build/install-inventory/hyperv-live-home.json`, evidencia de consola Hyper-V Home |
| 2026-09-12 | TECH | TECH-1.9 | Captura real de VirtualBox y reorientacion del flujo `check-only` | La validacion en VirtualBox confirmo un entorno live mas operable para la fase de instalacion y proporciono un selector estable reutilizable para `/dev/sda` mediante `ID_SERIAL` e `ID_PATH`. El repo ya conserva ese inventario real manual de Home, listo para regenerar el bundle derivado y ejecutar la validacion `check-only` sobre la VM de VirtualBox | `build/install-inventory/virtualbox-live-home.json`, evidencia de consola VirtualBox Home |
| 2026-09-13 | TECH | TECH-1.10 | Correccion del selector serial tras la primera prueba `check-only` real | La primera corrida `check-only` desde la VM de VirtualBox alcanzo la validacion real del disco pero revelo una incompatibilidad entre `ID_SERIAL` e `ID_SERIAL_SHORT`. El generador del ejecutor fue corregido para aceptar ambos formatos, y el bundle de Home fue regenerado para repetir la prueba sin cambiar el selector estable del inventario | `scripts/generate_installation_executor.php`, `build/install/w4-os-home/apply-installation.sh`, evidencia de `check-only` fallando con serial en VirtualBox |
| 2026-09-13 | TECH | TECH-1.11 | Captura real de VirtualBox para Business | La validacion en VirtualBox para Business confirmo un entorno live operable y proporciono un selector estable reutilizable para `/dev/sda` mediante `ID_SERIAL` e `ID_PATH`. El repo ya conserva ese inventario real manual de Business, listo para regenerar el bundle derivado y ejecutar la validacion `check-only` sobre la VM de VirtualBox | `build/install-inventory/virtualbox-live-business.json`, evidencia de consola VirtualBox Business |
| 2026-09-13 | TECH | TECH-1.12 | Revalidacion robusta del tamaño de disco en `check-only` | La primera corrida `check-only` de Business detecto un falso negativo al comparar el tamaño del disco con igualdad estricta. El generador del ejecutor ahora normaliza la salida de `lsblk` y acepta una tolerancia minima de `1 MiB`, manteniendo obligatoria la coincidencia por identificador estable; los bundles de Home y Business fueron regenerados con esta correccion | `scripts/generate_installation_executor.php`, `build/install/w4-os-home/apply-installation.sh`, `build/install/w4-os-business/apply-installation.sh` |
| 2026-09-15 | TECH | TECH-1.13 | Reestructuracion Composer del pipeline PHP | La logica reusable del sistema de build e instalacion ahora vive en `src/` bajo namespace `W4\\OS\\...`, con `composer.json` como contrato del paquete y `scripts/bootstrap.php` como puente de autoload para Composer o fallback local. Los wrappers en `scripts/lib` conservan compatibilidad con los CLI existentes mientras la base de codigo pasa a una estructura de paquete mantenible y testeable | `composer.json`, `src/Manifest/ManifestToolkit.php`, `src/Installer/InstallerToolkit.php`, `src/Support/ValidationError.php`, `src/Support/JsonPrinter.php`, `scripts/bootstrap.php`, `tests/ComposerPackageStructureTest.php` |
| 2026-09-15 | TECH | TECH-1.14 | Adopcion de PHPUnit para la base Composer | Se instalo PHPUnit mediante Composer con `composer.lock` y `vendor/`, se agrego `phpunit.xml.dist` y se incorporaron pruebas sobre `ManifestToolkit` e `InstallerToolkit` usando fixtures reales del repositorio. La suite ahora cubre autoload PSR-4, resolucion de manifiestos, fusion de paquetes, generacion de planes de instalacion y rechazo de selectores ambiguos | `composer.lock`, `vendor/bin/phpunit`, `phpunit.xml.dist`, `tests/ComposerPackageStructureTest.php`, `tests/ManifestToolkitTest.php`, `tests/InstallerToolkitTest.php` |
| 2026-09-15 | TECH | TECH-1.15 | Integracion CLI bajo PHPUnit para el bundle y el ejecutor | La suite se extendio para ejecutar `prepare_installation_bundle.php` y `generate_installation_executor.php` en directorios temporales, validando que los entrypoints CLI produzcan artefactos consistentes, hereden el selector real del disco y mantengan el ejecutor en modo `check-only` por defecto sin depender de los bundles persistidos del repo | `tests/InstallationScriptsIntegrationTest.php`, `vendor/bin/phpunit`, fixtures de `build/install` e inventarios `virtualbox-live-home.json` |
| 2026-09-15 | TECH | TECH-1.16 | Preparacion del runtime destructivo controlado | Se agrego `prepare_installation_runtime.php` como entrypoint Composer para preparar la ejecucion real del instalador: detecta `filesystem.squashfs` o `rootfs`, genera `install.env`, runners separados para `check-only` y escritura real, templates de secretos y, cuando se solicita, secretos efimeros dentro de `build/install/<perfil>/runtime/`. Home y Business quedaron preparados usando la fuente live real de `build/live-output` | `scripts/prepare_installation_runtime.php`, `.gitignore`, `composer.json`, `tests/InstallationRuntimePreparationTest.php`, `build/install/w4-os-home/runtime/*`, `build/install/w4-os-business/runtime/*` |

## Regla de versionado

Incrementar:

- version `DOC` cuando cambie la estructura de seguimiento o gobernanza,
- version `PLAN` cuando cambie prioridad, alcance o fases,
- version `TECH` cuando exista un entregable tecnico verificable,
- version `REL` cuando exista un candidato de release formal.

## Eventos que obligan registro

- Aprobacion o reemplazo de un ADR base.
- Cambio del escritorio oficial.
- Cambio del alcance V1.
- Primer build reproducible firmado.
- Primer instalador funcional.
- Primera prueba exitosa de update + recovery.
- Apertura del piloto Business.
- Creacion de candidato V1.

## Regla de consistencia

Todo cambio relevante registrado aqui debe reflejarse tambien en:

- `DEVELOPMENT_MATRIX.md` si altera estado operativo,
- `DEVELOPMENT_TABLE.md` si altera prioridad,
- `EXECUTIVE_IMPLEMENTATION_PLAN.md` si altera fases o alcance,
- `DEVELOPMENT_GUIDELINES.md` si introduce una nueva regla de trabajo.
