# 266 · W4 OS — Versioning Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Releases y soporte de versiones · **Responsabilidad propuesta:** Ingeniería de releases  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Versionar producto, paquetes, API y esquema de datos sin confundirlos.

## Alcance, arquitectura y decisiones

Versión W4 mayor.menor.parche propuesta; paquetes respetan orden Debian y sufijo W4; APIs y datos declaran compatibilidad propia. Build-id identifica artefacto exacto.

## Componentes y flujo operativo

1. Asignar versión
2. validar orden
3. generar manifiesto
4. construir
5. impedir reutilización de identificador publicado.

## Seguridad y riesgos

No hacer pasar recompilación distinta por el mismo artefacto. Un cambio de esquema irreversible requiere evaluación aunque la versión comercial sea menor.

## Criterios de aceptación

Aceptar comparación de upgrade correcta y trazabilidad desde UI hasta paquete/build.

## Rendimiento y evidencia

Medir colisiones y versiones ambiguas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [042 — DEB Package Architecture](042_W4_OS_DEB_PACKAGE_ARCHITECTURE.md)
- [070 — Release Artifact System](070_W4_OS_RELEASE_ARTIFACT_SYSTEM.md)
- [366 — API Architecture](366_W4_OS_API_ARCHITECTURE.md)
- [398 — Major Version Migration](398_W4_OS_MAJOR_VERSION_MIGRATION.md)

## Roadmap y condiciones de evolución

Convención aprobada antes de primer candidato público.

---

[Anterior](265_W4_OS_RELEASE_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](267_W4_OS_LTS_STRATEGY.md)
