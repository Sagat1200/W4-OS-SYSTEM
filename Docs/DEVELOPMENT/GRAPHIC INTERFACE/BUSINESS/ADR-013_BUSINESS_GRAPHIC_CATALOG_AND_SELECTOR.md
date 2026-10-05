# ADR-013 · Catalogo grafico y selector de interfaz para Business

Fecha: 2026-10-04
Estado: Aprobada por solicitud
Alcance: `W4 OS Business V1`

## Proposito

Cerrar la decision arquitectonica de la interfaz grafica predeterminada de `Business`, el alcance del catalogo visual soportado y el comportamiento del instalador cuando existan variantes graficas disponibles en el medio.

## Contexto

- `Business` ya cuenta con base instalable, actualizable y endurecida.
- La matriz `MX-010_BUSINESS_V1_DESKTOP_MATRIX.md` ya comparo `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon`.
- La direccion de producto confirmo `KDE Plasma` como interfaz grafica predeterminada de `Business`.
- El producto necesita una ruta visible mas administrable y configurable que la elegida para `Home`.

## Decision

1. `KDE Plasma` queda aprobado como interfaz grafica predeterminada de `Business`.
2. El instalador de `Business` puede exponer un selector de interfaz grafica cuando el medio incluya variantes calificadas.
3. Si el usuario no cambia la seleccion, o si el medio no incluye selector, la instalacion debe resolver `KDE Plasma`.
4. `GNOME`, `XFCE` y `Cinnamon` quedan aprobados como variantes controladas del catalogo grafico de `Business`.
5. La aprobacion del catalogo no implica paridad automatica de soporte entre variantes:
   - `KDE Plasma`: ruta base y soporte principal de `Business V1`.
   - `GNOME`, `XFCE`, `Cinnamon`: variantes seleccionables solo cuando el medio y la evidencia de QA las califiquen.
6. La composicion por paquetes debe separarse en:
   - `w4-desktop-meta` como base comun,
   - `w4-desktop-kde-meta` como ruta predeterminada de `Business`,
   - `w4-desktop-gnome-meta`,
   - `w4-desktop-xfce-meta`,
   - `w4-desktop-cinnamon-meta` como variantes.
7. Las futuras capas de branding, settings y experiencia empresarial deben tomar `KDE Plasma` como referencia principal de `Business`.

## Motivos

- `KDE Plasma` ofrece mayor densidad de configuracion util para despliegues empresariales y soporte.
- La decision de producto prioriza una ruta visible mas administrable sin abrir un fork profundo del escritorio.
- Mantener variantes controladas preserva flexibilidad sin prometer soporte simetrico inmediato.
- El selector de interfaz queda permitido, pero atado a medios concretos y a evidencia real de calificacion.

## Alternativas consideradas

### 1. `GNOME` como default

No se adopta para `Business`, aunque siga siendo una opcion madura. La direccion de producto priorizo `KDE Plasma` por su mayor superficie de configuracion y mejor encaje con una experiencia administrable.

### 2. Un unico escritorio sin selector

Se descarta. `Business` conserva un catalogo grafico y permite seleccion cuando el medio lo soporte.

### 3. Cuatro variantes con soporte pleno equivalente en V1

Se descarta por costo de QA, mantenimiento, accesibilidad, updates y recovery. Solo `KDE Plasma` queda como ruta principal garantizada desde esta ADR.

## Consecuencias

- La politica visual de `Business` queda separada de `Home`.
- `Business` ya no necesita compartir el mismo default grafico que `Home`.
- La documentacion de instalacion, perfiles y alcance de `Business` debe asumir `KDE Plasma` como ruta base.
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

- `Business` documenta ya una ruta base `KDE Plasma`.
- La futura implementacion de `Business` debe apoyarse primero en `KDE Plasma + SDDM`.
- Las variantes adicionales quedan subordinadas a medios calificados.
- La documentacion de producto y diferencias de edicion debe reflejar que `Home` y `Business` no comparten el mismo default grafico.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/BUSINESS/MX-010_BUSINESS_V1_DESKTOP_MATRIX.md`
- `Docs/W4-OS/012_W4_OS_BUSINESS_EDITION.md`
- `Docs/W4-OS/409_W4_OS_V1_BUSINESS_SCOPE.md`
- `Docs/W4-OS/032_W4_OS_INSTALLATION_FLOW.md`
- `Docs/W4-OS/014_W4_OS_EDITION_PACKAGE_PROFILES.md`
