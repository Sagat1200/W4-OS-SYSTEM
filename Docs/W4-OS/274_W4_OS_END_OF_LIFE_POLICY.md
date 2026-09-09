# 274 · W4 OS — End Of Life Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Releases y soporte de versiones · **Responsabilidad propuesta:** Ingeniería de releases  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Finalizar soporte sin dejar ambiguos riesgos, fechas y rutas de salida.

## Alcance, arquitectura y decisiones

Registro por línea y componente con fin de correcciones, disponibilidad de artefactos y migración recomendada. Fechas se publican tras aprobación operativa.

## Componentes y flujo operativo

1. Planificar fin
2. avisar con antelación definida
3. ofrecer evaluación de migración
4. mantener acceso a datos
5. archivar fuentes y evidencia.

## Seguridad y riesgos

No bloquear arranque por fin de suscripción ni borrar archivos. Repositorios archivados se identifican como sin mantenimiento, sin ocultar caducidad de seguridad.

## Criterios de aceptación

Aceptar usuario que identifica estado y ruta de transición antes del vencimiento.

## Rendimiento y evidencia

Medir equipos pendientes y cobertura de aviso.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [267 — LTS Strategy](267_W4_OS_LTS_STRATEGY.md)
- [398 — Major Version Migration](398_W4_OS_MAJOR_VERSION_MIGRATION.md)
- [400 — Long Term Maintenance](400_W4_OS_LONG_TERM_MAINTENANCE.md)

## Roadmap y condiciones de evolución

Política antes de lanzamiento comercial; plazos concretos al fijar soporte.

---

[Anterior](273_W4_OS_RELEASE_ROLLOUT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](275_W4_OS_TESTING_ARCHITECTURE.md)
