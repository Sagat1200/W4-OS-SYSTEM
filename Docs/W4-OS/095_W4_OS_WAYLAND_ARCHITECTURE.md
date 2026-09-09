# 095 · W4 OS — Wayland Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Integrar Wayland con funciones reales de escritorio, captura y dispositivos de entrada.

## Alcance, arquitectura y decisiones

El compositor del entorno elegido, XWayland y portales forman una unidad de compatibilidad. PipeWire se propone para captura mediada; validar versiones de Debian fijadas.

## Componentes y flujo operativo

1. Aplicación solicita captura
2. portal presenta selección
3. compositor autoriza recurso
4. sesión termina y revoca acceso.

## Seguridad y riesgos

No conceder captura global permanente para emular APIs heredadas. Verificar que bloqueo de pantalla detenga o proteja contenido sensible.

## Criterios de aceptación

Aceptar videollamada, compartir una ventana, escalado y hotplug en hardware certificado.

## Rendimiento y evidencia

Medir latencia y problemas de entrada.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [094 — Display Server Strategy](094_W4_OS_DISPLAY_SERVER_STRATEGY.md)
- [104 — Accessibility System](104_W4_OS_ACCESSIBILITY_SYSTEM.md)
- [136 — Multimedia System](136_W4_OS_MULTIMEDIA_SYSTEM.md)

## Roadmap y condiciones de evolución

Calificar portales con apps reales antes de declarar sesión predeterminada.

---

[Anterior](094_W4_OS_DISPLAY_SERVER_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](096_W4_OS_DESKTOP_SHELL.md)
