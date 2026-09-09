# 203 · W4 OS — Metrics System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Diagnóstico y privacidad · **Responsabilidad propuesta:** Diagnóstico y privacidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Medir recursos y comportamiento con definiciones estables.

## Alcance, arquitectura y decisiones

Catálogo de métricas incluye unidad, agregación, etiquetas permitidas y costo. Priorizar CPU, memoria, disco y latencia de operaciones; no mezclar eventos con métricas de alta cardinalidad.

## Componentes y flujo operativo

1. Muestrear
2. agregar localmente
3. aplicar retención
4. consultar
5. exportar sólo cuando esté autorizado.

## Seguridad y riesgos

No usar rutas, correos o seriales como etiquetas. Una métrica ausente se distingue de cero para evitar decisiones incorrectas.

## Criterios de aceptación

Aceptar desconexión y contador reiniciado con interpretación correcta.

## Rendimiento y evidencia

Medir overhead y cardinalidad máxima.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [201 — Telemetry Architecture](201_W4_OS_TELEMETRY_ARCHITECTURE.md)
- [291 — Performance Architecture](291_W4_OS_PERFORMANCE_ARCHITECTURE.md)
- [361 — Observability Architecture](361_W4_OS_OBSERVABILITY_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Métricas locales V1; exportación Business después de política de datos.

## Diccionario de métricas

Cada métrica declara si es contador acumulativo, valor instantáneo o distribución de duración, además de unidad y periodo de agregación. Reiniciar un servicio puede reiniciar contadores: el consumidor debe detectarlo en lugar de calcular tasas negativas. Etiquetas se limitan a conjuntos acotados como componente, tipo de operación y resultado. El nombre de un archivo, UUID de cada solicitud o correo de usuario no son etiquetas admisibles de alta cardinalidad. Los identificadores de operación se consultan en eventos, no se multiplican como series métricas.

---

[Anterior](202_W4_OS_SYSTEM_LOGGING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](204_W4_OS_CRASH_REPORTING.md)
