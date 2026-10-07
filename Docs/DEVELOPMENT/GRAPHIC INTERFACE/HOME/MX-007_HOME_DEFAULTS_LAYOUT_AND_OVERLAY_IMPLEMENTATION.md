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

## Endurecimiento operativo posterior

Una vez materializado ese siguiente subcorte de packaging, el bloqueo ya no quedo en defaults, `dconf` ni branding, sino en la ejecucion real del bootstrap pesado dentro de WSL.

Para reducir esa fragilidad operativa sin reabrir manifests ni overlays, `generate_rootfs_bundle.php` ya genera un `build-rootfs.sh` con dos rutas explicitas:

- `bootstrap_with_mmdebstrap()` como camino preferente cuando el host lo soporta;
- `bootstrap_with_debootstrap()` como fallback operativo automatico si `mmdebstrap` falla.

La regla nueva es concreta:

- si `mmdebstrap` completa el bootstrap, el flujo sigue igual;
- si `mmdebstrap` devuelve error, el script limpia el rootfs parcial y reintenta por `debootstrap`;
- la decision queda trazada en el propio bundle materializado de `rootfs`, sin depender de pasos manuales fuera del repo.

Con esto, el subcorte de `MX-007` mantiene separado el problema real:

- manifests y baseline `GNOME + GDM` ya aprobados;
- defaults y branding ya materializados;
- validacion final ya cerrada sobre una ruta WSL limpia.

## Cierre operativo materializado

La validacion fuerte pendiente ya quedo cerrada con una corrida real sobre:

- `/var/tmp/w4-os-system/w4-os-home-fallback-validation/assembled-rootfs`

Secuencia confirmada:

1. `run_rootfs_in_wsl.php` cerro el `rootfs` limpio de `Home` con el fallback operativo `mmdebstrap -> debootstrap` disponible en el bundle generado.
2. `apply-overlay.sh` aplico sobre ese mismo arbol `desktop-defaults.json`, `dconf` de usuario/login y el wallpaper W4.
3. `run_live_bundle_in_wsl.php` recompuso `build/live-output/w4-os-home/image-root/` con `W4_GENERATED_AT="2026-10-07T09:39:20Z"`.

Evidencia de cierre:

- `build/live-output/w4-os-home/image-root/live/filesystem.manifest` ya contiene `gdm3`, `gnome-control-center`, `gnome-session`, `gnome-shell`, `gnome-software`, `gnome-terminal`, `nautilus` y `xdg-desktop-portal-gnome`.
- `build/live-output/w4-os-home/image-root/system-overlay/etc/w4/desktop-defaults.json` sigue presente en el artefacto final.
- `build/live-output/w4-os-home/image-root/system-overlay/etc/dconf/db/local.d/00-w4-home` y `build/live-output/w4-os-home/image-root/system-overlay/etc/dconf/db/gdm.d/00-w4-login` sobreviven la composicion live.
- `build/live-output/w4-os-home/image-root/system-overlay/usr/share/w4/branding/home/wallpapers/w4-home-default.svg` confirma que el branding reversible tambien viaja hasta el artefacto.

Con esta evidencia, `MX-007` puede tratarse como cerrado para `Home`: la ruta `GNOME + GDM` ya no es solo contrato documental ni packaging declarativo, sino una base real validada de extremo a extremo.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_REVERSIBLE_BRANDING_POLICY.md`
- `Docs/W4-OS/100_W4_OS_DESKTOP_CONFIGURATION.md`
- `Docs/W4-OS/379_W4_OS_SYSTEM_DEFAULTS.md`
- `Docs/W4-OS/380_W4_OS_CONFIGURATION_LAYERING.md`
