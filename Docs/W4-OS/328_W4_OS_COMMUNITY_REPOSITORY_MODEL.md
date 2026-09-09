# 328 · W4 OS — Community Repository Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Comunidad y gobernanza · **Responsabilidad propuesta:** Gobernanza y mantenimiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Alojar aportes comunitarios sin confundirlos con repositorio soportado.

## Alcance, arquitectura y decisiones

Archivo y claves separados, catálogo con nivel de revisión y origen visible; opt-in por usuario. No dar prioridad global que reemplace base estable.

## Componentes y flujo operativo

1. Enviar paquete
2. comprobar mínimos
3. publicar como comunitario
4. recibir feedback
5. promover sólo tras proceso oficial independiente.

## Seguridad y riesgos

No considerar popularidad como auditoría. Código comunitario se construye aislado y no accede a firma de producción.

## Criterios de aceptación

Aceptar cliente oficial que no instala paquetes comunitarios sin elección y origen visible en UI.

## Rendimiento y evidencia

Medir incidentes y mantenimiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [051 — Repository Architecture](051_W4_OS_REPOSITORY_ARCHITECTURE.md)
- [056 — Testing Repository](056_W4_OS_TESTING_REPOSITORY.md)
- [126 — Third Party Application Support](126_W4_OS_THIRD_PARTY_APPLICATION_SUPPORT.md)

## Roadmap y condiciones de evolución

Posterior a V1, cuando exista capacidad de moderación y build seguro.

---

[Anterior](327_W4_OS_PACKAGE_MAINTAINER_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](329_W4_OS_SUPPORT_MODEL.md)
