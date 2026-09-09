# 074 · W4 OS — Transactional Update Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Evaluar preparación de actualizaciones en una raíz separada inspirada en openSUSE, adaptada a Debian.

## Alcance, arquitectura y decisiones

El prototipo ejecuta instalación de paquetes en generación aislada con servicios impedidos o controlados, monta recursos estrictamente necesarios y administra artefactos de arranque aparte.

## Componentes y flujo operativo

1. Clonar estado
2. aplicar paquetes
3. capturar efectos de scripts
4. verificar
5. sellar
6. activar; un error descarta candidata sin tocar raíz activa.

## Seguridad y riesgos

Scripts de mantenimiento pueden escribir fuera de la raíz o depender de servicios vivos. La compatibilidad debe probarse por paquete crítico; no portar transactional-update sin análisis.

## Criterios de aceptación

Aceptar paquetes con triggers, initramfs y migración de conffiles sin efectos sobre la activa.

## Rendimiento y evidencia

Medir costo de preparación y espacio.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [062 — Open Build Service Strategy](062_W4_OS_OPEN_BUILD_SERVICE_STRATEGY.md)
- [073 — Atomic Update Model](073_W4_OS_ATOMIC_UPDATE_MODEL.md)
- [075 — Update Staging System](075_W4_OS_UPDATE_STAGING_SYSTEM.md)

## Roadmap y condiciones de evolución

Prueba técnica después del flujo offline V1; elegir implementación mediante ADR y evidencia.

---

[Anterior](073_W4_OS_ATOMIC_UPDATE_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](075_W4_OS_UPDATE_STAGING_SYSTEM.md)
