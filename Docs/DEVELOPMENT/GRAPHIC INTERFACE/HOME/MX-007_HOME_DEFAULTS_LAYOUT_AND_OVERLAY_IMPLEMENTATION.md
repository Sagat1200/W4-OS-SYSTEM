# MX-007 · Layout tecnico de recursos y defaults para `Home`

Fecha: 2026-10-04
Estado: Activo para implementacion
Alcance: `W4 OS Home V1` sobre `GNOME + GDM`
Dependencias: `MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`, `MX-007_HOME_REVERSIBLE_BRANDING_POLICY.md`

## Proposito

Traducir el contrato de `GNOME + GDM` y la politica de branding reversible a una implementacion tecnica concreta dentro del pipeline actual del repo.

La meta de este corte no es cerrar toda la UX de `Home`, sino fijar una ruta real y reutilizable para:

- defaults de escritorio;
- branding visible reversible;
- assets W4;
- y su transporte desde configuracion de edicion hasta overlay, live e instalacion.

## Decision tecnica

La primera materializacion real de `MX-007` se apoya en el `system-overlay`, porque ya es la capa del pipeline encargada de inyectar identidad, defaults y servicios dentro del sistema final.

La ruta elegida es:

1. `config/editions/home/desktop-defaults.json` como contrato declarativo de defaults desktop;
2. `scripts/generate_system_overlay.php` como traductor de ese contrato a archivos reales;
3. payload de `dconf` y recursos W4 dentro de `build/overlays/<profile>/files/`;
4. metadata en `overlay-manifest.json` para dejar trazada la procedencia.

## Layout objetivo

### 1. Configuracion declarativa por edicion

Archivo fuente:

- `config/editions/home/desktop-defaults.json`

Responsabilidad:

- declarar display manager, sesion, shell, tema, iconos, wallpaper, favoritos y mecanismo de defaults;
- separar branding comun de branding especifico de `Home`;
- dejar fuera cualquier preferencia profunda no aprobada en `MX-007`.

### 2. Metadata runtime dentro del overlay

Archivos de salida:

- `files/etc/w4/desktop-defaults.json`
- `files/etc/w4/profile.env`

Responsabilidad:

- exponer los defaults efectivos de `Home` al sistema desplegado;
- dejar trazada la relacion entre politica declarativa y archivos realmente aplicados.

### 3. Defaults de `dconf`

Archivos de salida:

- `files/etc/dconf/profile/user`
- `files/etc/dconf/db/local.d/00-w4-home`
- `files/etc/dconf/db/gdm.d/00-w4-login`

Responsabilidad:

- aplicar defaults de sesion y login por mecanismos upstream;
- limitar el alcance a perfil nuevo o ausencia de valor efectivo;
- evitar scripts o overrides frágiles de `GNOME` o `GDM`.

### 4. Recursos visuales W4

Archivos de salida iniciales:

- `files/usr/share/w4/branding/home/wallpapers/w4-home-default.svg`

Responsabilidad:

- proveer un wallpaper real empaquetado por el overlay;
- dejar una ruta estable para assets W4 de escritorio;
- permitir fallback claro si el recurso no existe o cambia en futuras iteraciones.

## Mecanismo de aplicacion

La implementacion de este corte sigue esta regla:

- el JSON declarativo vive en `config/editions/home/desktop-defaults.json`;
- el generador de overlay lo traduce a archivos reales;
- `firstboot` y `live-prep` recompilan `dconf` cuando la herramienta exista;
- los defaults aplican por capas de sistema y no reescriben preferencias personales en cada arranque.

## Campos minimos del contrato declarativo

El archivo `desktop-defaults.json` debe cubrir, como minimo:

- `desktop.shell`
- `desktop.session`
- `desktop.display_manager`
- `theme.gtk`
- `theme.color_scheme`
- `theme.icon`
- `wallpaper.uri`
- `favorites`
- `branding.scope`
- `application.method`

## Materializacion inicial aprobada

En este corte se aprueba una implementacion minima y conservadora:

- `GNOME` como shell y sesion base;
- `GDM` como display manager;
- wallpaper W4 propio;
- tema e iconos declarados como defaults reversibles;
- favoritos minimos del shell;
- defaults via `dconf`;
- recompilacion de `dconf` desde scripts existentes del overlay cuando proceda.

## Fuera de alcance

Queda fuera de este corte:

- onboarding de `Home`;
- extensiones GNOME propias obligatorias;
- shell fuertemente customizado;
- catalogo amplio de assets;
- temas profundos por toolkit;
- multiples layouts de login o shell por variante.

## Criterios de cierre de este subcorte

Este bloque puede darse por cerrado cuando existan estas condiciones:

1. `Home` tenga un archivo declarativo real de defaults desktop;
2. `generate_system_overlay.php` lo traduzca a archivos reales;
3. el overlay publique metadata suficiente para trazabilidad;
4. existan pruebas que validen archivos y contratos generados;
5. la documentacion ejecutiva del repo refleje la nueva ruta.

## Siguiente paso despues de este corte

Una vez cerrado este subcorte, el siguiente avance razonable es conectar esta misma capa con manifests o metapaquetes desktop cuando el repo ya tenga la materializacion real de `w4-desktop-gnome-meta`.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_REVERSIBLE_BRANDING_POLICY.md`
- `Docs/W4-OS/100_W4_OS_DESKTOP_CONFIGURATION.md`
- `Docs/W4-OS/379_W4_OS_SYSTEM_DEFAULTS.md`
- `Docs/W4-OS/380_W4_OS_CONFIGURATION_LAYERING.md`
