# W4 Linux Base

## 50 — Provider Contract

**Documento:** `50_PROVIDER_CONTRACT.md`  
**Bloque:** V — Providers y adapters  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Contrato de provider declara precondiciones, efectos y límites de compensación por acción.

Responsable: **Equipo de integración Debian**. Dependencias de implementación: Interfaces mantenidas de apt/dpkg, systemd, NetworkManager y udev. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Resultado incluye observed_state, backend_reference, changed y errores parciales; cancelabilidad explícita.

Modelo del dominio: provider_id, contract_major, capabilities, health, backend_version

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Descubrir manifiesto instalado, validar contrato, sondear backend y resolver un solo escritor por recurso antes de ejecutar.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

Manifiestos instalados por paquetes en /usr/share/w4/providers/; selección administrativa en /etc/w4/system/.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

No permitir providers descargados o scripts de usuario como extensiones privilegiadas; fallo de provider no autoriza fallback inseguro.

**Caso de fallo específico:** Provider que desconoce estado final devuelve unknown, nunca success supuesto.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Matar worker después de efecto y verificar que reconcile determina estado antes de reintento.

La evidencia debe incluir versión Debian, arquitectura, perfil, versiones del backend, entradas saneadas, estado inicial, resultado observado y estado final. La prueba debe verificar efectos en el backend y no sólo el texto presentado por `w4ctl`. Si la función no está soportada, la prueba exige una respuesta `NOT_SUPPORTED` y ausencia de cambios parciales; no se considera éxito funcional.

Para cerrar el documento en una release se vinculan requisito, implementación y evidencia en el reporte de pruebas. Esta entrega documental define esos criterios; no afirma que las pruebas del futuro sistema operativo ya se hayan ejecutado.

## 7. Referencias y navegación

- [00_PROJECT_CONTEXT.md](00_PROJECT_CONTEXT.md)
- [01_V1_SCOPE_AND_OBJECTIVES.md](01_V1_SCOPE_AND_OBJECTIVES.md)
- [02_LINUX_BASE_ARCHITECTURE.md](02_LINUX_BASE_ARCHITECTURE.md)
- [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md)
- [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md)
- [49_PROVIDER_ARCHITECTURE.md](49_PROVIDER_ARCHITECTURE.md)
- [51_PROVIDER_REGISTRY.md](51_PROVIDER_REGISTRY.md)
- [61_CONFIGURATION_ARCHITECTURE.md](61_CONFIGURATION_ARCHITECTURE.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Interfaz lógica de provider

```text
probe() -> BackendInfo(version, available, reason)
capabilities() -> CapabilitySet(operations, limits, cancelability)
query(context, selector) -> Observation(data, revision, observed_at)
plan(context, intent, observation) -> Plan(effects, prerequisites, limits)
execute(context, plan, operation_id) -> ExecutionReference
observe(context, execution_reference) -> ProgressOrResult
reconcile(context, operation_record) -> ObservedOutcome
cancel(context, execution_reference) -> CancelResult
```

El método puede responder unsupported si la capacidad no aplica; por ejemplo, un inventario no necesita execute. Los servicios no construyen una operación ficticia para cada consulta. Contexto es inmutable y procede del Core; el provider no acepta un UID arbitrario en parámetros como prueba de autorización.

| Invariante | Obligación del adapter |
|---|---|
| Ownership | Confirmar que el backend administra el recurso |
| Versión | Rechazar contrato major incompatible antes de cargar |
| Entradas | Resolver IDs a recursos actuales y validar límites |
| Efectos | No modificar recursos fuera del plan sin reportar cambio requerido |
| Concurrencia | Respetar locks/backend y coordinación del servicio |
| Seguridad | Usar argv o API tipada; no shell concatenado |
| Recuperación | Dar referencia de job cuando exista y distinguir desconocido |
| Observación | Consultar backend después del efecto, no asumir éxito |

Ejecutores por subprocess usan ejecutable de ruta fija, argumentos validados, entorno mínimo, descriptores cerrados y stdout/stderr con límite. No se mata indiscriminadamente un proceso que puede estar modificando una base de paquetes. Un deadline de recepción no se reutiliza como permiso para interrumpir una operación crítica.

Un provider no registra sus propias políticas de producto. Puede rechazar por seguridad del mecanismo: disco montado, ABI inválida o lock ocupado. El servicio transforma ese rechazo a error público y conserva la causa. Los adapters de prueba deben poder observar si se intentó una mutación: esto permite demostrar que una denegación anterior produjo cero efectos.
