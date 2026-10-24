# VirtualBox Modern Linux Native D-Bus Runtime Check

Fecha: 2026-10-18

## Objetivo

Comprobar si el modulo compilado `dbus.so` no solo carga en `Debian 13 + PHP 8.4.24`, sino si ya sostiene una prueba minima de runtime real.

## Entorno

- VM: `W4-OS-Server-DbusModern`
- Host remoto: `127.0.0.1:2240`
- Usuario live: `w4live`
- Modulo probado: `/home/w4live/dbus-0.1.1/modules/dbus.so`
- Script de comprobacion: `/home/w4live/native-dbus-runtime-check/check_native_dbus_runtime.php`

## API visible confirmada

La reflexion del modulo cargado confirma:

- clase `Dbus`
- constante `Dbus::BUS_SYSTEM`
- metodos `requestName`, `registerObject` y `waitLoop`

## Resultado operativo

### 1. Constructor y carga del runtime

El script puede instanciar correctamente:

- `new Dbus(Dbus::BUS_SYSTEM)`

No hay fallo de carga del modulo ni de resolucion de la clase principal.

### 2. Ownership en `BUS_SYSTEM`

Con nombres de bus de prueba unicos:

- `org.w4.System1.Test1792310390`

el runtime devuelve:

- `Dbus::requestName(): Not Primary Owner (-1)`

El fallo es consistente y no desaparece al variar el nombre, por lo que ya no apunta a un problema de compilacion ni a una colision trivial del nombre.

La misma prueba repetida como `root` devuelve el mismo resultado:

- `org.w4.System1.RootTest1792310495`
- `Dbus::requestName(): Not Primary Owner (-1)`

Esto reduce mucho la probabilidad de que el bloqueo sea solo el usuario `w4live`.

### 3. Ownership en `BUS_SESSION`

Ejecutando la misma sonda bajo `dbus-run-session` y usando:

- `org.w4.System1.SessionTest1792310394`

el runtime completa la ruta minima de ownership sin excepcion y devuelve estado `ok=true`.

### 4. Contraste directo con `busctl`

La llamada directa al daemon del system bus:

- `busctl --system call org.freedesktop.DBus /org/freedesktop/DBus org.freedesktop.DBus RequestName su <nombre> 0`

devuelve:

- `Call failed: Access denied`

Esto sugiere que en esta live el system bus tiene una politica que no acepta ese `RequestName` para la identidad usada en la prueba, y que la extension legacy probablemente traduce esa condicion a un mensaje menos preciso (`Not Primary Owner (-1)`).

## Lectura honesta

El fork ya cruzo dos umbrales importantes:

1. compila de forma real en el host Linux moderno homologado;
2. la extension cargada ya puede ejecutar una prueba minima de runtime y pedir nombres en `BUS_SESSION`.

El bloqueo que queda para la vertical `org.w4.System1` ya esta acotado al carril `BUS_SYSTEM`:

- politica del bus del sistema en la live actual,
- y/o semantica de traduccion de errores de `requestName()` en esta extension legacy.

## Siguiente paso recomendado

1. Contrastar el mismo `requestName()` en `BUS_SYSTEM` con una politica D-Bus explicita para `org.w4.System1` o sobre un host donde esa politica ya permita ownership del nombre.
2. Intentar la misma prueba minima dentro del runtime real de `System API`, no solo con la sonda aislada.
3. Abrir un corte de limpieza de `arginfo` para reducir el ruido de warnings `Missing arginfo` al cargar la extension en PHP 8.4.

## Actualizacion posterior: `waitLoop()` estable en el host moderno

Tras aplicar el corte `0011-php84-wait-loop-signal-shutdown-stabilization.patch` sobre el fork activo y recompilar `/home/w4live/dbus-0.1.1/modules/dbus.so` en la misma VM:

- `check_native_dbus_wait_loop.php` ya completa `phase:after-wait-loop` y devuelve:
  - `"wait_loop": true`
- `check_native_dbus_shutdown_after_wait_loop.php` ya completa:
  - `phase:after-wait-loop`
  - `phase:after-unset-connection`
  - `"after_unset_connection": true`

La regresion previa quedo mejor acotada:

- el `general protection fault` no estaba en `requestName()` ni en `registerObject()`;
- tampoco en el destructor base de `Dbus`;
- estaba en el carril de señales de `waitLoop()`, donde la extension legacy materializaba `DbusSignal` y destruia ese valor devuelto de forma inestable bajo `PHP 8.4`.

El ajuste aplicado para `PHP >= 8` consume las señales entrantes dentro de `waitLoop()`, libera el `DBusMessage` en el propio metodo y evita devolver `DbusSignal` en ese carril. El runtime host de `System API` ya vuelve a publicar `wait_loop_result = true` dentro de `MaterializeNativeBusOwnership`, por lo que el ownership real de `org.w4.System1` deja de depender del workaround `deferred-legacy-one-shot`.

El workspace suma ahora tambien `build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_wait_loop_signal_contract.php`, una sonda dedicada para volver ese comportamiento un contrato repetible y no solo una lectura indirecta del runtime host. La sonda abre receptor y emisor sobre `BUS_SYSTEM`, registra una `watch` real, emite una señal real y verifica que `waitLoop(250)` responda segun el contrato esperado por version:

- en `PHP >= 8`, la senal entrante debe normalizarse a `true`
- en ramas legacy, el carril historico seguiria materializando `DbusSignal`

Rerun homologado ejecutado en `W4-OS-Server-DbusModern`:

- `sudo -S php -d extension=/home/w4live/w4-os-system/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so /mnt/w4repo/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_wait_loop_signal_contract.php`

Resultado persistido en `guest-artifacts/wait-loop-signal-contract/check-native-dbus-wait-loop-signal-contract.json`:

- `add_watch = true`
- `send_signal = true`
- `wait_loop_type = boolean`
- `wait_loop_value = true`
- `contract_state = incoming-signal-normalized-to-true`
- `ok = true`

El rerun primero dejo visible una deuda menor pero real del wrapper saliente: `DbusSignal::send()` estaba enviando la señal correctamente, pero el metodo no devolvia exito explicito y el probe observaba `send_signal = null`. El fork activo ya normaliza ese retorno a `true/false`, se recompilo y rehidrato en la misma VM, y el segundo rerun homologado ya deja cerrado tambien ese punto sin mover el contrato verde de `waitLoop()`.

## Actualizacion posterior: el contrato de senales sube a smoke permanente liviano del runtime-check

Para no dejar el contrato de senales y la smoke de wrappers como probes separados, el repo suma `build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_runtime_smoke.php`, un agregador liviano que ahora ejecuta sobre el mismo `dbus.so` persistente:

- `check_native_dbus_wrapper_smoke.php`
- `check_native_dbus_wait_loop_signal_contract.php`
- `check_native_dbus_method_roundtrip_smoke.php`

Rerun homologado ejecutado en `W4-OS-Server-DbusModern`:

- `sudo -S php -d extension=/home/w4live/w4-os-system/build/system-api/validation/native-dbus-real-host/runtime-check/guest-artifacts/dbus-module/dbus.so /mnt/w4repo/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_runtime_smoke.php`

Resultado consolidado persistido en `guest-artifacts/runtime-smoke/check-native-dbus-runtime-smoke.json`:

- `ok = true`
- `checks.wrapper_smoke.exit_code = 0`
- `checks.wait_loop_signal_contract.exit_code = 0`
- `checks.method_roundtrip_smoke.exit_code = 0`

Lectura actualizada:

- la smoke de wrappers y el contrato de senales quedan promovidos a un smoke permanente de bajo costo dentro del `runtime-check`
- el rerun homologado ya no depende de recordar probes sueltos: el artefacto `runtime-smoke` resume wrappers, señales y roundtrip real de method calls sobre la misma VM
- la live moderna volvio a arrancar sin la policy D-Bus aplicada sobre `/`, asi que antes del rerun fue necesario reinyectar el bundle `system-api-dbus-binding-bundle/apply-binding.sh /` y reiniciar `dbus`; solo despues de eso `requestName()` recupero `request_name_result = 1`
- esa preparacion operativa ya no queda manual: `scripts/prepare_native_dbus_live_vm.php` automatiza por tty la shared folder transitoria `w4repo`, el mount de `/mnt/w4repo`, la verificacion del bundle `apply-binding.sh` y el reinicio de `dbus`
- `check_native_dbus_method_roundtrip_smoke.php` queda ahora como la sonda liviana de method call real: levanta un servicio minimo con el ordering sano (`preload-classes-before-connection`), ejecuta `busctl --system call ... GetCapabilities ...`, decodifica el payload JSON y verifica que el proceso siga vivo hasta el shutdown controlado
- con este corte, el baseline rapido del binding nativo ya no solo prueba “wrappers viven” y “señales se normalizan”; tambien cubre un roundtrip real de marshaling sobre `org.w4.System1.Manager`
- usando ese helper, un nuevo rerun homologado de `check_native_dbus_runtime_smoke.php` vuelve a dejar `ok = true` sobre la misma live preparada, confirmando que el atajo operativo ya sirve para recuperar el baseline verde sin repetir la inyeccion manual paso a paso
- el siguiente wrapper host-side ya queda materializado en `scripts/run_native_dbus_runtime_smoke_via_live_vm.php`: una sola invocacion prepara la live, dispara el `runtime_smoke` por tty y devuelve un JSON final con `status = completed`, `prepare_result.status = prepared`, `runtime_smoke.ok = true` y los tres checks internos en `exit_code = 0`
- el carril pesado ya tiene runner equivalente en `scripts/run_native_dbus_heavy_validation_via_live_vm.php`: prepara la live y luego orquesta `check_native_dbus_launch_process.php`, `check_native_dbus_method_probe_ordering.php` y `probe_native_dbus_heap_after_request_name.php` leyendo cada artefacto desde el repo compartido
- la primera ejecucion real del runner pesado no quedo completamente verde: `launch_process.ok = true` y `heap_after_request_name.ok = true`, pero `method_probe_ordering.ok = false` porque `preload-first` y `preload-object-only` ahora dejan `ready = true` y `service_reachable_ok = true`, contradiciendo la expectativa historica modelada en la sonda; `preload-classes-only` se mantiene verde

## Actualizacion posterior: method calls reales sobre `org.w4.System1.Manager`

Con la sonda viva `serve_native_dbus_method_probe.php` ejecutada en la VM moderna bajo:

- `php -d extension=/home/w4live/dbus-0.1.1/modules/dbus.so /home/w4live/native-dbus-runtime-check/serve_native_dbus_method_probe.php`

y con el nombre `org.w4.System1` ya visible en:

- `busctl --system list`
- `busctl --system status org.w4.System1`

se contrastaron llamadas reales de cliente sobre el bus del sistema:

1. `GetCapabilities`

- comando:
  - `busctl --system call org.w4.System1 /org/w4/System1 org.w4.System1.Manager GetCapabilities s dbus-probe-capabilities-2`
- resultado:
  - respuesta D-Bus correcta con payload JSON `system.capabilities`
  - `provider = local-w4ctl-facade`
  - `binding.dbus.name = org.w4.System1`
  - `binding.dbus.interface = org.w4.System1.Manager`

2. `Call(system.dbus-runtime)`

- request usado:
  - `{"api_version":"1.0","request_id":"dbus-probe-call","method":"system.dbus-runtime","params":[]}`
- ejecucion:
  - `php /home/w4live/native-dbus-runtime-check/call_native_dbus_runtime_via_busctl.php`
- resultado:
  - respuesta D-Bus correcta con payload JSON `system.dbus-runtime`
  - `provider = local-system-api-runtime-contract`
  - `status = ready-for-runtime-adapter`
  - `runtime_backend.selected_backend = contract-pending`

Lectura honesta de este corte:

- el bridge callback ya no solo registra el objeto en el bus;
- tambien atiende method calls reales sobre `org.w4.System1.Manager` desde un cliente externo (`busctl`) en el host homologado;
- el hecho de que `system.dbus-runtime` siga reportando `contract-pending` en esta sonda no implica fallo de dispatch D-Bus, sino que el request se resolvio con el contrato por defecto del manager exportado, sin inyectar `W4_SYSTEM_API_RUNTIME_BACKEND=native-dbus-extension` en ese proceso de prueba.

## Actualizacion posterior: modulo recompilado sin `Missing arginfo`

Despues de añadir arginfo explicito al fork activo y recompilar `/home/w4live/dbus-0.1.1/modules/dbus.so` en la misma VM:

- comando de contraste:
  - `php -d extension=/home/w4live/dbus-0.1.1/modules/dbus.so -m 2>&1 | grep -i arginfo`
- resultado:
  - sin salida

Eso confirma que el modulo deja de emitir los warnings `Missing arginfo` que seguian contaminando la carga en `PHP 8.4`.

Ademas, el smoke funcional posterior sigue pasando:

- la sonda `serve_native_dbus_method_probe.php` vuelve a registrar `org.w4.System1`;
- `busctl --system call org.w4.System1 /org/w4/System1 org.w4.System1.Manager GetCapabilities s dbus-probe-post-arginfo`
  devuelve respuesta JSON valida `system.capabilities`;
- el loop de la sonda sigue cerrando limpio con `stop-file-detected` y `service-loop-finished`.

## Actualizacion posterior: `system.dbus-runtime` fuera de `contract-pending`

Levantando la misma sonda viva con override explicito:

- `W4_SYSTEM_API_RUNTIME_BACKEND=native-dbus-extension`

en el proceso servido:

- `env W4_SYSTEM_API_RUNTIME_BACKEND=native-dbus-extension php -d extension=/home/w4live/dbus-0.1.1/modules/dbus.so /home/w4live/native-dbus-runtime-check/serve_native_dbus_method_probe.php`

la llamada cliente:

- `php /home/w4live/native-dbus-runtime-check/call_native_dbus_runtime_via_busctl.php`

ya devuelve `system.dbus-runtime` con el backend nativo realmente seleccionado:

- `runtime_backend.selected_backend = native-dbus-extension`
- `runtime_backend.status = native-runtime-backend-materialized`
- `runtime_backend.materialized = true`
- `native_runtime_service.selected_backend = native-dbus-extension`
- `native_runtime_service.status = native-runtime-service-ready-to-start`

Lectura honesta de este corte:

- el estado `contract-pending` ya no es el resultado por defecto de la sonda viva cuando el proceso exportado recibe el override de entorno correcto;
- el siguiente bloqueo real ya no es de seleccion de backend, sino de lifecycle:
  - `native_runtime_service.blocking_reason = native-runtime-service-not-started`
  - `next_action = start-native-runtime-service`

El smoke complementario `GetCapabilities` sigue consistente en el mismo proceso, publicando:

- `local_runtime_adapter.selected_runtime_backend = native-dbus-extension`
- `local_runtime_launcher.selected_runtime_backend = native-dbus-extension`

## Actualizacion posterior: startup honesto y observacion condicionada a PID real

Al empujar el lifecycle nativo desde la misma sonda viva ya configurada con:

- `W4_SYSTEM_API_RUNTIME_BACKEND=native-dbus-extension`

aparecio un hallazgo mas util que seguir repitiendo llamadas: `system.dbus-native-startup` estaba declarando un estado demasiado optimista.

Evidencia observada en el mismo corte:

- el startup dejaba artefactos persistidos del runtime nativo;
- el archivo `w4-system-api-native-runtime.pid` quedaba con `0`;
- despues de ese corte, `busctl --system list` ya no mostraba `org.w4.System1` como poseido por un proceso vivo, sino solo como `(activatable)`.

Lectura honesta de ese contraste:

- el backend nativo ya puede registrar bootstrap del runtime;
- ese bootstrap no demuestra por si solo que exista un proceso vivo atendiendo el servicio;
- por tanto, llamar a ese estado `native-runtime-service-started` sobrestimaba la realidad del host.

El contrato local queda endurecido a partir de este hallazgo:

- `system.dbus-native-startup` pasa a reportar `status = native-runtime-service-bootstrap-recorded`;
- el estado de ownership intermedio pasa a `startup-recorded-no-process`;
- `system.dbus-native-observation` solo considera servicio observado cuando hay artefactos y `pid > 0`;
- el siguiente paso sugerido tras un bootstrap exitoso ya no es observar pasivamente, sino `launch-native-runtime-service`.

La alineacion de regresion se cerro localmente en PHPUnit sobre backend, adapter, launcher, host, binding generation y CLI, evitando volver a aceptar `pid = 0` como si representara un runtime nativo realmente activo.

## Actualizacion posterior: carril `launch-process` preparado, pero guest sin transporte util

El adapter local ya suma un carril `launch-process` para que `StartNativeRuntimeService` pueda disparar un hijo real `serve-native-runtime-service` y persistir `pid > 0` sin volver a confundir bootstrap con proceso vivo. El siguiente contraste natural era medir ese carril sobre la VM homologada `W4-OS-Server-DbusModern`.

En este corte se logro:

- arrancar la VM desde host con `VBoxManage startvm "W4-OS-Server-DbusModern" --type headless`;
- confirmar que sigue configurado el port forwarding `guestssh` sobre `127.0.0.1:2240 -> 22`;
- verificar que la VM publica Guest Additions visibles (`Additions run level: 1`).

El bloqueo real quedo del lado del guest:

- `Paramiko` no pudo completar handshake SSH porque el invitado no entrego banner en `127.0.0.1:2240`;
- `VBoxManage guestcontrol ... run` tampoco quedo utilizable, devolviendo `The guest execution service is not ready (yet)`.

Lectura honesta:

- el nuevo carril `launch-process` ya quedo implementado y cubierto localmente;
- la validacion homologada sobre Linux real sigue pendiente por indisponibilidad del transporte del invitado, no por una regresion detectada del adapter;
- para evitar improvisacion cuando vuelva el acceso, se dejo preparado el probe remoto `runtime-check/check_native_dbus_launch_process.php`.

Ese probe esta pensado para ejecutarse dentro de la VM y concentrar en una sola corrida:

- `StartNativeRuntimeService` con `W4_SYSTEM_API_NATIVE_RUNTIME_STARTUP_MODE=launch-process`;
- `ObserveNativeRuntimeServiceState`;
- lectura de artefactos (`ready`, `status`, `pid`);
- contraste complementario con `busctl --system status org.w4.System1` y `GetCapabilities`.

## Actualizacion posterior: la sonda homologada ya corre en tty y revela modulo faltante

Se recupero una via de ejecucion util dentro del guest sin depender de SSH:

- la VM confirma `sshd` escuchando en `0.0.0.0:22`, pero el forwarding NAT sigue sin entregar banner;
- se corrigio ademas el hostname activo de `w4-server-lvie` a `w4-server`, eliminando la deriva mas visible respecto de `/etc/hosts`;
- como fallback de transporte se monto el workspace del host dentro del guest mediante shared folders transitorios `vboxsf`.

Con ese carril, `check_native_dbus_launch_process.php` ya pudo ejecutarse directamente desde la tty del guest usando el repo montado.

El resultado cambia el foco del bloqueo:

- la sonda ya arranca y devuelve JSON estructurado en la propia VM;
- el corte ya no falla por transporte ni por `launch-process`;
- la salida actual marca `ok = false` con `error = dbus-module-missing`;
- el path esperado por la sonda, `/home/w4live/dbus-0.1.1/modules/dbus.so`, no existe en esta live.

Lectura honesta del nuevo estado:

- el carril homologado ya esta instrumentado y puede correrse dentro de la VM aun cuando SSH siga roto;
- el siguiente bloqueo operativo real es rehidratar o recompilar el modulo `dbus.so` en el guest moderno;
- una vez presente el modulo, la misma sonda ya queda lista para reintentar `StartNativeRuntimeService`, `ObserveNativeRuntimeServiceState` y el contraste con `busctl`.

## Actualizacion posterior: `launch-process` homologado ya deja PID real y el bloqueo vuelve a `requestName()`

El modulo `dbus.so` ya fue rehidratado dentro de la live y el aislamiento siguiente se hizo en dos capas:

- `run_serve_native_runtime_service_in_guest.sh` se ajusto para dejar `stdout`, `stderr`, `exit_code` y artefactos del runtime dentro del repo compartido (`runtime-check/guest-artifacts/direct-serve`);
- ese runner directo confirmo `pid > 0`, `constructor_mode = bus-system-constructor` y un fallo real del ownership nativo en `Dbus::requestName(): Not Primary Owner (-1)`.

Ese contraste dejo claro que el siguiente fallo del carril `launch-process` ya no era del servicio nativo, sino del propio relanzamiento desacoplado:

- el hijo se reejecutaba sin preservar `-d extension=/home/w4live/dbus-0.1.1/modules/dbus.so`;
- y el padre reescribia luego los artefactos de `startup`, ocultando el estado de ownership que el hijo ya habia materializado.

Con ambos cortes corregidos en el adapter y en la sonda homologada:

- `SystemApiDbusRuntimeAdapter` ya propaga `W4_SYSTEM_API_NATIVE_RUNTIME_EXTENSION_PATH`/`W4_SYSTEM_API_REMOTE_DBUS_MODULE` al hijo `serve-native-runtime-service`;
- el padre deja de pisar los artefactos del hijo cuando el launch ya quedo materializado;
- `check_native_dbus_launch_process.php` ahora escribe su reporte consolidado en `runtime-check/guest-artifacts/launch-process`, distingue `launch_ok` de `service_reachable_ok` y captura tambien `ps`, `delayed_observation` y los contrasts de `busctl`.

Resultado homologado actual sobre `W4-OS-Server-DbusModern`:

- `StartNativeRuntimeService` devuelve `status = native-runtime-service-launch-recorded`, `launched = true`, `launch_mode = background-process` y `pid > 0`;
- la sonda ya deja `launch_ok = true`;
- el artefacto final `w4-system-api-native-runtime-status.json` vuelve a reflejar el bloqueo real del host y ya no queda congelado en `startup`: `status = native-runtime-bus-ownership-failed`, `ownership_state = native-runtime-ownership-failed`, `constructor_mode = bus-system-constructor`, `error_message = "Dbus::requestName(): Not Primary Owner (-1)\n"`;
- `busctl --system status org.w4.System1` y `GetCapabilities` siguen fallando en esta live, por lo que `service_reachable_ok = false`.

Lectura honesta del estado:

- el carril homologado `launch-process` ya quedo reparado y produce un hijo real con PID positivo en la VM moderna;
- el bloqueo autentico restante ya no es `spawn`, ni `dbus.so` ausente, ni artefactos inconsistentes;
- el siguiente trabajo util vuelve a ser explicar por que esta live concreta falla `requestName()` sobre `BUS_SYSTEM` con `Not Primary Owner (-1)`.

## Actualizacion posterior: policy aplicada en la live, `RequestName` verde con `busctl` y crash nativo tras `requestName()`

Tras apagar y volver a arrancar la live se comprobo otra deuda operativa: el modulo recompilado en `/home/w4live/dbus-0.1.1` no sobrevive al reinicio, asi que la rehidratacion se corrigio para dejar el artefacto persistente en el repo compartido (`runtime-check/guest-artifacts/dbus-module/dbus.so`). Tanto la sonda homologada como el runner directo ya priorizan ese `.so` persistente.

Con el modulo estable en el repo, se ejecuto una nueva cadena de aislamiento:

- `inspect_busctl_request_name_in_guest.sh` se corrio como `root` real (`uid=0(root)`), y aun asi `busctl RequestName org.w4.System1` devolvio `Access denied`;
- la causa ya no era el fork, sino que la live no tenia aplicada la policy D-Bus del proyecto;
- se aplico sobre `/` el bundle real `system-api-dbus-binding-bundle` del repo y se reinicio `dbus`;
- despues de eso, `busctl RequestName` paso a cerrar en verde (`exit_code = 0`, `stdout = "u 1"`), confirmando que el permiso de `own="org.w4.System1"` ya estaba activo.

Una vez resuelto el problema de policy:

- `check_native_dbus_launch_process.php` sigue materializando `launch-recorded`, `launched = true` y `pid > 0`;
- pero el hijo sigue muriendo antes de hacerse visible para `busctl`;
- `run_serve_native_runtime_service_in_guest.sh` deja ahora el fallo real en primer plano: `exit_code = 134` y `stderr = "zend_mm_heap corrupted"`.

Para separar el punto exacto del crash, `NativeDbusExtensionOwnershipDriver` recibio un marcador de fase persistente (`W4_SYSTEM_API_NATIVE_RUNTIME_PHASE_MARKER_FILE`). El rerun directo deja:

- `phase = after-request-name` con traza gruesa;
- `phase = before-exported-object` con traza fina.

Lectura operativa actual:

- el problema de `policy` del system bus ya quedo cerrado en esta live;
- `requestName()` ya no es el bloqueo visible de permisos;
- la corrupcion de heap aparece inmediatamente despues de `requestName()` y se manifiesta al entrar en el siguiente bloque PHP asociado al objeto exportado / preparacion de `registerObject()`;
- el siguiente trabajo util ya no es seguir tocando el bus, sino construir un probe minimo del fork que compare `requestName()` solo contra `requestName() + NativeDbusExtensionRuntimeObject` y luego `registerObject()`.

Ese probe minimo ya se corrio en la misma live:

- `probe_native_dbus_heap_after_request_name.php` ejecuta subprocesos separados con el mismo `dbus.so` persistente;
- `request_name_only` ya aborta con `zend_mm_heap corrupted`;
- `exported_object_only` cierra limpio;
- `request_name_then_exported_object` vuelve a abortar igual que el caso minimo.

Lectura actualizada:

- el objeto PHP `NativeDbusExtensionRuntimeObject` no es el disparador primario;
- la policy del bus ya no es el bloqueo;
- el daño de heap lo introduce el propio `requestName()` del fork nativo cuando la policy ya permite adquirir `org.w4.System1`.

## Actualizacion posterior: `requestName()` puro ya queda estable, pero el heap sigue rompiendose al primer `require`

Sobre el mismo fork activo se probaron tres cortes adicionales:

- `Dbus::requestName()` ya devuelve explicitamente el `reply` `uint32` y maneja `DBusError` de forma estable;
- se intento tambien cambiar el constructor a conexion privada (`dbus_bus_get_private()`) y reemplazar `dbus_bus_request_name()` por una method call manual `org.freedesktop.DBus.RequestName`;
- ninguno de esos dos ultimos experimentos movio el crash de la combinacion posterior.

El probe refinado `probe_native_dbus_heap_after_request_name.php` ya deja ahora este mapa:

- `request_name_only` cierra en verde con `request_name_result = 1`;
- `request_name_then_scalar_allocation` cierra en verde;
- `request_name_then_gc_collect` cierra en verde;
- `request_name_then_require_noop`, `request_name_then_require_autoload` y `request_name_then_exported_object` abortan con `zend_mm_heap corrupted`.

Los marcadores `guest-artifacts/heap-after-request-name/scenario-phases/*.phase` dejan ademas que el proceso cae en:

- `before-require-noop`;
- `before-require-autoload`.

Lectura operativa nueva:

- el caso minimo de `requestName()` puro ya no es el que aborta;
- tampoco es un problema generico de cualquier trabajo PHP posterior, porque asignaciones y `gc_collect_cycles()` sobreviven;
- el heap sigue quedando dañado por `requestName()`, pero ese daño se revela especificamente al entrar en el primer `require`/compilacion de otro archivo PHP;
- la siguiente investigacion util ya no es seguir tocando policy, `registerObject()` ni el tipo de conexion, sino revisar que ownership interno deja `requestName()` sobre estructuras Zend/libdbus que el motor vuelve a tocar al incluir otro script.

## Actualizacion posterior: el falso positivo de observacion en `launch-process` queda corregido en codigo y pendiente de rerun homologado

Al releer el consolidado `guest-artifacts/launch-process/check-native-dbus-launch-process.json` quedo claro que el `ok = false` ya no estaba alineado solo con el crash de heap original. El propio reporte mostraba:

- `busctl_status.stderr = "Failed to get credentials: No such device or address"`;
- `busctl_capabilities.stderr = "Failed to activate service 'org.w4.System1': timed out ..."`;
- `ObserveNativeRuntimeServiceState` seguia considerando `observed = true` aunque el chequeo dependia solo de artefactos (`ready`, `status`, `pid`) y no de liveness real del proceso.

La causa operativa que queda documentada en repo es doble:

- `NativeDbusExtensionOwnershipDriver` trataba `waitLoop()` como un one-shot sin timeout efectivo, de modo que el hijo del `launch-process` podia registrar ownership/artefactos pero no quedarse despachando method calls durante la ventana en que `busctl` lo contrastaba.
- `SystemApiDbusRuntimeBackendToolkit` tomaba cualquier PID positivo persistido como si siguiera vivo, aunque el proceso ya hubiera salido y el name del bus ya no estuviera atendiendo.

Correccion aplicada en codigo local:

- `src/SystemApi/NativeDbusExtensionOwnershipDriver.php` ahora normaliza `waitLoop(250)` y lo repite durante `W4_SYSTEM_API_NATIVE_RUNTIME_KEEPALIVE_SECONDS`, dejando un `service_loop` explicito en el driver y manteniendo dispatch userland real mientras corre el probe homologado.
- `src/SystemApi/SystemApiDbusRuntimeBackendToolkit.php` ahora resuelve `is_running` sobre el PID observado y solo acepta `native-runtime-service-observed-active` cuando el proceso sigue vivo de verdad.

Estado honesto de este corte:

- `php -l src/SystemApi/NativeDbusExtensionOwnershipDriver.php` y `php -l src/SystemApi/SystemApiDbusRuntimeBackendToolkit.php` cierran en verde.
- Todavia falta rerunear `check_native_dbus_launch_process.php` dentro de `W4-OS-Server-DbusModern` para confirmar que el fix mueve `service_reachable_ok` a `true` y no solo corrige la lectura local del lifecycle.

## Actualizacion posterior: `launch-process` ya queda cerrado end-to-end en la VM homologada

Se recupero primero el transporte de laboratorio contra la live con el helper canonico `scripts/enable_live_ssh_in_virtualbox.php`, y desde ahi se reejecuto `check_native_dbus_launch_process.php` dentro de `W4-OS-Server-DbusModern`.

El primer rerun despues del fix de lifecycle ya no fallaba por timeout de activacion: el reporte mostraba al hijo real visible desde `busctl --system status org.w4.System1`, pero `busctl --system call ... GetCapabilities ...` todavia lo hacia caer con `Message recipient disconnected from message bus without replying`. Para separar ese ultimo bloqueo se agregaron tres sondas vivas:

- `serve_native_dbus_method_probe_preload_first.php`
- `serve_native_dbus_method_probe_preload_object_only.php`
- `serve_native_dbus_method_probe_preload_classes_only.php`

La evidencia homologada fue consistente:

- precargar el `runtimeObject` completo antes de abrir `Dbus` vuelve a abortar con `zend_mm_heap corrupted`;
- precargar solo clases (`class_exists(...)`) antes del constructor nativo mantiene el servicio vivo;
- con ese orden, `GetCapabilities` vuelve a responder por `busctl` en verde.

Sobre esa lectura, `NativeDbusExtensionOwnershipDriver` se reajusto para:

- calentar clases del lado PHP antes de `new Dbus(...)`;
- ejecutar `requestName()` con la conexion ya abierta;
- crear `NativeDbusExtensionRuntimeObject` y primar `NativeDbusExtensionCallbackBridge` solo despues de `requestName()`;
- continuar luego con `registerObject()` y el loop sostenido.

Resultado homologado final del rerun:

- `launch_ok = true`
- `service_reachable_ok = true`
- `busctl_status.exit_code = 0`
- `busctl_capabilities.exit_code = 0`
- `delayed_observation.status = native-runtime-service-observed-pending-ownership`
- `process_status.exit_code = 0`

Lectura actualizada:

- el carril `launch-process` ya queda cerrado de punta a punta en la VM moderna;
- el problema real del orden de carga no era "precargar PHP" en abstracto, sino instanciar el `runtimeObject` demasiado pronto;
- el orden que sobrevive en real es: precarga de clases, conexion D-Bus, `requestName()`, runtime object + bridge, `registerObject()`, `waitLoop()`.

Para conservar esta regresion como contraste reproducible y no solo como memoria del rerun, el workspace suma ahora la sonda matriz `runtime-check/check_native_dbus_method_probe_ordering.php`. Ese script relanza los tres modos de ordering (`preload-first`, `preload-object-only`, `preload-classes-only`), contrasta `busctl --system status org.w4.System1` y `GetCapabilities`, y evalua si cada modo cumple la expectativa correcta. Las tres sondas vivas originales quedan como apoyo de aislamiento manual; la referencia canonica para futuros reruns de ordering pasa a ser la matriz.

La corrida homologada de esa matriz ya quedo ejecutada con:

- `sudo -S php /mnt/w4repo/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_method_probe_ordering.php`

y deja su reporte consolidado en:

- `runtime-check/guest-artifacts/method-probe-ordering/check-native-dbus-method-probe-ordering.json`

Resultado real de la matriz:

- `ok = true`
- `preload-first`: `ready = true`, `service_reachable_ok = false`, `busctl --system status` ve el owner real, pero `GetCapabilities` lo derriba con `Message recipient disconnected from message bus without replying` y el proceso termina en `zend_mm_heap corrupted`
- `preload-object-only`: `ready = false`, `service_reachable_ok = false`, el proceso aborta antes de publicar el artefacto de ready
- `preload-classes-only`: `ready = true`, `service_reachable_ok = true`, `busctl --system status` y `GetCapabilities` cierran en verde y el loop se mantiene vivo hasta que la propia matriz deja el `stop-file`

Lectura actualizada de esta regresion:

- el patrón bueno ya queda confirmado por una sonda canonica ejecutada en el host homologado, no solo por probes manuales separados;
- el modo `preload-first` no falla "antes de arrancar", sino despues de exponer ownership y justo al primer method call real;
- el siguiente trabajo útil vuelve a ser instrumentar la deuda del heap post-`requestName()` usando esta matriz como guardrail de regresión.

## Actualizacion posterior: el trigger visible baja de `require` a `tsrm_realpath()` incluso sin `requestName()`

Sobre el mismo host homologado se amplio `probe_native_dbus_heap_after_request_name.php` con cuatro escenarios nuevos:

- `connection_then_realpath_noop_file`
- `connection_then_file_exists_noop_file`
- `request_name_then_realpath_noop_file`
- `request_name_then_file_exists_noop_file`

Todos usan el mismo payload `runtime-check/noop_require_after_request_name.php`, pero sin compilarlo necesariamente.

Resultado homologado del rerun:

- `connection_then_realpath_noop_file`: aborta con `zend_mm_heap corrupted`
- `request_name_then_realpath_noop_file`: aborta con `zend_mm_heap corrupted`
- `connection_then_file_exists_noop_file`: cierra en verde
- `request_name_then_file_exists_noop_file`: cierra en verde

Eso cambia la lectura del bug:

- ya no hace falta llegar a `requestName()` para reproducir la corrupcion visible;
- tampoco hace falta compilar otro archivo con `require_once`;
- basta entrar en la ruta de normalizacion `realpath()` de PHP despues de abrir `Dbus`.

La traza fresca de `gdb` para el escenario `connection_then_realpath_noop_file` confirma ademas el mismo carril nativo observado antes:

- `virtual_file_ex()`
- `tsrm_realpath()`
- `zend_mm_heap corrupted`

Lectura actualizada:

- el fork sigue afectando memoria que PHP usa al resolver rutas reales del filesystem;
- `file_exists()` sobrevive, asi que el daño visible no es "cualquier I/O de path", sino algo mas fino ligado a `realpath`/normalizacion canonica;
- el siguiente corte util ya no es hablar solo de "primer require posterior a requestName()", sino instrumentar por que abrir `Dbus` ya deja inestable el carril `tsrm_realpath()` del runtime PHP.

## Actualizacion posterior: el contraste `shared` vs `private` no mueve el crash de `realpath()`

Para cerrar la duda mas directa del constructor nativo, el fork activo de `dbus.c` ya acepta `W4_DBUS_CONNECTION_MODE=shared` y deja el modo actual `private` como default. Eso permitio recompilar el mismo modulo y contrastar, sobre la VM homologada, la ruta `dbus_bus_get()` (shared, mas cercana al upstream) contra `dbus_bus_get_private()` sin tocar el resto del probe.

Contraste homologado ejecutado:

- recompilacion y rehidratacion del modulo con `runtime-check/rehydrate_dbus_module_in_guest.sh`
- `env W4_DBUS_CONNECTION_MODE=shared php -d extension=... probe_native_dbus_heap_after_request_name.php --scenario=connection_then_realpath_noop_file`
- `env W4_DBUS_CONNECTION_MODE=shared php -d extension=... probe_native_dbus_heap_after_request_name.php --scenario=connection_then_file_exists_noop_file`
- `env W4_DBUS_CONNECTION_MODE=shared ... run_request_name_require_noop_under_gdb.sh` con `connection_then_realpath_noop_file`

Resultado real:

- `shared-connection-then-realpath-noop-file.log`: sigue abortando con `zend_mm_heap corrupted`
- `shared-connection-then-file-exists-noop-file.log`: sigue cerrando limpio
- `gdb-shared-connection-then-realpath-noop-file.log`: vuelve a caer en `virtual_file_ex()` / `tsrm_realpath()` antes del `SIGABRT`

Lectura actualizada:

- el bug ya no parece explicarse solo por haber migrado a `dbus_bus_get_private()`;
- tampoco por `dbus_connection_set_exit_on_disconnect(FALSE)`, porque en el carril `shared` ese detalle deja de aplicar y el crash visible igual persiste;
- el siguiente corte util vuelve a quedar dentro del estado que el constructor de `Dbus` deja en memoria del runtime PHP, no solo en la eleccion `shared/private` de la conexion libdbus.

## Actualizacion posterior: el layout de `php_dbus_obj` era el fix real

El contraste siguiente cerro el problema de forma mucho mas concreta que los toggles previos.

Hallazgo fino:

- saltar `zend_hash_init(&dbusobj->objects, ...)` limpiaba el crash de `realpath()` y tambien el carril `requestName() + require`
- saltar `object_properties_init()` no lo limpiaba
- cambiar `shared/private` tampoco lo movia

Esa forma del fallo apuntaba ya no a libdbus, sino al layout del objeto `Dbus` en el binding PHP. `php_dbus_obj` seguia declarando `zend_object std` al principio del struct, y el fork activo se corrigio moviendo `std` al final de `php_dbus_obj`.

Rerun homologado despues del fix:

- recompilacion y rehidratacion del modulo con `runtime-check/rehydrate_dbus_module_in_guest.sh`
- `sudo -S php /mnt/w4repo/build/system-api/validation/native-dbus-real-host/runtime-check/probe_native_dbus_heap_after_request_name.php`
- `sudo -S php /mnt/w4repo/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_launch_process.php`

Resultado real:

- `probe_native_dbus_heap_after_request_name.php` ya cierra `ok = true`
- pasan en verde `connection_then_realpath_noop_file`, `connection_then_require_noop`, `request_name_then_realpath_noop_file`, `request_name_then_require_noop`, `request_name_then_require_autoload` y `request_name_then_exported_object`
- `check_native_dbus_launch_process.php` mantiene `launch_ok = true` y `service_reachable_ok = true`
- `busctl --system status` y `GetCapabilities` siguen cerrando en verde en el carril end-to-end

Lectura actualizada:

- el crash visible ya no queda abierto en el carril minimo ni en el carril homologado `launch-process`;
- la causa mas probable del bug estaba en el layout del objeto `Dbus` para PHP 8.x, no en `dbus_bus_get_private()` ni en `requestName()` como tal;
- la deuda siguiente ya no es “seguir buscando el heap corrupt”, sino endurecer el fork y decidir si el mismo ajuste de layout debe extenderse preventivamente a otros wrappers del modulo.

## Actualizacion posterior: el endurecimiento del layout ya cubre todos los wrappers del modulo

Sobre el mismo fork se extendio el patron `zend_object std` al final del struct a:

- `DbusObject`
- `DbusSignal`
- `DbusArray`
- `DbusDict`
- `DbusVariant`
- `DbusSet`
- `DbusStruct`
- `DbusObjectPath`
- wrappers escalares definidos por `PHP_DBUS_SETUP_TYPE_OBJ(...)`

Rerun homologado posterior:

- recompilacion y rehidratacion del modulo con `runtime-check/rehydrate_dbus_module_in_guest.sh`
- `sudo -S php /mnt/w4repo/build/system-api/validation/native-dbus-real-host/runtime-check/probe_native_dbus_heap_after_request_name.php`
- `sudo -S php /mnt/w4repo/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_launch_process.php`

Resultado real:

- el modulo recompila limpio y se vuelve a publicar como `guest-artifacts/dbus-module/dbus.so`
- `probe_native_dbus_heap_after_request_name.php` sigue cerrando `ok = true`
- `check_native_dbus_launch_process.php` sigue cerrando `launch_ok = true` y `service_reachable_ok = true`

Lectura actualizada:

- el endurecimiento general no introdujo regresiones visibles en el carril homologado real;
- el fix de layout deja de ser un parche solo para `Dbus` y pasa a ser la regla base del fork para wrappers Zend en PHP 8.4;
- la deuda que queda ya no es de heap visible, sino de cobertura dedicada para wrappers que hoy no pasan por el runtime real.

## Actualizacion posterior: la smoke dedicada de wrappers queda verde y cierra deuda nativa real de destructores

Para no dejar la cobertura de wrappers colgando del runtime real, el repo suma ahora `build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_wrapper_smoke.php`, una smoke suite dedicada que relanza en subprocess escenarios para:

- proxy `DbusObject` via `createProxy()`
- `DbusSignal`
- `DbusArray`
- `DbusDict`
- `DbusVariant`
- `DbusSet`
- `DbusStruct`
- `DbusObjectPath`
- wrappers escalares
- un bundle mixto de lifecycle/GC

Primer rerun homologado:

- `sudo -S php /mnt/w4repo/build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_wrapper_smoke.php`

Hallazgos reales del primer corte:

- `DbusObject::__construct()` es privado, asi que la smoke se corrigio para validar ese contrato por `Reflection` y usar `createProxy()` en lugar de construccion global invalida
- `DbusSignal` abortaba al destruir porque hacia `dbus_message_unref()` sobre `NULL`
- `DbusArray`, `DbusDict`, `DbusVariant` y `DbusStruct` todavia mezclaban ownership manual de `zval` y dejaban `zend_mm_heap corrupted`
- el escenario `nested_wrappers_bundle` pedia una simplificacion para medir lifecycle/destruccion sin sobrecargarlo con introspeccion profunda ya cubierta por los casos individuales

Corte aplicado en el fork:

- guardia `if (intern->msg)` antes de `dbus_message_unref()` en `DbusSignal`
- reemplazo del manejo manual de refs por `zval_ptr_dtor(...)` en destructores de `DbusArray`, `DbusDict`, `DbusVariant` y `DbusStruct`
- ajuste de la smoke para tratar `DbusObject` como wrapper de factory y no como constructor publico
- simplificacion del bundle mixto para medir creacion, destruccion y GC sin ruido de script

Rerun homologado final:

- `guest-artifacts/wrapper-smoke/check-native-dbus-wrapper-smoke.json` ya cierra `ok = true`
- pasan los escenarios de proxy, signal, colecciones, variant, object-path, escalares y bundle mixto
- el modulo recompilado sigue publicandose como `guest-artifacts/dbus-module/dbus.so`

Guardrails revalidados despues del mismo rebuild:

- `probe_native_dbus_heap_after_request_name.php` vuelve a cerrar `ok = true`
- `check_native_dbus_launch_process.php` mantiene `launch_ok = true` y `service_reachable_ok = true`

Lectura actualizada:

- la deuda de cobertura dedicada para wrappers ya queda cerrada con evidencia homologada propia
- ademas del layout Zend, el fork tambien necesitaba correcciones reales de destructor/ownership en wrappers que no estaban siendo recorridos por el carril principal del runtime
- el carril minimo del heap, el carril `launch-process` y la nueva smoke de wrappers quedan simultaneamente verdes sobre la misma VM homologada

## Actualizacion posterior: el ordering lane ya queda realineado y el carril pesado vuelve a verde

El desfase expuesto por el primer rerun de `scripts/run_native_dbus_heavy_validation_via_live_vm.php` ya no queda abierto como deuda de interpretacion.

La evidencia homologada mas reciente deja dos conclusiones utiles:

- `check_native_dbus_method_probe_ordering.php` ya fija en `orderingExpectation()` el contrato realmente observado para los tres modos canónicos:
  - `preload-first`: `ready = true`, `service_reachable_ok = true`
  - `preload-object-only`: `ready = true`, `service_reachable_ok = true`
  - `preload-classes-only`: `ready = true`, `service_reachable_ok = true`
- el artefacto `runtime-check/guest-artifacts/method-probe-ordering/check-native-dbus-method-probe-ordering.json` ya vuelve a cerrar:
  - `ok = true`
  - `expectations_met = true` en los tres modos

Ese realineamiento no mueve los otros guardrails del carril pesado:

- `runtime-check/guest-artifacts/launch-process/check-native-dbus-launch-process.json` conserva `launch_ok = true` y `service_reachable_ok = true`
- `runtime-check/guest-artifacts/heap-after-request-name/probe-native-dbus-heap-after-request-name.json` sigue `ok = true` en sus 17 escenarios

Con estos tres artefactos alineados otra vez, el rerun host-side de:

- `php scripts/run_native_dbus_heavy_validation_via_live_vm.php --vm-name W4-OS-Server-DbusModern`

vuelve a cerrar:

- `status = completed`
- `launch_process.ok = true`
- `method_probe_ordering.ok = true`
- `heap_after_request_name.ok = true`

Lectura actualizada:

- el runner host-side pesado ya no solo automatiza la ejecucion; tambien vuelve a reflejar un baseline completamente verde en la live homologada
- el ajuste del ordering ya no se trata como una hipotesis ni como un parche cosmetico de la sonda, sino como una realineacion del contrato canonico con el comportamiento realmente observado y revalidado

## Actualizacion posterior: `Call(system.dbus-runtime)` ya sube a smoke permanente del baseline rapido

El siguiente hueco que seguia cubierto solo por la sonda viva historica ya queda materializado como probe reutilizable dentro del repo.

Nuevo script agregado:

- `build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_runtime_call_smoke.php`

La sonda levanta el manager nativo sobre `org.w4.System1`, fuerza `W4_SYSTEM_API_RUNTIME_BACKEND=native-dbus-extension`, ejecuta `busctl --system call org.w4.System1 /org/w4/System1 org.w4.System1.Manager Call s <request_json>` con `method = system.dbus-runtime` y valida que la respuesta JSON deje:

- `provider = local-system-api-runtime-contract`
- `runtime_backend.selected_backend = native-dbus-extension`
- `runtime_backend.status = native-runtime-backend-materialized`
- `runtime_backend.materialized = true`
- `native_runtime_service.status = native-runtime-service-ready-to-start`

Ademas, la sonda exige que el proceso servido siga vivo despues del call y cierre limpio al recibir el `stop-file`.

Estado actualizado de este corte:

- `php -l build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_runtime_call_smoke.php` ya cierra en verde
- `php -l build/system-api/validation/native-dbus-real-host/runtime-check/check_native_dbus_runtime_smoke.php` ya cierra en verde con la integracion del cuarto check
- el primer rerun host-side del baseline rapido detecto un bug real del propio wrapper `scripts/run_native_dbus_runtime_smoke_via_live_vm.php`: seguia aceptando cualquier `guest-artifacts/runtime-smoke/check-native-dbus-runtime-smoke.json` ya existente y por eso devolvia un resumen viejo con solo tres checks
- el runner ya queda corregido con `waitForArtifactAfter(...)`, obligando a leer un artefacto fresco por timestamp antes de resumirlo
- tras ese fix, el rerun real sobre `W4-OS-Server-DbusModern` vuelve a cerrar:
  - `status = completed`
  - `prepare_result.status = prepared`
  - `runtime_smoke.ok = true`
  - `checks.wrapper_smoke.exit_code = 0`
  - `checks.wait_loop_signal_contract.exit_code = 0`
  - `checks.method_roundtrip_smoke.exit_code = 0`
  - `checks.runtime_call_smoke.exit_code = 0`

Lectura actualizada:

- el baseline rapido ya no depende solo de `GetCapabilities` para cubrir method calls reales sobre el manager exportado
- la seleccion efectiva del backend nativo dentro de `system.dbus-runtime` ya queda fijada tambien dentro del smoke permanente y no solo como evidencia historica de la sonda viva manual
- el runner host-side rapido vuelve a reflejar de forma honesta la ultima corrida real de la live, en lugar de poder resumir artefactos viejos
- el refinamiento siguiente deja ademas el mismo criterio de frescura que el carril pesado: `scripts/run_native_dbus_runtime_smoke_via_live_vm.php` ya usa el `filemtime()` previo del artefacto consolidado (`mtime + 1`) en lugar de un umbral generico basado en `time()`, y un nuevo rerun real sobre `W4-OS-Server-DbusModern` mantiene `runtime_smoke.ok = true` con los cuatro checks en `exit_code = 0`

## Actualizacion posterior: el contrato `sudo env` ya queda corregido y el bloqueo real se mueve a transporte de la live

El siguiente atasco ya no vino del binding ni del propio smoke, sino del modo en que los runners host-side estaban disparando comandos dentro de la live.

La captura de la TTY de `W4-OS-Server-DbusModern` deja visible el problema:

- `scripts/run_native_dbus_runtime_smoke_via_live_vm.php` y `scripts/run_native_dbus_heavy_validation_via_live_vm.php` estaban inyectando `sudo W4_SYSTEM_API_REMOTE_ROOT_DIR=... php ...`
- esa forma es invalida para `sudo`: no exporta las variables de entorno ni ejecuta el comando esperado

Sobre esa lectura, ambos runners ya quedan corregidos a:

- `sudo env W4_SYSTEM_API_REMOTE_ROOT_DIR=... W4_SYSTEM_API_REMOTE_DBUS_MODULE=... php ...`

El runner liviano conserva ademas la identificacion por `run_id` para no depender solo de timestamps del artefacto consolidado.

Estado honesto despues del fix:

- `php -l scripts/run_native_dbus_runtime_smoke_via_live_vm.php` cierra en verde
- `php -l scripts/run_native_dbus_heavy_validation_via_live_vm.php` cierra en verde
- el rerun homologado todavia no puede cerrarse porque el bloqueo util ya no es de codigo sino de transporte/ejecucion en la live

Evidencia del bloqueo actual:

- la consola inyectada por `keyboardputstring` ya no ejecuta ni un `echo` corto sobre el repo compartido
- los forwards `127.0.0.1:2222`, `:2224` y `:2240` aceptan TCP pero no entregan banner SSH
- `VBoxManage guestcontrol ... run --username w4live ...` responde `The guest execution service is not ready (yet)`

Lectura actualizada:

- el contrato de ejecucion de los runners ya queda saneado en codigo
- el siguiente trabajo util ya no es seguir tocando `runtime_smoke` ni el fork DBus, sino recuperar un transporte confiable hacia la live para volver a homologar el baseline
- para ese siguiente corte, el repo ya deja listo `scripts/run_native_dbus_wrapper_smoke_via_paramiko.php` como carril alternativo una vez que la live vuelva a exponer SSH valido

## Actualizacion posterior: reset de la live y revalidacion homologada otra vez en verde

Ese bloqueo operativo ya queda absorbido en la propia `W4-OS-Server-DbusModern`.

Un `VBoxManage controlvm W4-OS-Server-DbusModern reset` devolvio una TTY limpia y funcional. La diferencia observable fue inmediata:

- la pantalla vuelve a mostrar login automatico + prompt limpio
- la consola ya vuelve a aceptar comandos inyectados
- el problema restante deja de apuntar al binding y queda explicado por el estado roto previo de la sesion headless

Con la live recuperada, el carril liviano ya vuelve a cerrar en verde real:

- `php scripts/run_native_dbus_runtime_smoke_via_live_vm.php --vm-name W4-OS-Server-DbusModern --smoke-wait-seconds 120`
- resultado:
  - `status = completed`
  - `prepare_result.status = prepared`
  - `runtime_smoke.ok = true`
  - `checks.wrapper_smoke.exit_code = 0`
  - `checks.wait_loop_signal_contract.exit_code = 0`
  - `checks.method_roundtrip_smoke.exit_code = 0`
  - `checks.runtime_call_smoke.exit_code = 0`

Sobre la misma live, el carril pesado tambien vuelve a cerrar completamente en verde:

- `php scripts/run_native_dbus_heavy_validation_via_live_vm.php --vm-name W4-OS-Server-DbusModern`
- resultado:
  - `status = completed`
  - `launch_process.ok = true`
  - `method_probe_ordering.ok = true`
  - `heap_after_request_name.ok = true`

Lectura actualizada:

- la correccion de `sudo env ...` en los runners ya no queda solo como fix teorico en codigo: vuelve a estar homologada en ejecucion real
- el baseline rapido y el carril pesado regresan a verde sobre la live moderna
- el helper `scripts/run_native_dbus_wrapper_smoke_via_paramiko.php` queda disponible como carril alternativo, pero ya no es el desbloqueo inmediato necesario para seguir validando

## Actualizacion posterior: la recuperacion de la live ya queda versionada como helper reusable

El reset manual de `W4-OS-Server-DbusModern` ya habia mostrado que podia devolver una TTY util, pero esa recuperacion seguia siendo un paso operativo tacito. Ese hueco ya queda cubierto por:

- `scripts/recover_virtualbox_live_vm.php`

Contrato actual del helper:

- valida que la VM siga corriendo
- ejecuta `VBoxManage controlvm <vm> reset`
- espera a que `GuestAdditionsRunLevel` vuelva a `1`
- agrega `--settle-seconds` para dejar madurar el autologin de `tty1`
- captura evidencia visual en `guest-artifacts/vm-recovery/post-reset-console.png`

La propia validacion del helper cerro una nuance importante:

- la primera version esperaba solo el reset y el retorno de Guest Additions
- eso todavia podia dejar la consola mostrando mensajes de arranque sin prompt util
- por eso el helper queda endurecido con el settle wait explicito

Validacion homologada del helper:

- `php -l scripts/recover_virtualbox_live_vm.php`
- `php scripts/recover_virtualbox_live_vm.php --vm-name W4-OS-Server-DbusModern --wait-seconds 90 --settle-seconds 25`

Tras ese reset guiado por helper:

- `php scripts/run_native_dbus_runtime_smoke_via_live_vm.php --vm-name W4-OS-Server-DbusModern --smoke-wait-seconds 120`
  - `status = completed`
  - `runtime_smoke.ok = true`
  - `wrapper_smoke = 0`
  - `wait_loop_signal_contract = 0`
  - `method_roundtrip_smoke = 0`
  - `runtime_call_smoke = 0`

- `php scripts/run_native_dbus_heavy_validation_via_live_vm.php --vm-name W4-OS-Server-DbusModern`
  - `status = completed`
  - `launch_process.ok = true`
  - `method_probe_ordering.ok = true`
  - `heap_after_request_name.ok = true`

Lectura actualizada:

- la recuperacion de la live deja de ser conocimiento operativo disperso y pasa a ser herramienta versionada del laboratorio
- el helper no sustituye a los runners DBus; su valor es devolver una sesion headless util cuando la homologacion vuelve a degradarse
- el baseline rapido y el carril pesado siguen verdes despues de una recuperacion guiada por ese helper

## Actualizacion posterior: la recuperacion ya queda integrada como fallback dentro de los runners

El siguiente refinamiento operativo ya no agrega una herramienta nueva, sino que inserta la recuperacion dentro del flujo normal de ejecucion host-side.

Tanto:

- `scripts/run_native_dbus_runtime_smoke_via_live_vm.php`
- `scripts/run_native_dbus_heavy_validation_via_live_vm.php`

ya integran un fallback opcional sobre:

- `scripts/recover_virtualbox_live_vm.php`

Contrato actual:

- por defecto la recuperacion queda habilitada
- ambos runners exponen en su JSON:
  - `vm_recovery_enabled`
  - `recovery_wait_seconds`
  - `recovery_settle_seconds`
  - `recovery_command`
- ambos aceptan `--skip-vm-recovery` para desactivar el fallback cuando se quiera aislar el fallo operativo en crudo

Validacion homologada:

- `php -l scripts/run_native_dbus_runtime_smoke_via_live_vm.php`
- `php -l scripts/run_native_dbus_heavy_validation_via_live_vm.php`

Resultado del carril liviano:

- `php scripts/run_native_dbus_runtime_smoke_via_live_vm.php --vm-name W4-OS-Server-DbusModern --smoke-wait-seconds 120`
- cierra por la ruta normal:
  - `status = completed`
  - `runtime_smoke.ok = true`
  - `wrapper_smoke = 0`
  - `wait_loop_signal_contract = 0`
  - `method_roundtrip_smoke = 0`
  - `runtime_call_smoke = 0`

Resultado del carril pesado:

- `php scripts/run_native_dbus_heavy_validation_via_live_vm.php --vm-name W4-OS-Server-DbusModern`
- el primer intento no consigue el artefacto fresco de `method_probe_ordering`
- el runner ejecuta automaticamente `recover_virtualbox_live_vm.php`
- vuelve a correr `prepare_native_dbus_live_vm.php`
- repite el probe fallido
- cierra finalmente:
  - `status = completed`
  - `launch_process.ok = true`
  - `method_probe_ordering.ok = true`
  - `heap_after_request_name.ok = true`

Lectura actualizada:

- la recuperacion de la live ya no depende de una maniobra manual previa
- el fallback sigue siendo visible y desactivable; no queda escondido dentro de una caja negra
- el carril pesado ya demuestra en evidencia homologada que la recuperacion automatica absorbe una degradacion real de la sesion headless y devuelve el laboratorio a verde

## Actualizacion posterior: el carril pesado ya reduce la frecuencia del fallback ajustando su espera por ordering

La evidencia de la corrida anterior dejaba dos lecturas distintas:

- el fallback automatico funciona y esta bien integrado
- pero el artefacto final de `check_native_dbus_method_probe_ordering.php` no apuntaba a un probe realmente lento, sino a un margen de espera demasiado agresivo para la entrega del JSON bajo la sesion headless real

Se hizo primero una corrida de control sin fallback:

- `php scripts/run_native_dbus_heavy_validation_via_live_vm.php --vm-name W4-OS-Server-DbusModern --skip-vm-recovery --ordering-wait-seconds 75`

Resultado:

- `status = completed`
- `launch_process.ok = true`
- `method_probe_ordering.ok = true`
- `heap_after_request_name.ok = true`

Con esa evidencia, el runner ya fija:

- `ordering_wait_seconds = 75`

como valor por defecto en:

- `scripts/run_native_dbus_heavy_validation_via_live_vm.php`

Rerun homologado con defaults:

- `php scripts/run_native_dbus_heavy_validation_via_live_vm.php --vm-name W4-OS-Server-DbusModern`
- resultado:
  - `status = completed`
  - `launch_process.ok = true`
  - `method_probe_ordering.ok = true`
  - `heap_after_request_name.ok = true`
  - sin `recovery_attempted`

Lectura actualizada:

- el fallback automatico sigue siendo necesario cuando la sesion headless se degrada de verdad
- pero deja de activarse por una expectativa operativa demasiado corta del ordering lane
- el carril pesado vuelve a quedar verde por defecto con un timeout mas realista y menos propenso a ruido operacional

## Actualizacion posterior: el carril pesado ya expone telemetria real por probe

El siguiente refinamiento deja de mover tiempo de espera a ciegas y añade observabilidad directamente al runner:

- `scripts/run_native_dbus_heavy_validation_via_live_vm.php`

Nuevos campos del JSON:

- `started_at`
- `prepare_elapsed_seconds`
- `elapsed_seconds_total`
- `results.<probe>.timing.wait_seconds`
- `results.<probe>.timing.elapsed_seconds_total`
- `results.<probe>.timing.recovery_used`
- `results.<probe>.timing.attempts[]`

La primera corrida con telemetria encontro un miss transitorio:

- `heap_after_request_name` no materializo artefacto fresco en esa ejecucion

Eso no se tomo como cambio contractual del probe; se hizo un rerun inmediato sobre la misma live homologada:

- `php scripts/run_native_dbus_heavy_validation_via_live_vm.php --vm-name W4-OS-Server-DbusModern`

Resultado homologado del rerun:

- `status = completed`
- sin `recovery_attempted`
- `prepare_elapsed_seconds = 17.061`
- `elapsed_seconds_total = 60.496`

Tiempos observados por probe:

- `launch_process`
  - `elapsed_seconds_total = 14.001`
  - intento inicial con artefacto en `13.999s`
- `method_probe_ordering`
  - `elapsed_seconds_total = 16.190`
  - intento inicial con artefacto en `16.189s`
- `heap_after_request_name`
  - `elapsed_seconds_total = 13.242`
  - intento inicial con artefacto en `13.242s`

Lectura actualizada:

- el runner pesado ya no solo dice verde o rojo; ahora deja tambien duracion observada por tramo
- el miss inicial de `heap_after_request_name` queda clasificado como transitorio mientras no reaparezca en corridas sucesivas
- los siguientes ajustes de timeout ya pueden salir de datos del laboratorio en vez de intuicion operativa

## Actualizacion posterior: el runner liviano ya expone la misma clase de telemetria operativa

El siguiente refinamiento alinea la lectura del baseline rapido con la del carril pesado:

- `scripts/run_native_dbus_runtime_smoke_via_live_vm.php`

Nuevos campos del JSON:

- `started_at`
- `prepare_elapsed_seconds`
- `elapsed_seconds_total`
- `attempts[].elapsed_seconds`
- `attempts[].artifact_found`

El contrato funcional se mantiene igual:

- `runtime_smoke.ok`
- checks internos del agregador
- fallback opcional de recuperacion

Validacion homologada:

- `php -l scripts/run_native_dbus_runtime_smoke_via_live_vm.php`
- `php scripts/run_native_dbus_runtime_smoke_via_live_vm.php --vm-name W4-OS-Server-DbusModern --smoke-wait-seconds 120`

Resultado:

- `status = completed`
- `runtime_smoke.ok = true`
- `wrapper_smoke = 0`
- `wait_loop_signal_contract = 0`
- `method_roundtrip_smoke = 0`
- `runtime_call_smoke = 0`
- `prepare_elapsed_seconds = 17.810`
- intento inicial con artefacto en `15.623s`
- `elapsed_seconds_total = 33.437`

Lectura actualizada:

- baseline rapido y carril pesado ya exponen telemetria compatible
- el laboratorio ya puede comparar tiempo de preparacion, tiempo de entrega del artefacto y tiempo total sin cambiar de modelo mental entre runners
- el siguiente ajuste operativo puede apoyarse en una sola lectura para ambos carriles

## Actualizacion posterior: el baseline rapido ya baja su espera por defecto a 30s

Con la telemetria del runner liviano ya visible, el siguiente paso fue dejar de cargar un timeout heredado de `120s` que no reflejaba la latencia real observada.

Pruebas de control sin fallback:

- `php scripts/run_native_dbus_runtime_smoke_via_live_vm.php --vm-name W4-OS-Server-DbusModern --skip-vm-recovery --smoke-wait-seconds 35`
- `php scripts/run_native_dbus_runtime_smoke_via_live_vm.php --vm-name W4-OS-Server-DbusModern --skip-vm-recovery --smoke-wait-seconds 30`

Ambas corridas cerraron:

- `status = completed`
- `runtime_smoke.ok = true`
- `wrapper_smoke = 0`
- `wait_loop_signal_contract = 0`
- `method_roundtrip_smoke = 0`
- `runtime_call_smoke = 0`

Con esa evidencia, el runner ya fija:

- `smoke_wait_seconds = 30`

como valor por defecto en:

- `scripts/run_native_dbus_runtime_smoke_via_live_vm.php`

Rerun homologado con defaults:

- `php scripts/run_native_dbus_runtime_smoke_via_live_vm.php --vm-name W4-OS-Server-DbusModern`

Resultado:

- `status = completed`
- `runtime_smoke.ok = true`
- `prepare_elapsed_seconds = 17.242`
- intento inicial con artefacto en `16.577s`
- `elapsed_seconds_total = 33.821`
- sin `recovery_attempted`

Lectura actualizada:

- el baseline rapido deja de arrastrar un timeout sobredimensionado
- `30s` sigue dejando margen real frente a la latencia observada del artefacto
- el fallback permanece como red de seguridad y el override manual sigue disponible para sesiones de laboratorio mas lentas
