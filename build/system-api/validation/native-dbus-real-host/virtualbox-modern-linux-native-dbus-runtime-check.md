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
