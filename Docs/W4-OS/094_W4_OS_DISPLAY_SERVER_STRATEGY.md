# 094 · W4 OS — Display Server Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir protocolo gráfico por sesión y una política limitada de compatibilidad heredada.

## Alcance, arquitectura y decisiones

Wayland es objetivo preferido sujeto a calificación del hardware; XWayland atiende aplicaciones X11 cuando esté disponible. Sesión X11 alternativa sólo si se mantiene y prueba explícitamente. Esta estrategia aplica a composiciones gráficas como `Home` y `Business`; `Server` queda fuera de esta ruta por `ADR-014`, con política `headless` por defecto.

## Componentes y flujo operativo

1. Seleccionar sesión compatible
2. iniciar compositor
3. lanzar aplicaciones
4. usar portales para funciones sensibles
5. registrar fallos gráficos.

## Seguridad y riesgos

La compatibilidad X11 tiene un modelo de aislamiento distinto; no asumir que todas las aplicaciones heredadas reciben protección equivalente.

## Criterios de aceptación

Aceptar aplicaciones nativas y heredadas de la matriz con pantalla compartida y entrada funcional.

## Rendimiento y evidencia

Medir fallos por tipo de sesión.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [095 — Wayland Architecture](095_W4_OS_WAYLAND_ARCHITECTURE.md)
- [122 — Application Installation System](122_W4_OS_APPLICATION_INSTALLATION_SYSTEM.md)
- [296 — Graphics Performance](296_W4_OS_GRAPHICS_PERFORMANCE.md)

## Roadmap y condiciones de evolución

Certificar ruta principal V1 y limitar fallback a hardware probado.

---

[Anterior](093_W4_OS_WINDOW_MANAGER_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](095_W4_OS_WAYLAND_ARCHITECTURE.md)
