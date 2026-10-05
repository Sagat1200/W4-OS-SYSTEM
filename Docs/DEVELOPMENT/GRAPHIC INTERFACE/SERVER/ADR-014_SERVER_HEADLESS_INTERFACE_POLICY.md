# ADR-014 · Politica de interfaz para Server

Fecha: 2026-10-04
Estado: Aprobada por solicitud
Alcance: `W4 OS Server V1`

## Proposito

Cerrar la decision arquitectonica de interfaz para `Server` y dejar explícito que la ruta base del producto es `headless`, sin interfaz gráfica por defecto ni selector gráfico en la instalación estándar.

## Contexto

- `Server` ya fue validado operativamente como perfil headless.
- El producto ya tiene evidencia real de instalación, reboot cifrado, `SSH`, `w4-firstboot`, baseline y flujo automatizado sin GUI.
- `Home` y `Business` ya documentan sus propias políticas gráficas; `Server` no debe heredar esas decisiones.
- El contrato headless de `Server` ya excluye `w4-desktop-meta`, `pipewire`, `xdg-desktop-portal` y otras dependencias desktop del artefacto ISO.

## Decision

1. `Server V1` no incluye interfaz gráfica por defecto.
2. La instalación estándar de `Server` no expone selector de interfaz gráfica.
3. La ruta predeterminada de composición se resuelve mediante `w4-server-meta` y dependencias headless, sin `w4-desktop-meta`.
4. `Server` no comparte el catálogo gráfico de `Home` ni de `Business`.
5. Cualquier variante gráfica futura para `Server` queda fuera del producto base y requerirá:
   - un medio separado o política explícita del medio,
   - composición de paquetes diferenciada,
   - evidencia propia de arranque, sesión, update y recovery,
   - y aprobación documental independiente.
6. La documentación de instalación, perfiles y alcance de `Server` debe tratar `headless` como única ruta oficial de `V1`.

## Motivos

- La identidad funcional de `Server` es operación remota y mínima superficie local.
- La evidencia operativa real ya está cerrada sobre el camino headless.
- Añadir GUI al perfil base aumentaría complejidad, tamaño de imagen, superficie de ataque y deriva frente al contrato ya validado.
- `Home` y `Business` ya cubren las necesidades de UX gráfica del producto.

## Alternativas consideradas

### 1. `KDE Plasma` como default de `Server`

Se descarta. Aunque podría facilitar algunos casos locales de administración, contradice la identidad headless del producto y reabre dependencias desktop innecesarias.

### 2. `GNOME`, `XFCE` o `Cinnamon` como opciones seleccionables

Se descartan para la instalación estándar de `V1`. El producto base no necesita selector gráfico y no existe evidencia operativa equivalente para sostenerlo.

### 3. Mantener una GUI opcional silenciosa dentro del mismo medio

Se descarta. Introducir paquetes gráficos “por si acaso” erosiona el contrato headless y confunde el soporte oficial.

## Consecuencias

- `Server` queda formalmente separado de las decisiones gráficas de `Home` y `Business`.
- El instalador debe resolver directamente el perfil headless sin ofrecer selección visual.
- `w4-linux-base` no debe volver a contaminar a `Server` con dependencias desktop implícitas.
- Cualquier GUI futura para `Server` será una excepción controlada, no parte del comportamiento por defecto.

## Regla operativa para medios

Un medio oficial estándar de `Server`:

1. no debe incluir selector de interfaz gráfica,
2. no debe instalar display manager,
3. no debe arrastrar `w4-desktop-meta`,
4. no debe prometer una sesión gráfica local como parte del soporte base.

Si en el futuro existiera un medio especializado de `Server` con GUI, deberá declararse como variante explícita y no como ISO estándar del producto.

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
