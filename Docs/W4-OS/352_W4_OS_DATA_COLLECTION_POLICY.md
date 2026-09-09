# 352 · W4 OS — Data Collection Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Privacidad de datos · **Responsabilidad propuesta:** Privacidad y gobierno de datos  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Limitar recolección a campos necesarios con esquemas que rechacen extras.

## Alcance, arquitectura y decisiones

Catálogo de datos por finalidad: inventario técnico, evento de error, consentimiento y soporte. Contenido de documentos, pulsaciones y contraseñas queda fuera por defecto.

## Componentes y flujo operativo

1. Proponer campo
2. justificar uso
3. clasificar sensibilidad
4. aprobar esquema
5. validar cliente/servidor
6. retirar si no aporta.

## Seguridad y riesgos

No recolectar por si acaso ni tratar hash estable como anónimo automáticamente. Evitar etiquetas de métricas con datos personales.

## Criterios de aceptación

Aceptar payload con campo no autorizado rechazado y catálogo coherente con tráfico observado.

## Rendimiento y evidencia

Medir minimización y uso real.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [201 — Telemetry Architecture](201_W4_OS_TELEMETRY_ARCHITECTURE.md)
- [227 — Inventory System](227_W4_OS_INVENTORY_SYSTEM.md)
- [351 — Privacy Architecture](351_W4_OS_PRIVACY_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Esquemas mínimos antes de piloto remoto; ampliación mediante revisión de propósito.

---

[Anterior](351_W4_OS_PRIVACY_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](353_W4_OS_DATA_RETENTION_POLICY.md)
