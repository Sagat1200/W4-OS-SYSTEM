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
