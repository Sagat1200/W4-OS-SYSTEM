# MX-010 · Business piloto V1 · Slice D · Inventario minimo remoto acotado

Estado: materializado sobre `overlay`, `install` y `live-output`, incluyendo `build/live-output/w4-os-business-c174-clean/`
Fecha: 2026-10-11
Alcance: `W4 OS Business V1`
Dependencias: `MX-010_BUSINESS_PILOT_V1_CONTRACT.md`, `MX-010_BUSINESS_PILOT_V1_SLICE_C_LOCAL_POLICY_AND_INVENTORY.md`, `221_W4_OS_FLEET_MANAGEMENT.md`, `224_W4_OS_REMOTE_UPDATE_MANAGEMENT.md`, `227_W4_OS_INVENTORY_SYSTEM.md`, `409_W4_OS_V1_BUSINESS_SCOPE.md`

## Objetivo

Abrir el siguiente slice tecnico de `Business` publicando un contrato remoto minimo y verificable para inventario, sin vender todavia backend empresarial completo, portal operativo, reconciliacion remota plena ni ventanas de update ya gobernadas por servidor.

Este slice existe para congelar una frontera remota acotada:

1. cual es el payload minimo que `Business` puede exponer fuera del equipo;
2. como se conserva una cola local antes de cualquier entrega remota;
3. donde vive el ultimo intento de entrega y el ultimo payload enviado;
4. donde queda tipado el endpoint remoto;
5. y como mantener esa capa remota sin romper la autoridad local de politica e inventario.

## Regla de alcance

Este slice si abre:

- contrato fuente remoto minimo de inventario;
- payload remoto reducido y redaccionado;
- cola local de entrega diferida;
- slot tipado para endpoint remoto;
- puente runtime verificable entre contrato, overlay, instalacion y `live-output`;
- y activacion remota acotada a `configured-or-enrolled`.

Este slice no abre todavia:

- backend empresarial obligatorio;
- reconciliacion remota de inventario;
- escrituras remotas fuera del dominio de inventario;
- ventanas de update remotas;
- portal de operadores;
- ni reporting de compliance completo.

## Aterrizaje tecnico de esta pasada

Este slice ya deja un aterrizaje tecnico real en el arbol:

1. `config/editions/business/remote-minimum-inventory.json` ya fija el contrato fuente `business-remote-minimum-inventory`;
2. `manifests/w4-os-business.profile.json` ya publica `inventory-remote-ready` como feature explicita del perfil;
3. `scripts/generate_system_overlay.php` ya materializa ese contrato como `files/etc/w4/business-remote-minimum-inventory.json` dentro de `build/overlays/w4-os-business/` y lo publica tambien en `overlay-manifest.json`;
4. `scripts/prepare_installation_runtime.php` ya expone en `build/install/w4-os-business/runtime/install.env` las variables `W4_REMOTE_INVENTORY_*`;
5. `src/Business/BusinessRemoteMinimumInventoryToolkit.php`, expuesto por `scripts/validate_business_remote_minimum_inventory.php`, ya valida en verde el contrato sobre `overlay/install`;
6. `src/Business/BusinessRemoteMinimumInventoryLiveOutputToolkit.php`, expuesto por `scripts/validate_business_remote_minimum_inventory_live_output.php`, ya valida en verde la proyeccion materializada sobre `build/live-output/w4-os-business/` y `build/live-output/w4-os-business-c174-clean/`;
7. `tests/BusinessRemoteMinimumInventoryCliTest.php`, `tests/BusinessRemoteMinimumInventoryLiveOutputCliTest.php`, `tests/SystemOverlayGenerationTest.php`, `tests/ManifestToolkitTest.php` y `tests/RootfsBundleGenerationTest.php` ya congelan este slice en QA.

## Contrato remoto fijado

La pasada deja fijado como inventario remoto minimo verificable:

1. `scope.mode=remote-inventory-minimum`;
2. `backend_required=false`;
3. `activation=configured-or-enrolled`;
4. `delivery_state=deferred-ready`;
5. `source_device_state_file=/var/lib/w4/inventory/device-state.json`;
6. `queue_file=/var/lib/w4/inventory/remote/pending/device-state.json`;
7. `last_delivery_file=/var/lib/w4/inventory/remote/last-delivery.json`;
8. `last_payload_file=/var/lib/w4/inventory/remote/last-payload.json`;
9. `endpoint_config_file=/etc/w4/inventory/remote-endpoint.json`;
10. `transport=https-json-manifest`;
11. `transport_security=tls-required`;
12. `authentication_mode=device-credential`;
13. `delivery_mode=deferred-push`;
14. `retry_policy=local-backoff`;
15. `max_batch_size=1`;
16. payload minimo con `profile_id`, `hostname`, `policy_state`, `last_sync_at`, `update_channel` y `policy_revision`;
17. redaccion explicita de `local_username`, `installed_packages` y `recent_documents`;
18. fronteras `policy_authority=local-baseline`, `inventory_authority=local-device-state`, `remote_writeback=inventory-only` y `update_windows_deferred=true`.

## Puente runtime

El runtime de instalacion ya conserva de forma visible:

1. `W4_REMOTE_INVENTORY_SOURCE_FILE`;
2. `W4_REMOTE_INVENTORY_QUEUE_FILE`;
3. `W4_REMOTE_INVENTORY_LAST_DELIVERY_FILE`;
4. `W4_REMOTE_INVENTORY_LAST_PAYLOAD_FILE`;
5. `W4_REMOTE_INVENTORY_ENDPOINT_FILE`;
6. `W4_REMOTE_INVENTORY_TRANSPORT`;
7. `W4_REMOTE_INVENTORY_AUTH_MODE`;
8. `W4_REMOTE_INVENTORY_ACTIVATION`.

Con ello, el slice deja de depender solo del contrato JSON: el pipeline tambien vuelve a publicar la semantica minima del inventario remoto durante la instalacion.

## Cierre materializado

Este mismo slice ya queda proyectado sobre `build/live-output/w4-os-business/` y `build/live-output/w4-os-business-c174-clean/` mediante:

1. la regeneracion de `build/live/w4-os-business/` con `business-remote-minimum-inventory.json` ya presente en `files/system-overlay/etc/w4/`;
2. la sincronizacion del `system-overlay` actualizado dentro de ambos arboles `live-output`;
3. `src/Business/BusinessRemoteMinimumInventoryLiveOutputToolkit.php`, que ya valida en verde el contrato materializado en `image-root/system-overlay/etc/w4/business-remote-minimum-inventory.json`;
4. validacion real en verde confirmando `inventory-remote-ready`, la cola local remota, el slot tipado del endpoint, el payload minimo redaccionado y la frontera remota acotada del piloto.

## Criterio de cierre del slice

Este slice puede tratarse como correctamente materializado cuando:

1. el contrato fuente remoto exista y sea estable;
2. el overlay publique una copia runtime de ese contrato;
3. el runtime de instalacion exponga las variables necesarias para no perder la semantica de inventario remoto minimo;
4. el `live-output` materializado publique el mismo contrato remoto;
5. la validacion confirme cola local, payload minimo, redaccion explicita y slot de endpoint;
6. el piloto siga explicitamente dentro de una frontera remota `inventory-only`.

En esta pasada ya quedan satisfechos esos seis peldaños sobre `config`, `build/overlays`, `build/install`, `build/live` y `build/live-output`.

## Riesgo activo

El riesgo principal de este slice es leer la nueva capa remota como si ya existiera un backend de flota plenamente operativo o un plano de update gobernado desde servidor.

Mientras no existan:

- autenticacion remota materializada por credencial viva;
- reconciliacion real del inventario;
- reporting consolidado de flota;
- ventanas de update remotas;
- cohortes remotas activas;
- ni portal empresarial de operadores,

`Business` no debe presentarse como piloto empresarial plenamente conectado.

## Siguiente paso recomendado

El siguiente paso natural despues de este corte es:

1. tratar `build/live-output/w4-os-business-c174-clean/` como baseline limpio verificable tambien para la capa remota minima de inventario;
2. y decidir si conviene abrir un `Slice E` acotado a ventanas de update remotas, manteniendo fuera del alcance el backend empresarial generalista.
