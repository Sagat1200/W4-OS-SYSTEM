# 199 · W4 OS — Thermal Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Energía · **Responsabilidad propuesta:** Hardware y energía  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Observar temperatura y throttling sin sustituir mecanismos de protección del fabricante.

## Alcance, arquitectura y decisiones

Leer sensores disponibles y relacionarlos con carga; perfiles W4 respetan límites del kernel y firmware. No controlar ventiladores con reglas genéricas para todos los equipos.

## Componentes y flujo operativo

1. Muestrear
2. identificar tendencia
3. reducir perfil permitido o avisar
4. verificar descenso; sensor ausente se marca desconocido.

## Seguridad y riesgos

No desactivar protección térmica para mejorar benchmarks. Evitar escrituras a interfaces de hardware sin soporte del modelo.

## Criterios de aceptación

Aceptar carga sostenida con límites respetados y sensor ausente sin alarma falsa.

## Rendimiento y evidencia

Medir temperatura, ruido cuando sea posible y throttling.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [196 — Power Management](196_W4_OS_POWER_MANAGEMENT.md)
- [200 — Performance Profiles](200_W4_OS_PERFORMANCE_PROFILES.md)
- [294 — CPU Optimization](294_W4_OS_CPU_OPTIMIZATION.md)

## Roadmap y condiciones de evolución

Observación V1; control avanzado sólo con certificación OEM.

---

[Anterior](198_W4_OS_SLEEP_HIBERNATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](200_W4_OS_PERFORMANCE_PROFILES.md)
