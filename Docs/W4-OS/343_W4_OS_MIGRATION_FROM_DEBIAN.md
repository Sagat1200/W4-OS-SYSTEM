# 343 · W4 OS — Migration From Debian

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Migración · **Responsabilidad propuesta:** Migración y compatibilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Migrar desde Debian preservando datos sin asumir compatibilidad completa del layout.

## Alcance, arquitectura y decisiones

Aunque upstream coincida, W4 incorpora perfiles, estado y recuperación propios. Ruta inicial soportada: instalación limpia y restauración; conversión directa requiere calificación por versión/layout.

## Componentes y flujo operativo

1. Inventariar release, paquetes y montajes
2. comparar con W4
3. respaldar
4. instalar perfil en destino nuevo
5. restaurar
6. validar.

## Seguridad y riesgos

No afirmar que instalar un metapaquete convierte cualquier Debian en W4 soportado. Conffiles y paquetes externos pueden romper contratos.

## Criterios de aceptación

Aceptar base destino limpia y datos preservados con diferencias de paquetes explicadas.

## Rendimiento y evidencia

Medir trabajo de ajuste.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [005 — Debian Base Strategy](005_W4_OS_DEBIAN_BASE_STRATEGY.md)
- [014 — Edition Package Profiles](014_W4_OS_EDITION_PACKAGE_PROFILES.md)
- [034 — Disk Layout Strategy](034_W4_OS_DISK_LAYOUT_STRATEGY.md)
- [344 — Migration Toolkit](344_W4_OS_MIGRATION_TOOLKIT.md)

## Roadmap y condiciones de evolución

Método limpio V1; conversión directa posterior con pruebas específicas.

---

[Anterior](342_W4_OS_MIGRATION_FROM_UBUNTU.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](344_W4_OS_MIGRATION_TOOLKIT.md)
