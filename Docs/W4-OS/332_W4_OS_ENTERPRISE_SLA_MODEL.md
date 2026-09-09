# 332 · W4 OS — Enterprise SLA Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Soporte · **Responsabilidad propuesta:** Operación de soporte  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Diseñar SLA como contrato medible sin fijar promesas aún no financiadas.

## Alcance, arquitectura y decisiones

Plantilla distingue respuesta, restauración y resolución; severidad por impacto y alcance, horario, exclusiones, pausas y método de medición. Valores numéricos quedan por acuerdo comercial.

## Componentes y flujo operativo

1. Definir servicio
2. medir piloto
3. calcular capacidad
4. acordar objetivos
5. instrumentar
6. revisar incumplimientos.

## Seguridad y riesgos

No equiparar respuesta automática con atención técnica ni garantizar disponibilidad del sistema local por uptime del portal. Exclusiones deben ser visibles y razonables.

## Criterios de aceptación

Aceptar cálculo reproducible sobre incidentes de prueba y responsable de cada compromiso.

## Rendimiento y evidencia

Medir capacidad antes de fijar cifras.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [267 — LTS Strategy](267_W4_OS_LTS_STRATEGY.md)
- [329 — Support Model](329_W4_OS_SUPPORT_MODEL.md)
- [331 — Business Support](331_W4_OS_BUSINESS_SUPPORT.md)
- [348 — Business Continuity](348_W4_OS_BUSINESS_CONTINUITY.md)

## Roadmap y condiciones de evolución

Modelo propuesto; contrato sólo tras validación operativa y revisión jurídica.

---

[Anterior](331_W4_OS_BUSINESS_SUPPORT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](333_W4_OS_REMOTE_SUPPORT_POLICY.md)
