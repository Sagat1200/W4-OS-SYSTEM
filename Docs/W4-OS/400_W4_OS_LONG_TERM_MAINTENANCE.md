# 400 · W4 OS — Long Term Maintenance

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Evolución y mantenimiento · **Responsabilidad propuesta:** Mantenimiento de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Sostener mantenimiento de líneas y componentes durante toda su cobertura publicada.

## Alcance, arquitectura y decisiones

Plan incluye responsables, builds repetibles, fuentes retenidas, hardware de regresión, avisos y presupuesto. Duración depende de soporte upstream y capacidad W4.

## Componentes y flujo operativo

1. Revisar cobertura
2. actualizar riesgos
3. mantener infraestructura
4. probar correcciones
5. informar EOL
6. ayudar transición.

## Seguridad y riesgos

No dejar claves y repositorios sin custodio al terminar desarrollo activo. Componentes sin mantenimiento necesitan sustitución o cobertura financiada.

## Criterios de aceptación

Aceptar simulacro de corrección de una línea antigua soportada con build y pruebas disponibles.

## Rendimiento y evidencia

Medir deuda, cobertura y costo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [267 — LTS Strategy](267_W4_OS_LTS_STRATEGY.md)
- [274 — End Of Life Policy](274_W4_OS_END_OF_LIFE_POLICY.md)
- [327 — Package Maintainer Model](327_W4_OS_PACKAGE_MAINTAINER_MODEL.md)
- [397 — Debian Release Transition](397_W4_OS_DEBIAN_RELEASE_TRANSITION.md)

## Roadmap y condiciones de evolución

Plan antes de comprometer LTS y revisión periódica según línea.

---

[Anterior](399_W4_OS_BACKWARD_COMPATIBILITY_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](401_W4_OS_ARCHITECTURE_DECISION_RECORDS.md)
