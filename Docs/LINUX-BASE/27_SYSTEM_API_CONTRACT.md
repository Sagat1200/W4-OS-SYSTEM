# W4 Linux Base

## 27 — System Api Contract

**Documento:** `27_SYSTEM_API_CONTRACT.md`  
**Bloque:** III — W4 System API  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Fijar contrato lógico V1 y binding D-Bus detallado en el suplemento de este documento.

Responsable: **Equipo de contrato público**. Dependencias de implementación: D-Bus del sistema, Core y autorización. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Solicitudes validan versión, método, parámetros y límites; comandos largos devuelven operation_id persistente.

Modelo del dominio: api_version, request_id, operation_id, revision, result, error

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Resolver identidad del emisor, validar mensaje, autorizar acción sobre recurso, despachar y observar resultado del provider.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

API mayor 1; límites iniciales: 256 KiB por payload W4, 100 elementos por página y máximo 500; sin listener TCP por defecto.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

La identidad declarada por el cliente no es autenticación. Filtrar listados y señales por permiso de lectura.

**Caso de fallo específico:** Clave idempotente reutilizada con otro payload devuelve CONFLICT antes de ejecutar.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Repetir petición tras pérdida de conexión y verificar un único efecto y mismo identificador.

La evidencia debe incluir versión Debian, arquitectura, perfil, versiones del backend, entradas saneadas, estado inicial, resultado observado y estado final. La prueba debe verificar efectos en el backend y no sólo el texto presentado por `w4ctl`. Si la función no está soportada, la prueba exige una respuesta `NOT_SUPPORTED` y ausencia de cambios parciales; no se considera éxito funcional.

Para cerrar el documento en una release se vinculan requisito, implementación y evidencia en el reporte de pruebas. Esta entrega documental define esos criterios; no afirma que las pruebas del futuro sistema operativo ya se hayan ejecutado.

## 7. Referencias y navegación

- [00_PROJECT_CONTEXT.md](00_PROJECT_CONTEXT.md)
- [01_V1_SCOPE_AND_OBJECTIVES.md](01_V1_SCOPE_AND_OBJECTIVES.md)
- [02_LINUX_BASE_ARCHITECTURE.md](02_LINUX_BASE_ARCHITECTURE.md)
- [26_SYSTEM_API_ARCHITECTURE.md](26_SYSTEM_API_ARCHITECTURE.md)
- [28_SYSTEM_API_VERSIONING.md](28_SYSTEM_API_VERSIONING.md)
- [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md)
- [50_PROVIDER_CONTRACT.md](50_PROVIDER_CONTRACT.md)
- [61_CONFIGURATION_ARCHITECTURE.md](61_CONFIGURATION_ARCHITECTURE.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Binding público propuesto

La [especificación D-Bus](https://dbus.freedesktop.org/doc/dbus-specification.html) proporciona transporte, tipos y nombres de conexión. W4 define encima su contrato de aplicación; no modifica el protocolo del bus.

| Elemento | Valor de esta edición |
|---|---|
| Bus | System bus local |
| Nombre bien conocido propuesto | `org.w4.System1` |
| Object path | `/org/w4/System1` |
| Interfaz | `org.w4.System1.Manager` |
| `Call` | Entrada `request_json: s`; salida `response_json: s` |
| `GetCapabilities` | Sin argumentos; devuelve JSON con el mismo sobre |
| `Subscribe` | Entrada filtros JSON; salida subscription_id y cursor de inicio |
| `Unsubscribe` | Entrada subscription_id; resultado idempotente |
| `Event` | Señal dirigida al nombre único suscrito; argumento event_json |

Los nombres son una propuesta interna, no una afirmación de propiedad de dominio o registro upstream. La implementación deberá comprobar conflictos antes de publicarlos. La envoltura JSON facilita un único esquema para CLI y clientes; los bindings futuros no cambian el modelo lógico. No se aceptan llamadas sin respuesta para mutaciones.

Errores de conexión/autenticación D-Bus permanecen errores del transporte. Cuando se puede decodificar una solicitud W4, la respuesta usa el sobre W4 incluso ante fallo de dominio. `GetCapabilities` no expone secretos; los datos de capacidades sujetos a autorización se filtran por emisor.

## 9. Solicitud y respuesta

```json
{
  "api_version": "1.0",
  "request_id": "9ceaf101-304a-4f23-9780-387e006eab82",
  "method": "services.start",
  "params": {"unit": "ssh.service"},
  "idempotency_key": "8d7d3cd2-1076-41ce-8322-8be2d91bb8bd",
  "expected_revision": "r-17",
  "allow_interaction": false,
  "deadline_ms": 5000
}
```

`request_id` es UUID de correlación por intento. `idempotency_key` es UUID de intención para comandos; se conserva en reintentos y es obligatorio en mutaciones. La identidad no se envía en este objeto: la resuelve el servicio desde el bus. `expected_revision` es obligatorio cuando se modifica un recurso versionado; para instalaciones por plan la revisión está dentro del plan. Las consultas no requieren clave idempotente.

```json
{
  "api_version": "1.0",
  "request_id": "9ceaf101-304a-4f23-9780-387e006eab82",
  "result": {
    "operation_id": "op-982f13",
    "state": "queued"
  },
  "error": null
}
```

`result` y `error` son mutuamente excluyentes salvo `result` nulo; una respuesta de error puede incluir `operation_id` dentro de `error.details`. Un éxito de admisión sólo confirma persistencia y cola. El éxito administrativo se obtiene consultando el trabajo terminal.

JSON debe ser UTF-8, sin claves duplicadas, profundidad máxima 16, strings individuales hasta 16 KiB y payload total hasta 256 KiB. Los secretos no viajan como strings de parámetros: se utilizan referencias a un mecanismo de credenciales autorizado. Arrays y objetos tienen límites por método. Los bytes y contadores de 64 bits se representan como cadenas decimales si pueden exceder el entero exacto de JavaScript; los esquemas lo declaran expresamente. Timestamps usan RFC 3339 UTC; duraciones especifican unidad y medición monotónica.

## 10. Catálogo obligatorio de métodos lógicos

| Métodos | Parámetros principales | Resultado | Permiso |
|---|---|---|---|
| `system.info`, `system.health` | Ninguno | Versión/perfil/capacidades o salud con razones | Lectura local filtrada |
| `hardware.list`, `storage.devices`, `users.list` | limit, cursor, filtros | items, next_cursor, revision, observed_at | Lectura del dominio |
| `network.status`, `network.interfaces` | Filtro opcional | Estado por conexión/interfaz | Lectura de red |
| `packages.search`, `packages.info`, `packages.list_installed` | query o name+architecture | Recursos de paquete | Lectura de paquetes |
| `packages.plan` | acción install/remove/upgrade, selección, conffile_policy | Plan y digest | Lectura privilegiada del plan según datos |
| `packages.apply` | plan_id, plan_hash, clave idempotente | operation_id | `org.w4.packages.modify` |
| `updates.check`, `updates.list` | refresh opcional en check | Trabajo de refresco o catálogo | Consulta/refresco según política |
| `updates.plan`, `updates.apply` | selección o plan_id+hash | Plan u operación | `org.w4.updates.modify` al aplicar |
| `services.list`, `services.status` | unit opcional/obligatorio respectivamente | Estado normalizado | Lectura de servicios |
| `services.start/stop/restart/enable/disable` | unit y revisión | operation_id | Acción por unidad |
| `network.plan`, `network.apply` | connection_uuid/config o plan_id+hash | Plan/operación con salvaguarda | `org.w4.network.modify` |
| `storage.mount`, `storage.unmount` | resource_id, destino/opciones permitidas | operation_id | Acción storage específica |
| `users.create`, `users.lock`, `groups.modify` | Parámetros tipados de identidad | operation_id | Acción identity específica |
| `security.status`, `telemetry.status`, `recovery.status` | Ninguno | Estado y cobertura | Lectura del dominio |
| `operations.get` | operation_id | Estado, progreso, resultado/error | Dueño de operación o administrador autorizado |
| `operations.cancel` | operation_id | cancel_requested y cancelable | Dueño con permiso para acción |

El catálogo se publica con schemas por método. Las funciones que la implementación no soporte devuelven `NOT_SUPPORTED`; esto no excusa omitir funciones obligatorias al cerrar V1. No hay un método `exec`, `shell` o `run_script` genérico.

## 11. Planes y deduplicación

El servicio genera el plan y su hash sobre representación canónica; el cliente devuelve el digest recibido y no lo recalcula. El plan está ligado a actor, selección, versiones observadas, política, provider y efectos. Caducidad inicial: cinco minutos, ajustable por dominio. La autorización de apply es actual, no heredada de plan. Para comandos simples sin plan explícito el servicio valida intención y precondiciones en el mismo handler.

Ledger único por `(principal_scope, method, idempotency_key)`. Misma clave y mismo digest devuelve operación previa; clave igual con otros parámetros produce `CONFLICT`. Registros no terminales se conservan hasta resolución; terminales al menos treinta días por defecto. Tras expirar deduplicación, una clave antigua ya no garantiza recuperación del resultado: el cliente debe reconciliar intención y estado antes de enviar otra. No se promete exactly-once sobre mecanismos externos.

## 12. Trabajo y eventos

```text
queued → running → succeeded
                 → failed       (sin éxito completo)
                 → partial      (efectos identificados incompletos)
                 → unknown      (requiere reconciliación)
queued → cancelled
running → needs_attention → running / failed / partial
unknown → succeeded / failed / partial / needs_attention
```

`cancel_requested` es atributo, no estado terminal; sólo se convierte en cancelled cuando el provider garantiza que no quedan efectos por completar. Una operación crítica de dpkg normalmente no es cancelable de forma segura. `unknown` es observable y no autoriza reejecutar.

Suscripciones tienen cuota inicial de 1000 eventos pendientes por cliente; al excederla se envía señal de resync o se cierra con causa. No son colas durables. Evento: event_id UUID, boot_id, sequence decimal, domain, kind, resource_id, revision, observed_at y data filtrado. Consultas y señales aplican los mismos permisos. No se emiten broadcasts con datos privados.

## 13. Vectores de aceptación mínimos

1. Mismo comando/clave dos veces: mismo operation_id, una sola admisión.
2. Misma clave con otra unidad: CONFLICT y ningún segundo efecto.
3. Usuario sin permiso: DENIED, cero llamadas mutantes al provider.
4. Desconexión después de aceptación: consulta recupera trabajo.
5. Crash después del efecto antes del commit: unknown/reconciliación, nunca reintento ciego.
6. Revisión obsoleta o plan vencido: CONFLICT antes de modificar.
7. Evento perdido: consulta reconstruye estado actual.
8. Cuenta distinta: no lee operación privada aunque conozca operation_id.
