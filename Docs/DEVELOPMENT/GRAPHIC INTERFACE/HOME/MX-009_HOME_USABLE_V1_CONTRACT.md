# MX-009 · Home utilizable V1 · Contrato operativo inicial

Estado: abierto
Fecha: 2026-10-08
Alcance: `W4 OS Home V1`
Dependencias: `MX-003`, `MX-004`, `MX-005`, `MX-007`, `MX-008`

## Objetivo

Traducir la base ya validada de `Home` a un primer recorrido visible y utilizable para tareas domesticas basicas, sin abrir todavia una capa de onboarding interactivo grande ni un catalogo de aplicaciones fuera de control.

Este frente ya no debe discutir:

- el escritorio base de `Home`,
- la ruta `GNOME + GDM`,
- la politica minima de branding,
- ni la base de `Settings` ya fijada por `MX-008`.

Debe partir de ese baseline y convertirlo en una experiencia minima verificable.

## Referencias de producto

La bajada de `MX-009` debe mantenerse coherente con:

- `Docs/W4-OS/011_W4_OS_HOME_EDITION.md`
- `Docs/W4-OS/131_W4_OS_DEFAULT_APPLICATIONS.md`
- `Docs/W4-OS/231_W4_OS_HOME_ARCHITECTURE.md`
- `Docs/W4-OS/232_W4_OS_HOME_ONBOARDING.md`
- `Docs/W4-OS/237_W4_OS_HOME_APPLICATION_PROFILE.md`
- `Docs/W4-OS/408_W4_OS_V1_HOME_SCOPE.md`

## Alcance operativo V1

Para este primer aterrizaje, `Home utilizable` se divide en cuatro bloques:

1. perfil minimo de aplicaciones para tareas basicas;
2. rutas visibles de primera sesion ya presentes en shell y `Settings`;
3. recorrido inicial local sin dependencia cloud obligatoria;
4. accesibilidad y onboarding como slices posteriores, no como requisito para abrir el frente.

## Lo que entra ahora

### E-1 · Perfil minimo de aplicaciones visible

La primera capa verificable de `MX-009` debe tratar como baseline utilizable de `Home`:

- navegador: `firefox-esr`
- documentos: `libreoffice`
- PDF: `evince`
- multimedia basica: `vlc`
- archivos: `nautilus`
- ajustes: `W4 Settings`
- updates visibles: `W4 Settings · Actualizaciones`

Este corte no declara aun un stack final completo de:

- escaneo,
- respaldo guiado,
- onboarding interactivo.

Eso queda para slices posteriores cuando exista evidencia mas fuerte de packaging y de flujo visible.

### E-2 · Rutas visibles ya materializadas

`Home utilizable` debe apoyarse en rutas que ya existen en el artefacto final:

- favoritos del shell en `desktop-defaults.json` y `dconf`,
- launchers `.desktop` de `Control Center`,
- baseline GNOME ya validado en `filesystem.manifest`.

No se abre una UI nueva para esta fase.

### E-3 · Regla de minima sorpresa

Si una tarea domestica basica ya puede resolverse con:

- una aplicacion upstream estable,
- un favorito del shell,
- o un launcher `GNOME-augmented` ya validado,

esa ruta se reutiliza antes de crear otra superficie W4.

### E-4 · Handoff de onboarding y accesibilidad

`MX-009` no absorbe todo `Home V1` de una sola vez.

El frente queda protegido con dos reglas:

- onboarding completo no entra en este primer slice tecnico;
- accesibilidad solo entra cuando haya un contrato tecnico verificable y no como promesa documental suelta.

## Primer slice tecnico

### Slice A · Baseline utilizable materializado

El primer slice tecnico de `MX-009` debe validar sobre `build/live-output/w4-os-home/`:

1. que el baseline de aplicaciones minimo siga presente en `filesystem.manifest`;
2. que los favoritos del shell sigan exponiendo las rutas visibles de `Home`;
3. que `W4 Settings` y `Updates` sigan aterrizados como entrypoints reales;
4. que `evince` y `vlc` pasen de decision documental a baseline materializado real;
5. que la ruta materializada no dependa de inspeccion manual archivo por archivo.

### Slice B · UI ligera de onboarding local

Una vez cerrado el readiness local de primer inicio, el siguiente slice visible de `MX-009` debe abrir una UI ligera de onboarding sobre la base ya materializada, sin convertirla en wizard grande ni en superficie paralela a `GNOME Settings`.

Este slice debe:

1. mostrar una bienvenida breve y accionable;
2. reutilizar entrypoints reales ya materializados de `Home`;
3. limitar el recorrido visible a pocos pasos;
4. permitir omitir pasos opcionales;
5. cerrar sin reaparecer sin motivo.

Su contrato operativo detallado queda en:

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-009_HOME_ONBOARDING_LIGHT_UI_SLICE_B.md`

## Entregables obligatorios de este primer slice

1. un validador canonico de `Home utilizable` sobre `live-output`;
2. una salida `json` reutilizable y un resumen `text`;
3. cobertura PHPUnit sobre workspace sintetico;
4. trazabilidad ejecutiva alineada para abrir `MX-009` como nuevo frente activo.

## Criterios de aceptacion de apertura

`MX-009` puede considerarse correctamente abierto cuando:

1. `MX-008` quede tratado como baseline suficiente de `Home V1`;
2. exista un contrato operativo propio para `Home utilizable`;
3. el primer slice tecnico ya cuente con evidencia ejecutable;
4. el roadmap deje de presentar `MX-009` como placeholder vacio.

## Riesgos a contener

### R-1 · Sobrealcance funcional

Si el frente intenta cerrar de una vez:

- onboarding completo,
- accesibilidad completa,
- apps extra,
- backups guiados,
- catalogo de extras,

`MX-009` perdera foco y volvera a bloquearse por alcance.

### R-2 · Reabrir capas ya cerradas

`MX-009` no debe reabrir:

- el escritorio oficial,
- branding base,
- ni la arquitectura de `Settings`.

### R-3 · Prometer Home final sin evidencia

La regla para esta fase es simple:
solo se eleva a baseline de `Home utilizable` lo que pueda probarse sobre artefactos reales o validadores reproducibles.

## Resultado esperado del frente

Al cerrar su primer slice, `MX-009` debe dejar a `Home` con:

- una base minima de aplicaciones visible y verificable,
- un recorrido inicial coherente sobre shell + `Settings`,
- y una ruta clara para slices posteriores de onboarding y accesibilidad.
