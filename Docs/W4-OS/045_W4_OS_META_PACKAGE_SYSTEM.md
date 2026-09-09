# 045 · W4 OS — Meta Package System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Paquetes y repositorios · **Responsabilidad propuesta:** Mantenimiento y distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Usar metapaquetes para expresar intención de instalación sin duplicar software.

## Alcance, arquitectura y decisiones

Un metapaquete contiene dependencias y descripción; configuración reside en paquetes propios. Diferenciar dependencias esenciales de aplicaciones recomendadas.

## Componentes y flujo operativo

1. Instalar perfil
2. resolver dependencias
3. registrar elección manual
4. actualizar dependencias del perfil en nuevas releases.

## Seguridad y riesgos

Cambiar Depends puede retirar componentes de forma indirecta. Revisar el resultado del resolver para perfiles previamente personalizados.

## Criterios de aceptación

Aceptar retirada de una aplicación opcional y actualización del perfil sin reinstalarla indebidamente ni eliminar la base.

## Rendimiento y evidencia

Medir cambios inducidos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [014 — Edition Package Profiles](014_W4_OS_EDITION_PACKAGE_PROFILES.md)
- [046 — Package Dependency Policy](046_W4_OS_PACKAGE_DEPENDENCY_POLICY.md)
- [050 — Package Lifecycle](050_W4_OS_PACKAGE_LIFECYCLE.md)

## Roadmap y condiciones de evolución

Publicar perfiles mínimos y ampliar dependencias con pruebas de transición.

---

[Anterior](044_W4_OS_PACKAGE_METADATA_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](046_W4_OS_PACKAGE_DEPENDENCY_POLICY.md)
