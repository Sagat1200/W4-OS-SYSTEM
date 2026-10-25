# W4 OS · System API

Documento de referencia corto para explicar que es la `System API`, para que sirve y cuales son sus funcionalidades principales dentro de `W4 OS System`.

## 1. Que es la System API

La `System API` es la capa de gestion programatica del sistema `W4 OS`.

No es solo una coleccion de scripts sueltos ni una API pensada para exponer datos genericos. Su objetivo es convertir las capacidades operativas del sistema en contratos estables de lectura y ejecucion para que puedan ser consumidos por:

- CLI local,
- bridges y wrappers internos,
- procesos automatizados,
- runtime host/launcher,
- y la integracion D-Bus bajo `org.w4.System1`.

En la arquitectura actual, la `System API` funciona como la fachada publica que unifica diagnostico, activacion, observacion y control del runtime del sistema.

## 2. Para que sirve

La `System API` sirve para:

- exponer capacidades del sistema de forma uniforme,
- reutilizar contratos operativos ya estabilizados en `w4ctl`,
- preparar y diagnosticar el runtime D-Bus,
- arrancar y observar el servicio nativo del sistema,
- validar el acople con la extension `dbus`,
- intentar materializar el ownership de `org.w4.System1`,
- y dejar una superficie lista para automatizacion local y futura administracion del sistema.

En terminos practicos, es el `control plane` local de `W4 OS`.

## 3. Rol dentro del proyecto

Dentro de `W4 OS System`, la `System API` ocupa la capa de gestion entre:

1. la base operativa del sistema,
2. la CLI `w4ctl`,
3. los toolkits internos del runtime,
4. y la superficie de integracion D-Bus.

Eso significa que no duplica la logica del producto: la organiza y la expone como contratos reutilizables.

## 4. Funcionalidades principales

### 4.1 Capabilities y descubrimiento

La `System API` puede describir:

- que transportes soporta,
- que scripts y bridges existen,
- que metodos publica,
- y cual es el estado actual del runtime local.

Metodo principal:

- `system.capabilities`

### 4.2 Diagnostico del runtime D-Bus

Puede inspeccionar el entorno necesario para la vertical D-Bus:

- disponibilidad de runtime,
- capacidades del host,
- compatibilidad de PHP,
- presencia de `pecl`,
- extension `dbus`,
- y evidencia de fallos de build o de coupling.

Metodo principal:

- `system.dbus-runtime`

### 4.3 Preparacion y activacion del runtime

La `System API` puede explicar si el runtime esta listo, que bloquea su activacion y cual es la siguiente accion operativa.

Tambien prepara el tramo de activacion sin fingir que el runtime ya esta completo cuando el host aun no cumple el contrato.

Metodo principal:

- `system.dbus-activation`

### 4.4 Servicio nativo D-Bus

La API publica el contrato del servicio nativo que se quiere exponer sobre:

- bus `system`,
- name `org.w4.System1`,
- object path `/org/w4/System1`,
- interface `org.w4.System1.Manager`.

Metodo principal:

- `system.dbus-native-service`

### 4.5 Startup del runtime nativo

La `System API` puede arrancar el servicio nativo de forma contractual y reportar:

- backend seleccionado,
- estado del arranque,
- artefactos,
- bloqueos,
- y siguiente accion.

Metodo principal:

- `system.dbus-native-startup`

### 4.6 Observacion del servicio nativo

Una vez iniciado el servicio, la API puede observar su estado sin tener que pasar directamente por comandos internos del adapter.

Para evitar lecturas ambiguas del runtime, la observacion distingue dos planos:

- `pid = 0` sigue significando que el servicio no puede considerarse vivo ni iniciado,
- `pid > 0` persistido puede actuar como senal contractual suficiente para continuar el carril de validacion y ownership,
- y la comprobacion de proceso realmente vivo sigue siendo una evidencia mas fuerte cuando el host puede observarla directamente.

En otras palabras: la observacion no debe degradar un ownership ya validado solo porque el escenario sea sintetico o porque el proceso que materializo el contrato no siga vivo en el mismo host de prueba.

Metodo principal:

- `system.dbus-native-observation`

### 4.7 Coupling del backend nativo

La API puede diagnosticar el acople real entre el runtime y la extension `dbus`, incluyendo:

- extension cargada o no,
- clases resolubles `Dbus/DBus`,
- compatibilidad de la API,
- readiness del backend,
- y acciones de recuperacion.

Metodo principal:

- `system.dbus-native-coupling`

### 4.8 Ownership de `org.w4.System1`

La capa mas avanzada de la vertical nativa permite intentar materializar el ownership real del bus y reportar de forma honesta:

- si el ownership fue validado,
- si el host sigue bloqueado,
- o que paso concreto falta cerrar.

En el modo de validacion sintetica, el ownership tambien puede materializarse cuando el `startup` ya fue aceptado y existen `phase overrides` o un driver nativo configurado, incluso si el `startup` previo persistio `pid = 0`. En ese caso, el proceso que ejecuta la materializacion debe persistir un PID positivo propio para dejar trazabilidad coherente del intento validado.

Metodo principal:

- `system.dbus-native-ownership`

### 4.9 Integracion con operaciones del sistema

Ademas de la vertical D-Bus, la `System API` forma parte de una superficie mayor de operacion que reutiliza contratos de plataforma como:

- `profiles.list`
- `profiles.get`
- `builds.validate`
- `live.inspect`

Eso la conecta con el resto del producto y no la deja como una API aislada del sistema real.

### 4.10 Superficie de paquetes

La `System API` ya expone un primer frente operativo de paquetes del host cuando el entorno es compatible:

- `packages.search`
- `packages.info`
- `packages.list_installed`
- `packages.plan`
- `packages.apply`

La capa de lectura reutiliza el backend real del sistema (`apt-cache` y `dpkg-query`) cuando esta disponible y, cuando no lo esta, devuelve `NOT_SUPPORTED` de forma honesta en lugar de fingir inventario o metadata.

Para el carril mutante, la API ya puede:

- construir un plan canonico con `plan_id`, `plan_hash`, seleccion y `conffile_policy`,
- persistir opcionalmente ese plan a disco para reutilizarlo entre llamadas,
- ejecutar `packages.apply` validando que `plan_id` y `plan_hash` coincidan con el plan suministrado,
- rechazar `packages.apply` cuando `expected_catalog_revision` no coincide con `catalog_revision` del plan,
- y reutilizar la operacion previa cuando entra el mismo `idempotency_key` con el mismo digest de intencion.

Lo que sigue pendiente para cerrar V1 en este dominio no es la existencia del contrato, sino endurecer el ledger durable de `idempotency_key` mas alla del store local actual, la reconciliacion posterior y la evidencia end-to-end sobre un host Linux real.

### 4.11 Superficie de servicios

La `System API` ya expone un frente operativo inicial de servicios del host:

- `services.list`
- `services.status`
- `services.start`
- `services.stop`
- `services.restart`
- `services.enable`
- `services.disable`

En esta etapa la lectura y las mutaciones basicas se apoyan en `systemctl`, con degradacion honesta a `NOT_SUPPORTED` fuera de Linux o cuando el backend requerido no existe en el host. Lo que sigue pendiente para cerrar V1 no es la existencia del dominio, sino endurecer idempotencia, revisionado y validacion end-to-end sobre hosts Linux reales.

En el frente mutante, `services.start/stop/restart/enable/disable` ya aceptan tambien `idempotency_key` opcional. Cuando la misma clave vuelve a entrar con la misma intencion sobre la misma unidad, la API reutiliza la operacion previa; si la clave reaparece con otra unidad o con otra intencion logica, responde `CONFLICT`.

Ademas, `services.status` ya publica una `revision` observable del estado normalizado del servicio, y las mutaciones `services.*` aceptan `expected_revision` opcional. Si la revision observada antes de mutar no coincide con la esperada por el cliente, la API responde `CONFLICT` en lugar de ejecutar a ciegas sobre un estado que ya cambio.

### 4.12 Operaciones consultables

La `System API` ya expone tambien una primera capacidad de consulta de operaciones:

- `operations.get`
- `operations.cancel`

Con este corte, las mutaciones que ya generan `operation_id` en la superficie publica del sistema dejan una operacion local consultable en `build/system-api/operations/`, al menos para:

- `packages.apply`
- `services.start`
- `services.stop`
- `services.restart`
- `services.enable`
- `services.disable`

El objetivo de este frente no es simular un scheduler complejo, sino cerrar la semantica minima de V1: una mutacion ya no devuelve un `operation_id` aislado, sino una referencia que puede reconsultarse para observar estado terminal, error saneado y resultado persistido.

En este corte, `operations.cancel` es deliberadamente honesta: si una operacion ya termino o el provider no ofrece cancelacion segura, la respuesta deja `cancel_requested = false` y `cancelable = false` en lugar de fingir una cancelacion efectiva.

La deduplicacion basica por `idempotency_key` ya cubre tanto `packages.apply` como las mutaciones de servicios. Todavia no es un ledger distribuido ni durable a nivel de release, pero ya evita la readmision de la misma intencion dentro del store local actual.

## 5. Componentes principales de la vertical

La implementacion actual se apoya en varias piezas:

- `SystemApiToolkit`
- `SystemApiDbusRuntimeProbeToolkit`
- `SystemApiDbusRuntimeActivationToolkit`
- `SystemApiDbusRuntimeBackendToolkit`
- `SystemApiDbusRuntimeAdapter`
- `SystemApiDbusRuntimeHost`
- `SystemApiDbusRuntimeLauncher`
- `SystemApiDbusBindingToolkit`
- `SystemApiPackagesToolkit`
- `SystemApiServicesToolkit`
- `SystemApiOperationsToolkit`

Estas piezas permiten separar:

- diagnostico,
- activacion,
- host,
- launcher,
- bundle empaquetado,
- y evidencia operativa del runtime.

## 6. Valor tecnico de la System API

La `System API` aporta valor porque:

- estabiliza contratos de gestion del sistema,
- reduce logica dispersa en scripts aislados,
- hace observable el estado real del runtime,
- genera handoff y diagnosticos accionables,
- y prepara el cierre V1 de la integracion D-Bus nativa.

## 7. Estado actual

La `System API` ya no esta en fase conceptual.

Hoy puede considerarse una superficie real y util de gestion local, con la vertical D-Bus en cierre avanzado. El punto que todavia bloquea su cierre completo hacia V1 no es de arquitectura, sino operativo:

- validar el ownership real de `org.w4.System1` sobre un host Linux compatible,
- con `PHP 8.x`,
- y una extension `dbus` realmente viable para el stack objetivo.

## 8. Estrategia de transicion de lenguaje

La estrategia vigente para la `System API` queda definida en dos etapas:

1. **cerrar la V1** de `W4 OS Home`, `W4 OS Business` y `W4 OS Server` manteniendo la base actual en PHP,
2. **migrar despues** la `System API` y otras estructuras de plataforma hacia Rust.

En consecuencia:

- durante el cierre V1 se autoriza parchear o forkear `pecl/DBus` para `PHP 8.4`,
- esa ruta se considera una solucion transitoria para no bloquear la validacion de producto,
- y no debe interpretarse como la arquitectura definitiva del runtime nativo Linux.

La direccion oficial post-V1 es que la `System API` deje de apoyarse estructuralmente en PHP para el runtime base de Linux y evolucione hacia una implementacion principal en Rust, alineada con la evolucion de `W4 Linux Base` y otras piezas nucleares del proyecto.

## 9. Resumen ejecutivo

La `System API` es la API local de administracion y control de `W4 OS`.

Su funcion es exponer capacidades del sistema como contratos programaticos reutilizables, especialmente en la vertical D-Bus nativa. Ya cubre diagnostico, activacion, servicio, startup, observacion, coupling y ownership, y suma un primer catalogo operativo para paquetes y servicios del host, incluyendo `plan/apply` de paquetes, mutaciones basicas de servicios y consulta local de operaciones por `operation_id`. Con ello actua como la base del control plane local del sistema.

Para el cierre V1, esa base sigue en PHP y puede apoyarse temporalmente en un fork o patch de `pecl/DBus`. Una vez validada la V1 de las ediciones del sistema, la direccion oficial del proyecto es migrar la `System API` y la base Linux estructural hacia Rust.
