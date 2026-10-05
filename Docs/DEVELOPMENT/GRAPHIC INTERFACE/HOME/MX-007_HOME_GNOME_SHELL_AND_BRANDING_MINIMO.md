# MX-007 · Shell y branding minimo de `Home` sobre `GNOME`

Fecha: 2026-10-04
Estado: Activo para aterrizaje operativo
Alcance: `W4 OS Home V1`
Dependencias cerradas: `MX-006`, `ADR-006`, `MX-003`, `MX-004`, `MX-005`

## Objetivo

Traducir la decision de `ADR-006` a una ruta base visible y mantenible para `Home`, usando `GNOME + GDM` como shell oficial sin abrir todavia una implementacion UX profunda ni un fork del shell upstream.

Este frente debe dejar una base clara para `MX-008` y `MX-009`, no una experiencia final completa.

## Resultado esperado del ciclo

Al cerrar `MX-007`, el proyecto debe contar con una definicion operativa suficiente para implementar una primera composicion `Home` sobre `GNOME` con branding minimo reversible.

Eso implica:

1. una ruta base `GNOME + GDM` explicitamente delimitada;
2. una frontera clara entre branding comun, branding de `Home` y variantes futuras;
3. un paquete o contrato objetivo para `w4-desktop-gnome-meta`;
4. una politica de defaults visible pero reversible;
5. dependencias y riesgos de `MX-008` y `MX-009` reescritos sobre esta base.

## Entregables exactos

### E-1 · Contrato de composicion `GNOME + GDM`

Debe quedar definido:

- si `GDM` es obligatorio en `Home V1`;
- si `gnome-shell` y `gnome-session` forman parte del baseline visible;
- que paquetes son base obligatoria;
- que paquetes quedan fuera por ahora;
- que parte de la composicion vive en `w4-desktop-meta`;
- que parte vive en `w4-desktop-gnome-meta`.

Salida minima:

- lista de paquetes base;
- lista de paquetes excluidos en V1;
- razon corta de cada exclusion relevante.

### E-2 · Politica minima de branding reversible

Debe quedar definido:

- wallpaper por defecto;
- tema y modo visual por defecto;
- iconos o branding visual si aplica;
- nombre visible de la sesion o display manager si aplica;
- defaults de shell que pueden revertirse sin romper la sesion.

Regla:

ningun cambio de branding puede requerir fork profundo de `gnome-shell` para entrar en V1.

### E-3 · Frontera entre branding comun y branding por variante

Debe quedar separado:

- branding comun W4 reutilizable;
- branding especifico de `Home`;
- overrides futuros de variantes graficas controladas.

Esto debe impedir que `Home` contamine de forma accidental el futuro frente `Business` o las variantes no predeterminadas.

### E-4 · Mapa de defaults funcionales

Debe quedar definido al menos:

- login manager objetivo;
- sesion grafica por defecto;
- comportamiento de fondo de pantalla inicial;
- defaults minimos de panel, dock o quick settings solo si son reversibles;
- criterio de que no toca todavia `MX-007`.

### E-5 · Bloqueadores y aperturas para `MX-008` y `MX-009`

Debe quedar explicitado:

- que necesita `MX-008` del shell base;
- que necesita `MX-009` del shell base;
- que elementos siguen fuera del alcance de `MX-007`;
- que riesgos tecnicos o de QA se heredan si se retrasa este frente.

## Criterios de cierre

`MX-007` puede considerarse cerrado cuando existan estas cinco condiciones al mismo tiempo:

1. `GNOME + GDM` quede fijado como ruta visible base de `Home`;
2. `w4-desktop-gnome-meta` tenga un contrato minimo documentado;
3. el branding minimo reversible quede definido sin fork profundo;
4. `MX-008` y `MX-009` queden actualizados para depender de esta base;
5. la trazabilidad ejecutiva del repo refleje este cierre sin ambiguedad.

## Fuera de alcance en este ciclo

Queda fuera de `MX-007`:

- implementar ya `Control Center`;
- resolver onboarding completo;
- cerrar catalogo final de aplicaciones `Home`;
- abrir soporte fuerte equivalente para `KDE Plasma`, `XFCE` o `Cinnamon`;
- redisenar componentes internos de `gnome-shell`;
- comprometer una capa de extensiones propias no probadas.

## Secuencia de ejecucion recomendada

### Paso 1 · Delimitar composicion base

- fijar `GNOME + GDM`;
- redactar el contrato inicial de `w4-desktop-gnome-meta`;
- separar base comun vs base especifica de `Home`.

### Paso 2 · Delimitar branding reversible

- elegir wallpaper base;
- fijar politica de tema, iconos y assets;
- anotar que defaults son reversibles y cuales no entran en V1.

### Paso 3 · Delimitar defaults funcionales

- documentar sesion por defecto;
- documentar display manager;
- decidir que tocar y que no tocar en shell/panel/dock.

### Paso 4 · Reescribir dependencias aguas abajo

- actualizar `MX-008`;
- actualizar `MX-009`;
- dejar explicitado que `Business` sigue otra ruta sobre `KDE Plasma`.

### Paso 5 · Abrir implementacion

Solo despues de cerrar los pasos anteriores conviene pasar a cambios tecnicos reales en manifests, paquetes, overlays o artefactos.

## Riesgos a vigilar

### R-1 · Fork innecesario de shell

Si el branding exige tocar internals de `gnome-shell`, el costo de mantenimiento sube demasiado para V1.

### R-2 · Mezcla de branding comun y branding de `Home`

Si no se separan capas, `Business` y variantes futuras heredan decisiones visibles que no les corresponden.

### R-3 · Abrir `MX-008` sin shell base

`Control Center` corre el riesgo de diseñarse contra una UI todavia no fijada.

### R-4 · Abrir `MX-009` sin defaults visibles

`Home utilizable` se volveria una mezcla de onboarding, apps y branding sin una base comun estable.

## Evidencia documental minima esperada

Este frente debe reflejarse al menos en:

- `README.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_TABLE.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_MATRIX.md`
- `Docs/DEVELOPMENT/EXECUTIVE_IMPLEMENTATION_PLAN.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_VERSIONS.md`

## Siguiente paso inmediato

Con este marco, el siguiente corte tecnico correcto es documentar el contrato de `w4-desktop-gnome-meta` y la politica minima de branding reversible antes de tocar implementacion UX o abrir `MX-008`.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/ADR-006_HOME_GRAPHIC_CATALOG_AND_SELECTOR.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-006_HOME_V1_DESKTOP_MATRIX.md`
- `Docs/DEVELOPMENT/EXECUTIVE_IMPLEMENTATION_PLAN.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_MATRIX.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_TABLE.md`
