# 026 · W4 OS — Bootloader Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Arranque y servicios · **Responsabilidad propuesta:** Integración de arranque  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Elegir un cargador que permita mantener la base Debian y una ruta de recuperación comprobable.

## Alcance, arquitectura y decisiones

Propuesta inicial GRUB de Debian para amd64 UEFI. La integración W4 de entradas de generaciones se desarrolla y prueba; no se presume disponible por instalar Snapper.

## Componentes y flujo operativo

Generar entrada desde manifiesto de kernel y raíz, verificar archivos, preservar entrada buena y activar candidata sólo tras preparación completa.

## Seguridad y riesgos

No sobrescribir cargadores ajenos sin elección explícita. Secure Boot exige cadena de binarios admitida y firmas verificadas.

## Criterios de aceptación

Aceptar entradas normales y de recuperación después de actualizar kernel; probar NVRAM perdida y restauración desde medio externo.

## Rendimiento y evidencia

Medir demora de selección de entrada y recuperación desde medio externo; comprobar espacio de arranque suficiente para conservar artefactos del estado bueno.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [025 — Boot Architecture](025_W4_OS_BOOT_ARCHITECTURE.md)
- [038 — Dual Boot Strategy](038_W4_OS_DUAL_BOOT_STRATEGY.md)
- [161 — Secure Boot](161_W4_OS_SECURE_BOOT.md)

## Roadmap y condiciones de evolución

Resolver selección en ADR y mantener alternativas fuera del soporte inicial hasta calificarlas.

---

[Anterior](025_W4_OS_BOOT_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](027_W4_OS_INIT_SYSTEM.md)
