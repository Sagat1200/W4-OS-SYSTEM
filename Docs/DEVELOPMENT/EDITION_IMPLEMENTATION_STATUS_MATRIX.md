# W4 OS · Matriz comparativa de estado por edicion

## Objetivo

Dar una lectura ejecutiva y tecnica del estado relativo de `Home`, `Business` y `Server` por capacidad, separando:

- base comun del sistema ya validada,
- capacidades operativas realmente cerradas,
- y frentes de producto o UX que siguen pendientes.

Este documento no sustituye `DEVELOPMENT_MATRIX.md`. Su funcion es resumir, por edicion, que ya esta `cerrado`, que permanece `parcial` y que sigue `no iniciado`.

## Convenciones

- `Cerrado`: existe artefacto, prueba y/o evidencia operativa suficiente para tratar la capacidad como validada.
- `Parcial`: hay politica, artefacto o evidencia incompleta; aun no conviene leerlo como frente terminado.
- `No iniciado`: no existe todavia un cierre tecnico o de producto suficiente.
- `N/A`: la capacidad no es objetivo principal de la edicion.

## Resumen corto

- `Server` es hoy la edicion tecnicamente mas madura en pipeline operativo y validacion runtime.
- `Home` ya tiene cerrada la base tecnica instalable, actualizable y endurecida, y ahora tambien su baseline visible de shell + `Settings`; su deuda principal pasa a `MX-009`.
- `Business` comparte gran parte de la base tecnica cerrada, aunque su piloto funcional sigue mas verde que `Home`.

## Matriz por capacidad

| Capacidad | Home | Business | Server | Lectura ejecutiva |
| --- | --- | --- | --- | --- |
| `build-input` y manifiestos base | Cerrado | Cerrado | Cerrado | Las tres ediciones ya derivan desde la base comun y politicas por edicion. |
| `rootfs` | Cerrado | Cerrado | Cerrado | La cadena de ensamblado ya fue recorrida y materializada por perfil. |
| `overlay` | Cerrado | Cerrado | Cerrado | El overlay ya transporta configuracion efectiva y branding/politica por edicion. |
| `live bundle` | Cerrado | Cerrado | Cerrado | Existen artefactos `live` generados y contratos validados. |
| `iso` | Cerrado | Cerrado | Cerrado | Las tres rutas tienen ISO y `Server` ya la revalido ademas en WSL y VirtualBox. |
| instalacion destructiva | Cerrado | Cerrado | Cerrado | Las tres ediciones ya tienen evidencia de instalacion real en VM. |
| primer boot cifrado | Cerrado | Cerrado | Cerrado | El desbloqueo LUKS y el arranque posterior ya fueron validados. |
| `update` y recovery | Cerrado | Cerrado | Parcial | `Home` y `Business` ya cerraron corridas reales de update; `Server` conserva la base comun pero no tiene el mismo cierre operativo especifico documentado para ese frente. |
| baseline de seguridad | Cerrado | Cerrado | Cerrado | Las tres ediciones ya tienen baseline runtime en verde dentro de su contrato. |
| evidencia runtime end-to-end | Cerrado | Cerrado | Cerrado | `Server` incluso ya queda encapsulado en runner host->VM de un solo comando. |
| automatizacion VM/QA integrada | Parcial | Parcial | Cerrado | `Server` es el frente mas encapsulado; `Home` y `Business` aun dependen mas de playbook operativo que de runner integrado equivalente. |
| politica de interfaz | Cerrado | Cerrado | Cerrado | `Home`, `Business` y `Server` ya tienen matriz y ADR propias. |
| shell, branding y defaults visibles | Cerrado | No iniciado | N/A | `Home` ya aterrizo `GNOME + GDM`, branding reversible y favoritos visibles; `Business` aun no traduce su ADR grafica a composicion UX real. |
| apps base / experiencia utilizable | Parcial | No iniciado | N/A | `Home` ya cerro el baseline visible materializado de navegador, documentos, PDF, multimedia, archivos, `W4 Settings` y `Updates`; ademas ya abrio onboarding local como readiness verificable con `HomeOnboardingLiveOutputToolkit`, aunque el `live-output` vigente aun debe rematerializarse con el fix de activacion por `default target` antes de tratar ese subcorte como cierre materializado. `Business` sigue sin aterrizaje utilizable equivalente. |
| control center / gestion UX | Parcial | No iniciado | N/A | `Home` ya tiene baseline `GNOME-augmented` validado y suficiente para `Home V1`, aunque no una suite completa de producto; `Business` sigue sin este frente. |
| perfil de producto final | Parcial | Parcial | Cerrado | `Server` ya esta fuerte para su objetivo headless; `Home` y `Business` aun no cierran su capa final de producto. |

## Lectura por edicion

### Home

`Home` ya no esta en fase puramente documental. Tiene cerrada la base tecnica para:

- construir,
- instalar,
- arrancar cifrado,
- actualizar,
- recuperar,
- y validar baseline de seguridad.

Su hueco real ya no es infraestructura base ni `Settings` de arranque, sino `MX-009`: cerrar onboarding local y luego accesibilidad sobre la base visible ya validada.

### Business

`Business` comparte la mayor parte del cierre tecnico de `Home` en build, instalacion, update y baseline. Su diferencia es que el frente de producto sigue mas verde:

- existe politica visual aprobada,
- existe direccion documental de piloto,
- pero aun no existe el mismo aterrizaje visible de composicion, branding, enrollment y settings empresariales minimos.

### Server

`Server` ya quedo muy por delante en madurez operativa del pipeline:

- `rootfs -> overlay -> live -> iso` revalidados,
- instalacion real repetida,
- reboot cifrado,
- baseline runtime,
- helper de live SSH,
- y runner integrado host->VM con contrato de QA reproducible.

Su deuda no esta en estabilidad base, sino en decidir cuanto de la GUI opcional se calificara sin romper su identidad `headless` por defecto.

## Conclusion ejecutiva

Si la pregunta es por la base tecnica de `W4 Linux Base` aplicada a cada edicion, la lectura actual es:

- `Server`: la mas madura.
- `Home`: muy cerca en la base tecnica, pero con mayor deuda de producto visible.
- `Business`: comparte base tecnica fuerte, pero sigue mas atras en aterrizaje de producto.

Si la pregunta es por producto final visible:

- `Home` y `Business` todavia no estan al nivel de cierre que ya tiene `Server` para su objetivo operativo.

## Siguiente uso recomendado

Usar esta matriz antes de abrir un nuevo frente para responder una pregunta simple:

`¿Estamos discutiendo una deuda de base tecnica o una deuda de producto?`
