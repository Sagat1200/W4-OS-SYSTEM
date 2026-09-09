# 264 · W4 OS — Cross Platform Application Support

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Compatibilidad Windows · **Responsabilidad propuesta:** Interoperabilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Validar interoperabilidad por formatos y funciones, no sólo por nombre de aplicación.

## Alcance, arquitectura y decisiones

Matriz incluye apertura, edición, impresión, macros, colaboración y exportación. Usar corpus representativo sin datos personales; conservar alternativas para funciones incompatibles.

## Componentes y flujo operativo

1. Elegir caso
2. ejecutar en W4
3. comparar con referencia
4. clasificar diferencia
5. documentar workaround o bloqueo.

## Seguridad y riesgos

No sobrescribir originales durante pruebas de conversión. Componentes externos y plugins tienen revisión de permisos y licencias.

## Criterios de aceptación

Aceptar funciones críticas del caso con resultado comparable y límites visibles.

## Rendimiento y evidencia

Medir pérdida de fidelidad y tiempo de tarea.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [126 — Third Party Application Support](126_W4_OS_THIRD_PARTY_APPLICATION_SUPPORT.md)
- [133 — Office Suite Strategy](133_W4_OS_OFFICE_SUITE_STRATEGY.md)
- [261 — Windows Compatibility Strategy](261_W4_OS_WINDOWS_COMPATIBILITY_STRATEGY.md)
- [288 — Compatibility Certification](288_W4_OS_COMPATIBILITY_CERTIFICATION.md)

## Roadmap y condiciones de evolución

Matriz inicial antes de migración; ampliación por sectores reales.

---

[Anterior](263_W4_OS_WINDOWS_VM_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](265_W4_OS_RELEASE_MODEL.md)
