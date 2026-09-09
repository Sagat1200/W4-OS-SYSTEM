# 084 · W4 OS — Automatic Snapshot Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Retener suficientes estados útiles sin agotar espacio del sistema.

## Alcance, arquitectura y decisiones

Propuesta inicial conserva estado activo, último bueno y previo de operación en curso; snapshots adicionales usan límite de edad y espacio medido. Los protegidos no se eliminan automáticamente.

## Componentes y flujo operativo

1. Evaluar espacio libre y metadatos
2. limpiar sólo candidatos elegibles
3. crear nuevo snapshot o bloquear actualización con explicación.

## Seguridad y riesgos

No liberar espacio borrando copias que constituyen la única recuperación conocida. Retención de snapshots con secretos requiere revisión de privacidad.

## Criterios de aceptación

Aceptar disco casi lleno con rechazo seguro de actualización y preservación del último bueno.

## Rendimiento y evidencia

Medir frecuencia de bloqueo y espacio exclusivo retenido.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [081 — Snapshot Architecture](081_W4_OS_SNAPSHOT_ARCHITECTURE.md)
- [191 — Disk Health System](191_W4_OS_DISK_HEALTH_SYSTEM.md)
- [353 — Data Retention Policy](353_W4_OS_DATA_RETENTION_POLICY.md)

## Roadmap y condiciones de evolución

Ajustar límites con piloto; ofrecer controles avanzados con consecuencias visibles.

---

[Anterior](083_W4_OS_SYSTEM_STATE_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](085_W4_OS_ROLLBACK_ARCHITECTURE.md)
