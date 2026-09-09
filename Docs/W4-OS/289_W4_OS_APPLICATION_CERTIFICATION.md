# 289 · W4 OS — Application Certification

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Pruebas y calificación · **Responsabilidad propuesta:** Calidad y validación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Calificar aplicaciones por instalación, tareas, accesibilidad y ciclo de actualización.

## Alcance, arquitectura y decisiones

Expediente incluye formato, origen, versión, permisos, datos y soporte. La certificación W4 propuesta no equivale a aval del fabricante.

## Componentes y flujo operativo

1. Instalar limpio
2. ejecutar corpus
3. actualizar
4. probar retirada/conservación de datos
5. revisar permisos
6. publicar resultado.

## Seguridad y riesgos

No aceptar app que requiere desactivar controles globales sin riesgo explícito. Cambios de permisos invalidan parte de la calificación.

## Criterios de aceptación

Aceptar tareas críticas y actualización sin pérdida de datos de prueba.

## Rendimiento y evidencia

Medir fallos, cobertura y mantenimiento necesario.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [123 — Application Sandboxing](123_W4_OS_APPLICATION_SANDBOXING.md)
- [130 — Application Lifecycle](130_W4_OS_APPLICATION_LIFECYCLE.md)
- [264 — Cross Platform Application Support](264_W4_OS_CROSS_PLATFORM_APPLICATION_SUPPORT.md)

## Roadmap y condiciones de evolución

Apps predeterminadas primero; terceros según programa posterior.

---

[Anterior](288_W4_OS_COMPATIBILITY_CERTIFICATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](290_W4_OS_BUSINESS_CERTIFICATION.md)
