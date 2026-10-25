# W4 OS · Matriz de criterios V1, trazabilidad y validacion

Matriz operativa para convertir los criterios `V1-01` a `V1-20` de `Docs/LINUX-BASE/536_V1_RELEASE_CRITERIA.md` en una lectura accionable del estado real del repositorio.

Este documento no declara una release V1.
Su funcion es separar:

- lo ya cerrado con evidencia suficiente,
- lo parcialmente materializado,
- y lo que sigue faltando para un cierre formal de release.

## Relacion con otros documentos

- `Docs/LINUX-BASE/536_V1_RELEASE_CRITERIA.md`: fuente normativa de los veinte criterios.
- `AUDITORIA_ESTADO_V1_Y_HOJA_DE_RUTA.md`: lectura ejecutiva de madurez, riesgos y hoja de ruta.
- `DEVELOPMENT_MATRIX.md`: seguimiento operativo del trabajo activo.
- `DEVELOPMENT_VERSIONS.md`: trazabilidad documental y tecnica.
- `EDITION_IMPLEMENTATION_STATUS_MATRIX.md`: comparativa de madurez por edicion.

## Convenciones de estado

- `Cerrado`: existe evidencia suficiente en codigo, pruebas y/o artefactos para tratar el criterio como cubierto a nivel tecnico.
- `Parcial`: existen piezas reales del criterio, pero falta cobertura, integracion o evidencia de cierre para release.
- `Faltante`: no hay aun una superficie suficiente para tratar el criterio como implementado o validado.

## Matriz V1

| ID | Criterio | Estado | Lectura actual | Evidencia principal | Comando de validacion recomendado | Brecha para cierre |
| --- | --- | --- | --- | --- | --- | --- |
| `V1-01` | Instalacion | `Cerrado` | Las tres ediciones ya cuentan con pipeline de instalacion y evidencia real en VM. | `README.md`, `tests/InstallerToolkitTest.php`, `tests/InstallationScriptsIntegrationTest.php`, `build/install/` | `php vendor/bin/phpunit tests/InstallerToolkitTest.php tests/InstallationScriptsIntegrationTest.php tests/InstallationRuntimePreparationTest.php tests/InstallationTransferPreparationTest.php` | Consolidar esta evidencia dentro del expediente final de release. |
| `V1-02` | Arranque | `Cerrado` | Hay evidencia de boot desde disco, prompt LUKS y acceso operativo posterior, especialmente fuerte en `Server`. | `README.md`, `tests/ServerVirtualBoxInstallFlowCliTest.php`, `build/vbox-server-smoke/` | `php vendor/bin/phpunit tests/ServerVirtualBoxInstallFlowCliTest.php tests/ServerVmPreflightTest.php` | Reunir la evidencia por edicion en una matriz unica de release. |
| `V1-03` | Core | `Parcial` | Existen piezas reales de inicio, readiness, reinicio y reconciliacion, pero no aun como dominio V1 consolidado de punta a punta. | `src/SystemApi/`, `src/Update/UpdateToolkit.php`, `tests/SystemApiDaemonCliTest.php`, `tests/UpdateToolkitTest.php` | `php vendor/bin/phpunit tests/SystemApiDaemonCliTest.php tests/SystemApiManagerCliTest.php tests/SystemApiDbusRuntimeHostCliTest.php tests/UpdateToolkitTest.php` | Cerrar la lectura de `core lifecycle` como criterio de release y no solo como suma de subsistemas. |
| `V1-04` | API | `Parcial` | La `System API` local es real y extensa: ya suma lecturas de paquetes, `packages.plan/apply` con deduplicacion basica por `idempotency_key`, `updates.check/list/apply`, lecturas y mutaciones basicas para servicios con la misma deduplicacion opcional, `expected_revision` sobre `services.*` y las operaciones `operations.get/cancel` para el store local, pero todavia no cubre un catalogo V1 completo ni cierra aun la ruta D-Bus nativa real. | `src/SystemApi/SystemApiToolkit.php`, `src/SystemApi/SystemApiPackagesToolkit.php`, `src/SystemApi/SystemApiServicesToolkit.php`, `src/SystemApi/SystemApiOperationsToolkit.php`, `tests/SystemApiCliTest.php`, `tests/SystemApiManagerCliTest.php` | `php vendor/bin/phpunit tests/SystemApiCliTest.php tests/SystemApiManagerCliTest.php tests/SystemApiDbusBridgeCliTest.php` | Cerrar la vertical D-Bus nativa y endurecer ledger/idempotencia durable, cancelacion segura y operaciones faltantes del contrato V1. |
| `V1-05` | `w4ctl` | `Parcial` | La CLI existe y es util, pero su superficie aun es mas MVP que contrato V1 completo. | `src/W4ctl/W4ctlApplication.php`, `tests/W4ctlCliTest.php`, `README.md` | `php vendor/bin/phpunit tests/W4ctlCliTest.php` | Completar comandos fundamentales o explicitar que parte queda fuera de V1. |
| `V1-06` | Hardware | `Parcial` | Existe inventario real de discos y runners WSL, pero no se ve aun un frente V1 cerrado de hardware general. | `scripts/generate_disk_inventory.php`, `tests/DiskInventoryCliTest.php`, `tests/DiskInventoryWslRunnerCliTest.php` | `php vendor/bin/phpunit tests/DiskInventoryCliTest.php tests/DiskInventoryWslRunnerCliTest.php` | Extender la cobertura mas alla de storage e inventario de laboratorio. |
| `V1-07` | Red | `Faltante` | Hay evidencia operativa de SSH y de entorno live, pero no una superficie V1 clara de consulta/configuracion de red. | evidencia indirecta en `README.md` y playbooks operativos | `N/A` | Definir y materializar interfaz V1 de red con validacion reproducible. |
| `V1-08` | Storage | `Parcial` | El proyecto tiene inventario de discos, proteccion de targets y layout cifrado/Btrfs, pero no una superficie completa de gestion V1. | `src/Installer/InstallerToolkit.php`, `tests/DiskInventoryCliTest.php`, `tests/InstallerToolkitTest.php` | `php vendor/bin/phpunit tests/DiskInventoryCliTest.php tests/InstallerToolkitTest.php tests/InstallationScriptsIntegrationTest.php` | Cerrar lectura V1 de storage mas alla del instalador. |
| `V1-09` | Paquetes | `Parcial` | El proyecto ya expone `packages.search`, `packages.info`, `packages.list_installed`, `packages.plan` y `packages.apply` sobre `apt-cache`/`dpkg-query`/`apt-get`; `packages.apply` ya deja una operacion consultable, reutiliza la previa cuando coincide el mismo `idempotency_key` con el mismo digest y puede rechazar ejecuciones si `expected_catalog_revision` no coincide con el plan, pero aun faltan ledger durable, reconciliacion posterior y validacion sobre host Linux real. | `src/SystemApi/SystemApiPackagesToolkit.php`, `src/SystemApi/SystemApiOperationsToolkit.php`, `src/SystemApi/SystemApiToolkit.php`, `tests/SystemApiCliTest.php`, `tests/SystemApiManagerCliTest.php` | `php vendor/bin/phpunit tests/SystemApiCliTest.php tests/SystemApiManagerCliTest.php` | Endurecer la semantica de apply y cerrar evidencia real de paquetes sobre host Linux soportado. |
| `V1-10` | Updates | `Parcial` | El proyecto ya expone `updates.check`, `updates.list` y `updates.apply` sobre `apt-get update`, `apt list --upgradable` y una primera secuencia APT derivada de `update-plan`; `updates.plan` ya devuelve `plan_hash`, el apply deja una operacion `pending-reboot`/`needs_attention` e integra deduplicacion por `idempotency_key`, pero aun faltan estados de frescura/lock mas ricos y la integracion end-to-end con el coordinador durable sobre host Linux real. | `src/SystemApi/SystemApiPackagesToolkit.php`, `src/SystemApi/SystemApiToolkit.php`, `tests/SystemApiCliTest.php`, `tests/SystemApiManagerCliTest.php` | `php vendor/bin/phpunit tests/SystemApiCliTest.php tests/SystemApiManagerCliTest.php` | Cerrar la semantica operativa de updates y enlazarla con apply/reconciliacion sobre host soportado. |
| `V1-10` | Updates | `Parcial` | `Update/recovery` esta muy avanzado y con evidencia fuerte en `Home` y `Business`, pero el criterio V1 completo aun no esta empaquetado como gate final unico. | `src/Update/UpdateToolkit.php`, `tests/UpdateToolkitTest.php`, `tests/UpdateScriptsIntegrationTest.php`, `build/update/validation/` | `php vendor/bin/phpunit tests/UpdateToolkitTest.php tests/UpdateScriptsIntegrationTest.php` | Convertir la evidencia existente en criterio formal de release y cerrar su lectura para las tres ediciones relevantes. |
| `V1-11` | Servicios | `Parcial` | El proyecto ya expone `services.list`, `services.status`, `services.start`, `services.stop`, `services.restart`, `services.enable` y `services.disable` sobre `systemctl`; `services.status` ya publica `revision`, las mutaciones aceptan `expected_revision` e `idempotency_key` opcional, y dejan operaciones consultables por `operations.get`, pero aun falta cobertura end-to-end sobre host Linux real. | `src/SystemApi/SystemApiServicesToolkit.php`, `src/SystemApi/SystemApiOperationsToolkit.php`, `src/SystemApi/SystemApiToolkit.php`, `tests/SystemApiCliTest.php`, `tests/SystemApiManagerCliTest.php` | `php vendor/bin/phpunit tests/SystemApiCliTest.php tests/SystemApiManagerCliTest.php` | Validar el contrato contra `systemd` real y cerrar las reglas de mutacion V1 que hoy siguen mas livianas que la especificacion. |
| `V1-12` | Autorizacion | `Faltante` | No se observa aun una matriz positiva/negativa de autorizacion ni contrato de denegacion sin efectos laterales. | `Docs/LINUX-BASE/536_V1_RELEASE_CRITERIA.md` como objetivo | `N/A` | Diseñar y validar el modelo de autorizacion de V1. |
| `V1-13` | Logs y diagnostico | `Parcial` | Hay eventos NDJSON, reportes de update, baseline y diagnosticos D-Bus, pero falta una lectura integrada como criterio V1 de diagnstico. | `src/Update/UpdateToolkit.php`, `src/SystemApi/`, `tests/SystemApiDaemonCliTest.php`, `build/update/operations/` | `php vendor/bin/phpunit tests/SystemApiDaemonCliTest.php tests/UpdateToolkitTest.php` | Consolidar correlacion y bundles de diagnostico a nivel release. |
| `V1-14` | Telemetria local | `Faltante` | La telemetria local como dominio V1 no aparece aun materializada en codigo y pruebas. | criterio normativo en `536_V1_RELEASE_CRITERIA.md` | `N/A` | Definir si entra en V1 y, si si, implementarla con cobertura verificable. |
| `V1-15` | Recovery basico | `Parcial` | El proyecto tiene recovery operativo fuerte en update, instalacion y diagnostico de runtime, pero no aun como frente V1 completo y unificado. | `src/Update/UpdateToolkit.php`, `README.md`, `tests/SystemApiDbusRuntimeProbeCliTest.php` | `php vendor/bin/phpunit tests/UpdateToolkitTest.php tests/SystemApiDbusRuntimeProbeCliTest.php` | Unificar la historia de recovery como criterio de release, no solo por subsistemas. |
| `V1-16` | Tres perfiles | `Cerrado` | `Home`, `Business` y `Server` existen como perfiles reales con manifiestos, artefactos y validaciones. | `manifests/`, `build/inputs/`, `tests/BuildInputCliTest.php`, `tests/ServerIsoArtifactTest.php` | `php vendor/bin/phpunit tests/BuildInputCliTest.php tests/ManifestValidationCliTest.php tests/ServerIsoArtifactTest.php` | Consolidar la evidencia final en la matriz de release. |
| `V1-17` | Base compartida | `Parcial` | La base compartida esta muy clara en manifests, politica y toolkits, pero la prueba de mismos digests Core/API/providers aun no aparece cerrada como tal. | `manifests/w4-linux-base.manifest.json`, `tests/ManifestToolkitTest.php`, `README.md` | `php vendor/bin/phpunit tests/ManifestToolkitTest.php tests/BuildInputCliTest.php` | Formalizar la evidencia de paridad y digests compartidos entre las tres ediciones. |
| `V1-18` | Repositorio W4 | `Cerrado` | El repositorio APT W4 ya tiene evidencia fuerte, incluso firmada, y ya fue consumido por `Home` y `Business`. | `src/Update/RepositoryPublicationToolkit.php`, `tests/RepositoryPublicationToolkitTest.php`, `build/update/repository-output/` | `php vendor/bin/phpunit tests/RepositoryPublicationToolkitTest.php tests/UpdateScriptsIntegrationTest.php` | Consolidar el playbook final de publicacion y consumo como evidencia de release. |
| `V1-19` | Actualizacion por paquetes | `Parcial` | La actualizacion por paquetes ya fue validada fuertemente, pero la lectura de compatibilidad y preservacion de configuracion aun debe empaquetarse como criterio formal cerrado. | `build/update/validation/`, `README.md`, `tests/UpdateToolkitTest.php` | `php vendor/bin/phpunit tests/UpdateToolkitTest.php tests/UpdateScriptsIntegrationTest.php` | Convertir la evidencia de laboratorio y VM en gate de release reconocido. |
| `V1-20` | Pruebas criticas automatizadas | `Parcial` | La suite automatizada es fuerte y amplia, pero todavia no esta presentada como pipeline V1 consolidado por criterio. | `tests/`, `composer.json`, `phpunit.xml.dist` | `php vendor/bin/phpunit` | Pasar de buena cobertura tecnica a matriz critica de release oficialmente trazada. |

## Resumen de lectura

- `Cerrado`: `V1-01`, `V1-02`, `V1-16`, `V1-18`
- `Parcial`: `V1-03`, `V1-04`, `V1-05`, `V1-06`, `V1-08`, `V1-10`, `V1-13`, `V1-15`, `V1-17`, `V1-19`, `V1-20`
- `Faltante`: `V1-07`, `V1-09`, `V1-11`, `V1-12`, `V1-14`

## Conclusiones operativas

1. El proyecto ya tiene base suficiente para sostener una lectura `pre-V1 fuerte` en build, instalacion, artefactos, perfiles y repositorio.
2. El mayor hueco tecnico sigue concentrado en la `System API` y su cierre nativo real por D-Bus.
3. El mayor hueco de release no es tanto codigo faltante como `trazabilidad de cierre`: transformar evidencia dispersa en expediente V1 consolidado.
4. Los criterios mas inmaduros son red, paquetes, servicios, autorizacion y telemetria local.

## Siguiente uso recomendado

Usar esta matriz como checklist de trabajo inmediato:

- primero para marcar cada criterio como `cerrado`, `parcial` o `faltante`,
- despues para enlazar la evidencia real por comando, artefacto y prueba,
- y por ultimo para decidir si V1 puede pasar a `RC` o si aun debe seguir en fase `alpha avanzada`.
