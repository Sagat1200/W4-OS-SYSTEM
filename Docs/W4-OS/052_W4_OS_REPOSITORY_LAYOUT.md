# 052 · W4 OS — Repository Layout

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Paquetes y repositorios · **Responsabilidad propuesta:** Mantenimiento y distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ordenar el archivo de paquetes para que suites y arquitecturas no se mezclen.

## Alcance, arquitectura y decisiones

Usar layout compatible con APT con dists, pool e índices por arquitectura; nomenclatura W4 incluye línea de producto y base. El snapshot inmutable se registra aparte del canal mutable.

## Componentes y flujo operativo

1. Ingresar paquete
2. calcular índices
3. verificar referencias y hashes
4. publicar como conjunto
5. actualizar puntero de canal.

## Seguridad y riesgos

No reutilizar el mismo nombre de suite para bases Debian incompatibles. Las rutas del archivo no aceptan nombres no normalizados del build.

## Criterios de aceptación

Aceptar que ningún índice apunte a un archivo inexistente y que retirar un canal no rompa snapshots retenidos.

## Rendimiento y evidencia

Medir objetos duplicados.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [051 — Repository Architecture](051_W4_OS_REPOSITORY_ARCHITECTURE.md)
- [308 — Repository Naming](308_W4_OS_REPOSITORY_NAMING.md)
- [070 — Release Artifact System](070_W4_OS_RELEASE_ARTIFACT_SYSTEM.md)

## Roadmap y condiciones de evolución

Congelar convenciones antes de clientes públicos y documentar retención de archivos.

---

[Anterior](051_W4_OS_REPOSITORY_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](053_W4_OS_REPOSITORY_CHANNELS.md)
