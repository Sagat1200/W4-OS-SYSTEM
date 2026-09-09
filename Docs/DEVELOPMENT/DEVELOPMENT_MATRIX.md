# W4 OS · Development Matrix

Matriz operativa para seguimiento continuo del desarrollo. Su objetivo es mostrar estado real por area, responsable, evidencia disponible y siguiente accion.

## Instrucciones de uso

- Actualizar esta matriz en cada ciclo de trabajo.
- No marcar una fila como cerrada sin evidencia verificable.
- El estado documental no sustituye el estado tecnico.
- Cada fila debe enlazar, cuando exista, a commits, artefactos, pruebas o incidencias.

## Estados sugeridos

- `No iniciado`
- `En analisis`
- `Decision pendiente`
- `En implementacion`
- `En prueba`
- `Bloqueado`
- `Validado`
- `Cerrado`

## Matriz maestra

| ID | Prioridad | Area | Estado | Responsable | Dependencias | Evidencia actual | Siguiente accion | Riesgo activo |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| MX-001 | P0 | ADRs y base de plataforma | Decision pendiente | Pendiente | 001, 401, 406, 414, 415 | Especificacion documental base disponible | Aprobar ADRs de escritorio, boot, layout y update V1 | Reabrir decisiones durante el MVP |
| MX-002 | P0 | Supply, build y repositorios | En implementacion | Pendiente | MX-001 | Manifiestos versionados, `build-inputs`, bundles de `rootfs` y ejecutor WSL2 disponibles; el chequeo detecta `debootstrap` como unica dependencia faltante | Instalar `debootstrap` en Ubuntu WSL2 y ejecutar el primer ensamblado real de rootfs | Artefactos sin procedencia comprobable |
| MX-003 | P0 | Instalacion | No iniciado | Pendiente | MX-001, MX-002 | Contrato funcional documentado | Elegir motor del instalador y prototipo en VM | Error destructivo de disco o particionado |
| MX-004 | P0 | Update y recovery | No iniciado | Pendiente | MX-001, MX-002 | Contrato de estados y recovery documentado | Implementar coordinador durable y prueba de reinicio | Rollback inconsistente |
| MX-005 | P0 | Seguridad baseline | No iniciado | Pendiente | MX-002, MX-003, MX-004 | Baseline documental disponible | Convertir baseline en checklist validable por imagen | Imagen util sin baseline verificable |
| MX-006 | P1 | Escritorio oficial | Decision pendiente | Pendiente | MX-001 | Candidatos definidos: KDE vs GNOME | Ejecutar matriz de comparacion y ADR | Elegir sin evidencia o mantener dos escritorios |
| MX-007 | P1 | Shell y branding minimo | No iniciado | Pendiente | MX-006 | Lineamientos de personalizacion definidos | Empaquetar defaults y assets minimos | Deriva excesiva del upstream |
| MX-008 | P1 | Control Center | No iniciado | Pendiente | MX-004, MX-006 | Arquitectura modular documentada | Definir modulos V1 y API minima | UI con privilegios implicitos |
| MX-009 | P1 | Home utilizable | No iniciado | Pendiente | MX-003, MX-004, MX-005, MX-006, MX-007, MX-008 | Scope documental V1 Home disponible | Seleccionar apps base y onboarding minimo | Sobrealcance funcional |
| MX-010 | P2 | Business piloto | No iniciado | Pendiente | MX-004, MX-005 | Arquitectura y politica documental disponibles | Definir enrollment, inventario y politicas minimas | Multi-tenant debil o control remoto generico |
| MX-011 | P2 | Compliance, soporte y release | No iniciado | Pendiente | MX-009, MX-010 | Plan de release documental disponible | Preparar expediente de candidato V1 | Salida sin capacidad operativa real |

## Registro de ciclos

| Ciclo | Fecha | Objetivo | Areas tocadas | Resultado | Evidencia | Proximo paso |
| --- | --- | --- | --- | --- | --- | --- |
| C-000 | 2026-09-09 | Crear base de gobierno de desarrollo | Todas | Documentos iniciales de gestion creados | `DEVELOPMENT_TABLE.md`, `EXECUTIVE_IMPLEMENTATION_PLAN.md`, `DEVELOPMENT_MATRIX.md`, `DEVELOPMENT_VERSIONS.md`, `DEVELOPMENT_GUIDELINES.md` | Empezar H0 con ADRs y responsables |
| C-001 | 2026-09-09 | Crear presentacion inicial del proyecto | Identidad documental, onboarding del repositorio | `README.md` introductorio creado con vision, alcance y documentos clave | `README.md` | Continuar con H0 y alinear nombre del proyecto en futuras piezas nuevas |
| C-002 | 2026-09-09 | Crear el primer artefacto tecnico base | Supply, build y perfiles de edicion | Manifiestos versionados iniciales y validador funcional creados | `manifests/w4-linux-base.manifest.json`, `manifests/w4-os-home.profile.json`, `manifests/w4-os-business.profile.json`, `scripts/validate_manifests.php` | Conectar los manifiestos a un pipeline minimo de resolucion y generacion de artefactos |
| C-003 | 2026-09-09 | Crear pipeline minimo de exportacion de build | Supply, build y perfiles de edicion | Libreria PHP compartida y generador de `build-input` funcionales creados | `scripts/lib/ManifestToolkit.php`, `scripts/generate_build_input.php`, `build/inputs/w4-os-home.build-input.json`, `build/inputs/w4-os-business.build-input.json` | Implementar la etapa siguiente: resolucion hacia ensamblado de raiz o imagen |
| C-004 | 2026-09-09 | Crear etapa de ensamblado de raiz | Supply, build y perfiles de edicion | Bundles de `rootfs` generados con metadata, listas de paquetes y script Linux de `debootstrap` | `scripts/generate_rootfs_bundle.php`, `build/rootfs/w4-os-home/rootfs-manifest.json`, `build/rootfs/w4-os-home/build-rootfs.sh`, `build/rootfs/w4-os-business/rootfs-manifest.json`, `build/rootfs/w4-os-business/build-rootfs.sh` | Implementar configuracion de primer inicio y preparacion de imagen live |
| C-005 | 2026-09-09 | Adaptar el ensamblado a WSL2 | Supply, build y ejecucion Linux | Ejecutor PHP para WSL2 creado y chequeo funcional realizado sobre Ubuntu WSL2 | `scripts/run_rootfs_in_wsl.php` | Instalar `debootstrap` y lanzar el primer ensamblado real dentro de WSL2 |

## Regla de cierre

Una fila solo pasa a `Cerrado` cuando existe evidencia tecnica suficiente y su impacto fue reflejado en:

- esta matriz,
- el registro de versiones,
- la tabla de prioridades si cambia el orden de ejecucion.
