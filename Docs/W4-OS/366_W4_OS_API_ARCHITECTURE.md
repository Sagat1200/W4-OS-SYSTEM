# 366 · W4 OS — API Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** APIs y protocolos · **Responsabilidad propuesta:** Arquitectura de servicios  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir APIs W4 por autoridad, compatibilidad y fallos observables.

## Alcance, arquitectura y decisiones

Separar APIs locales privilegiadas, UI y remotas; operaciones tipadas con versión, autorización, idempotencia y resultados explícitos. Los nombres de interfaces son propuestas hasta existir implementación.

## Componentes y flujo operativo

1. Autenticar sujeto
2. validar esquema
3. comprobar permiso y precondición
4. ejecutar
5. persistir resultado
6. responder con identificador.

## Seguridad y riesgos

No usar una API genérica de ejecutar comandos. Validar límites, rutas y recursos; proteger contra replay y acceso entre organizaciones.

## Criterios de aceptación

Aceptar cliente antiguo compatible, petición inválida y permiso insuficiente sin efectos.

## Rendimiento y evidencia

Medir latencia y cambios incompatibles.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [151 — Privilege Escalation Model](151_W4_OS_PRIVILEGE_ESCALATION_MODEL.md)
- [367 — System Service API](367_W4_OS_SYSTEM_SERVICE_API.md)
- [370 — Device Management API](370_W4_OS_DEVICE_MANAGEMENT_API.md)
- [399 — Backward Compatibility Policy](399_W4_OS_BACKWARD_COMPATIBILITY_POLICY.md)

## Roadmap y condiciones de evolución

Contratos mínimos en MVP; publicar SDK sólo después de estabilización.

## Sobre de operación propuesto

```json
{
  "schema_version": 1,
  "operation_id": "lab-operation-001",
  "kind": "update.prepare",
  "expected_state_revision": 12,
  "target_manifest": "lab-release-manifest",
  "idempotency_key": "lab-request-001"
}
```

Este ejemplo es un contrato ilustrativo. No define URL de producción ni implica que exista un ejecutable W4. La autenticación real se obtiene del canal y del sujeto verificado; no de un campo `is_admin` enviado por el cliente. Las referencias se resuelven dentro del ámbito permitido, sin aceptar rutas de disco arbitrarias como autoridad.

| Resultado tipado | Significado | Reintento |
|---|---|---|
| `invalid_request` | Esquema o parámetro no admitido | Corregir solicitud |
| `not_authorized` | Sujeto sin permiso | No insistir con la misma autoridad |
| `state_conflict` | Cambió la precondición | Releer y recalcular plan |
| `in_progress` | Operación conocida todavía activa | Consultar mismo identificador |
| `temporary_unavailable` | Dependencia temporalmente caída | Reintento acotado si no hubo efecto |
| `partial_failure` | Hubo efectos incompletos | Reconciliar; no repetir a ciegas |

La compatibilidad requiere definir qué campos pueden agregarse sin romper clientes y cómo se responde a versiones no admitidas. Se prueban permisos y efectos observados, además del código HTTP o respuesta D-Bus.

---

[Anterior](365_W4_OS_HEALTH_SCORE_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](367_W4_OS_SYSTEM_SERVICE_API.md)
