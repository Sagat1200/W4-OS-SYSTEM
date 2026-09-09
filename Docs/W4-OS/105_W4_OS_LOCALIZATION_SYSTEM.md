# 105 · W4 OS — Localization System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Separar idioma, región, teclado y zona horaria para no inferir preferencias incorrectas.

## Alcance, arquitectura y decisiones

Catálogo de cadenas con contexto y formato plural; fechas y números usan bibliotecas de localización. El nombre del usuario o ubicación no determina automáticamente su idioma.

## Componentes y flujo operativo

1. Seleccionar idioma
2. cargar catálogo
3. aplicar formatos regionales elegidos
4. presentar fallback legible para cadenas no traducidas.

## Seguridad y riesgos

No concatenar mensajes que oculten objeto o consecuencia de una acción. Traducciones de seguridad requieren revisión y ejemplos.

## Criterios de aceptación

Aceptar cambio de idioma sin alterar zona horaria ni archivos personales.

## Rendimiento y evidencia

Medir cobertura de cadenas críticas y texto truncado.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [103 — Font System](103_W4_OS_FONT_SYSTEM.md)
- [106 — Language Pack System](106_W4_OS_LANGUAGE_PACK_SYSTEM.md)
- [107 — Input Method System](107_W4_OS_INPUT_METHOD_SYSTEM.md)

## Roadmap y condiciones de evolución

Español como redacción de referencia; idiomas de lanzamiento se aprueban por cobertura real.

---

[Anterior](104_W4_OS_ACCESSIBILITY_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](106_W4_OS_LANGUAGE_PACK_SYSTEM.md)
