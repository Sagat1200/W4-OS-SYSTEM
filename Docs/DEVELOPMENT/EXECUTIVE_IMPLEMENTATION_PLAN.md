# W4 OS · Executive Implementation Plan

Plan ejecutivo para convertir la especificacion documental de W4 OS en una implementacion verificable, manteniendo separacion entre diseno, evidencia tecnica y estado de release.

## Relacion con otros documentos

- `DEVELOPMENT_TABLE.md`: prioridades y entregables por area.
- `DEVELOPMENT_MATRIX.md`: seguimiento operativo del trabajo activo.
- `EDITION_IMPLEMENTATION_STATUS_MATRIX.md`: comparacion ejecutiva por edicion para separar madurez de base tecnica y deuda de producto.
- `DEVELOPMENT_VERSIONS.md`: registro de versiones documentales y tecnicas.
- `DEVELOPMENT_GUIDELINES.md`: reglas de trabajo, evidencia y actualizacion.
- `W4_LINUX_BASE_GAP_MATRIX.md`: brechas entre la arquitectura objetivo de `W4-Linux-Base` y el estado tecnico realmente validado en este repositorio.

## Objetivo ejecutivo

Entregar un V1 centrado en una imagen de escritorio instalable, actualizable y recuperable, con alcance controlado, evidencia verificable y capacidad de mantenimiento real.

## Principios de implementacion

1. No confundir documento aprobado con funcionalidad implementada.
2. No ampliar alcance mientras exista un bloqueo P0 sin resolver.
3. Cada capacidad pasa por la secuencia `decision -> artefacto -> prueba -> evidencia -> cierre`.
4. La prioridad tecnica del proyecto es `instalar -> actualizar -> fallar -> recuperar`.
5. Home y Business comparten base; Business no debe bloquear el MVP local.
6. La especificacion base puede adelantar capacidades objetivo; la gobernanza del repo solo reconoce como estado real aquello que ya tenga artefacto, prueba y evidencia enlazada.
7. Los manifiestos y contratos declarativos del tronco comun deben reflejar la topologia real vigente entre Home, Business y Server para no introducir deuda de lectura en el roadmap tecnico.
8. Cuando exista una politica de edicion versionada, los generadores del pipeline deben preferir consumirla antes que duplicar hostname, target o defaults equivalentes en catalogos hardcodeados.
9. Los validadores de artefactos y preflight deben contrastar la metadata emitida por `live`/`iso` con la politica efectiva de la edicion siempre que esa comparacion aporte una verificacion objetiva del contrato.
10. La instalacion unattended debe tratar `edition-policy.json` como contrato operativo del target y no solo como documentacion: cualquier decision sobre `default_target`, hostname o hardening base debe derivarse desde esa politica o quedar trazablemente justificada fuera de ella.
11. Las capas `runtime/transfer` y la validacion posterior al primer arranque deben conservar suficiente metadata de politica para demostrar que el sistema desplegado sigue alineado con `edition-policy.json` y no solo con defaults implicitos del instalador.
12. Cuando dos o mas etapas del pipeline necesiten reinterpretar `edition-policy.json`, esa normalizacion debe vivir en una sola clase compartida para evitar drift entre manifests, scripts y baseline runtime.
13. Cuando varias etapas publiquen manifests o payloads de exito equivalentes, la envoltura de metadata base debe salir de un helper comun para reducir drift sin reescribir el pipeline operativo.
14. La extension de ese helper comun debe avanzar por anillos pequenos: primero las etapas ya estables y con pruebas focalizadas, despues los dominios con contratos mas amplios como update o publicacion.
15. Ese helper comun no debe imponer identidades falsas a los artifacts: cuando una etapa publique `repository_snapshot`, `publication_profile` u otra clave primaria distinta de `profile_id`, la metadata canonica debe adaptarse al dominio real sin romper la consistencia transversal.
16. Los entrypoints base del pipeline tambien deben alinearse con la envoltura canonica, pero los scripts acoplados al inventario real del sistema o a WSL deben moverse en ciclos separados con evidencia de entorno suficiente.
17. Los flujos CLI operativos que ya tienen suites integradas estables, como `update`, deben converger a la misma envoltura antes de extraer contratos mas profundos de dominio, para reducir drift con el menor costo de fractura posible.
18. En instalacion, los entrypoints que aceptan fixtures controlables deben converger antes que los que dependen del inventario real del host; por eso `generate_installation_plan.php` puede cerrarse antes que `generate_disk_inventory.php`.
19. Cuando un entrypoint depende de comandos del host (`lsblk`, `udevadm`, `wipefs`), la automatizacion debe usar fixtures inyectables solo para QA repetible, manteniendo intacto el camino operativo real para evidencia de entorno.
20. Los wrappers Windows->WSL deben seguir la misma regla: su QA puede simular distros disponibles y salidas del colector, pero la evidencia tecnica operativa sigue dependiendo de ejecucion real contra una distro WSL valida o una sesion live.
21. Cuando varios runners WSL comparten la misma forma de preflight/ejecucion, conviene congelarlos como familia con fixtures comunes (`distros`, `wslpath`, ejecucion) antes de pasar a evidencia operativa real, para reducir drift transversal entre `rootfs`, `overlay`, `live` e `iso`.
22. Una vez cerrada la capa de QA reproducible, el siguiente paso obligatorio es volver a capturar evidencia operativa real del mismo eje en una distro WSL valida o una sesion live, para confirmar que el contrato canonico sigue describiendo el comportamiento efectivo del entorno.
23. La evidencia operativa vale mas cuando se captura en cadena sobre el mismo arbol de trabajo: `rootfs -> overlay -> live` produce una senal mucho mas util que validaciones aisladas, porque demuestra continuidad real entre etapas y deja artefactos inspeccionables en el workspace.
24. Cuando esa misma cadena ya dispone de `image-root` real verificable, el cierre natural es ejecutar tambien `iso` sobre ese mismo estado y registrar checksum, metadata operativa y comando efectivo para no dejar la validacion WSL a medio camino.
25. Cuando una ISO fresca se contrasta contra una VM historica, el estado EFI/NVRAM o el disco ya instalado pueden falsear la lectura del artefacto actual; para validar el live vigente conviene una VM efimera o un contexto de arranque limpiado explicitamente.
26. En Server, el transporte host->live para corridas de instalacion sigue dependiendo de un setup minimo previo de SSH dentro de la sesion live; hasta automatizar ese paso, la evidencia real debe dejar trazado explicito de `/run/sshd`, el override de `PasswordAuthentication`, la regla temporal `ufw allow 22/tcp` y el reinicio de `ssh`.
27. `verify-installation.sh` es fiable cuando el target esta totalmente desmontado o cuando `/boot` y `/boot/efi` vuelven a montarse explicitamente; si `cryptroot` sigue abierto y solo `/` permanece montado, el runner puede producir un falso negativo aunque la instalacion ya haya cerrado correctamente.
28. Los verificadores runtime de seguridad no deben mezclar defaults historicos con politica efectiva de edicion; cuando `edition-policy.env` ya expone el contrato canonico de firewall, el baseline debe derivar de ahi la expectativa de `DEFAULT_INPUT_POLICY` y `DEFAULT_OUTPUT_POLICY` en vez de hardcodear valores heredados.
29. En la tty Server de VirtualBox, el Enter extendido `e0 1c e0 9c` es el scancode fiable para ejecutar comandos inyectados por consola; asumir `1c 9c` como Enter universal vuelve fragil el flujo host->live.
30. Una vez estabilizados los helpers puntuales del eje VM (`live SSH`, unlock LUKS, baseline remoto), el siguiente paso correcto es encapsularlos en runners host->VM mas altos para que la evidencia operativa pueda recapturarse con menos pasos sueltos y menor riesgo de deriva manual.
31. Cuando un runner host->VM necesite recuperar un codigo remoto desde una tty compartida con `sudo`, no conviene confiar en la salida cruda del comando: hace falta emitir un marcador explicito y parseable (`__W4_INSTALL_EXIT__:<codigo>`) para no mezclar el exit status con warnings como `sudo: unable to resolve host ...`.
32. El baseline runtime de Server debe correrse con `--sudo` cuando se quiera verificar `apparmor-enforced-profiles`, porque sin privilegios la lectura de `/sys/kernel/security/apparmor/profiles` produce falsos negativos aun cuando AppArmor ya esta en `enforce`.
33. En la live Server endurecida no basta con publicar solo `PasswordAuthentication yes`: si la imagen efectiva impone `AuthenticationMethods publickey`, el helper temporal de laboratorio debe sobreescribir de forma completa `PasswordAuthentication`, `KbdInteractiveAuthentication`, `PubkeyAuthentication`, `AuthenticationMethods` y `UsePAM` para reabrir transporte por password sin alterar el contrato principal del sistema instalado.
34. Cuando un runner reutiliza helpers que imprimen mas de un JSON en `stdout`, el parseo canonico debe tomar el ultimo objeto valido y no la salida completa; eso permite encadenar reporte operativo y payload final sin reescribir helpers ya validados ni romper el cierre "un solo comando".
35. Cuando un runner CLI ya fue validado en real de punta a punta, el siguiente endurecimiento correcto no es repetir mas pasos manuales, sino congelar tambien su payload `ok` bajo `fixture map`, incluyendo archivos derivados clave como inventario generado y resumen textual; asi el contrato integrado queda protegido contra drift aunque los helpers externos no se ejecuten de verdad en cada prueba.
36. Cuando un gate QA valide artefactos materializados que pueden regenerarse legitimamente, el checksum esperado debe derivarse del checksum publicado por la propia etapa (`SHA256SUMS` o metadata equivalente) y no de un SHA historico hardcodeado; el drift real a detectar es inconsistencia entre artefacto y metadata, no simple antiguedad del valor congelado.

## Alcance ejecutivo V1

Incluye:

- Base Debian estable integrada como `W4 Linux Base`.
- Referencia inicial `amd64 UEFI`.
- Un catálogo gráfico aprobado para `Home`, con selector en instalación solo si queda calificado.
- Instalacion funcional con cifrado por contrasena.
- Build reproducible y artefactos firmados.
- Actualizacion offline con coordinador durable y recuperacion probada.
- Baseline minima de seguridad.
- Perfil Home utilizable.
- Piloto Business limitado, solo despues del MVP recuperable.

Excluye del compromiso inicial:

- Atomicidad integral no probada.
- `arm64` general.
- Compatibilidad universal con Windows.
- Dependencia obligatoria de servicios cloud.
- Certificaciones o SLA no acreditados.

## Fases ejecutivas

### Fase H0 · Decisiones base

Objetivo:
cerrar las decisiones que condicionan toda la implementacion.

Salida obligatoria:

- ADRs base aprobados.
- Responsables por area definidos.
- Alcance V1 congelado.

Bloqueos tipicos:

- Escritorio no elegido.
- Bootloader no fijado.
- Contrato de rollback ambiguo.

### Fase H1 · Supply minimo

Objetivo:
obtener una cadena de build, firma y empaquetado trazable.

Salida obligatoria:

- Repositorio controlado.
- Imagen `amd64` construida.
- Firma y procedencia verificables.

### Fase H2 · MVP recuperable

Objetivo:
demostrar el ciclo minimo del producto.

Salida obligatoria:

- Instalar en entorno limpio.
- Actualizar sin corrupcion.
- Simular fallo controlado.
- Recuperar sin perder el archivo de prueba definido.

Este es el hito tecnico mas importante del proyecto.

### Fase H3 · Home utilizable

Objetivo:
convertir el MVP tecnico en una experiencia de escritorio minima aceptable.

Salida obligatoria:

- Escritorio oficial estabilizado.
- Shell y branding minimos.
- Apps base seleccionadas.
- Accesibilidad y onboarding verificados.

### Fase H4 · Business piloto

Objetivo:
validar gestion empresarial limitada sin comprometer la base local.

Salida obligatoria:

- Enrollment basico.
- Politicas tipadas minimas.
- Inventario.
- Operacion offline con ultima politica valida.

### Fase H5 · Candidato V1

Objetivo:
cerrar expediente tecnico y operativo para un release controlado.

Salida obligatoria:

- QA de release.
- Fuentes y licencias trazables.
- Runbooks.
- Alcance de soporte definido.

## Ruta priorizada

1. ADRs y base de plataforma.
2. Supply, build y repositorios.
3. Instalacion.
4. Update, estado y recovery.
5. Seguridad baseline.
6. Escritorio oficial.
7. Shell, control center y Home.
8. Business piloto.
9. Compliance, soporte y release.

## Siguiente ciclo recomendado · MX-008

Objetivo del ciclo:
traducir la base ya validada de `GNOME + GDM` de `Home` a un primer contrato operativo de `Control Center`, evitando construir una UI ambigua o demasiado grande antes de fijar modulos, privilegios y APIs minimas.

Estado de avance dentro de este ciclo:

- `ADR-006` ya fue aprobada y `GNOME` queda fijado como interfaz predeterminada de `Home`.
- `MX-007` ya dejo validada la cadena real `rootfs -> overlay -> live` con `gnome-control-center` presente en el baseline materializado de `Home`.
- `Business` sigue otra ruta visible por `ADR-013`, por lo que `MX-008` no debe forzar convergencia artificial entre ediciones.
- La arquitectura general de `Control Center` ya existe en `111`, `112` y `368`, pero faltaba bajarla a un frente operativo concreto para `Home`.

Resultado ejecutivo esperado:

- Modulos V1 de `Control Center` identificados para `Home`.
- Frontera clara entre paneles `GNOME-native`, modulos `W4-augmented` y piezas `W4-native`.
- Contrato minimo de lectura, validacion, autorizacion y aplicacion fijado antes de abrir UI.
- Dependencias de `MX-009` reescritas para apoyarse en este frente.
- Trazabilidad ejecutiva y matriz de desarrollo alineadas a `MX-008` como nuevo ciclo activo.

Entregables tecnicos obligatorios:

1. Mapa inicial de modulos V1 para `Sistema`, `Red`, `Usuarios`, `Seguridad`, `Actualizaciones`, `Almacenamiento` y `Aplicaciones`.
2. Regla explicita de reutilizacion de `gnome-control-center` frente a UI W4 propia.
3. Frontera de privilegios y de autorizacion congelada para cambios de sistema.
4. Contrato minimo API/controlador para modulos con backend.
5. Handoff claro hacia `MX-009` sin mezclar este frente con onboarding o capa de producto completa.

Ese aterrizaje operativo ya queda abierto en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-008_HOME_CONTROL_CENTER_V1_CONTRACT.md`, para que `Control Center` deje de ser solo una arquitectura general y pase a contar con un contrato V1 concreto sobre `Home`. La pieza toma como referencias `Docs/W4-OS/111_W4_OS_CONTROL_CENTER_ARCHITECTURE.md`, `Docs/W4-OS/112_W4_OS_SYSTEM_SETTINGS.md` y `Docs/W4-OS/368_W4_OS_CONTROL_CENTER_API.md`, pero las baja a una ruta conservadora: reutilizar `GNOME` cuando el upstream ya cubre el caso, envolver con W4 solo donde haga falta politica, procedencia o validacion adicional, y evitar una UI privilegiada sin contrato estable.

Resultado del siguiente paso:
`MX-008` ya no parte de una shell hipotetica ni se queda en un read model aislado. La base visible de `Home` queda materializada en `build/live-output/w4-os-home/image-root/live/filesystem.manifest` y `build/live-output/w4-os-home/image-root/system-overlay/`; el frente ya selecciona un primer paquete tecnico concreto en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-008_HOME_CONTROL_CENTER_V1_SLICE_A_FOUNDATION.md`; ya materializa una primera superficie de codigo en `src/ControlCenter/ControlCenterSliceAToolkit.php`; consume ese `Slice A` desde `src/ControlCenter/ControlCenterHomeToolkit.php`, con salida reutilizable por `scripts/read_control_center_home.php` tanto para home agregada como para detalle por modulo; puede persistir snapshots autocontenidos en `build/control-center/<perfil>/` mediante `scripts/generate_control_center_snapshot.php`; puede leer esa persistencia como contrato estable con `src/ControlCenter/ControlCenterSnapshotToolkit.php` y `scripts/read_control_center_snapshot.php`; puede traducirla a una respuesta API estable mediante `src/ControlCenter/ControlCenterApiToolkit.php` y `scripts/read_control_center_api.php`; puede materializar una primera interfaz visual estatica mediante `src/ControlCenter/ControlCenterUiToolkit.php` y `scripts/generate_control_center_ui_bundle.php`; queda formalmente orientado por `ADR-015` a una ruta `GNOME-augmented` con mapa tecnico inicial en `src/ControlCenter/ControlCenterGnomeIntegrationToolkit.php` y `scripts/generate_control_center_gnome_integration.php`; puede emitir launchers `.desktop` reales para GNOME mediante `src/ControlCenter/ControlCenterGnomeLauncherToolkit.php` y `scripts/generate_control_center_gnome_launchers.php`; `scripts/generate_system_overlay.php` ya integra ese bundle dentro del overlay real de `Home`; `scripts/generate_live_bundle.php` ya propaga ese estado hacia el contrato del live bundle; y `src/ControlCenter/ControlCenterLiveOutputToolkit.php` junto con `scripts/validate_control_center_live_output.php` ya añaden un gate especifico para contrastar un `live-output` materializado real contra esa ruta `GNOME-augmented`.

Siguiente paso despues de este corte:
ese primer ajuste UX ya queda materializado tambien en defaults: `config/editions/home/desktop-defaults.json` ahora fija `w4-control-center-home-home.desktop` y `w4-control-center-home-updates.desktop` como favoritos por defecto de `Home`, manteniendo `MX-009` como el siguiente frente visible de producto una vez que confirmemos si este set de favoritos queda congelado tal cual o si requiere un ajuste menor adicional.

Estado actual de ese subcorte:
ya existe una base suficiente para abrirlo: `gnome-control-center` forma parte del baseline materializado de `Home`; la arquitectura documental de `Control Center` y `System Settings` ya estaba redactada; `MX-008_HOME_CONTROL_CENTER_V1_CONTRACT.md` ya define la clasificacion inicial entre modulos `GNOME-native`, `W4-augmented` y `W4-native`; `MX-008_HOME_CONTROL_CENTER_V1_SLICE_A_FOUNDATION.md` ya fija una estrategia `read-first` con fuentes tecnicas concretas en `EditionPolicyToolkit`, `SecurityBaselineToolkit`, `UpdateToolkit` e `InstallerToolkit`; `ControlCenterSliceAToolkit` ya expone un read model homogeneo para `Sistema`, `Seguridad`, `Actualizaciones` y `Almacenamiento`; `ControlCenterHomeToolkit` ya agrega ese material en una primera home de `Settings` con estado visible, highlights, `status_reason`, `deep-links` a `GNOME` y `drill-down` por modulo en formatos `json` y `text`; `ControlCenterHomeRenderer` centraliza el render reutilizable; `generate_control_center_snapshot.php` ya deja evidencia persistida lista para consumidores posteriores; `ControlCenterSnapshotToolkit` junto con `read_control_center_snapshot.php` ya consumen esa persistencia como fachada estable; `ControlCenterApiToolkit` junto con `read_control_center_api.php` ya la traducen a una respuesta API versionada para `home` y `module`; `ControlCenterUiToolkit` junto con `generate_control_center_ui_bundle.php` ya convierten esa respuesta en una primera UI HTML autocontenida; `ADR-015` junto con `ControlCenterGnomeIntegrationToolkit` ya congelan que esa UI queda como referencia contractual dentro de una ruta `GNOME-augmented`; `ControlCenterGnomeLauncherToolkit` junto con `generate_control_center_gnome_launchers.php` ya convierten ese mapa en launchers `.desktop` listos para un handoff real; `generate_system_overlay.php` ya integra esos launchers dentro del overlay efectivo; `generate_live_bundle.php` ya propaga esa integracion al `live-manifest`; `ControlCenterLiveOutputToolkit` junto con `validate_control_center_live_output.php` ya permiten validar el `live-output` materializado final sin inspeccion manual archivo por archivo; y `config/editions/home/desktop-defaults.json` ya promociona `W4 Settings` y `Updates` dentro de los favoritos por defecto del shell usando esos mismos launchers GNOME. La fotografia operativa ya queda cerrada tambien en artefacto real: `build/live-output/w4-os-home/` fue recompuesto desde el rootfs validado de `Home`, el validador ya cierra con `status=ok`, los cuatro launchers GNOME ya estan presentes en `system-overlay` y el `filesystem.manifest` vuelve a confirmar `gdm3`, `gnome-control-center`, `gnome-session`, `gnome-shell`, `gnome-software`, `nautilus` y `xdg-desktop-portal-gnome`.

Como guardrail de este frente, la regla principal queda congelada desde el arranque: si `GNOME` ya ofrece un panel suficiente para V1 y no existe politica W4 adicional, se reutiliza antes de construir una UI nueva. Con ello, `MX-008` mantiene el enfoque incremental, minimiza deriva frente al upstream y prepara una base mas realista para `MX-009`.

## Referencia historica · MX-004

Objetivo del ciclo:
materializar el primer tramo verificable de `Update y recovery` sobre la base ya validada de instalacion, sin reabrir alcance de escritorio ni gestion Business.

Resultado ejecutivo esperado:

- coordinador durable de actualizacion con `operation_id`,
- registro persistente de estado y ultimo error tipado,
- preparacion de snapshot previo a aplicar cambios,
- flujo offline de aplicacion y verificacion post-arranque,
- recuperacion manual probada con evidencia repetible en VM.

Entregables tecnicos obligatorios:

1. `update plan generator` que resuelva una operacion versionada sobre una fuente controlada.
2. `operation store` durable fuera del estado revertible, con esquema minimo:
   - `operation_id`
   - etapa actual
   - origen y destino
   - snapshot previo
   - manifiesto de arranque
   - ultimo error tipado
3. `update executor` capaz de:
   - validar precondiciones,
   - crear snapshot,
   - preparar aplicacion offline,
   - registrar transiciones de estado,
   - dejar diagnostico legible si falla.
4. `health check` post-arranque con decision explicita:
   - confirmar,
   - marcar `failed`,
   - o indicar recuperacion.
5. `recovery playbook` operativo para VM con procedimiento de retorno al ultimo estado util.

Pruebas minimas del ciclo:

1. actualizacion nominal de paquete de prueba sin corrupcion del sistema,
2. reinicio con operacion en estado `pending_health`,
3. fallo inducido antes de confirmar salud,
4. recuperacion al snapshot previo con arranque util,
5. verificacion de que el archivo de prueba definido sigue accesible tras recovery,
6. reintento idempotente de una misma operacion sin crear duplicados inconsistentes.

Evidencia esperada:

- artefactos versionados del plan y del estado de operacion,
- logs de transicion por etapa,
- identificador de snapshot previo y criterio de seleccion,
- evidencia de arranque posterior a update,
- evidencia de fallo controlado,
- evidencia de recovery y de preservacion del archivo de prueba,
- actualizacion de `DEVELOPMENT_MATRIX.md` y `DEVELOPMENT_VERSIONS.md`.

Fuera de alcance de este ciclo:

- atomicidad integral por generaciones,
- UI final de usuario para update,
- orquestacion de flota Business,
- promesas de rollback universal sobre firmware o datos externos al snapshot.

### Backlog operativo de cierre para `MX-004`

1. Publicacion operativa del repo firmado oficial
   - reconstruir el repositorio APT usando `--signing-profile prod`,
   - verificar `InRelease`, `Release.gpg`, keyring y `repo.env` alineados con `signed-by=`,
   - congelar la ruta oficial de salida y el procedimiento repetible de reconstruccion.

2. Revalidacion final del flujo firmado sobre Home y Business
   - repetir `w4-update-smoke-003` y `w4-update-business-smoke-001` contra el repo firmado oficial,
   - confirmar `operation.json.stage=confirmed`, `health-report.status=ok` y evidencia descargable al host,
   - verificar que el consumo se haga por la source firmada y no por fallback `trusted=yes`.

3. Endurecimiento final del desbloqueo LUKS en laboratorio
   - eliminar la necesidad de reinyeccion manual de passphrase cuando el helper se adelanta al prompt,
   - decidir entre deteccion por consola, espera sincronizada o criterio operativo equivalente,
   - dejar el comportamiento cubierto por prueba automatizada o documentado como limitacion aceptada.

4. Congelacion del playbook operativo de `MX-004`
   - consolidar el comando oficial de reconstruccion/publicacion,
   - consolidar el comando oficial de validacion host -> VM,
   - actualizar la documentacion operativa para que un tercero pueda repetir el flujo sin conocimiento tacito.

5. Cierre formal del ciclo y handoff a `MX-005`
   - actualizar `DEVELOPMENT_MATRIX.md` y `DEVELOPMENT_VERSIONS.md` con el cierre real,
   - dejar enlazada la evidencia final de Home y Business,
   - abrir `MX-005` solo cuando el repo firmado oficial y el recovery probado queden estabilizados.

### Criterio de salida especifico de `MX-004`

`MX-004` solo debe considerarse listo para ceder prioridad a `MX-005` cuando se cumplan simultaneamente estas condiciones:

1. el repo firmado oficial se reconstruye de forma repetible con `--signing-profile prod`,
2. Home y Business completan de nuevo el flujo firmado hasta `confirmed`,
3. el desbloqueo LUKS deja de requerir intervencion manual fuera del playbook aceptado,
4. la evidencia final y los comandos operativos quedan trazados en la documentacion de desarrollo.

## Criterio de gobernanza

Una fase no se considera cerrada por redaccion adicional de documentos. Solo se cierra cuando existe:

- artefacto tecnico identificable,
- prueba ejecutada,
- evidencia enlazada,
- decision aprobada cuando aplique,
- responsable visible.

## Cadencia recomendada

Por cada ciclo de desarrollo:

1. ejecutar trabajo tecnico,
2. actualizar `DEVELOPMENT_MATRIX.md`,
3. registrar cambio de version en `DEVELOPMENT_VERSIONS.md`,
4. ajustar `DEVELOPMENT_TABLE.md` si cambia prioridad o alcance,
5. revisar `DEVELOPMENT_GUIDELINES.md` si se introduce una nueva regla de trabajo.

## Decision de exito del proyecto

W4 OS V1 se considera encaminado correctamente cuando el proyecto demuestra primero su capacidad de instalacion, actualizacion y recuperacion, y solo despues amplia funciones de experiencia, gestion empresarial o posicionamiento comercial.
