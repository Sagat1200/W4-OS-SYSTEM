# W4 OS · Matriz de brechas entre `W4-Linux-Base` y estado actual

## Objetivo

Registrar de forma trazable las discrepancias mas relevantes entre:

- la especificacion arquitectonica de `C:\W4\W4-Linux-Base\docs`, y
- la implementacion/evidencia actual de `C:\W4\Packages\W4-OS SYSTEM`.

Este documento no reemplaza la especificacion base ni la matriz maestra del proyecto.
Su funcion es evitar que la documentacion objetivo se lea como si ya fuera evidencia de implementacion cerrada.

## Alcance de esta pasada

La comparacion se concentro en los contratos que afectan directamente:

- `W4 Linux Base` como base comun,
- la relacion entre Home, Business y Server,
- el estado real de `System API`, `w4ctl`, GUI, Agent y politicas,
- y la trazabilidad ejecutiva actual (`MX-006`, `MX-009`, `MX-010`, `MX-012`).

Para una lectura resumida por edicion sobre que ya esta realmente al mismo nivel tecnico, ver tambien `Docs/DEVELOPMENT/EDITION_IMPLEMENTATION_STATUS_MATRIX.md`.

## Convenciones

- `Alineado`: la documentacion objetivo y el estado real ya convergen razonablemente.
- `Parcial`: existe direccion comun, pero la evidencia real aun no alcanza lo prometido por la documentacion.
- `Desalineado`: la documentacion puede inducir a una lectura falsa del estado actual.

## Resumen ejecutivo

La especificacion de `W4-Linux-Base` describe correctamente la direccion arquitectonica de largo plazo, pero hoy el repositorio `W4 OS System` solo tiene evidencia cerrada en:

- supply/build,
- instalacion,
- update y recovery,
- baseline de seguridad,
- y bootstrap Server headless.

Siguen pendientes o en fase anterior de madurez:

- `W4 System API`,
- `w4ctl`,
- GUI/Home integration real,
- Agent/Business piloto,
- y la paridad operativa plena entre Home, Business y Server bajo un mismo contrato de plataforma, aunque las tres ediciones ya cuentan con ancla explicita de politica.

## Matriz de brechas

| ID | Area | Documento base | Estado esperado por la especificacion | Estado real del repo | Evaluacion | Impacto | Accion recomendada |
| --- | --- | --- | --- | --- | --- | --- | --- |
| GAP-001 | Plataforma V1 | `01_V1_SCOPE_AND_OBJECTIVES.md` | V1 ya se define alrededor de `W4 System API`, `W4 Services`, `W4 Providers`, `W4 CLI`, `W4 Recovery` y ejemplos `w4ctl` | El repo actual tiene evidencia fuerte en build/install/update/security/Server, pero no una `System API` materializada ni `w4ctl` operativo como frente validado | Desalineado | Alto | Marcar expresamente en la documentacion de desarrollo que `W4-Linux-Base` representa contrato objetivo y que `System API`/`w4ctl` siguen fuera del estado tecnico validado del repo |
| GAP-002 | Integracion Home | `362_W4_OS_HOME_INTEGRATION.md` | Home conecta `Settings` y experiencia de escritorio a una API comun; incluso se propone validar updates desde GUI y seguir el mismo trabajo por CLI | El proyecto ya fijo por `ADR-006` que `GNOME` es la interfaz predeterminada de `Home`, y que `KDE Plasma`, `XFCE` y `Cinnamon` solo aparecen como variantes controladas cuando el medio las califique. Ademas, `MX-007` ya cuenta con un aterrizaje operativo propio en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_SHELL_AND_BRANDING_MINIMO.md`, su primer subcorte documenta la frontera entre `w4-desktop-meta`, `w4-desktop-gnome-meta` y la ruta base `GNOME + GDM` en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`, el segundo fija la politica minima de branding reversible en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_REVERSIBLE_BRANDING_POLICY.md`, y el tercero ya materializa una capa tecnica real en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_DEFAULTS_LAYOUT_AND_OVERLAY_IMPLEMENTATION.md`, `config/editions/home/desktop-defaults.json` y `generate_system_overlay.php`. Ahora, ademas, `manifests/w4-os-home.profile.json` ya declara un baseline GNOME real (`gdm3`, `gnome-shell`, `gnome-session`, `gnome-software`, `nautilus`, `xdg-desktop-portal-gnome`), esa resolucion ya queda reflejada en `build-input` y `rootfs-manifest`, y la validacion materializada completa `rootfs -> overlay -> live` ya cerro sobre una ruta WSL limpia con `filesystem.manifest` real y `system-overlay` intacto. El siguiente hueco real ya no es de shell o packaging, sino de `Settings`: `MX-008` queda abierto, fija su `Slice A` para `Sistema`, `Seguridad`, `Actualizaciones` y `Almacenamiento`, ya cuenta con una primera capa real de lectura en `src/ControlCenter/ControlCenterSliceAToolkit.php` y ahora ya consume ese material desde `src/ControlCenter/ControlCenterHomeToolkit.php` y `scripts/read_control_center_home.php` como home/CLI `read-first` | Parcial | Medio | Mantener Home integration como objetivo futuro y no como capacidad ya disponible; usar `ADR-006`, el cierre operativo de `MX-007`, el contrato general de `MX-008`, su `Slice A`, la home `read-first` inicial y el CLI JSON como guardrails antes de abrir implementación UX, API comun y validaciones GUI de nivel producto |
| GAP-003 | Perfil Business | `359_BUSINESS_PROFILE.md` | Business ya se describe con Agent y politicas, con instalacion sin enrolar pero con comportamiento empresarial delimitado | El manifiesto actual solo declara preparacion (`device-enrollment-ready`, `inventory-ready`) y aclara que no habilita gestion remota arbitraria; `MX-010` sigue sin implementacion. Sin embargo, la politica grafica documental ya quedo fijada por `ADR-013`: `KDE Plasma` es la interfaz predeterminada de `Business`, mientras `GNOME`, `XFCE` y `Cinnamon` quedan como variantes controladas solo cuando el medio las califique | Desalineado | Alto | Reforzar en docs/roadmap que Business actual es base preparada, no piloto materializado; usar `ADR-013` como guardrail de UX sin promocionar todavia Agent/politicas como evidencia implementada |
| GAP-004 | Perfil Server | `W4-OS-SERVER-PLAN-MAESTRO.md` | Server se define como edición headless con SSH y operación remota, sin depender de una GUI local | El estado técnico ya valida este contrato y ahora la documentación también lo fija por `ADR-014`: `Server` no incluye interfaz gráfica por defecto, pero puede ofrecer durante la instalación variantes gráficas opcionales con `headless` como selección inicial. La brecha pendiente ya no es decidir el default visual, sino evolucionar operación, roles y administración remota sin romper el perfil headless ni anunciar variantes no calificadas | Alineado | Medio | Mantener el contrato headless como guardrail documental y evitar que futuros cambios de Base o Desktop reintroduzcan dependencias gráficas obligatorias en `Server` |
| GAP-004 | Base comun Home/Business/Server | `361_W4_OS_INTEGRATION_ARCHITECTURE.md` y manifiestos actuales | La especificacion pide una base comun real para los tres productos | El repo ya hace heredar Server desde `w4-linux-base`, pero el manifiesto base aun anota que la base reusable es para Home y Business, lo que ya no refleja la realidad del proyecto | Parcial | Medio | Corregir la redaccion del manifiesto base para declarar explicitamente que `w4-linux-base` es base reusable de Home, Business y Server |
| GAP-005 | Paridad entre ediciones | `361_W4_OS_INTEGRATION_ARCHITECTURE.md` y `536_V1_RELEASE_CRITERIA.md` | Misma base, tres productos, clientes coherentes, hashes compartidos, misma operacion vista por GUI/CLI/Agent y desacoplamiento comprobado | Las tres ediciones ya cuentan con base compartida, `policy.json` explicito y consumo real de politica en `system-overlay`; ademas, las cadenas `build input -> disk inventory -> installation plan -> prepare bundle -> executor/verify`, `rootfs -> overlay -> live -> iso -> preflight`, `runtime -> transfer -> security baseline`, `update plan/store/executor/reconcile`, `update repository -> publication`, el wrapper `Windows -> WSL -> disk inventory` y la familia de runners `Windows -> WSL -> rootfs/overlay/live/iso` ya preservan contratos objetivos bajo una metadata cada vez mas uniforme. Esta capa ya cuenta tambien con evidencia operativa renovada desde `Ubuntu` en WSL: un inventario real recapturado, una corrida real completa de `rootfs`, una aplicacion real de `overlay`, una composicion real de `live` para Server, el cierre real de `iso` sobre ese mismo `image-root`, el preflight Server de nuevo en `status=ready`, una validacion de arranque live fresco en VirtualBox usando una VM efimera, una instalacion real completa desde la ISO vigente sobre `W4-OS-Server-C123-Install`, el login local/SSH de `w4admin` ya confirmados, el baseline Server revalidado sobre esa misma VM con `9 passed`, `0 failed`, `1 skipped`, el helper canonico `scripts/enable_live_ssh_in_virtualbox.php` validado en real y finalmente el runner host->VM `scripts/run_server_virtualbox_install_flow.php` ya absorbido como cierre totalmente autocontenido sobre `W4-OS-Server-C128-Flow`: `installation_exit=0`, apagado ACPI, detach ISO, reboot desde disco, captura previa al prompt LUKS, retorno de SSH tras el desbloqueo, `hostname=w4-server-vm`, `w4-firstboot=active` y baseline final otra vez en `9 passed`, `0 failed`, `1 skipped`. Sobre ese mismo cierre, el contrato CLI integrado ya no depende solo de evidencia manual: `tests/ServerVirtualBoxInstallFlowCliTest.php` congela tambien el `status=ok`, el parseo del ultimo JSON del baseline, el inventario VirtualBox generado y el resumen textual `server-*-install-validation.txt` mediante `fixture map`, reforzando la convergencia entre QA reproducible y evidencia operativa real. Con ello, `filesystem.squashfs`, `filesystem.manifest`, `filesystem.size`, `initrd`, `vmlinuz`, `SHA256SUMS`, `iso-summary.env`, la ISO `build/iso-output/w4-os-server/w4-os-server-live-amd64.iso` (SHA256 `345d30a555a2a2998e47854a137c2aee64dfea0117002f6b7ba3240df6e65861`), los bundles `build/install/w4-os-server-vbox-c123`, `build/install/w4-os-server-w4-os-server-c126-install`, `build/install/w4-os-server-w4-os-server-c128-flow`, `build/security/w4-os-server`, los helpers `scripts/enable_live_ssh_in_virtualbox.php`, `scripts/run_server_virtualbox_install_flow.php` y las capturas `build/vbox-server-smoke/server-c121-live-boot-freshvm.png`, `server-c123-before-luks.png`, `server-c123-after-luks.png`, `server-c123-postlogin.png`, `server-c123-runtime-checks.png`, `server-c125-live-before-ssh-automation.png`, `server-c125-live-after-ssh-automation.png`, `server-c126-before-luks.png`, `server-c126-postlogin.png`, `server-c128-flow-before-luks.png` y `server-c128-flow-postlogin.png` ya quedan sincronizados al workspace como evidencia reciente del eje Server. En esa misma linea, `tests/ServerIsoArtifactTest.php` ya deriva el SHA256 esperado desde `metadata/SHA256SUMS` del artefacto materializado, reduciendo drift cuando la ISO vigente se recompone legitimamente sin relajar el guardrail headless ni la verificacion de identidad. La normalizacion de politica se centralizo en `src/Installer/EditionPolicyToolkit.php` y la envoltura base de metadata de artefactos ya cubre tambien `generate_build_input.php`, `generate_disk_inventory.php`, `run_disk_inventory_in_wsl.php`, `run_rootfs_in_wsl.php`, `run_overlay_in_wsl.php`, `run_live_bundle_in_wsl.php`, `run_iso_bundle_in_wsl.php`, `validate_manifests.php`, `generate_installation_plan.php`, el flujo CLI de `update`, `rootfs`, `prepare_installation_bundle.php`, `generate_update_repository_bundle.php` y `publish_update_repository.php` a traves de `src/Support/ArtifactMetadataToolkit.php`, reduciendo drift entre scripts, manifests y payloads de exito. Aun asi, la equivalencia funcional sigue siendo parcial porque solo Server tiene hoy un contrato runtime diferenciado de SSH y Home/Business siguen sin clientes GUI/API/Agent materializados | Parcial | Medio | Tratar la paridad actual como parcial: la capa declarativa, la metadata de artifacts y varias etapas del pipeline ya convergen, pero la equivalencia funcional completa sigue pendiente |

## Evidencia principal usada

### Especificacion `W4-Linux-Base`

- `C:\W4\W4-Linux-Base\docs\01_V1_SCOPE_AND_OBJECTIVES.md`
- `C:\W4\W4-Linux-Base\docs\359_BUSINESS_PROFILE.md`
- `C:\W4\W4-Linux-Base\docs\360_SERVER_PROFILE.md`
- `C:\W4\W4-Linux-Base\docs\361_W4_OS_INTEGRATION_ARCHITECTURE.md`
- `C:\W4\W4-Linux-Base\docs\362_W4_OS_HOME_INTEGRATION.md`
- `C:\W4\W4-Linux-Base\docs\536_V1_RELEASE_CRITERIA.md`

### Estado real `W4 OS System`

- `README.md`
- `manifests/w4-linux-base.manifest.json`
- `manifests/w4-os-business.profile.json`
- `manifests/w4-os-server.profile.json`
- `config/editions/home/policy.json`
- `config/editions/business/policy.json`
- `config/editions/server/policy.json`
- `scripts/generate_system_overlay.php`
- `scripts/generate_build_input.php`
- `scripts/generate_disk_inventory.php`
- `scripts/run_disk_inventory_in_wsl.php`
- `scripts/run_rootfs_in_wsl.php`
- `scripts/run_overlay_in_wsl.php`
- `scripts/run_live_bundle_in_wsl.php`
- `scripts/run_iso_bundle_in_wsl.php`
- `scripts/generate_installation_plan.php`
- `scripts/generate_update_plan.php`
- `scripts/prepare_update_operation.php`
- `scripts/advance_update_operation.php`
- `scripts/reconcile_update_operation.php`
- `scripts/generate_update_executor.php`
- `scripts/generate_rootfs_bundle.php`
- `scripts/generate_live_bundle.php`
- `scripts/generate_iso_bundle.php`
- `scripts/validate_manifests.php`
- `scripts/generate_update_repository_bundle.php`
- `scripts/publish_update_repository.php`
- `scripts/preflight_server_vm_validation.php`
- `scripts/prepare_installation_bundle.php`
- `src/Installer/InstallerToolkit.php`
- `scripts/generate_installation_executor.php`
- `scripts/prepare_installation_runtime.php`
- `scripts/prepare_installation_transfer.php`
- `src/Installer/EditionPolicyToolkit.php`
- `src/Support/ArtifactMetadataToolkit.php`
- `src/Security/SecurityBaselineToolkit.php`
- `tests/SystemOverlayGenerationTest.php`
- `tests/RootfsBundleGenerationTest.php`
- `tests/LiveBundleGenerationTest.php`
- `tests/IsoBundleGenerationTest.php`
- `tests/ServerVmPreflightTest.php`
- `tests/InstallationScriptsIntegrationTest.php`
- `tests/InstallationRuntimePreparationTest.php`
- `tests/InstallationTransferPreparationTest.php`
- `tests/EditionPolicyToolkitTest.php`
- `tests/ArtifactMetadataToolkitTest.php`
- `tests/SecurityBaselineToolkitTest.php`
- `tests/ServerIsoArtifactTest.php`
- `tests/ServerVmPreflightTest.php`
- `Docs/DEVELOPMENT/DEVELOPMENT_MATRIX.md`
- `Docs/DEVELOPMENT/EXECUTIVE_IMPLEMENTATION_PLAN.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_VERSIONS.md`

## Lectura operativa recomendada

Para trabajo tecnico diario, la lectura mas segura hoy es:

1. usar `W4-Linux-Base` como contrato de direccion;
2. usar `README.md` + `DEVELOPMENT_MATRIX.md` + `DEVELOPMENT_VERSIONS.md` como estado real del repositorio;
3. no asumir que `System API`, `w4ctl`, GUI Home o Agent Business existen solo porque la especificacion ya los define;
4. tratar Server como el producto con mayor formalizacion operativa reciente dentro del tronco comun.

## Siguiente uso recomendado

Esta matriz debe servir como insumo para:

- depurar `MX-001` y futuras ADRs de plataforma;
- evitar sobrepromesas documentales durante `MX-006`, `MX-009` y `MX-010`;
- y decidir que partes de `W4-Linux-Base` deben rebajarse a "target architecture" hasta que exista evidencia equivalente en el repo operativo.
