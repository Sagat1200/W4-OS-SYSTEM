# ADR-015 · Integracion `GNOME-augmented` para `Settings` en `Home`

Fecha: 2026-10-07
Estado: Aprobada por solicitud
Alcance: `W4 OS Home V1`, `MX-008`

## Proposito

Cerrar la decision de producto y arquitectura sobre el rol de la primera UI HTML de `Control Center` y fijar la ruta oficial de integracion de `Settings` para `Home`.

## Contexto

- `ADR-006` ya fijo `GNOME` como interfaz predeterminada de `Home`.
- `MX-007` ya valido la ruta real `GNOME + GDM` con `gnome-control-center` presente en el baseline materializado.
- `MX-008` ya cuenta con read model, home/CLI, snapshot persistido, lectura estable, respuesta API y un bundle HTML estatico generado desde esa API.
- El proyecto necesita una interfaz de `Settings` usable sin abrir un fork UX paralelo a `GNOME` ni convertir `MX-008` en una suite propia demasiado amplia.

## Decision

1. La ruta oficial de `Settings` para `Home V1` queda fijada como `GNOME-augmented`.
2. `gnome-control-center` se reutiliza como primera superficie visible cuando el panel upstream ya cubre suficientemente el caso para V1.
3. W4 aporta capas de:
   - estado efectivo,
   - procedencia,
   - evidencia,
   - politica,
   - autorizacion,
   - y contratos API estables,
   sin reimplementar por defecto toda la UI de `GNOME`.
4. El bundle HTML generado por `MX-008` queda expresamente clasificado como `reference-only`:
   - sirve para contrato visual,
   - QA,
   - evidencia,
   - y prototipado,
   pero no pasa a ser el frontend oficial de `Settings`.
5. La integracion por modulo debe clasificarse en una de estas rutas:
   - `delegate`: `GNOME` cubre el caso y W4 solo referencia o acompana;
   - `augment`: `GNOME` sigue siendo la superficie principal, pero W4 agrega evidencia, politica o navegacion;
   - `w4-surface`: no existe hoy una superficie `GNOME` suficiente y la UI propia queda permitida de forma focalizada.
6. Para el `Slice A` inicial de `MX-008`, la ruta por defecto queda asi:
   - `Sistema`: `augment`
   - `Seguridad`: `augment`
   - `Actualizaciones`: `augment`
   - `Almacenamiento`: `w4-surface`

## Motivos

- Minimiza deriva frente a `GNOME`, que ya es la base aprobada y materializada de `Home`.
- Evita que la primera UI HTML de `MX-008` se convierta demasiado pronto en un producto paralelo.
- Conserva el valor del trabajo ya hecho: el bundle HTML sigue siendo util como referencia contractual y evidencia visible.
- Mantiene el enfoque incremental y deja a `MX-009` apoyarse en una base de `Settings` mas realista.

## Alternativas consideradas

### 1. Promover el bundle HTML como base oficial de `Settings`

No se adopta. Daba velocidad visible, pero abria una nueva superficie UX propia con mayor costo de mantenimiento, mas drift respecto a `GNOME` y riesgo de absorcion prematura de `MX-009`.

### 2. Reutilizar solo `gnome-control-center` sin capa W4 adicional

No se adopta. Es demasiado debil para cubrir procedencia, evidencia, politica y lectura estable entre modulos.

### 3. Construir una suite W4 completamente propia desde ahora

Se descarta por sobrealcance. Reabriria deuda de backend y UX antes de cerrar la frontera `read-first` del `Slice A`.

## Consecuencias

- `MX-008` ya no necesita decidir entre bundle HTML oficial o integracion `GNOME`: la decision queda cerrada.
- La UI HTML generada permanece como artefacto de referencia y QA.
- El siguiente trabajo tecnico debe acercar la integracion real con `GNOME`, no expandir sin control una UI propia.
- Las piezas W4 deben seguir saliendo de contratos estables:
   - snapshot persistido,
   - lectura estable,
   - respuesta API,
   - y manifiestos de integracion.

## Regla operativa

Antes de abrir una nueva pantalla propia de `Settings`, el modulo debe responder estas preguntas:

1. ¿Existe ya un panel suficiente en `GNOME` para V1?
2. ¿El valor agregado de W4 es politica, evidencia, autorizacion o trazabilidad visible?
3. ¿La nueva pantalla evita recomputar estado y se apoya en el contrato persistido/API existente?

Si la respuesta a la primera es si y las otras dos no justifican una UI nueva, se reutiliza `GNOME`.

## Impacto en roadmap

- `MX-008`: queda formalmente orientado a `GNOME-augmented`.
- `MX-009`: puede apoyarse en una base de `Settings` visible sin heredar una suite HTML paralela como deuda obligatoria.
- `Business`: no queda forzado a copiar esta decision; su ruta visible sigue determinada por `ADR-013`.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/ADR-006_HOME_GRAPHIC_CATALOG_AND_SELECTOR.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-008_HOME_CONTROL_CENTER_V1_CONTRACT.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-008_HOME_CONTROL_CENTER_V1_SLICE_A_FOUNDATION.md`
- `Docs/W4-OS/111_W4_OS_CONTROL_CENTER_ARCHITECTURE.md`
- `Docs/W4-OS/112_W4_OS_SYSTEM_SETTINGS.md`
- `Docs/W4-OS/368_W4_OS_CONTROL_CENTER_API.md`
