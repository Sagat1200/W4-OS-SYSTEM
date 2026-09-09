# 079 · W4 OS — Update Channels

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Controlar la selección de versiones de cada cliente sin confundir edición con estabilidad.

## Alcance, arquitectura y decisiones

El cliente guarda línea W4, canal y base Debian. Cambiar entre candidate y stable no implica que un downgrade sea seguro; se calcula una transición soportada.

## Componentes y flujo operativo

1. Leer selección
2. consultar manifiesto compatible
3. comparar versiones
4. validar transición
5. aplicar por motor
6. registrar nuevo canal.

## Seguridad y riesgos

Rechazar salto a una base incompatible y regresión de seguridad no autorizada. La selección local puede estar restringida por política empresarial.

## Criterios de aceptación

Aceptar cambio que mantiene compatibilidad y rechazo de downgrade de esquema.

## Rendimiento y evidencia

Medir clientes fuera del canal esperado.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [053 — Repository Channels](053_W4_OS_REPOSITORY_CHANNELS.md)
- [268 — Release Channels](268_W4_OS_RELEASE_CHANNELS.md)
- [398 — Major Version Migration](398_W4_OS_MAJOR_VERSION_MIGRATION.md)

## Roadmap y condiciones de evolución

Exponer canal estable en V1; cambios avanzados tras pruebas de migración.

---

[Anterior](078_W4_OS_UPDATE_RINGS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](080_W4_OS_OFFLINE_UPDATE_SYSTEM.md)
