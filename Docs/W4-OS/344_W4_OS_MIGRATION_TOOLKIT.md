# 344 · W4 OS — Migration Toolkit

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Migración · **Responsabilidad propuesta:** Migración y compatibilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Construir herramientas de migración por inventario y copia verificable.

## Alcance, arquitectura y decisiones

Toolkit de lectura identifica cuentas, datos, aplicaciones y formato; exporta plan y manifiesto. Ejecutores separados copian sólo rutas seleccionadas y validadas.

## Componentes y flujo operativo

1. Escanear
2. redactar inventario
3. proponer mapeo
4. validar respaldo
5. copiar
6. comprobar hashes/permisos
7. emitir reporte.

## Seguridad y riesgos

No ejecutar aplicaciones del origen ni seguir enlaces fuera de selección. No copiar secretos a reportes o destinos no cifrados por defecto.

## Criterios de aceptación

Aceptar origen con nombres especiales y enlaces sin escape de destino.

## Rendimiento y evidencia

Medir cobertura y errores por archivo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [341 — Migration From Windows](341_W4_OS_MIGRATION_FROM_WINDOWS.md)
- [342 — Migration From Ubuntu](342_W4_OS_MIGRATION_FROM_UBUNTU.md)
- [343 — Migration From Debian](343_W4_OS_MIGRATION_FROM_DEBIAN.md)
- [345 — User Data Migration](345_W4_OS_USER_DATA_MIGRATION.md)

## Roadmap y condiciones de evolución

Inventario y copia V1; traducción de configuraciones después.

---

[Anterior](343_W4_OS_MIGRATION_FROM_DEBIAN.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](345_W4_OS_USER_DATA_MIGRATION.md)
