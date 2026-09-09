# 003 · W4 OS — Product Family

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Delimitar una familia de dos ediciones y evitar multiplicar ciclos de desarrollo.

## Alcance, arquitectura y decisiones

W4 Linux Base es una plataforma compartida, no una tercera edición de consumo. Home y Business son perfiles declarativos sobre la misma versión del núcleo; Developer es un perfil opcional.

## Componentes y flujo operativo

Seleccionar base versionada, aplicar manifiesto de edición y producir artefacto identificado. Comparar diferencias de paquetes y políticas antes de publicar.

## Seguridad y riesgos

Una edición no debe recibir un nivel inferior de correcciones de seguridad. Los derechos comerciales de soporte se separan del funcionamiento local.

## Criterios de aceptación

Aceptar si las imágenes del mismo lanzamiento comparten exactamente versiones del núcleo común y sus diferencias coinciden con la lista autorizada.

## Rendimiento y evidencia

Comparar versiones de paquetes compartidos entre imágenes y tamaño incremental de cada edición; cualquier diferencia de núcleo necesita justificación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [008 — Shared Core Architecture](008_W4_OS_SHARED_CORE_ARCHITECTURE.md)
- [013 — Edition Differentiation Model](013_W4_OS_EDITION_DIFFERENTIATION_MODEL.md)
- [014 — Edition Package Profiles](014_W4_OS_EDITION_PACKAGE_PROFILES.md)

## Roadmap y condiciones de evolución

Lanzar dos ediciones; nuevas variantes requieren demanda demostrada, presupuesto y ADR.

---

[Anterior](002_W4_OS_PRODUCT_VISION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](004_W4_OS_ARCHITECTURE.md)
