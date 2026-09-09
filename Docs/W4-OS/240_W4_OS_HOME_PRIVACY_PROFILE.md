# 240 · W4 OS — Home Privacy Profile

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Experiencia Home · **Responsabilidad propuesta:** Producto Home  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Aplicar privacidad Home con valores concretos y visibles.

## Alcance, arquitectura y decisiones

Telemetría opcional apagada; cuenta local disponible; historial y sincronización pertenecen a cada aplicación. Panel resume servicios vinculados y permisos sensibles.

## Componentes y flujo operativo

1. Revisar permisos
2. autorizar propósito específico
3. comprobar estado
4. revocar
5. confirmar que no hay nuevas transmisiones del flujo.

## Seguridad y riesgos

No afirmar que no existe ninguna comunicación de red: actualizaciones y aplicaciones tienen sus propios servicios. Documentar cada flujo incluido por W4.

## Criterios de aceptación

Aceptar arranque sin telemetría W4 y desvinculación que retira tokens.

## Rendimiento y evidencia

Medir conexiones opcionales activas sin consentimiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [207 — Privacy Model](207_W4_OS_PRIVACY_MODEL.md)
- [209 — Opt In Telemetry](209_W4_OS_OPT_IN_TELEMETRY.md)
- [233 — Home Account System](233_W4_OS_HOME_ACCOUNT_SYSTEM.md)

## Roadmap y condiciones de evolución

Baseline de privacidad MVP; revisión por cada nuevo servicio.

---

[Anterior](239_W4_OS_HOME_MEDIA_PROFILE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](241_W4_OS_W4_ACCOUNT_INTEGRATION.md)
