# 091 · W4 OS — Desktop Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Reutilizar una plataforma de escritorio mantenida con personalización W4 acotada.

## Alcance, arquitectura y decisiones

Propuesta: un entorno principal compartido por ambas ediciones, con shell upstream, servicios de sesión, portales y centro de control adaptado. No desarrollar compositor propio para V1.

## Componentes y flujo operativo

1. Abrir sesión
2. cargar perfil de usuario
3. aplicar ajustes efectivos
4. lanzar servicios necesarios
5. mostrar estado de sistema.

## Seguridad y riesgos

Extensiones de escritorio tienen privilegios de sesión y pueden leer datos; limitar las incluidas y mantener compatibilidad por release.

## Criterios de aceptación

Aceptar tareas de escritorio, bloqueo, impresión y aplicaciones aisladas en ambos perfiles.

## Rendimiento y evidencia

Medir memoria ociosa y estabilidad.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [092 — Desktop Environment Strategy](092_W4_OS_DESKTOP_ENVIRONMENT_STRATEGY.md)
- [096 — Desktop Shell](096_W4_OS_DESKTOP_SHELL.md)
- [097 — Desktop Session System](097_W4_OS_DESKTOP_SESSION_SYSTEM.md)

## Roadmap y condiciones de evolución

Seleccionar entorno por prototipo accesible y congelar uno para V1.

---

[Anterior](090_W4_OS_SYSTEM_REPAIR_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](092_W4_OS_DESKTOP_ENVIRONMENT_STRATEGY.md)
