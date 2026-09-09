# 310 · W4 OS — Open Source Compliance

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Licencias y distribución · **Responsabilidad propuesta:** Cumplimiento de distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Convertir obligaciones de software libre en tareas verificables de release.

## Alcance, arquitectura y decisiones

Registro por paquete con licencia, avisos, fuente correspondiente, modificaciones y mecanismo de entrega. Compliance se vincula al artefacto exacto, no sólo a una lista de proyectos.

## Componentes y flujo operativo

1. Extraer inventario
2. clasificar obligaciones
3. generar avisos y fuentes
4. verificar acceso
5. archivar evidencia.

## Seguridad y riesgos

No retirar atribuciones ni usar un enlace al upstream cambiante como sustituto universal de fuente correspondiente. Revisar obligaciones de cada licencia.

## Criterios de aceptación

Aceptar muestra de binarios trazada a fuente y avisos correctos, sin entradas desconocidas bloqueantes.

## Rendimiento y evidencia

Medir completitud.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [309 — Licensing Strategy](309_W4_OS_LICENSING_STRATEGY.md)
- [311 — GPL Compliance](311_W4_OS_GPL_COMPLIANCE.md)
- [313 — Source Code Publication Policy](313_W4_OS_SOURCE_CODE_PUBLICATION_POLICY.md)
- [388 — SBOM Strategy](388_W4_OS_SBOM_STRATEGY.md)

## Roadmap y condiciones de evolución

Puerta desde primera distribución, con revisión especializada de casos dudosos.

---

[Anterior](309_W4_OS_LICENSING_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](311_W4_OS_GPL_COMPLIANCE.md)
