# 038 · W4 OS — Dual Boot Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Conservar instalaciones existentes cuando el usuario elija convivencia con otro sistema.

## Alcance, arquitectura y decisiones

MVP prioriza disco dedicado o espacio ya libre; no automatiza reducción de particiones ajenas. El instalador identifica ESP existente y entradas de arranque antes de proponer cambios.

## Componentes y flujo operativo

1. Inventariar
2. mostrar sistemas detectados y límites
3. usar espacio autorizado
4. registrar W4 sin borrar entradas ajenas
5. verificar ambos arranques.

## Seguridad y riesgos

BitLocker, hibernación y firmware pueden complicar acceso a datos. No escribir en volúmenes ajenos hibernados ni prometer detección universal.

## Criterios de aceptación

Aceptar doble arranque en la matriz elegida y eliminación de W4 con recuperación documentada del cargador anterior; comprobar particiones preservadas.

## Rendimiento y evidencia

Registrar tiempo de selección y arranque de ambos sistemas; verificar que la instalación W4 no haya cambiado el contenido de las particiones preservadas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [026 — Bootloader Strategy](026_W4_OS_BOOTLOADER_STRATEGY.md)
- [033 — Partitioning System](033_W4_OS_PARTITIONING_SYSTEM.md)
- [341 — Migration From Windows](341_W4_OS_MIGRATION_FROM_WINDOWS.md)

## Roadmap y condiciones de evolución

Piloto de convivencia acotado; redimensionamiento se evalúa como función independiente.

---

[Anterior](037_W4_OS_ENCRYPTED_INSTALLATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](039_W4_OS_OEM_INSTALLATION_MODE.md)
