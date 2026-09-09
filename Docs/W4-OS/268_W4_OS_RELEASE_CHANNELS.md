# 268 · W4 OS — Release Channels

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Releases y soporte de versiones · **Responsabilidad propuesta:** Ingeniería de releases  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Publicar canales de release con significado consistente para clientes y documentación.

## Alcance, arquitectura y decisiones

Experimental, candidate y stable describen madurez dentro de una línea; anillos controlan despliegue. Business no consume paquetes distintos por el simple nombre del canal.

## Componentes y flujo operativo

1. Asignar candidato
2. probar
3. promover mismo digest
4. actualizar metadatos
5. notificar cambios de canal permitidos.

## Seguridad y riesgos

No permitir downgrade incompatible por cambiar selector de UI. Experimental usa distribución y soporte claramente separados.

## Criterios de aceptación

Aceptar canal stable sin artefactos no calificados y promoción verificable.

## Rendimiento y evidencia

Medir tiempo entre candidato y publicación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [053 — Repository Channels](053_W4_OS_REPOSITORY_CHANNELS.md)
- [079 — Update Channels](079_W4_OS_UPDATE_CHANNELS.md)
- [265 — Release Model](265_W4_OS_RELEASE_MODEL.md)

## Roadmap y condiciones de evolución

Candidate/stable V1; experimental cuando exista recuperación y aislamiento.

---

[Anterior](267_W4_OS_LTS_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](269_W4_OS_RELEASE_BRANCHING_MODEL.md)
