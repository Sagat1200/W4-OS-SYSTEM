# 021 · W4 OS — Driver Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Kernel y hardware · **Responsabilidad propuesta:** Plataforma y habilitación de hardware  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Asignar una autoridad clara para instalación y selección de controladores.

## Alcance, arquitectura y decisiones

Preferir módulos del kernel; drivers externos se empaquetan con restricciones de kernel y firma. Un registro vincula dispositivo, módulo, origen y estado de soporte.

## Componentes y flujo operativo

1. Resolver identificador
2. elegir controlador
3. verificar dependencias
4. cargar
5. comprobar función; ante conflicto deshabilitar candidato y restaurar selección anterior.

## Seguridad y riesgos

No descargar scripts de fabricante durante detección. Cargar código de kernel exige origen autenticado y autorización administrativa.

## Criterios de aceptación

Aceptar reconexión y actualización sin dos controladores incompatibles reclamando el dispositivo.

## Rendimiento y evidencia

Medir tiempo de detección y fallos de carga.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [016 — Kernel Strategy](016_W4_OS_KERNEL_STRATEGY.md)
- [020 — Firmware Management](020_W4_OS_FIRMWARE_MANAGEMENT.md)
- [022 — GPU Driver System](022_W4_OS_GPU_DRIVER_SYSTEM.md)

## Roadmap y condiciones de evolución

Catálogo de controladores V1 con excepciones mantenidas por paquete.

---

[Anterior](020_W4_OS_FIRMWARE_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](022_W4_OS_GPU_DRIVER_SYSTEM.md)
