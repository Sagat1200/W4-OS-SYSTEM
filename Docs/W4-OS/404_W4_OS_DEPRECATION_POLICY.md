# 404 · W4 OS — Deprecation Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Decisiones y riesgos · **Responsabilidad propuesta:** Arquitectura y gobernanza  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Retirar interfaces y funciones con aviso, sustitución y migración verificable.

## Alcance, arquitectura y decisiones

Ciclo propuesto activo → deprecado → deshabilitado por defecto → retirado; plazos dependen de consumidores y soporte publicado. Excepciones urgentes por seguridad se explican.

## Componentes y flujo operativo

1. Inventariar uso
2. anunciar alternativa
3. proveer migración
4. medir adopción
5. retirar
6. conservar guía histórica.

## Seguridad y riesgos

No borrar datos de la función al retirarla sin plan y elección correspondiente. Evitar mantener rutas inseguras indefinidamente.

## Criterios de aceptación

Aceptar consumidor soportado con aviso y ruta de migración ensayada.

## Rendimiento y evidencia

Medir uso restante y tickets tras retirada.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [050 — Package Lifecycle](050_W4_OS_PACKAGE_LIFECYCLE.md)
- [274 — End Of Life Policy](274_W4_OS_END_OF_LIFE_POLICY.md)
- [399 — Backward Compatibility Policy](399_W4_OS_BACKWARD_COMPATIBILITY_POLICY.md)

## Roadmap y condiciones de evolución

Política desde API V1; fechas al existir compromisos concretos.

---

[Anterior](403_W4_OS_TECHNICAL_DEBT_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](405_W4_OS_EXPERIMENTAL_FEATURE_POLICY.md)
