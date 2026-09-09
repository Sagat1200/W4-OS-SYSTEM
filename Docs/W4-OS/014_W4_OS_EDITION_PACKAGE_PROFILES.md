# 014 · W4 OS — Edition Package Profiles

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Convertir las ediciones en conjuntos de paquetes reconstruibles.

## Alcance, arquitectura y decisiones

Proponer w4-base-meta, w4-desktop-meta, w4-home-meta y w4-business-meta con dependencias explícitas. Separar requisitos indispensables de recomendaciones removibles.

## Componentes y flujo operativo

Resolver perfil sobre repositorio fijado, exportar lista exacta, construir imagen y comparar con manifiesto aprobado.

## Seguridad y riesgos

No usar scripts de metapaquete para descargar ejecutables externos. Eliminar una aplicación recomendada no debe desinstalar componentes esenciales por sorpresa.

## Criterios de aceptación

Aceptar instalación limpia de cada perfil y actualización desde el anterior; el diferencial de paquetes debe explicar toda variación de tamaño.

## Rendimiento y evidencia

Registrar tamaño de imagen y cierre de dependencias por metapaquete, además de paquetes retirados al actualizar un perfil personalizado.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [045 — Meta Package System](045_W4_OS_META_PACKAGE_SYSTEM.md)
- [046 — Package Dependency Policy](046_W4_OS_PACKAGE_DEPENDENCY_POLICY.md)
- [068 — Image Build System](068_W4_OS_IMAGE_BUILD_SYSTEM.md)

## Roadmap y condiciones de evolución

Crear perfiles mínimos, después añadir opciones por capacidad de hardware.

---

[Anterior](013_W4_OS_EDITION_DIFFERENTIATION_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](015_W4_OS_EDITION_UPGRADE_STRATEGY.md)
