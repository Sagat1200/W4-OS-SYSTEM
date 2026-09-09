# 036 · W4 OS — Filesystem Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Separar filesystem de sistema, intercambio y medios externos según sus necesidades.

## Alcance, arquitectura y decisiones

Btrfs es propuesta para raíz certificada; ESP usa el formato requerido por UEFI. Medios externos conservan formato soportado; no convertirlos para obtener snapshots.

## Componentes y flujo operativo

Detectar formato y capacidad, seleccionar política de montaje y exponer límites como tamaño de archivo o falta de permisos POSIX.

## Seguridad y riesgos

No ejecutar reparación destructiva al montar. Formatos sin semántica Linux no alojan silenciosamente un directorio de sistema que dependa de ella.

## Criterios de aceptación

Aceptar lectura y escritura en matriz de formatos permitidos y rechazo claro de formatos desconocidos.

## Rendimiento y evidencia

Medir copia de archivos pequeños y grandes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [034 — Disk Layout Strategy](034_W4_OS_DISK_LAYOUT_STRATEGY.md)
- [186 — Storage Architecture](186_W4_OS_STORAGE_ARCHITECTURE.md)
- [188 — External Storage](188_W4_OS_EXTERNAL_STORAGE.md)

## Roadmap y condiciones de evolución

Certificar formatos frecuentes; otras combinaciones se documentan como no calificadas.

---

[Anterior](035_W4_OS_BTRFS_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](037_W4_OS_ENCRYPTED_INSTALLATION.md)
