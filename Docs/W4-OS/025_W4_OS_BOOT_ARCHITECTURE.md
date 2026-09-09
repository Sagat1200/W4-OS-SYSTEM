# 025 · W4 OS — Boot Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Arranque y servicios · **Responsabilidad propuesta:** Integración de arranque  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir una cadena de arranque recuperable desde firmware hasta sesión.

## Alcance, arquitectura y decisiones

Ruta propuesta amd64: UEFI → cargador compatible con Debian → kernel e initramfs → raíz seleccionada → systemd → sesión. Vincular artefactos de arranque con el estado raíz.

## Componentes y flujo operativo

Validar entrada, desbloquear volumen cuando aplique, montar raíz y ejecutar comprobaciones; un error deriva a recuperación autenticada con diagnóstico.

## Seguridad y riesgos

ESP y firmware no forman parte del snapshot raíz. Una entrada candidata no puede referenciar artefactos incompletos ni exponer claves de cifrado.

## Criterios de aceptación

Aceptar arranque normal, raíz ausente y kernel candidato defectuoso en VM UEFI.

## Rendimiento y evidencia

Medir etapas del arranque sin ocultar fallos críticos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [026 — Bootloader Strategy](026_W4_OS_BOOTLOADER_STRATEGY.md)
- [029 — Startup Pipeline](029_W4_OS_STARTUP_PIPELINE.md)
- [088 — Boot Recovery](088_W4_OS_BOOT_RECOVERY.md)

## Roadmap y condiciones de evolución

V1 con recuperación explícita; automatizar selección sólo tras validar persistencia de estados.

---

[Anterior](024_W4_OS_DEVICE_COMPATIBILITY_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](026_W4_OS_BOOTLOADER_STRATEGY.md)
