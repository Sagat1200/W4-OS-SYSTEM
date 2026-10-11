# MX-010 · Business piloto V1 · Slice E · Ventanas de update remotas acotadas

Estado: materializado sobre `overlay`, `install` y `live-output`, incluyendo `build/live-output/w4-os-business-c174-clean/`
Fecha: 2026-10-11
Alcance: `W4 OS Business V1`
Dependencias: `MX-010_BUSINESS_PILOT_V1_CONTRACT.md`, `MX-010_BUSINESS_PILOT_V1_SLICE_D_REMOTE_MINIMUM_INVENTORY.md`, `224_W4_OS_REMOTE_UPDATE_MANAGEMENT.md`, `409_W4_OS_V1_BUSINESS_SCOPE.md`

## Objetivo

Abrir el siguiente slice tecnico de `Business` publicando un contrato remoto minimo y verificable para ventanas de update, sin vender todavia backend empresarial generalista, instalador remoto paralelo, downgrade remoto ni consola de operadores.

Este slice existe para congelar una frontera remota acotada:

1. como se asignan remotamente cohorte, ventana y canal objetivo;
2. que archivos locales coordinan esa decision remota;
3. como se preserva la ejecucion en el motor local ya validado;
4. que health checks siguen siendo obligatorios antes de aplicar cambios;
5. y como mantener esa capa remota dentro de una frontera `local-engine-only`.

## Regla de alcance

Este slice si abre:

- contrato fuente remoto minimo de ventanas de update;
- asignacion remota de cohorte y ventana sobre archivos locales tipados;
- canal objetivo remoto con politica local de deferimiento;
- puente runtime verificable entre contrato, overlay, instalacion y `live-output`;
- reuse explicito del motor local `reuse-mx-004`;
- y activacion remota acotada a `configured-or-enrolled`.

Este slice no abre todavia:

- instalador remoto paralelo;
- downgrade remoto;
- firmas remotas de ventanas;
- scoring de salud por cohorte;
- umbrales de pausa de flota;
- flujo de aprobacion de rollback;
- ni UI operativa de scheduling empresarial.

## Aterrizaje tecnico de esta pasada

Este slice ya deja un aterrizaje tecnico real en el arbol:

1. `config/editions/business/remote-update-windows.json` ya fija el contrato fuente `business-remote-update-windows`;
2. `manifests/w4-os-business.profile.json` ya publica `update-windows-remote-ready` como feature explicita del perfil;
3. `scripts/generate_system_overlay.php` ya materializa ese contrato como `files/etc/w4/business-remote-update-windows.json` dentro de `build/overlays/w4-os-business/` y lo publica tambien en `overlay-manifest.json`;
4. `scripts/prepare_installation_runtime.php` ya expone en `build/install/w4-os-business/runtime/install.env` las variables `W4_REMOTE_UPDATE_*`;
5. `src/Business/BusinessRemoteUpdateWindowsToolkit.php`, expuesto por `scripts/validate_business_remote_update_windows.php`, ya valida en verde el contrato sobre `overlay/install`;
6. `src/Business/BusinessRemoteUpdateWindowsLiveOutputToolkit.php`, expuesto por `scripts/validate_business_remote_update_windows_live_output.php`, ya valida en verde la proyeccion materializada sobre `build/live-output/w4-os-business/` y `build/live-output/w4-os-business-c174-clean/`;
7. `tests/BusinessRemoteUpdateWindowsCliTest.php`, `tests/BusinessRemoteUpdateWindowsLiveOutputCliTest.php`, `tests/SystemOverlayGenerationTest.php`, `tests/ManifestToolkitTest.php` y `tests/RootfsBundleGenerationTest.php` ya congelan este slice en QA.

## Contrato remoto fijado

La pasada deja fijado como capa remota minima de ventanas de update:

1. `scope.mode=remote-update-windows`;
2. `backend_required=false`;
3. `activation=configured-or-enrolled`;
4. `enforcement=local-engine-only`;
5. `source_device_state_file=/var/lib/w4/inventory/device-state.json`;
6. `source_remote_queue_file=/var/lib/w4/inventory/remote/pending/device-state.json`;
7. `cohort_assignment_file=/var/lib/w4/update/remote/cohort-assignment.json`;
8. `window_definition_file=/var/lib/w4/update/remote/window-definition.json`;
9. `plan_request_file=/var/lib/w4/update/remote/plan-request.json`;
10. `last_result_file=/var/lib/w4/update/remote/last-result.json`;
11. `update_channel_source=device-state`;
12. `allowed_channels=[testing, stable]`;
13. `default_channel=testing`;
14. `schedule_timezone=UTC`;
15. `max_deferral_minutes=1440`;
16. `reboot_outside_window=false`;
17. health checks obligatorios `compatibility`, `power`, `disk-space`, `snapshot` y `policy`;
18. fronteras `local_update_engine=reuse-mx-004`, `remote_decides=[cohort, window, target-channel]`, `local_validates=[compatibility, power, disk-space, policy, health-checks]`, `remote_installer_allowed=false` y `downgrade_allowed=false`.

## Puente runtime

El runtime de instalacion ya conserva de forma visible:

1. `W4_REMOTE_UPDATE_COHORT_ASSIGNMENT_FILE`;
2. `W4_REMOTE_UPDATE_WINDOW_DEFINITION_FILE`;
3. `W4_REMOTE_UPDATE_PLAN_REQUEST_FILE`;
4. `W4_REMOTE_UPDATE_LAST_RESULT_FILE`;
5. `W4_REMOTE_UPDATE_DEFAULT_CHANNEL`;
6. `W4_REMOTE_UPDATE_TIMEZONE`;
7. `W4_REMOTE_UPDATE_MAX_DEFERRAL_MINUTES`;
8. `W4_REMOTE_UPDATE_REBOOT_OUTSIDE_WINDOW`;
9. `W4_REMOTE_UPDATE_ENGINE`;
10. `W4_REMOTE_UPDATE_ACTIVATION`.

Con ello, el slice deja de depender solo del contrato JSON: el pipeline tambien vuelve a publicar la semantica minima de cohortes, ventanas y deferimiento durante la instalacion.

## Cierre materializado

Este mismo slice ya queda proyectado sobre `build/live-output/w4-os-business/` y `build/live-output/w4-os-business-c174-clean/` mediante:

1. la regeneracion de `build/live/w4-os-business/` con `business-remote-update-windows.json` ya presente en `files/system-overlay/etc/w4/`;
2. la sincronizacion del `system-overlay` actualizado dentro de ambos arboles `live-output`;
3. `src/Business/BusinessRemoteUpdateWindowsLiveOutputToolkit.php`, que ya valida en verde el contrato materializado en `image-root/system-overlay/etc/w4/business-remote-update-windows.json`;
4. validacion real en verde confirmando `update-windows-remote-ready`, la asignacion remota de cohorte y ventana, la politica de canal y deferimiento, el reuse del motor local con health checks obligatorios y la frontera remota sin instalador paralelo.

## Criterio de cierre del slice

Este slice puede tratarse como correctamente materializado cuando:

1. el contrato fuente remoto exista y sea estable;
2. el overlay publique una copia runtime de ese contrato;
3. el runtime de instalacion exponga las variables necesarias para no perder la semantica remota de cohortes y ventanas;
4. el `live-output` materializado publique el mismo contrato remoto;
5. la validacion confirme `local-engine-only`, `reuse-mx-004`, `default_channel`, `max_deferral_minutes` y los health checks obligatorios;
6. el piloto siga explicitamente dentro de una frontera remota sin instalador paralelo ni downgrade remoto.

En esta pasada ya quedan satisfechos esos seis peldaños sobre `config`, `build/overlays`, `build/install`, `build/live` y `build/live-output`.

## Riesgo activo

El riesgo principal de este slice es leer la nueva capa remota como si ya existiera una orquestacion empresarial completa de updates o un instalador remoto alternativo.

Mientras no existan:

- firma remota de ventanas;
- scoring de salud por cohorte;
- umbrales globales de pausa;
- aprobacion de rollback;
- scheduling UI de operadores;
- ni backend empresarial completo,

`Business` no debe presentarse como piloto de update remoto totalmente conectado.

## Siguiente paso recomendado

El siguiente paso natural despues de este corte es:

1. tratar `build/live-output/w4-os-business-c174-clean/` como baseline limpio verificable tambien para la capa remota de ventanas de update;
2. y decidir mas adelante si conviene abrir un slice adicional de endurecimiento remoto acotado a firma de ventanas, scoring de cohorte o scheduling operativo, manteniendo fuera del alcance el backend empresarial generalista.
