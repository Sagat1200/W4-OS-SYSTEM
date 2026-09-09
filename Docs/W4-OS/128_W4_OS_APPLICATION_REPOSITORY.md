# 128 · W4 OS — Application Repository

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener metadatos de aplicaciones separados de la confianza del archivo de paquetes.

## Alcance, arquitectura y decisiones

Catálogo enlaza app-id, origen, formato, versión, licencia, soporte y capacidades. Datos AppStream cuando estén disponibles se validan y normalizan.

## Componentes y flujo operativo

1. Importar metadatos
2. verificar enlaces a artefactos
3. eliminar duplicados sólo con reglas explícitas
4. publicar versión de catálogo.

## Seguridad y riesgos

No permitir que una app suplante el identificador o icono de otra para heredar confianza. Registrar procedencia de cada campo.

## Criterios de aceptación

Aceptar catálogo con enlace roto o identificador duplicado detectado antes de publicación.

## Rendimiento y evidencia

Medir cobertura y frescura.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [044 — Package Metadata System](044_W4_OS_PACKAGE_METADATA_SYSTEM.md)
- [124 — Flatpak Strategy](124_W4_OS_FLATPAK_STRATEGY.md)
- [127 — Application Store](127_W4_OS_APPLICATION_STORE.md)

## Roadmap y condiciones de evolución

Conjunto reducido en V1; automatizar validación al crecer.

---

[Anterior](127_W4_OS_APPLICATION_STORE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](129_W4_OS_APPLICATION_PERMISSIONS.md)
