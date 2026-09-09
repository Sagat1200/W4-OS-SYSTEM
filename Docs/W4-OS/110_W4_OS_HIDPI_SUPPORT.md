# 110 · W4 OS — HiDPI Support

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ofrecer escalado legible con aplicaciones de distintos toolkits.

## Alcance, arquitectura y decisiones

El compositor gestiona escala por pantalla cuando lo soporte; documentar diferencias de apps heredadas. No imponer escalado fraccional si introduce problemas en la matriz objetivo.

## Componentes y flujo operativo

1. Detectar densidad
2. sugerir escala
3. previsualizar texto y controles
4. confirmar
5. conservar por monitor.

## Seguridad y riesgos

Los diálogos de permisos no pueden quedar fuera de pantalla o con botones invisibles. Probar zoom y lector de pantalla juntos.

## Criterios de aceptación

Aceptar escalas de referencia con controles accesibles y capturas correctas.

## Rendimiento y evidencia

Medir nitidez percibida, memoria gráfica y rendimiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [103 — Font System](103_W4_OS_FONT_SYSTEM.md)
- [104 — Accessibility System](104_W4_OS_ACCESSIBILITY_SYSTEM.md)
- [108 — Display Configuration](108_W4_OS_DISPLAY_CONFIGURATION.md)

## Roadmap y condiciones de evolución

Escalas enteras como referencia; fraccional tras validar aplicaciones prioritarias.

---

[Anterior](109_W4_OS_MULTI_MONITOR_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](111_W4_OS_CONTROL_CENTER_ARCHITECTURE.md)
