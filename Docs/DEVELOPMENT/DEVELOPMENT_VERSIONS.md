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
| TECH | TECH-0.8 | 2026-09-10 | Activa | Rootfs Home y Business extendidos con overlay de sistema, primer inicio y preparacion live |
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
