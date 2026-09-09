# 007 · W4 OS — Fork And Derivative Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Distinguir una distribución derivada de un fork permanente de componentes.

## Alcance, arquitectura y decisiones

W4 conserva formatos Debian y reconstruye sólo cuando necesita cambios verificables. Un fork requiere propietario, capacidad de seguimiento de vulnerabilidades y estrategia de reintegración o sustitución.

## Componentes y flujo operativo

Solicitar excepción con alternativas, estimación de mantenimiento y prueba de incompatibilidad de la extensión; arquitectura registra la decisión antes de incorporar el fork.

## Seguridad y riesgos

No retirar avisos de autoría ni asumir derechos sobre marcas upstream. El abandono del mantenedor obliga a reevaluar distribución del componente.

## Criterios de aceptación

Aceptar un fork sólo con compilación reproducible evaluada, pruebas propias y plan financiado de actualizaciones.

## Rendimiento y evidencia

Medir paquetes realmente divergentes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [006 — Upstream Integration Strategy](006_W4_OS_UPSTREAM_INTEGRATION_STRATEGY.md)
- [309 — Licensing Strategy](309_W4_OS_LICENSING_STRATEGY.md)
- [395 — W4 Patch Queue](395_W4_OS_W4_PATCH_QUEUE.md)

## Roadmap y condiciones de evolución

Comenzar como derivada de integración; aprobar forks individualmente, nunca como meta porcentual.

---

[Anterior](006_W4_OS_UPSTREAM_INTEGRATION_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](008_W4_OS_SHARED_CORE_ARCHITECTURE.md)
