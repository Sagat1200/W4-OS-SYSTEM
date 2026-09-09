# 065 · W4 OS — Multi Arch Build System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Construcción y artefactos · **Responsabilidad propuesta:** Infraestructura de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Construir por arquitectura sin asumir equivalencia de binarios ni cobertura de hardware.

## Alcance, arquitectura y decisiones

Manifiestos distinguen amd64 y arm64, paquetes Architecture: all y específicos. Las dependencias y pruebas se resuelven por destino; emulación no reemplaza validación física.

## Componentes y flujo operativo

1. Planificar matriz
2. compilar nativamente o con ruta declarada
3. probar
4. publicar sólo arquitecturas calificadas.

## Seguridad y riesgos

No etiquetar un paquete como independiente si incluye ejecutables nativos. Evitar publicación parcial que deje perfiles irresolubles.

## Criterios de aceptación

Aceptar instalación completa por arquitectura anunciada y rechazo de binario equivocado.

## Rendimiento y evidencia

Medir duración y cobertura nativa.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [066 — amd64 Support](066_W4_OS_AMD64_SUPPORT.md)
- [067 — arm64 Support](067_W4_OS_ARM64_SUPPORT.md)
- [279 — Hardware Testing](279_W4_OS_HARDWARE_TESTING.md)

## Roadmap y condiciones de evolución

amd64 referencia inicial; arm64 experimental hasta completar hardware, arranque y recuperación.

---

[Anterior](064_W4_OS_BUILD_REPRODUCIBILITY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](066_W4_OS_AMD64_SUPPORT.md)
