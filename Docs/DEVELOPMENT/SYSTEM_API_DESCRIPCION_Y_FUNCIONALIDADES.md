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

Metodo principal:

- `system.dbus-native-ownership`

### 4.9 Integracion con operaciones del sistema

Ademas de la vertical D-Bus, la `System API` forma parte de una superficie mayor de operacion que reutiliza contratos de plataforma como:

- `profiles.list`
- `profiles.get`
- `builds.validate`
- `live.inspect`

Eso la conecta con el resto del producto y no la deja como una API aislada del sistema real.

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

## 8. Resumen ejecutivo

La `System API` es la API local de administracion y control de `W4 OS`.

Su funcion es exponer capacidades del sistema como contratos programaticos reutilizables, especialmente en la vertical D-Bus nativa. Ya cubre diagnostico, activacion, servicio, startup, observacion, coupling y ownership, y actua como la base del control plane local del sistema.
