# 008 · W4 OS — Shared Core Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Evitar que Home y Business desarrollen mecanismos incompatibles de instalación o recuperación.

## Alcance, arquitectura y decisiones

Un manifiesto w4-base fija kernel, arranque, repositorios y servicios comunes. Los metapaquetes de edición agregan aplicaciones y políticas sin sustituir bibliotecas centrales.

## Componentes y flujo operativo

Construir la base una vez, ejecutar pruebas compartidas y derivar ambas imágenes; cualquier excepción declara por qué no puede ser una configuración.

## Seguridad y riesgos

La administración empresarial no puede introducir credenciales ni agentes activos en la imagen Home. Verificar ausencia de secretos en la base distribuida.

## Criterios de aceptación

Aceptar si una corrección del núcleo produce resultados equivalentes en las dos ediciones.

## Rendimiento y evidencia

Medir duplicación de paquetes y duración de la matriz compartida.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [009 — Layer Model](009_W4_OS_LAYER_MODEL.md)
- [014 — Edition Package Profiles](014_W4_OS_EDITION_PACKAGE_PROFILES.md)
- [068 — Image Build System](068_W4_OS_IMAGE_BUILD_SYSTEM.md)

## Roadmap y condiciones de evolución

Publicar contrato de base en MVP y controlar compatibilidad en cada release.

---

[Anterior](007_W4_OS_FORK_AND_DERIVATIVE_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](009_W4_OS_LAYER_MODEL.md)
