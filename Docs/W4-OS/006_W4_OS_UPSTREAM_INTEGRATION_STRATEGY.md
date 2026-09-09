# 006 · W4 OS — Upstream Integration Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Absorber mejoras upstream con una superficie pequeña de modificaciones W4.

## Alcance, arquitectura y decisiones

Prioridad: configuración empaquetada, extensión mediante interfaces públicas y finalmente parche. Cada cambio registra origen, responsable, licencia y condición de retirada.

## Componentes y flujo operativo

Detectar nueva versión, clasificar conflicto con cambios W4, ejecutar integración y promover el mismo artefacto probado.

## Seguridad y riesgos

Un parche local puede bloquear una corrección urgente. Mantener una ruta de desactivación del cambio W4 cuando su valor no compense el riesgo.

## Criterios de aceptación

Aceptar si cada divergencia tiene prueba y vínculo con incidencia upstream.

## Rendimiento y evidencia

Medir antigüedad de parches y demora de integración de seguridad.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [007 — Fork And Derivative Strategy](007_W4_OS_FORK_AND_DERIVATIVE_STRATEGY.md)
- [394 — Patch Management](394_W4_OS_PATCH_MANAGEMENT.md)
- [396 — Upstream Contribution Policy](396_W4_OS_UPSTREAM_CONTRIBUTION_POLICY.md)

## Roadmap y condiciones de evolución

Inventariar divergencias en V1 y reducirlas antes de cada transición mayor.

---

[Anterior](005_W4_OS_DEBIAN_BASE_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](007_W4_OS_FORK_AND_DERIVATIVE_STRATEGY.md)
