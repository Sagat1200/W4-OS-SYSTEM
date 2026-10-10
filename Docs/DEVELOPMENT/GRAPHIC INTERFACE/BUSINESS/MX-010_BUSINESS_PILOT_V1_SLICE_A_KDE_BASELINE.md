# MX-010 · Business piloto V1 · Slice A · KDE baseline materializable

Estado: implementado en pipeline; pendiente de validacion final en `live-output`
Fecha: 2026-10-09
Alcance: `W4 OS Business V1`
Dependencias: `MX-010_BUSINESS_PILOT_V1_CONTRACT.md`, `ADR-013_BUSINESS_GRAPHIC_CATALOG_AND_SELECTOR.md`, `MX-004`, `MX-005`

## Objetivo

Abrir el primer slice tecnico real de `Business` materializando una base visible sobre `KDE Plasma + SDDM`, sin reabrir la arquitectura general del piloto ni vender todavia enrollment, politica central o gestion remota completa.

Este slice existe para convertir la decision ya aprobada de `KDE Plasma` en una ruta visible verificable sobre los artefactos del pipeline.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/BUSINESS/MX-010_BUSINESS_PILOT_V1_CONTRACT.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/BUSINESS/ADR-013_BUSINESS_GRAPHIC_CATALOG_AND_SELECTOR.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/BUSINESS/MX-010_BUSINESS_V1_DESKTOP_MATRIX.md`
- `Docs/W4-OS/012_W4_OS_BUSINESS_EDITION.md`
- `Docs/W4-OS/211_W4_OS_BUSINESS_ARCHITECTURE.md`
- `Docs/W4-OS/409_W4_OS_V1_BUSINESS_SCOPE.md`

## Regla de alcance

Este slice si abre:

- `KDE Plasma` como shell visible base de `Business`;
- `SDDM` como login manager preferido de la ruta KDE;
- una frontera tecnica explicita para `w4-desktop-kde-meta`;
- defaults y branding minimos compatibles con una ruta empresarial controlada;
- y validacion reproducible sobre artefactos del pipeline.

Este slice no abre todavia:

- enrollment materializado;
- politica central efectiva;
- inventario remoto;
- soporte remoto;
- ni variantes `GNOME`, `XFCE` o `Cinnamon` como capacidades equivalentes.

## Contrato tecnico

El primer cierre verificable de este slice debe demostrar:

1. que `Business` ya cuenta con una composicion visible propia sobre `KDE Plasma + SDDM`;
2. que la frontera entre `w4-desktop-meta` y `w4-desktop-kde-meta` queda documentada y lista para manifests;
3. que el perfil `Business` deja de depender solo de la ADR para su shell visible;
4. que existe una siguiente accion tecnica concreta sobre `build-input`, `rootfs` y `live-output`.

## Entregables obligatorios

1. documento operativo del `Slice A` con objetivo, alcance y criterios de cierre;
2. delimitacion de la ruta `KDE Plasma + SDDM` como baseline visible de `Business`;
3. lista minima de piezas que deben pasar despues a manifests, rootfs y live;
4. trazabilidad ejecutiva que trate este slice como apertura tecnica real y no como nota lateral.

## Baseline visible esperado

La primera composicion visible de `Business` debe orientarse a:

- shell: `KDE Plasma`
- login manager: `SDDM`
- base comun: `w4-desktop-meta`
- capa especifica: `w4-desktop-kde-meta`

El slice no obliga todavia a cerrar el listado final de paquetes, pero si obliga a fijar la direccion tecnica de esa composicion para que los siguientes cambios de manifiestos no salgan a ciegas.

## Piezas minimas a materializar despues de esta apertura

El siguiente paso tecnico de implementacion debera aterrizar como minimo:

1. declaracion del baseline KDE en el perfil `Business`;
2. propagacion canonica a `build-input`;
3. propagacion a `rootfs`;
4. evidencia verificable posterior en `live-output`;
5. una validacion que distinga base visible real de piloto empresarial completo.

## Cierre tecnico de esta pasada

Este slice ya deja un primer aterrizaje tecnico verificable en el arbol real:

1. `manifests/w4-os-business.profile.json` ya declara `w4-desktop-kde-meta` y un baseline visible de `Business` con `plasma-desktop`, `plasma-workspace`, `plasma-nm`, `sddm`, `dolphin`, `konsole`, `systemsettings` y `xdg-desktop-portal-kde`;
2. `config/editions/business/desktop-defaults.json` ya fija `KDE Plasma + SDDM` como ruta visible de `Business` con branding/defaults propios;
3. `scripts/generate_system_overlay.php` ya deja de asumir que cualquier `desktop-defaults` implica `GNOME`, genera wallpaper especifico de `Business` y evita arrastrar onboarding de `Home` al overlay de `Business`;
4. `build/inputs/w4-os-business.build-input.json`, `build/rootfs/w4-os-business/rootfs-manifest.json`, `build/overlays/w4-os-business/overlay-manifest.json` y `build/live/w4-os-business/live-manifest.json` ya fueron regenerados sobre esa ruta base;
5. `tests/ManifestToolkitTest.php`, `tests/SystemOverlayGenerationTest.php`, `tests/RootfsBundleGenerationTest.php` y `tests/LiveBundleGenerationTest.php` ya congelan este baseline en verde.

Con ello, `Slice A` deja de ser solo contrato documental y pasa a tener una base implementada sobre manifests, overlay y bundles del pipeline, aunque todavia no cierre el gate final de `live-output` materializado.

## Criterio de cierre del slice

Este slice solo puede tratarse como materializado cuando:

1. el perfil `Business` refleje de forma canonica la ruta `KDE Plasma + SDDM`;
2. el pipeline propague esa composicion hasta artefactos verificables;
3. la trazabilidad deje claro que esto cierra solo la base visible de `Business`, no el piloto empresarial completo.

En esta pasada ya quedan satisfechos los dos primeros peldaños sobre `manifests`, `build-input`, `rootfs`, `overlay` y `live`, pero sigue pendiente el cierre sobre `live-output` final materializado.

## Riesgo activo

El principal riesgo de este slice es intentar mezclar la base visible con todo el backlog empresarial:

- enrollment,
- politica,
- inventario,
- soporte remoto,
- portal,
- y variantes graficas.

Si eso ocurre, `MX-010` volvera a quedar bloqueado por sobrealcance.

## Resultado esperado del slice

Con este `Slice A`, `Business` debe quedar listo para pasar de politica grafica aprobada a una ruta visible real sobre artefactos del pipeline, manteniendo separado:

- baseline KDE materializable,
- piloto empresarial posterior,
- y backlog remoto aun no implementado.
