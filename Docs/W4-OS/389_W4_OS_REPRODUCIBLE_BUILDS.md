# 389 · W4 OS — Reproducible Builds

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Suministro verificable · **Responsabilidad propuesta:** Seguridad de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Operar verificación independiente de reproducibilidad y gestionar excepciones.

## Alcance, arquitectura y decisiones

Este documento convierte la estrategia 064 en campaña: doble build, comparador, clasificación y seguimiento. Priorizar paquetes W4 y componentes críticos antes de afirmar cobertura total.

## Componentes y flujo operativo

1. Seleccionar paquete
2. reconstruir en entorno independiente
3. comparar bytes
4. investigar diferencia
5. corregir
6. repetir
7. publicar estado.

## Seguridad y riesgos

Reproducibilidad no elimina riesgo de fuente maliciosa ni toolchain comprometida. Separar entorno verificador del de publicación cuando sea posible.

## Criterios de aceptación

Aceptar resultado por paquete con logs y diferencias explicadas.

## Rendimiento y evidencia

Medir porcentaje verificado y antigüedad de excepciones.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [048 — Package Build Pipeline](048_W4_OS_PACKAGE_BUILD_PIPELINE.md)
- [064 — Build Reproducibility](064_W4_OS_BUILD_REPRODUCIBILITY.md)
- [299 — Performance Benchmarks](299_W4_OS_PERFORMANCE_BENCHMARKS.md)
- [390 — Build Provenance](390_W4_OS_BUILD_PROVENANCE.md)

## Roadmap y condiciones de evolución

Primera campaña V1; cobertura ampliada según costo y criticidad.

---

[Anterior](388_W4_OS_SBOM_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](390_W4_OS_BUILD_PROVENANCE.md)
