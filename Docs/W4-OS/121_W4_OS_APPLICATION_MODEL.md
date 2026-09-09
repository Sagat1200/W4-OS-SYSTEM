# 121 · W4 OS — Application Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Separar aplicaciones de usuario de componentes que mantienen el sistema operativo.

## Alcance, arquitectura y decisiones

DEB para servicios y componentes integrados; Flatpak propuesto para aplicaciones gráficas cuando cumpla compatibilidad y soporte. El catálogo evita duplicados indistinguibles.

## Componentes y flujo operativo

1. Seleccionar aplicación
2. mostrar origen y formato
3. instalar por backend
4. registrar versión y permisos
5. actualizar en su ciclo.

## Seguridad y riesgos

No presentar aislamiento Flatpak como garantía absoluta; permisos amplios reducen su protección. Apps que requieren privilegios pasan evaluación de sistema.

## Criterios de aceptación

Aceptar instalación y retirada sin alterar núcleo ni perder datos no confirmados.

## Rendimiento y evidencia

Medir duplicación de runtimes y fallos de asociación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [122 — Application Installation System](122_W4_OS_APPLICATION_INSTALLATION_SYSTEM.md)
- [123 — Application Sandboxing](123_W4_OS_APPLICATION_SANDBOXING.md)
- [124 — Flatpak Strategy](124_W4_OS_FLATPAK_STRATEGY.md)

## Roadmap y condiciones de evolución

Aplicaciones esenciales V1; terceros por catálogo explícito.

---

[Anterior](120_W4_OS_BUSINESS_POLICY_SETTINGS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](122_W4_OS_APPLICATION_INSTALLATION_SYSTEM.md)
