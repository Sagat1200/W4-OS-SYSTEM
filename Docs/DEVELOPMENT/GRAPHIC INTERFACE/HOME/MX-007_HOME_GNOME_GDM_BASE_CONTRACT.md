# MX-007 · Contrato base `GNOME + GDM` y `w4-desktop-gnome-meta`

Fecha: 2026-10-04
Estado: Propuesta operativa para implementacion
Alcance: `W4 OS Home V1`
Dependencias: `ADR-006`, `MX-007_HOME_GNOME_SHELL_AND_BRANDING_MINIMO.md`

## Proposito

Definir el contrato minimo de composicion visible para `Home V1` sobre `GNOME`, fijando la frontera entre:

- base desktop comun,
- composicion especifica `GNOME + GDM`,
- branding reversible,
- y elementos que quedan fuera de este primer corte.

Este documento no declara aun implementacion materializada. Su funcion es reducir ambiguedad antes de tocar manifests, paquetes, overlays o UX.

## Decision operativa de este corte

Para `Home V1`, la ruta base visible se define como:

- `w4-desktop-meta` para la base desktop comun,
- `w4-desktop-gnome-meta` para la composicion especifica de `GNOME`,
- `GDM` como display manager obligatorio de la ruta predeterminada,
- `gnome-shell` como shell oficial de referencia,
- `gnome-session` como sesion grafica predeterminada.

La regla de producto es simple:

si el medio instala `Home` sin selector o el usuario no cambia la variante, la instalacion debe resolver `GNOME + GDM`.

## Frontera de composicion

### 1. `w4-desktop-meta`

Debe contener solo la base comun reutilizable por rutas desktop soportadas.

Incluye por contrato:

- integracion desktop comun requerida por `Home V1`,
- servicios o utilidades compartidas por varias rutas desktop,
- componentes de compatibilidad que no deben quedar atados a un shell concreto,
- dependencias ya asumidas por el baseline de escritorio del producto.

No debe contener por contrato:

- `gdm`,
- `gnome-shell`,
- `gnome-session`,
- componentes exclusivos de otra variante concreta,
- branding especifico de una sola ruta grafica,
- extensiones del shell acopladas a `GNOME`.

### 2. `w4-desktop-gnome-meta`

Debe contener la composicion visible especifica de la ruta `Home` oficial sobre `GNOME`.

Incluye por contrato:

- `gdm`,
- `gnome-shell`,
- `gnome-session`,
- componentes minimos necesarios para que la sesion GNOME arranque de forma consistente,
- dependencias minimas de experiencia visual directamente ligadas a `GNOME`,
- assets o defaults reversibles solo cuando dependan de esta ruta.

No debe contener por contrato:

- aplicaciones `Home` no estrictamente ligadas al shell,
- branding irreversible que requiera fork profundo,
- extensiones experimentales del shell,
- dependencias de `KDE Plasma`, `XFCE` o `Cinnamon`,
- configuracion de onboarding propia de `MX-009`,
- modulos de `Control Center` propios de `MX-008`.

## Contrato de `GDM`

`GDM` queda fijado como display manager obligatorio de la ruta base `Home + GNOME` por estas razones:

1. es la ruta natural y menos fracturada para `GNOME`;
2. reduce superficie de integracion entre shell y login manager;
3. evita abrir variabilidad innecesaria en `MX-007`;
4. deja una base mas clara para branding reversible y para futuros cambios controlados.

Consecuencia:

`LightDM`, `SDDM` u otros display managers no forman parte de la ruta base de `Home V1` sobre `GNOME`.

## Defaults visibles permitidos en este corte

Este frente puede fijar solo defaults reversibles y de bajo costo de mantenimiento.

Permitidos:

- wallpaper por defecto,
- tema visual por defecto, siempre que sea reversible sin romper la sesion,
- iconos por defecto si no fuerzan fork profundo,
- nombre visible o assets de login solo si se aplican sin modificar internals de `GDM` o `gnome-shell`,
- defaults minimos de sesion cuando puedan revertirse mediante configuracion.

No permitidos en este corte:

- parches profundos sobre `gnome-shell`,
- extensiones propias como requisito obligatorio de la sesion,
- reemplazo estructural del flujo de login,
- shell custom o dock fuertemente acoplado,
- onboarding integrado en el shell,
- settings avanzados propios de `MX-008`.

## Paquetes y niveles de decision

Para este corte conviene distinguir tres niveles:

### Nivel A · Obligatorio para la ruta base

Debe estar documentado como obligatorio en la futura implementacion:

- `w4-desktop-meta`
- `w4-desktop-gnome-meta`
- `gdm`
- `gnome-shell`
- `gnome-session`

### Nivel B · Esperado pero sujeto a cierre de manifiesto

Debe resolverse en el siguiente subcorte tecnico al pasar de contrato a composicion real:

- utilidades exactas de sesion GNOME requeridas para login fiable,
- componentes graficos minimos de branding,
- integracion de defaults por dconf, overlay o equivalente,
- assets visuales W4 que entren en V1.

### Nivel C · Fuera del contrato base

Se pospone a fases posteriores:

- apps completas de `Home`,
- onboarding,
- `Control Center`,
- variantes no predeterminadas,
- soporte fuerte multi-escritorio.

## Criterios de aceptacion del contrato

Este documento puede darse por valido si deja cerradas estas preguntas:

1. que pertenece a `w4-desktop-meta` y que no;
2. que pertenece a `w4-desktop-gnome-meta` y que no;
3. si `GDM` es obligatorio o no;
4. que defaults visuales puede tocar `MX-007`;
5. que cosas siguen fuera hasta `MX-008` y `MX-009`.

## Impacto directo en frentes siguientes

### Impacto en `MX-008`

`Control Center` ya no debe diseñarse contra un shell abstracto. Su primera referencia visible pasa a ser la ruta `Home` sobre `GNOME + GDM`.

### Impacto en `MX-009`

`Home utilizable` ya no debe abrir discusion sobre escritorio base. Debe partir de esta composicion y enfocarse en:

- apps,
- onboarding,
- defaults de uso diario,
- accesibilidad,
- recorrido inicial del usuario.

### Impacto en `Business`

Este contrato no debe reutilizarse automaticamente en `Business`. `Business` sigue su propia ruta visible sobre `KDE Plasma`.

## Riesgos si no se respeta este contrato

### R-1 · `w4-desktop-meta` demasiado grande

Si absorbe componentes especificos de `GNOME`, la base comun dejara de ser realmente reutilizable.

### R-2 · `w4-desktop-gnome-meta` demasiado amplio

Si absorbe onboarding, apps o branding profundo, `MX-007` invadira `MX-008` y `MX-009`.

### R-3 · `GDM` no fijado

Si se deja abierto el display manager, se multiplica QA y se vuelve difuso el frente de branding.

## Siguiente paso operativo

Con este contrato ya definido, el siguiente subcorte correcto es documentar la politica minima de branding reversible para:

- wallpaper,
- tema,
- iconos,
- assets de login,
- y mecanismo de defaults.

Solo despues de ese cierre conviene tocar manifests o composicion tecnica real del escritorio.

Ese siguiente subcorte ya queda documentado en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_REVERSIBLE_BRANDING_POLICY.md`.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/ADR-006_HOME_GRAPHIC_CATALOG_AND_SELECTOR.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-006_HOME_V1_DESKTOP_MATRIX.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_SHELL_AND_BRANDING_MINIMO.md`
- `Docs/W4-OS/014_W4_OS_EDITION_PACKAGE_PROFILES.md`
- `Docs/W4-OS/092_W4_OS_DESKTOP_ENVIRONMENT_STRATEGY.md`
