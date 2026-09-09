# 227 · W4 OS — Inventory System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener inventario empresarial útil sin recolectar actividad personal.

## Alcance, arquitectura y decisiones

Campos: modelo, hardware relevante, versión W4, paquetes gestionados y estado de controles; seriales sólo por propósito de activos. No inventariar documentos o historial de navegación.

## Componentes y flujo operativo

1. Recoger delta
2. normalizar
3. enviar autorizado
4. reconciliar identidad
5. marcar antigüedad
6. retirar activo sin perder historial permitido.

## Seguridad y riesgos

No inferir presencia del usuario por actividad del dispositivo. Aislar datos entre organizaciones y aplicar retención.

## Criterios de aceptación

Aceptar equipo renombrado sin duplicarse y offline marcado desactualizado.

## Rendimiento y evidencia

Medir frescura, duplicados y payload por equipo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [023 — Hardware Detection System](023_W4_OS_HARDWARE_DETECTION_SYSTEM.md)
- [010 — System Components](010_W4_OS_SYSTEM_COMPONENTS.md)
- [221 — Fleet Management](221_W4_OS_FLEET_MANAGEMENT.md)
- [352 — Data Collection Policy](352_W4_OS_DATA_COLLECTION_POLICY.md)

## Roadmap y condiciones de evolución

Inventario mínimo Business V1; campos adicionales requieren justificación.

---

[Anterior](226_W4_OS_REMOTE_DIAGNOSTICS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](228_W4_OS_COMPLIANCE_SYSTEM.md)
