# ADR-014 · Politica de interfaz para Server

Fecha: 2026-10-04
Estado: Aprobada por solicitud
Alcance: `W4 OS Server V1`

## Proposito

Cerrar la decision arquitectonica de interfaz para `Server` y dejar explícito que la ruta base del producto es `headless`, sin interfaz gráfica por defecto, pero permitiendo selector gráfico durante la instalación cuando el medio incluya variantes calificadas.

## Contexto

- `Server` ya fue validado operativamente como perfil headless.
- El producto ya tiene evidencia real de instalación, reboot cifrado, `SSH`, `w4-firstboot`, baseline y flujo automatizado sin GUI.
- `Home` y `Business` ya documentan sus propias políticas gráficas; `Server` no debe heredar esas decisiones.
- El contrato headless de `Server` ya excluye `w4-desktop-meta`, `pipewire`, `xdg-desktop-portal` y otras dependencias desktop del artefacto ISO.
- Existe un caso de uso válido para administración local o virtualización en laboratorio donde el usuario puede preferir instalar `Server` con una GUI opcional, sin que eso cambie el default del producto.

## Decision

1. `Server V1` no incluye interfaz gráfica por defecto.
2. El instalador de `Server` puede exponer un selector de interfaz gráfica cuando el medio incluya variantes calificadas.
3. Si el usuario no cambia la selección, o si el medio no incluye selector, la instalación debe resolver `headless`.
4. La ruta predeterminada de composición se resuelve mediante `w4-server-meta` y dependencias headless, sin `w4-desktop-meta`.
5. Las variantes gráficas opcionales aprobadas para `Server` son `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon`, siempre como opciones controladas y no como default.
6. Cuando el usuario elija una variante gráfica, la composición debe resolver `w4-server-meta` más el metapaquete gráfico aprobado correspondiente.
7. `Server` no comparte el mismo default gráfico de `Home` ni de `Business`, aunque pueda reutilizar parte de sus metapaquetes gráficos.
8. La documentación de instalación, perfiles y alcance de `Server` debe tratar `headless` como la ruta oficial predeterminada de `V1`, con GUI opcional bajo selección explícita del usuario.

## Motivos

- La identidad funcional de `Server` es operación remota y mínima superficie local.
- La evidencia operativa real ya está cerrada sobre el camino headless.
- Añadir GUI al perfil base aumentaría complejidad, tamaño de imagen, superficie de ataque y deriva frente al contrato ya validado.
- Permitir GUI solo bajo selección explícita del usuario cubre casos de laboratorio o baja experiencia sin romper el default del producto.
- `Home` y `Business` ya cubren las necesidades de UX gráfica del producto.

## Alternativas consideradas

### 1. `KDE Plasma` como default de `Server`

Se descarta. Aunque podría facilitar algunos casos locales de administración, contradice la identidad headless del producto y reabre dependencias desktop innecesarias.

### 2. `KDE Plasma`, `GNOME`, `XFCE` o `Cinnamon` como opciones seleccionables

Se aceptan solo como variantes opcionales. No deben sustituir el default `headless` ni anunciarse en medios que no las incluyan y califiquen.

### 3. Mantener una GUI opcional silenciosa dentro del mismo medio

Se descarta. Si existe GUI opcional, debe aparecer como elección explícita del usuario y no como carga silenciosa del medio.

## Consecuencias

- `Server` queda formalmente separado de las decisiones gráficas de `Home` y `Business`.
- El instalador puede ofrecer selección visual para `Server`, pero con `headless` preseleccionado.
- `w4-linux-base` no debe volver a contaminar a `Server` con dependencias desktop implícitas.
- Las variantes gráficas de `Server` quedan como opciones controladas del producto, no como comportamiento por defecto.

## Regla operativa para medios

Un medio oficial estándar de `Server`:

1. puede incluir selector de interfaz gráfica si el medio empaqueta variantes calificadas,
2. debe dejar `headless` como selección inicial predeterminada,
3. no debe instalar display manager ni `w4-desktop-meta` cuando el usuario mantiene la opción `headless`,
4. no debe prometer una sesión gráfica local como parte del soporte base si el usuario no la selecciona.

Si un medio de `Server` expone variantes gráficas, debe declararlas explícitamente y mantener evidencia propia para cada una.

## Impacto en roadmap

- `MX-012` conserva y endurece su contrato headless.
- Los frentes gráficos de `Home` y `Business` no deben alterar la composición base de `Server`.
- La futura evolución de `Server` debe concentrarse en operación, roles, políticas, administración remota y salud del sistema, no en UX gráfica local.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/SERVER/MX-012_SERVER_V1_INTERFACE_MATRIX.md`
- `Docs/DEVELOPMENT/W4-OS-SERVER-PLAN-MAESTRO.md`
- `Docs/W4-OS/014_W4_OS_EDITION_PACKAGE_PROFILES.md`
- `Docs/W4-OS/032_W4_OS_INSTALLATION_FLOW.md`
- `Docs/W4-OS/094_W4_OS_DISPLAY_SERVER_STRATEGY.md`
