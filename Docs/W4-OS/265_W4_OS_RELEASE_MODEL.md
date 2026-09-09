# 265 · W4 OS — Release Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Releases y soporte de versiones · **Responsabilidad propuesta:** Ingeniería de releases  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir releases W4 sobre una base Debian fijada con mantenimiento predecible.

## Alcance, arquitectura y decisiones

Línea mayor vincula base y contratos; releases menores añaden funciones compatibles y mantenimiento corrige defectos. Fechas y duración comercial no se comprometen sin capacidad.

## Componentes y flujo operativo

1. Planificar alcance
2. congelar
3. construir candidato
4. calificar
5. publicar
6. mantener
7. migrar o retirar al final.

## Seguridad y riesgos

No seguir automáticamente nueva Debian Stable ni prolongar soporte W4 más allá de cobertura real sin plan propio financiado.

## Criterios de aceptación

Aceptar release identificable y actualización desde versión anterior soportada.

## Rendimiento y evidencia

Medir cumplimiento de puertas, regresiones y deuda pendiente.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [266 — Versioning Policy](266_W4_OS_VERSIONING_POLICY.md)
- [267 — LTS Strategy](267_W4_OS_LTS_STRATEGY.md)
- [270 — Release Engineering](270_W4_OS_RELEASE_ENGINEERING.md)
- [274 — End Of Life Policy](274_W4_OS_END_OF_LIFE_POLICY.md)

## Roadmap y condiciones de evolución

V1 por hitos de evidencia; calendario público tras piloto.

---

[Anterior](264_W4_OS_CROSS_PLATFORM_APPLICATION_SUPPORT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](266_W4_OS_VERSIONING_POLICY.md)
