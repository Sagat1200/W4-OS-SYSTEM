# 369 · W4 OS — Update API

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** APIs y protocolos · **Responsabilidad propuesta:** Arquitectura de servicios  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ofrecer control de actualizaciones con progreso durable y operaciones idempotentes.

## Alcance, arquitectura y decisiones

Contrato propuesto: consultar plan, preparar, programar, consultar operación y solicitar recuperación permitida. Estado incluye destino, etapa, reinicio y resultado; no prometer cancelación en cualquier fase.

## Componentes y flujo operativo

1. Crear solicitud con clave idempotente
2. devolver operation_id
3. observar
4. cancelar sólo si etapa lo admite
5. consultar resultado tras reinicio.

## Seguridad y riesgos

No permitir downgrade u origen arbitrario desde API. Revalidar autorización y plan antes de modificar; pausa no interrumpe dpkg de forma insegura.

## Criterios de aceptación

Aceptar petición duplicada y cliente desconectado con una sola actualización.

## Rendimiento y evidencia

Medir exactitud de progreso y reconciliación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [072 — Update Engine](072_W4_OS_UPDATE_ENGINE.md)
- [075 — Update Staging System](075_W4_OS_UPDATE_STAGING_SYSTEM.md)
- [366 — API Architecture](366_W4_OS_API_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

API local V1; consumo remoto por agente calificado después.

---

[Anterior](368_W4_OS_CONTROL_CENTER_API.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](370_W4_OS_DEVICE_MANAGEMENT_API.md)
