# 035 · W4 OS — Btrfs Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Usar Btrfs como base de snapshots de sistema con fronteras explícitas.

## Alcance, arquitectura y decisiones

La raíz versionada y subvolúmenes de datos tienen ciclos distintos. Proponer compresión tras medición; evitar cuotas complejas hasta evaluar costo. Snapshots locales no sustituyen copias externas.

## Componentes y flujo operativo

Crear snapshot previo con identificador de operación, ejecutar cambio y conservar evidencia; limpieza respeta raíz activa, candidata y último estado bueno.

## Seguridad y riesgos

La pérdida del dispositivo destruye también snapshots locales. Un snapshot no incluye recursivamente otros subvolúmenes; documentar todas las exclusiones.

## Criterios de aceptación

Aceptar comparación de raíz y datos después de revertir y manejo de poco espacio sin borrar estados protegidos.

## Rendimiento y evidencia

Medir metadatos y latencia de snapshots.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [034 — Disk Layout Strategy](034_W4_OS_DISK_LAYOUT_STRATEGY.md)
- [081 — Snapshot Architecture](081_W4_OS_SNAPSHOT_ARCHITECTURE.md)
- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Validar integridad y espacio en V1; send/receive y cuotas avanzadas se evalúan después.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [Btrfs — Subvolumes](https://btrfs.readthedocs.io/en/latest/btrfs-subvolume.html). La documentación describe subvolúmenes y snapshots. El layout, retención y coordinación de recuperación aquí definidos son propuestas W4.

---

[Anterior](034_W4_OS_DISK_LAYOUT_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](036_W4_OS_FILESYSTEM_STRATEGY.md)
