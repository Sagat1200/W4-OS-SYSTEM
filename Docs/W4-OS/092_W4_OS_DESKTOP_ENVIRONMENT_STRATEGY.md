# 092 · W4 OS — Desktop Environment Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Elegir escritorio por accesibilidad, mantenimiento y hardware antes que por apariencia.

## Alcance, arquitectura y decisiones

Propuesta de trabajo: evaluar KDE Plasma de Debian como candidato principal y GNOME como alternativa, mediante matriz de pruebas. La selección final requiere ADR; los documentos asumen interfaces, no una elección aprobada.

## Componentes y flujo operativo

1. Construir prototipos equivalentes
2. probar tareas y lectores de pantalla
3. medir recursos
4. comparar mantenimiento
5. aprobar entorno.

## Seguridad y riesgos

No mantener dos escritorios oficiales en V1 sin capacidad para duplicar pruebas. Evitar extensiones que sustituyan funciones de seguridad upstream.

## Criterios de aceptación

Aceptar elección con resultados de accesibilidad, suspensión, gráficos y soporte.

## Rendimiento y evidencia

Medir esfuerzo de personalización necesario.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [091 — Desktop Architecture](091_W4_OS_DESKTOP_ARCHITECTURE.md)
- [104 — Accessibility System](104_W4_OS_ACCESSIBILITY_SYSTEM.md)
- [283 — Desktop Testing](283_W4_OS_DESKTOP_TESTING.md)

## Roadmap y condiciones de evolución

Resolver antes del cierre de imagen MVP y adaptar paquetes al entorno elegido.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [Debian 13 — What’s new](https://www.debian.org/releases/trixie/release-notes/whats-new.html). Debian documenta GNOME y KDE Plasma entre sus escritorios. La elección de escritorio W4 requiere comparación y pruebas propias.

---

[Anterior](091_W4_OS_DESKTOP_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](093_W4_OS_WINDOW_MANAGER_STRATEGY.md)
