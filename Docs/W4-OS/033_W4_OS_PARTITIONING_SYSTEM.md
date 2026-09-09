# 033 · W4 OS — Partitioning System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Evitar pérdida de datos por planificación ambigua del almacenamiento.

## Alcance, arquitectura y decisiones

Modelo declarativo con disco estable, tabla, particiones, formato y puntos de montaje. Distinguir crear, conservar y redimensionar; redimensionamiento queda fuera del MVP inicial.

## Componentes y flujo operativo

1. Inspeccionar firmas
2. comprobar montajes y capacidad
3. producir diff
4. confirmar
5. aplicar secuencia
6. verificar UUID y fstab.

## Seguridad y riesgos

Rechazar operaciones sobre el medio instalador y dispositivos con identidad cambiante. No tratar fallo de reconocimiento como disco vacío.

## Criterios de aceptación

Aceptar planes de disco vacío y particiones preservadas con hashes previos intactos; probar desconexión entre planificación y ejecución.

## Rendimiento y evidencia

Medir duración de planificación y aplicación por tipo de operación; conservar hashes de particiones preservadas como evidencia de ausencia de cambios fuera del plan.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [031 — Installer Architecture](031_W4_OS_INSTALLER_ARCHITECTURE.md)
- [034 — Disk Layout Strategy](034_W4_OS_DISK_LAYOUT_STRATEGY.md)
- [038 — Dual Boot Strategy](038_W4_OS_DUAL_BOOT_STRATEGY.md)

## Roadmap y condiciones de evolución

V1 admite rutas certificadas; habilitar redimensionamiento sólo con recuperación probada.

---

[Anterior](032_W4_OS_INSTALLATION_FLOW.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](034_W4_OS_DISK_LAYOUT_STRATEGY.md)
