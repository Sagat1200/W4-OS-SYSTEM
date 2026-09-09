# 365 · W4 OS — Health Score System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Observabilidad · **Responsabilidad propuesta:** Operación y observabilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Resumir salud sin ocultar fallos críticos detrás de una puntuación.

## Alcance, arquitectura y decisiones

Estado categórico por subsistema es primario; score numérico opcional requiere fórmula transparente, cobertura y datos faltantes. Un fallo crítico tiene prioridad sobre promedio.

## Componentes y flujo operativo

1. Recoger checks
2. marcar frescura
3. evaluar dependencias
4. mostrar causas
5. recomendar acción verificable.

## Seguridad y riesgos

No usar puntuación como certificación de seguridad ni penalizar ausencia de telemetría opcional. Evitar falsa precisión con pocas señales.

## Criterios de aceptación

Aceptar fallo de disco visible aunque otros checks pasen y datos faltantes explícitos.

## Rendimiento y evidencia

Medir falsos positivos y utilidad de recomendaciones.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [116 — Security Settings](116_W4_OS_SECURITY_SETTINGS.md)
- [191 — Disk Health System](191_W4_OS_DISK_HEALTH_SYSTEM.md)
- [206 — Health Monitor](206_W4_OS_HEALTH_MONITOR.md)
- [361 — Observability Architecture](361_W4_OS_OBSERVABILITY_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Estados explicables V1; score sólo después de validar correlación con incidentes.

---

[Anterior](364_W4_OS_DIAGNOSTIC_EVENT_PIPELINE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](366_W4_OS_API_ARCHITECTURE.md)
