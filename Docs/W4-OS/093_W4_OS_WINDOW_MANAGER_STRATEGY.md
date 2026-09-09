# 093 · W4 OS — Window Manager Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Delegar composición y gestión de ventanas al entorno principal elegido.

## Alcance, arquitectura y decisiones

Usar el compositor/manejador empaquetado con el escritorio; configuración W4 prioriza comportamiento predecible, atajos documentados y recuperación de ventanas fuera de pantalla.

## Componentes y flujo operativo

1. Abrir ventana
2. asignar pantalla y escala
3. gestionar foco
4. restaurar geometría tras desconexión de monitor.

## Seguridad y riesgos

No introducir automatismos que capturen teclas secretas ni extensiones de foco sin revisión. Las aplicaciones no deben imponerse sobre pantallas de seguridad.

## Criterios de aceptación

Aceptar diálogo modal, aplicación bloqueada y monitor retirado sin perder acceso.

## Rendimiento y evidencia

Medir latencia perceptible y consumo del compositor.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [092 — Desktop Environment Strategy](092_W4_OS_DESKTOP_ENVIRONMENT_STRATEGY.md)
- [094 — Display Server Strategy](094_W4_OS_DISPLAY_SERVER_STRATEGY.md)
- [109 — Multi Monitor System](109_W4_OS_MULTI_MONITOR_SYSTEM.md)

## Roadmap y condiciones de evolución

Defaults conservadores V1; tiling avanzado como opción posterior.

---

[Anterior](092_W4_OS_DESKTOP_ENVIRONMENT_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](094_W4_OS_DISPLAY_SERVER_STRATEGY.md)
