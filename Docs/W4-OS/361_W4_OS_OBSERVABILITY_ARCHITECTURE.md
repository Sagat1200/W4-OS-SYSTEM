# 361 · W4 OS — Observability Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Observabilidad · **Responsabilidad propuesta:** Operación y observabilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Unificar logs, métricas y eventos alrededor de operaciones identificables.

## Alcance, arquitectura y decisiones

Modelo de observabilidad distingue señal local, exportación consentida y administración empresarial. Correlation-id enlaza actualización, política y diagnóstico sin incluir identidad personal innecesaria.

## Componentes y flujo operativo

1. Instrumentar
2. agregar
3. limitar volumen
4. consultar
5. alertar ante cambio accionable
6. conservar evidencia mínima.

## Seguridad y riesgos

No exportar todo journal por comodidad. Señales faltantes se representan como desconocidas y no se convierten en éxito.

## Criterios de aceptación

Aceptar incidente rastreable entre componentes con costo de recursos medido; evaluar cardinalidad, volumen y latencia de consulta.

## Rendimiento y evidencia

Evaluar cardinalidad, volumen por componente y latencia de consulta; medir overhead con exportación remota deshabilitada y con una cola temporalmente desconectada.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [202 — System Logging](202_W4_OS_SYSTEM_LOGGING.md)
- [203 — Metrics System](203_W4_OS_METRICS_SYSTEM.md)
- [362 — System Event Model](362_W4_OS_SYSTEM_EVENT_MODEL.md)
- [364 — Diagnostic Event Pipeline](364_W4_OS_DIAGNOSTIC_EVENT_PIPELINE.md)

## Roadmap y condiciones de evolución

Instrumentación mínima MVP; panel central tras política de datos.

---

[Anterior](360_W4_OS_ENTERPRISE_COMPLIANCE_PROFILES.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](362_W4_OS_SYSTEM_EVENT_MODEL.md)
