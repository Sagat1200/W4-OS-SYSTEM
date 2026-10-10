# MX-010 · Business piloto V1 · Slice B · Enrollment readiness local

Estado: materializado sobre `live-output`; recomposicion limpia completa pendiente por drift externo del mirror Debian
Fecha: 2026-10-10
Alcance: `W4 OS Business V1`
Dependencias: `MX-010_BUSINESS_PILOT_V1_CONTRACT.md`, `MX-010_BUSINESS_PILOT_V1_SLICE_A_KDE_BASELINE.md`, `222_W4_OS_DEVICE_ENROLLMENT.md`, `213_W4_OS_CENTRAL_POLICY_SYSTEM.md`

## Objetivo

Abrir el siguiente slice tecnico de `Business` sin vender todavia enrollment real ni backend empresarial completo.

Este slice existe para congelar la frontera local del piloto:

1. donde vive la identidad local del equipo;
2. donde debe persistir la ultima politica valida;
3. como viaja la politica de edicion desde bundle a runtime de instalacion;
4. y como distinguir readiness local de una inscripcion ya efectivamente realizada.

## Regla de alcance

Este slice si abre:

- contrato local de identidad de dispositivo;
- contrato local de cache de politica;
- contrato local de estado de inventario;
- y un puente verificable entre `edition-policy.json`, bundle y runtime de instalacion.

Este slice no abre todavia:

- backend de tokens;
- generacion real de clave de dispositivo;
- emision real de credencial;
- descarga real de politica desde servidor;
- ni sincronizacion remota de inventario.

## Aterrizaje tecnico de esta pasada

Este slice ya deja un primer aterrizaje tecnico real en el arbol:

1. `config/editions/business/enrollment-readiness.json` ya fija el contrato fuente de readiness local para `Business`;
2. `scripts/generate_system_overlay.php` ya materializa ese contrato como `files/etc/w4/business-enrollment-readiness.json` dentro de `build/overlays/w4-os-business/`;
3. `build/install/w4-os-business/edition-policy.json`, `build/install/w4-os-business/installation-plan.json` y `build/install/w4-os-business/runtime/install.env` ya vuelven a publicar la politica efectiva, el hostname tipado y el puente runtime necesario para este slice;
4. `src/Business/BusinessEnrollmentReadinessToolkit.php`, expuesto por `scripts/validate_business_enrollment_readiness.php`, ya valida en verde esa readiness local sobre overlay e instalacion;
5. `tests/BusinessEnrollmentReadinessCliTest.php` y `tests/SystemOverlayGenerationTest.php` ya congelan el contrato en QA.

## Cierre materializado

Este mismo slice ya queda ahora tambien proyectado sobre `build/live-output/w4-os-business/` mediante:

1. la regeneracion de `build/live/w4-os-business/` con `business-enrollment-readiness.json` ya presente en `files/system-overlay/etc/w4/`;
2. `src/Business/BusinessEnrollmentReadinessLiveOutputToolkit.php`, expuesto por `scripts/validate_business_enrollment_readiness_live_output.php`, que ya valida en verde el contrato materializado en `image-root/system-overlay/etc/w4/business-enrollment-readiness.json`;
3. `tests/BusinessEnrollmentReadinessLiveOutputCliTest.php`, que ya congela ese gate de `live-output`;
4. validacion real en verde sobre el arbol materializado confirmando `w4-business-live`, `profile.env`, el contrato runtime de readiness, la cache local de politica y el anclaje local de inventario.

La recomposicion limpia completa del `live-output` se intento sobre WSL, pero `apt-get update` fallo por `Hash Sum mismatch` del mirror Debian al intentar reinstalar `live-boot/live-config` en la copia de trabajo. Ese bloqueo queda fuera del contrato `Business`; por eso esta pasada deja explicitamente trazado que el cierre funcional del slice queda validado sobre el `system-overlay` materializado del `live-output`, mientras el rebuild limpio completo del artefacto sigue pendiente de rerun cuando el mirror deje de derivar.

## Contrato local de readiness

La pasada deja fijado como readiness local verificable:

1. `initial_state=unenrolled-ready`;
2. identidad local reservada bajo `/var/lib/w4/device-identity/`;
3. cache de politica bajo `/var/lib/w4/policy/last-known-policy.json`;
4. politica efectiva visible bajo `/var/lib/w4/policy/effective-policy.json`;
5. estado de inventario bajo `/var/lib/w4/inventory/device-state.json`;
6. `enterprise_agent.installed=false` y `enterprise_agent.enrolled=false` como frontera explicita del piloto no inscrito;
7. `W4_EDITION_POLICY_FILE`, `W4_DEFAULT_TARGET`, `W4_HOSTNAME_PREFIX`, `W4_SSH_ENABLED`, `W4_FIREWALL_*` publicados en `runtime/install.env`.

## Criterio de cierre de este slice

Este slice puede tratarse como correctamente abierto cuando:

1. el contrato fuente de readiness exista y sea estable;
2. el overlay publique una copia runtime de ese contrato;
3. el bundle de instalacion conserve `edition-policy.json` de forma visible;
4. el runtime de instalacion exponga las variables necesarias para no perder el puente de politica;
5. la validacion deje claro que el equipo sigue en estado `unenrolled-ready` y no inscrito.

En esta pasada ya quedan satisfechos esos cinco peldaños sobre `config`, `build/overlays`, `build/install` y `build/live-output`.

## Riesgo activo

El riesgo principal de este slice es confundir readiness local con enrollment real.

Mientras no existan:

- backend de tokens,
- clave materializada por dispositivo,
- credencial emitida,
- descarga inicial de politica,
- ni reconciliacion remota de inventario,

`Business` no debe presentarse como inscripcion empresarial ya operativa.

## Siguiente paso recomendado

El siguiente paso natural despues de este corte es abrir el `Slice C` de politica local cacheada e inventario minimo sobre esta misma base, sin mezclar todavia portal, soporte remoto generico ni backend empresarial completo. En paralelo, conviene rerun la recomposicion limpia completa de `build/live-output/w4-os-business/` cuando el mirror Debian deje de devolver `Hash Sum mismatch` durante `apt-get update`.
