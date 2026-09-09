# 102 · W4 OS — Icon System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener iconografía reconocible con nombres semánticos y fallbacks.

## Alcance, arquitectura y decisiones

Usar un conjunto con licencia compatible, añadir únicamente iconos W4 necesarios y conservar nombres estándar. Diferenciar acciones destructivas por forma y texto, no sólo color.

## Componentes y flujo operativo

1. Resolver icono temático
2. fallback compatible
3. etiqueta textual cuando falta; revisar escalas pequeñas y alto contraste.

## Seguridad y riesgos

Los archivos externos se validan como recursos, no se ejecutan. Revisar marcas de terceros antes de redistribuir logos.

## Criterios de aceptación

Aceptar iconos presentes en tamaños objetivo y acción identificable sin color.

## Rendimiento y evidencia

Medir recursos ausentes y peso del paquete.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [101 — Theme System](101_W4_OS_THEME_SYSTEM.md)
- [104 — Accessibility System](104_W4_OS_ACCESSIBILITY_SYSTEM.md)
- [302 — Visual Identity](302_W4_OS_VISUAL_IDENTITY.md)

## Roadmap y condiciones de evolución

Catálogo mínimo antes de V1; crecer con componentes reales.

---

[Anterior](101_W4_OS_THEME_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](103_W4_OS_FONT_SYSTEM.md)
