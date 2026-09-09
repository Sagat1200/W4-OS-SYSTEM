# 403 · W4 OS — Technical Debt Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Decisiones y riesgos · **Responsabilidad propuesta:** Arquitectura y gobernanza  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Gestionar deuda técnica por costo y riesgo, evitando convertir atajos en arquitectura permanente.

## Alcance, arquitectura y decisiones

Entrada de deuda incluye causa, componente, impacto, interés operativo, dueño y condición de pago. Excepciones de prueba o soporte se vinculan a release.

## Componentes y flujo operativo

1. Registrar atajo
2. estimar efecto
3. priorizar frente a funciones
4. programar corrección
5. probar
6. cerrar evidencia.

## Seguridad y riesgos

No clasificar vulnerabilidad crítica o pérdida de datos como deuda opcional. Deuda acumulada puede bloquear nuevas variantes.

## Criterios de aceptación

Aceptar excepción con vencimiento y criterio de cierre, sin duplicación de tickets.

## Rendimiento y evidencia

Medir antigüedad y esfuerzo de mantenimiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [327 — Package Maintainer Model](327_W4_OS_PACKAGE_MAINTAINER_MODEL.md)
- [394 — Patch Management](394_W4_OS_PATCH_MANAGEMENT.md)
- [402 — Risk Register](402_W4_OS_RISK_REGISTER.md)
- [414 — Roadmap](414_W4_OS_ROADMAP.md)

## Roadmap y condiciones de evolución

Registrar desde MVP y reservar capacidad de corrección por release.

---

[Anterior](402_W4_OS_RISK_REGISTER.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](404_W4_OS_DEPRECATION_POLICY.md)
