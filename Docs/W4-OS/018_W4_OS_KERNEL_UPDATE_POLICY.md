# 018 · W4 OS — Kernel Update Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Kernel y hardware · **Responsabilidad propuesta:** Plataforma y habilitación de hardware  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Actualizar kernel sin dejar al usuario con un único arranque defectuoso.

## Alcance, arquitectura y decisiones

La unidad de compatibilidad incluye kernel, initramfs, módulos y metadatos de arranque. Retención mínima propuesta: versión activa y una confirmada anterior, condicionada a espacio.

## Componentes y flujo operativo

Descargar, verificar compatibilidad de módulos, preparar initramfs, registrar entrada candidata y reiniciar en ventana; confirmar después de salud.

## Seguridad y riesgos

Un kernel anterior vulnerable es una herramienta temporal de recuperación, no una solución permanente. Señalar estado degradado y limitar exposición según política.

## Criterios de aceptación

Aceptar corte de energía en preparación y fallo de módulo gráfico con retorno documentado.

## Rendimiento y evidencia

Medir tiempo de confirmación y uso de partición de arranque.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [025 — Boot Architecture](025_W4_OS_BOOT_ARCHITECTURE.md)
- [076 — Update Health Check](076_W4_OS_UPDATE_HEALTH_CHECK.md)
- [088 — Boot Recovery](088_W4_OS_BOOT_RECOVERY.md)

## Roadmap y condiciones de evolución

Automatizar preparación en V1; fallback automático requiere calificación separada.

---

[Anterior](017_W4_OS_KERNEL_CONFIGURATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](019_W4_OS_HARDWARE_ENABLEMENT_LAYER.md)
