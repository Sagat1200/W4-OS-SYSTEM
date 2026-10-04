# W4 OS · Executive Implementation Plan

Plan ejecutivo para convertir la especificacion documental de W4 OS en una implementacion verificable, manteniendo separacion entre diseno, evidencia tecnica y estado de release.

## Relacion con otros documentos

- `DEVELOPMENT_TABLE.md`: prioridades y entregables por area.
- `DEVELOPMENT_MATRIX.md`: seguimiento operativo del trabajo activo.
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

## Alcance ejecutivo V1

Incluye:

- Base Debian estable integrada como `W4 Linux Base`.
- Referencia inicial `amd64 UEFI`.
- Un escritorio oficial.
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

## Siguiente ciclo recomendado · MX-006

Objetivo del ciclo:
fijar el escritorio oficial V1 para Home sobre la base ya instalable, actualizable, endurecida y separada de Server, evitando abrir `shell`, `control center` y `MX-009` sin una decision de plataforma visible.

Resultado ejecutivo esperado:

- Decision ADR de escritorio V1 aprobada.
- Matriz comparativa de KDE vs GNOME cerrada con criterios tecnicos y de producto.
- Criterios minimos de accesibilidad, consumo base y mantenibilidad explicitados para Home.
- Direccion clara para `w4-desktop-meta`, branding, `shell` y `control center`.
- Dependencias y regresiones esperadas sobre Home/Business documentadas antes de abrir implementacion UX.

Entregables tecnicos obligatorios:

1. Matriz comparativa V1 entre KDE y GNOME con criterios de producto y operacion.
2. ADR de escritorio oficial aprobada y enlazada al roadmap.
3. Criterios minimos para `shell`, branding, onboarding y apps base delimitados a partir de la decision.
4. Riesgos y dependencias de `MX-007`, `MX-008` y `MX-009` actualizados contra la decision tomada.
5. Trazabilidad ejecutiva y matriz de desarrollo alineadas al escritorio elegido.

Siguiente paso despues de este corte:
abrir implementacion de `MX-007`, `MX-008` y despues `MX-009` sobre el escritorio oficial ya fijado.

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
