# 204 · W4 OS — Crash Reporting

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Diagnóstico y privacidad · **Responsabilidad propuesta:** Diagnóstico y privacidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Recoger fallos de procesos con información suficiente y mínima exposición.

## Alcance, arquitectura y decisiones

Reportes incluyen versión, señal, componente y stack cuando sea viable; volcados completos son opcionales porque pueden contener secretos. Sin carga automática por defecto.

## Componentes y flujo operativo

1. Detectar crash
2. guardar resumen limitado
3. ofrecer revisión
4. redactar
5. enviar si autorizado
6. enlazar incidencia.

## Seguridad y riesgos

No adjuntar memoria, variables o archivos abiertos sin consentimiento específico. Limitar tamaño y retención de core dumps.

## Criterios de aceptación

Aceptar crash de prueba con resumen útil y ausencia de token sintético en exportación.

## Rendimiento y evidencia

Medir tasa de reportes utilizables y espacio.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [201 — Telemetry Architecture](201_W4_OS_TELEMETRY_ARCHITECTURE.md)
- [205 — Diagnostics System](205_W4_OS_DIAGNOSTICS_SYSTEM.md)
- [210 — Support Bundle System](210_W4_OS_SUPPORT_BUNDLE_SYSTEM.md)

## Roadmap y condiciones de evolución

Resumen local MVP; symbol server y cargas tras controles de privacidad.

---

[Anterior](203_W4_OS_METRICS_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](205_W4_OS_DIAGNOSTICS_SYSTEM.md)
