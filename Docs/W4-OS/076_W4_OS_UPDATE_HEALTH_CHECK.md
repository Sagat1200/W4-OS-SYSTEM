# 076 · W4 OS — Update Health Check

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Determinar si una actualización puede marcarse como buena mediante señales verificables.

## Alcance, arquitectura y decisiones

Conjunto mínimo: raíz correcta, dpkg consistente, almacenamiento escribible donde corresponde, servicios esenciales y acceso a sesión. Red externa es señal opcional, no requisito universal de salud.

## Componentes y flujo operativo

1. Arranque candidato
2. ejecutar checks con plazos
3. registrar evidencia
4. confirmar si obligatorios pasan; fallos críticos mantienen estado pendiente o recuperación.

## Seguridad y riesgos

No confirmar sólo por tiempo transcurrido ni por respuesta de un servidor cloud. Diferenciar fallo de internet de fallo local introducido por actualización.

## Criterios de aceptación

Aceptar servicio esencial roto y red ausente con clasificaciones diferentes.

## Rendimiento y evidencia

Medir falsos positivos, tiempo de diagnóstico y cobertura de checks.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [029 — Startup Pipeline](029_W4_OS_STARTUP_PIPELINE.md)
- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [206 — Health Monitor](206_W4_OS_HEALTH_MONITOR.md)

## Roadmap y condiciones de evolución

Checks locales en V1; pruebas por hardware y perfil antes de automatizar fallback.

---

[Anterior](075_W4_OS_UPDATE_STAGING_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](077_W4_OS_UPDATE_ROLLBACK_SYSTEM.md)
