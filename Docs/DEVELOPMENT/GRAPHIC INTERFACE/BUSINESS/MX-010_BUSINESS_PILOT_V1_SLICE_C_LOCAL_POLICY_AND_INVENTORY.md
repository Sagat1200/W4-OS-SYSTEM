# MX-010 · Business piloto V1 · Slice C · Politica local cacheada e inventario minimo

Estado: materializado sobre `overlay`, `install` y `live-output`; recomposicion limpia completa pendiente por drift externo del mirror Debian
Fecha: 2026-10-10
Alcance: `W4 OS Business V1`
Dependencias: `MX-010_BUSINESS_PILOT_V1_CONTRACT.md`, `MX-010_BUSINESS_PILOT_V1_SLICE_B_ENROLLMENT_READINESS.md`, `213_W4_OS_CENTRAL_POLICY_SYSTEM.md`, `222_W4_OS_DEVICE_ENROLLMENT.md`, `224_W4_OS_REMOTE_UPDATE_MANAGEMENT.md`

## Objetivo

Abrir el siguiente slice tecnico de `Business` publicando un contrato local y verificable de politica cacheada e inventario minimo, sin vender todavia politica remota efectiva, inventario sincronizado ni backend empresarial completo.

Este slice existe para congelar la frontera offline del piloto:

1. cual es la fuente base de politica para `Business`;
2. donde vive la ultima politica valida;
3. donde se publica la politica efectiva local;
4. donde se conserva el estado minimo de inventario;
5. y como mantener el piloto dentro de una semantica `local-only`.

## Regla de alcance

Este slice si abre:

- contrato fuente local de politica e inventario;
- cache local de ultima politica valida;
- slot local de politica efectiva;
- reporte local de conflictos de politica;
- inventario minimo local;
- y puente runtime verificable entre contrato, overlay, instalacion y `live-output`.

Este slice no abre todavia:

- backend de politica central;
- sincronizacion remota de inventario;
- excepciones locales tipadas sobre politica remota;
- manifiestos de politica firmados;
- reporting de compliance;
- ni un portal empresarial final.

## Aterrizaje tecnico de esta pasada

Este slice ya deja un aterrizaje tecnico real en el arbol:

1. `config/editions/business/pilot-local-state.json` ya fija el contrato fuente `business-pilot-local-state`;
2. `scripts/generate_system_overlay.php` ya materializa ese contrato como `files/etc/w4/business-pilot-local-state.json` dentro de `build/overlays/w4-os-business/` y lo publica tambien en `overlay-manifest.json`;
3. `scripts/prepare_installation_runtime.php` ya expone en `build/install/w4-os-business/runtime/install.env` el puente runtime de politica e inventario local;
4. `src/Business/BusinessPilotLocalStateToolkit.php`, expuesto por `scripts/validate_business_pilot_local_state.php`, ya valida en verde el contrato sobre `overlay/install`;
5. `src/Business/BusinessPilotLocalStateLiveOutputToolkit.php`, expuesto por `scripts/validate_business_pilot_local_state_live_output.php`, ya valida en verde la proyeccion materializada sobre `build/live-output/w4-os-business/`;
6. `tests/BusinessPilotLocalStateCliTest.php`, `tests/BusinessPilotLocalStateLiveOutputCliTest.php` y `tests/SystemOverlayGenerationTest.php` ya congelan este slice en QA.

## Contrato local fijado

La pasada deja fijado como politica/inventario local verificable:

1. `scope.mode=local-policy-inventory`;
2. `backend_required=false`;
3. `policy_state=edition-baseline-only`;
4. `inventory_transport=local-only`;
5. `baseline_source=edition-policy`;
6. `last_known_policy_file=/var/lib/w4/policy/last-known-policy.json`;
7. `effective_policy_file=/var/lib/w4/policy/effective-policy.json`;
8. `conflict_report_file=/var/lib/w4/policy/conflicts.json`;
9. `invalid_policy_behavior=keep-last-valid`;
10. `device_state_file=/var/lib/w4/inventory/device-state.json`;
11. precedencia local `edition-baseline -> last-known-valid -> local-exception-none`;
12. inventario minimo con `profile_id`, `hostname`, `policy_state`, `last_sync_at` y `update_channel`.

## Puente runtime

El runtime de instalacion ya conserva de forma visible:

1. `W4_POLICY_BASELINE_SOURCE`;
2. `W4_POLICY_BASELINE_RUNTIME_ENV`;
3. `W4_POLICY_LAST_KNOWN_FILE`;
4. `W4_POLICY_EFFECTIVE_FILE`;
5. `W4_POLICY_INVALID_BEHAVIOR`;
6. `W4_INVENTORY_DEVICE_STATE_FILE`;
7. `W4_INVENTORY_TRANSPORT`.

Con ello, el slice deja de depender solo del contrato JSON: el pipeline tambien vuelve a publicar la semantica local de politica e inventario durante la instalacion.

## Cierre materializado

Este mismo slice ya queda proyectado sobre `build/live-output/w4-os-business/` mediante:

1. la regeneracion de `build/live/w4-os-business/` con `business-pilot-local-state.json` ya presente en `files/system-overlay/etc/w4/`;
2. la sincronizacion del `system-overlay` actualizado dentro del `live-output` materializado actual;
3. `src/Business/BusinessPilotLocalStateLiveOutputToolkit.php`, que ya valida en verde el contrato materializado en `image-root/system-overlay/etc/w4/business-pilot-local-state.json`;
4. validacion real en verde confirmando `w4-business-live`, `profile.env`, la precedencia local, la cache de politica efectiva y el anclaje local de inventario.

La recomposicion limpia completa del `live-output` se intento sobre WSL, pero `apt-get update` volvio a quedar bloqueado por `Hash Sum mismatch` del mirror Debian al reinstalar `live-boot/live-config`. Ese bloqueo sigue fuera del contrato `Business`; por eso esta pasada deja trazado que el cierre funcional del slice queda validado sobre el `system-overlay` materializado del `live-output`, mientras el rerun limpio completo del artefacto sigue pendiente de infraestructura externa.

## Criterio de cierre del slice

Este slice puede tratarse como correctamente materializado cuando:

1. el contrato fuente local exista y sea estable;
2. el overlay publique una copia runtime de ese contrato;
3. el runtime de instalacion exponga las variables necesarias para no perder la semantica de politica e inventario;
4. el `live-output` materializado publique el mismo contrato local;
5. la validacion confirme precedencia local, cache `last-known-valid`, politica efectiva e inventario minimo;
6. el piloto siga explicitamente dentro de una frontera `local-only`.

En esta pasada ya quedan satisfechos esos seis peldaños sobre `config`, `build/overlays`, `build/install`, `build/live` y `build/live-output`.

## Riesgo activo

El riesgo principal de este slice es leer la nueva capa local como si ya existiera una politica central efectiva o un inventario remoto sincronizado.

Mientras no existan:

- feed de revisiones de politica;
- manifiestos firmados;
- excepciones locales reales;
- sincronizacion remota de inventario;
- reporting de compliance;
- ni backend de gestion empresarial,

`Business` no debe presentarse como piloto empresarial plenamente conectado.

## Siguiente paso recomendado

El siguiente paso natural despues de este corte es doble:

1. rerun la recomposicion limpia completa de `build/live-output/w4-os-business/` cuando el mirror Debian deje de devolver `Hash Sum mismatch`;
2. y solo despues decidir si conviene abrir un slice posterior acotado al plano remoto de inventario/ventanas de update, manteniendo fuera del alcance el backend empresarial generalista.
