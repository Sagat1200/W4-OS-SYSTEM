# 342 · W4 OS — Migration From Ubuntu

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Migración · **Responsabilidad propuesta:** Migración y compatibilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Migrar desde Ubuntu evitando una conversión de repositorios in situ no calificada.

## Alcance, arquitectura y decisiones

Propuesta V1: respaldo e instalación limpia W4, luego restauración selectiva. No sustituir sources Ubuntu por Debian/W4 y ejecutar upgrade como método soportado.

## Componentes y flujo operativo

1. Inventariar paquetes y datos
2. mapear alternativas
3. respaldar
4. instalar
5. restaurar preferencias compatibles
6. validar.

## Seguridad y riesgos

PPAs y paquetes Ubuntu no se importan al sistema Debian. Revisar datos de snaps, servicios y cifrado antes de borrar origen.

## Criterios de aceptación

Aceptar documentos y aplicaciones prioritarias disponibles sin repositorios Ubuntu heredados.

## Rendimiento y evidencia

Medir incompatibilidades de configuración.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [005 — Debian Base Strategy](005_W4_OS_DEBIAN_BASE_STRATEGY.md)
- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)
- [344 — Migration Toolkit](344_W4_OS_MIGRATION_TOOLKIT.md)
- [345 — User Data Migration](345_W4_OS_USER_DATA_MIGRATION.md)

## Roadmap y condiciones de evolución

Ruta limpia V1; conversión in situ sólo como investigación independiente.

---

[Anterior](341_W4_OS_MIGRATION_FROM_WINDOWS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](343_W4_OS_MIGRATION_FROM_DEBIAN.md)
