# W4 Linux Base

## 32 — System Api Error Model

**Documento:** `32_SYSTEM_API_ERROR_MODEL.md`  
**Bloque:** III — W4 System API  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Mantener taxonomía estable de errores independiente de mensajes Debian.

Responsable: **Equipo de contrato público**. Dependencias de implementación: D-Bus del sistema, Core y autorización. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

INVALID_ARGUMENT, DENIED, NOT_FOUND, CONFLICT, NOT_SUPPORTED, RESOURCE_BUSY, TIMEOUT, BACKEND_UNAVAILABLE, PARTIAL_FAILURE e INTERNAL.

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

**Caso de fallo específico:** No convertir todos los fallos de provider en INTERNAL ni filtrar stack privilegiado.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Mapear fixtures de errores reales y verificar código, retryable y redacción.

La evidencia debe incluir versión Debian, arquitectura, perfil, versiones del backend, entradas saneadas, estado inicial, resultado observado y estado final. La prueba debe verificar efectos en el backend y no sólo el texto presentado por `w4ctl`. Si la función no está soportada, la prueba exige una respuesta `NOT_SUPPORTED` y ausencia de cambios parciales; no se considera éxito funcional.

Para cerrar el documento en una release se vinculan requisito, implementación y evidencia en el reporte de pruebas. Esta entrega documental define esos criterios; no afirma que las pruebas del futuro sistema operativo ya se hayan ejecutado.

## 7. Referencias y navegación

- [00_PROJECT_CONTEXT.md](00_PROJECT_CONTEXT.md)
- [01_V1_SCOPE_AND_OBJECTIVES.md](01_V1_SCOPE_AND_OBJECTIVES.md)
- [02_LINUX_BASE_ARCHITECTURE.md](02_LINUX_BASE_ARCHITECTURE.md)
- [26_SYSTEM_API_ARCHITECTURE.md](26_SYSTEM_API_ARCHITECTURE.md)
- [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md)
- [31_SYSTEM_API_QUERY_MODEL.md](31_SYSTEM_API_QUERY_MODEL.md)
- [33_SYSTEM_API_SECURITY.md](33_SYSTEM_API_SECURITY.md)
- [50_PROVIDER_CONTRACT.md](50_PROVIDER_CONTRACT.md)
- [61_CONFIGURATION_ARCHITECTURE.md](61_CONFIGURATION_ARCHITECTURE.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Sobre de error y decisiones del cliente

```json
{
  "api_version": "1.0",
  "request_id": "88290aee-c1c9-48b0-a249-a056b4eb9f9a",
  "result": null,
  "error": {
    "code": "RESOURCE_BUSY",
    "message": "El gestor de paquetes está ocupado.",
    "retryable": true,
    "details": {"domain": "packages", "retry_after_ms": 3000},
    "correlation_id": "corr-c942"
  }
}
```

| Código | Interpretación | Acción permitida |
|---|---|---|
| INVALID_ARGUMENT | Tipo, rango o combinación inválida | Corregir entrada; no retry idéntico |
| DENIED | Política o autorización insuficiente | Mostrar razón saneada; no elevar automáticamente |
| AUTHENTICATION_REQUIRED | Falta desafío permitido | Interacción explícita fuera de no-interaction |
| NOT_FOUND | Recurso no visible o inexistente | Refrescar inventario según permiso |
| NOT_SUPPORTED | Función fuera de capacidades | Adaptar cliente; no ejecutar shell fallback |
| CONFLICT | Revisión, plan o clave incompatible | Reconsultar y preparar nueva intención |
| RESOURCE_BUSY | Escritor/lock legítimo activo | Espera acotada si aún no hubo admisión |
| TIMEOUT | Se agotó espera definida | Consultar operación si fue aceptada |
| BACKEND_UNAVAILABLE | Backend no accesible | Diagnóstico y retry de lectura acotado |
| PARTIAL_FAILURE | Efectos incompletos identificados | Revisar detalle y plan de reparación |
| STATE_UNKNOWN | Evidencia insuficiente sobre efecto | Reconciliar antes de cualquier repetición |
| INTERNAL | Fallo no clasificado | Correlación para soporte, sin stack público |

`retryable=true` significa que podría recuperarse la causa, no que sea seguro repetir una mutación. `retry_after_ms` orienta espera; no anula deadline ni expiración de la intención. Detalles usan allowlist por código: nombres de recursos autorizados, etapa, operación y campos de validación. No contienen environment, comando completo, contraseña, token ni respuesta cruda del backend.

Para evitar filtración, NOT_FOUND puede cubrir recursos no visibles cuando revelar existencia violaría política. Auditoría privilegiada conserva la distinción. Un error de autorización se registra con el actor real del transporte, incluso si el cliente presenta nombres falsos.
