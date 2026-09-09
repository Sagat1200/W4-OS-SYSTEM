# 066 · W4 OS — amd64 Support

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Construcción y artefactos · **Responsabilidad propuesta:** Infraestructura de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir amd64 como objetivo inicial acotado por hardware y firmware.

## Alcance, arquitectura y decisiones

Propuesta V1: equipos x86-64 con UEFI y recursos mínimos medidos; no prometer todo PC histórico ni soporte BIOS heredado. Documentar instrucciones CPU mínimas del build.

## Componentes y flujo operativo

1. Construir imagen
2. probar VM UEFI
3. ejecutar matriz física de escritorio y portátil
4. publicar requisitos obtenidos.

## Seguridad y riesgos

No generar paquetes con optimizaciones de CPU del worker que excluyan equipos del catálogo. Conservar un baseline explícito.

## Criterios de aceptación

Aceptar arranque, red, audio, suspensión y recuperación en cada modelo certificado.

## Rendimiento y evidencia

Medir memoria ociosa, almacenamiento y arranque.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [016 — Kernel Strategy](016_W4_OS_KERNEL_STRATEGY.md)
- [024 — Device Compatibility Model](024_W4_OS_DEVICE_COMPATIBILITY_MODEL.md)
- [065 — Multi Arch Build System](065_W4_OS_MULTI_ARCH_BUILD_SYSTEM.md)

## Roadmap y condiciones de evolución

Plataforma prioritaria de MVP; ampliar hardware con certificación incremental.

---

[Anterior](065_W4_OS_MULTI_ARCH_BUILD_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](067_W4_OS_ARM64_SUPPORT.md)
