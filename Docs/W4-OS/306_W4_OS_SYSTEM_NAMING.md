# 306 · W4 OS — System Naming

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Identidad y nombres · **Responsabilidad propuesta:** Experiencia e identidad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener nombres de producto y sistema coherentes sin romper detección de compatibilidad.

## Alcance, arquitectura y decisiones

Nombres oficiales W4 OS Home, W4 OS Business y W4 Linux Base; metadatos de sistema distinguen identidad W4 de afinidad Debian mediante campos adecuados evaluados.

## Componentes y flujo operativo

1. Definir convención
2. generar metadatos en build
3. comprobar UI, soporte y scripts
4. validar compatibilidad de herramientas.

## Seguridad y riesgos

No fingir ser Debian sin distinguir la derivada ni cambiar campos de forma que instaladores elijan repositorios incompatibles.

## Criterios de aceptación

Aceptar identificación de edición/build y detección correcta de familia Debian.

## Rendimiento y evidencia

Medir nombres inconsistentes en artefactos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [003 — Product Family](003_W4_OS_PRODUCT_FAMILY.md)
- [266 — Versioning Policy](266_W4_OS_VERSIONING_POLICY.md)
- [307 — Package Naming](307_W4_OS_PACKAGE_NAMING.md)

## Roadmap y condiciones de evolución

Convención congelada antes de publicación pública.

---

[Anterior](305_W4_OS_DESKTOP_BRANDING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](307_W4_OS_PACKAGE_NAMING.md)
