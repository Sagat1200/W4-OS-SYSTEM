# 230 · W4 OS — Enterprise Reporting

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Producir reportes de flota que distingan cobertura, incertidumbre y periodo.

## Alcance, arquitectura y decisiones

Vistas de versiones, políticas, incidentes y antigüedad del inventario; agregaciones por organización con filtros reproducibles. No presentar instantánea parcial como toda la flota.

## Componentes y flujo operativo

1. Elegir periodo y población
2. calcular cobertura
3. agregar
4. mostrar excepciones
5. exportar con fecha y definición de métricas.

## Seguridad y riesgos

Aplicar roles y minimizar identificadores personales en exportaciones. No incluir equipos de otras organizaciones por caché compartida.

## Criterios de aceptación

Aceptar reporte con equipos sin datos y denominador correcto.

## Rendimiento y evidencia

Medir tiempo de consulta y consistencia con registros fuente.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [221 — Fleet Management](221_W4_OS_FLEET_MANAGEMENT.md)
- [227 — Inventory System](227_W4_OS_INVENTORY_SYSTEM.md)
- [228 — Compliance System](228_W4_OS_COMPLIANCE_SYSTEM.md)
- [361 — Observability Architecture](361_W4_OS_OBSERVABILITY_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Reportes operativos piloto; analítica avanzada después de validar calidad de datos.

---

[Anterior](229_W4_OS_ENTERPRISE_AUDITING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](231_W4_OS_HOME_ARCHITECTURE.md)
