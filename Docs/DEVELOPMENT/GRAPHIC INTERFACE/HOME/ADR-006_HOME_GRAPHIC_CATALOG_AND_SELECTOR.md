# ADR-006 · Catalogo grafico y selector de interfaz para Home

Fecha: 2026-10-04
Estado: Aprobada por solicitud
Alcance: `W4 OS Home V1`

## Proposito

Cerrar la decision arquitectonica de `MX-006` sobre la interfaz grafica predeterminada de `Home`, el alcance del catalogo visual soportado y el comportamiento del instalador cuando existan variantes graficas disponibles en el medio.

## Contexto

- `Home` ya cuenta con base instalable, actualizable y endurecida.
- La matriz `MX-006_HOME_V1_DESKTOP_MATRIX.md` ya comparo `GNOME`, `KDE Plasma`, `XFCE` y `Cinnamon`.
- La direccion de producto confirmo `GNOME` como interfaz grafica predeterminada.
- El proyecto quiere habilitar selector de interfaz en instalacion sin prometer paridad total entre todas las variantes desde el primer dia.

## Decision

1. `GNOME` queda aprobado como interfaz grafica predeterminada de `Home`.
2. El instalador de `Home` puede exponer un selector de interfaz grafica cuando el medio incluya variantes calificadas.
3. Si el usuario no cambia la seleccion, o si el medio no incluye selector, la instalacion debe resolver `GNOME`.
4. `KDE Plasma`, `XFCE` y `Cinnamon` quedan aprobados como variantes controladas del catalogo grafico de `Home`.
5. La aprobacion del catalogo no implica paridad automatica de soporte entre variantes:
   - `GNOME`: ruta base y soporte principal de `Home V1`.
   - `KDE Plasma`, `XFCE`, `Cinnamon`: variantes seleccionables solo cuando el medio y la evidencia de QA las califiquen.
6. La composicion por paquetes debe separarse en:
   - `w4-desktop-meta` como base comun,
   - `w4-desktop-gnome-meta` como ruta predeterminada,
   - `w4-desktop-kde-meta`,
   - `w4-desktop-xfce-meta`,
   - `w4-desktop-cinnamon-meta` como variantes.
7. `MX-007`, `MX-008` y la experiencia inicial de `Home` deben tomar `GNOME` como referencia principal de shell, branding, onboarding y defaults.

## Motivos

- `GNOME` ofrece una base visible mas contenida y opinionada para `Home`.
- La decision de producto prioriza una experiencia inicial coherente sobre el mayor puntaje bruto de la matriz.
- Mantener variantes controladas preserva flexibilidad sin obligar a declarar soporte simetrico inmediato.
- El selector de interfaz queda permitido, pero atado a medios concretos y a evidencia real de calificacion.

## Alternativas consideradas

### 1. `KDE Plasma` como default

No se adopta como decision final, aunque la matriz le dio mejor puntaje tecnico agregado. La direccion de producto priorizo `GNOME` como base visible de `Home`.

### 2. Un unico escritorio sin selector

Se descarta. El producto ya decidio conservar un catalogo grafico y permitir seleccion cuando el medio lo soporte.

### 3. Cuatro variantes con soporte pleno equivalente en V1

Se descarta por costo de QA, mantenimiento, accesibilidad, updates y recovery. Solo `GNOME` queda como ruta principal garantizada desde esta ADR.

## Consecuencias

- `MX-006` queda cerrado como frente de decision.
- El siguiente frente natural pasa a `MX-007`.
- La documentacion del instalador, perfiles y alcance V1 debe asumir `GNOME` como ruta base.
- Cada variante no predeterminada necesitara evidencia minima antes de aparecer en un medio oficial:
  - arranque,
  - login manager,
  - sesion grafica,
  - accesibilidad basica,
  - update,
  - recovery.

## Regla operativa para medios

Un medio de instalacion solo puede mostrar una variante grafica en el selector si:

1. el metapaquete correspondiente esta presente,
2. existe evidencia minima de QA para esa variante,
3. la variante esta declarada como soportada en el manifiesto o politica del medio.

Si alguna de esas condiciones no se cumple, la variante no debe anunciarse en el selector aunque exista documentalmente en el catalogo.

## Impacto en roadmap

- `MX-006`: cerrado por ADR aprobada.
- `MX-007`: pasa a tomar `GNOME` como base de shell y branding.
- `MX-008`: debe definir modulos compatibles con `GNOME` primero y abstraer el resto donde aporte valor real.
- `MX-009`: debe construir el recorrido de `Home` sobre `GNOME` antes de extender soporte fuerte a variantes adicionales.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-006_HOME_V1_DESKTOP_MATRIX.md`
- `Docs/W4-OS/092_W4_OS_DESKTOP_ENVIRONMENT_STRATEGY.md`
- `Docs/W4-OS/032_W4_OS_INSTALLATION_FLOW.md`
- `Docs/W4-OS/014_W4_OS_EDITION_PACKAGE_PROFILES.md`
- `Docs/W4-OS/406_W4_OS_V1_SCOPE.md`
