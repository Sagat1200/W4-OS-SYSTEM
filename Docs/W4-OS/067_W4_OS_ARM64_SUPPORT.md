# 067 · W4 OS — arm64 Support

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Construcción y artefactos · **Responsabilidad propuesta:** Infraestructura de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir arm64 como trabajo específico de plataforma, no como simple recompilación.

## Alcance, arquitectura y decisiones

Cada equipo requiere ruta de firmware y arranque, dispositivos y gráficos. Empezar con una plataforma UEFI de referencia; otras placas y SoC necesitan expediente separado.

## Componentes y flujo operativo

1. Seleccionar hardware
2. construir paquetes
3. validar imagen y controladores
4. probar instalación y recuperación
5. decidir nivel de soporte.

## Seguridad y riesgos

Firmware no redistribuible y controladores sin mantenimiento pueden bloquear entrega. No equiparar resultados de emulación con compatibilidad comercial.

## Criterios de aceptación

Aceptar flujo completo en equipo físico objetivo antes de usar etiqueta soportado.

## Rendimiento y evidencia

Medir energía, temperatura y rendimiento nativo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [019 — Hardware Enablement Layer](019_W4_OS_HARDWARE_ENABLEMENT_LAYER.md)
- [025 — Boot Architecture](025_W4_OS_BOOT_ARCHITECTURE.md)
- [065 — Multi Arch Build System](065_W4_OS_MULTI_ARCH_BUILD_SYSTEM.md)

## Roadmap y condiciones de evolución

Experimental después de MVP amd64; promoción por plataforma, no por arquitectura completa.

---

[Anterior](066_W4_OS_AMD64_SUPPORT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](068_W4_OS_IMAGE_BUILD_SYSTEM.md)
