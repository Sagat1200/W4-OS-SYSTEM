# 364 · W4 OS — Diagnostic Event Pipeline

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Observabilidad · **Responsabilidad propuesta:** Operación y observabilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Transportar diagnóstico con límites, reintentos y semántica de entrega explícitos.

## Alcance, arquitectura y decisiones

Cola local acotada por tamaño/edad, transporte autenticado y deduplicación por evento. Entrega al menos una vez propuesta; consumidores manejan duplicados.

## Componentes y flujo operativo

1. Validar evento
2. redactar
3. encolar autorizado
4. enviar lote
5. confirmar
6. retirar
7. caducar según categoría.

## Seguridad y riesgos

No bloquear función principal por backend caído ni descartar silenciosamente auditoría crítica; definir overflow por clase y alertar pérdida.

## Criterios de aceptación

Aceptar corte durante envío sin procesamiento duplicado y cola llena sin agotar raíz.

## Rendimiento y evidencia

Medir lag, volumen y descartes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [201 — Telemetry Architecture](201_W4_OS_TELEMETRY_ARCHITECTURE.md)
- [210 — Support Bundle System](210_W4_OS_SUPPORT_BUNDLE_SYSTEM.md)
- [362 — System Event Model](362_W4_OS_SYSTEM_EVENT_MODEL.md)
- [363 — Audit Log Architecture](363_W4_OS_AUDIT_LOG_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Pipeline local primero; remoto tras consentimiento o política aplicable.

---

[Anterior](363_W4_OS_AUDIT_LOG_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](365_W4_OS_HEALTH_SCORE_SYSTEM.md)
