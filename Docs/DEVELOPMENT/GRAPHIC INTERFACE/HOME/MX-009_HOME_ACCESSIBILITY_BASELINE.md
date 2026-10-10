# MX-009 · Home accessibility baseline

Estado: materializado
Fecha: 2026-10-09
Alcance: `W4 OS Home V1`
Dependencias: `MX-007`, `MX-008`, `MX-009_HOME_USABLE_V1_CONTRACT`

## Objetivo

Materializar el siguiente slice tecnico de `MX-009` para accesibilidad sin sobreactuar una calificacion UX completa: primero validar sobre `live-output` la base minima que hace viable lector de pantalla, ayudas visuales y una ruta visible a `Settings`.

## Referencias

- `Docs/W4-OS/104_W4_OS_ACCESSIBILITY_SYSTEM.md`
- `Docs/W4-OS/408_W4_OS_V1_HOME_SCOPE.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-009_HOME_USABLE_V1_CONTRACT.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-009_HOME_ONBOARDING_LIGHT_UI_SLICE_B.md`

## Regla de alcance

Este slice si abre:

- baseline de paquetes de accesibilidad para GNOME `Home`;
- ruta visible a `W4 Settings` como entrypoint de ajustes;
- evidencia materializable sobre `build/live-output/w4-os-home/`.

Este slice no abre todavia:

- validacion completa de flujo solo con teclado;
- validacion completa con lector de pantalla desde login hasta recovery;
- persistencia completa de preferencias de accesibilidad;
- accesibilidad de instalador, login manager y recovery como matriz final.

## Contrato tecnico

El `live-output` materializado de `Home` debe demostrar:

1. `W4_DEFAULT_TARGET=graphical.target` en `metadata/live-summary.env`;
2. `accessibility-baseline` en `image-root/system-overlay/etc/w4/profile.env`;
3. presencia en `filesystem.manifest` de:
   - `orca`
   - `speech-dispatcher`
   - `at-spi2-core`
   - `libatk-adaptor`
   - `gnome-accessibility-themes`
   - `gsettings-desktop-schemas`
   - `gnome-control-center`
4. una ruta visible a `W4 Settings` en `desktop-defaults.json`, `dconf` y `gnome-launchers.json`;
5. salida canonica `json` y `text` que deje claros paquetes, capacidades y pasos aun diferidos.

## Entregables obligatorios

1. un validador canonico sobre `build/live-output/w4-os-home/`;
2. cobertura PHPUnit sobre workspace sintetico;
3. promocion del baseline de accesibilidad al perfil `Home`;
4. trazabilidad ejecutiva que distinga baseline accesible de accesibilidad completa.

## Criterio de cierre

Este subcorte solo puede tratarse como materializado cuando:

1. `scripts/validate_home_accessibility_live_output.php --profile w4-os-home --format text` cierre en verde sobre `build/live-output/w4-os-home/`;
2. el `filesystem.manifest` materializado ya incluya el baseline minimo de accesibilidad;
3. la trazabilidad deje explicito que la matriz completa de accesibilidad sigue diferida.

## Cierre materializado

Este subcorte ya queda cerrado sobre `build/live-output/w4-os-home/` mediante:

- `src/Home/HomeAccessibilityLiveOutputToolkit.php`
- `scripts/validate_home_accessibility_live_output.php`
- `tests/HomeAccessibilityLiveOutputCliTest.php`

La validacion real ya confirma en verde:

1. `W4_DEFAULT_TARGET=graphical.target`;
2. `accessibility-baseline` activo en `profile.env`;
3. presencia materializada de `orca`, `speech-dispatcher`, `at-spi2-core`, `libatk-adaptor`, `gnome-accessibility-themes`, `gsettings-desktop-schemas` y `gnome-control-center`;
4. ruta visible a `W4 Settings` a traves de `desktop-defaults.json`, `dconf` y `gnome-launchers.json`;
5. capacidades `screen-reader`, `visual-assist` y `settings-route` en estado `available`.

## Riesgo activo

El principal riesgo de este slice es leer como "accesibilidad resuelta" algo que solo representa baseline materializable. El validador y la trazabilidad deben dejar visible que keyboard-only end-to-end, login y recovery siguen como QA posterior.
