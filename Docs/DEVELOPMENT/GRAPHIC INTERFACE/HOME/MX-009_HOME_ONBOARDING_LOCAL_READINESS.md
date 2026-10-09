# MX-009 · Home onboarding local readiness

Estado: abierto
Fecha: 2026-10-08
Alcance: `W4 OS Home V1`
Dependencias: `MX-007`, `MX-008`, `MX-009_HOME_USABLE_V1_CONTRACT`

## Objetivo

Abrir el siguiente slice visible de `MX-009` sin sobreactuar una UX grande de onboarding: primero validar que el artefacto materializado de `Home` ya trae la base local de primera sesion (`firstboot`, `live-prep`, flags y estado runtime`) necesaria para soportar onboarding posterior.

## Referencias

- `Docs/W4-OS/232_W4_OS_HOME_ONBOARDING.md`
- `Docs/W4-OS/408_W4_OS_V1_HOME_SCOPE.md`
- `Docs/W4-OS/140_W4_OS_FILE_MANAGER.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-009_HOME_USABLE_V1_CONTRACT.md`

## Regla de alcance

Este slice no abre todavia:

- wizard visual completo;
- telemetria opt-in interactiva;
- onboarding de cuenta local guiado;
- respaldo externo guiado;
- accesibilidad completa.

Primero se congela el sustrato tecnico local que hace viable ese recorrido.

## Contrato tecnico

El `live-output` materializado de `Home` debe demostrar:

1. `W4_DEFAULT_TARGET=graphical.target` en `metadata/live-summary.env`;
2. `home-onboarding` y `local-backup-ready` en `image-root/system-overlay/etc/w4/profile.env`;
3. presencia de `w4-firstboot.service` y `w4-live-prep.service`;
4. presencia de `w4-firstboot.sh` y `w4-live-prep.sh`;
5. activacion de ambos servicios en `${default_target}.wants`;
6. escritura runtime prevista en `/etc/w4/firstboot-state.env` y `/etc/w4/live-state.env`;
7. ausencia de `var/lib/w4/firstboot-complete` ya materializado dentro del artefacto.

## Entregables obligatorios

1. un validador canonico sobre `build/live-output/w4-os-home/`;
2. salida `json` y `text`;
3. cobertura PHPUnit sobre workspace sintetico;
4. trazabilidad que deje visible si el artefacto real ya esta alineado o si sigue pendiente de rematerializacion.

## Criterio de cierre

Este subcorte solo puede tratarse como materializado cuando:

1. `scripts/validate_home_onboarding_live_output.php --profile w4-os-home --format text` cierre en verde sobre `build/live-output/w4-os-home/`;
2. la activacion real de `firstboot` y `live-prep` respete el `default target` del perfil dentro del `live-output` final;
3. la trazabilidad ejecutiva refleje ese cierre sin prometer aun una UI de onboarding completa.

## Riesgo activo

El principal riesgo de este slice es leer como "onboarding implementado" algo que solo representa readiness local. El validador y la trazabilidad deben dejar explicito que la UI posterior sigue diferida.
