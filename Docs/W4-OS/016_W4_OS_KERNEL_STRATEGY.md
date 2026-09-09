# 016 · W4 OS — Kernel Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Kernel y hardware · **Responsabilidad propuesta:** Plataforma y habilitación de hardware  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener el kernel compatible con Debian y limitar variantes que amplían soporte.

## Alcance, arquitectura y decisiones

Usar kernel empaquetado por Debian como opción inicial; backports de hardware sólo con matriz propia. No definir kernel Home y Business distintos.

## Componentes y flujo operativo

1. Seleccionar versión soportada
2. probar arranque, suspensión, gráficos y red
3. publicar junto con módulos compatibles
4. conservar versión anterior recuperable.

## Seguridad y riesgos

Un módulo externo puede invalidar la ruta de arranque firmado. No retirar el kernel conocido como bueno antes de verificar el nuevo.

## Criterios de aceptación

Aceptar ambos perfiles sobre hardware de referencia y recuperación a kernel previo; comparar regresiones de energía y arranque respecto a referencia.

## Rendimiento y evidencia

Comparar arranque, consumo en reposo y ciclos de suspensión con el kernel anterior sobre el mismo hardware; conservar variación y condiciones del ensayo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [017 — Kernel Configuration](017_W4_OS_KERNEL_CONFIGURATION.md)
- [018 — Kernel Update Policy](018_W4_OS_KERNEL_UPDATE_POLICY.md)
- [022 — GPU Driver System](022_W4_OS_GPU_DRIVER_SYSTEM.md)

## Roadmap y condiciones de evolución

V1 con una línea principal; ampliar sólo ante necesidades de hardware verificadas.

---

[Anterior](015_W4_OS_EDITION_UPGRADE_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](017_W4_OS_KERNEL_CONFIGURATION.md)
