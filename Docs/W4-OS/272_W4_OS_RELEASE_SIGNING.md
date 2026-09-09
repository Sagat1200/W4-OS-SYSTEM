# 272 · W4 OS — Release Signing

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Releases y soporte de versiones · **Responsabilidad propuesta:** Ingeniería de releases  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Firmar entregables sólo después de validar su identidad y calificación.

## Alcance, arquitectura y decisiones

Servicio de firma recibe hashes y autorización de release; claves separadas de archivo APT y arranque cuando proceda. Publicar instrucciones de verificación y claves auténticas.

## Componentes y flujo operativo

1. Validar manifiesto
2. comprobar aprobación
3. firmar
4. verificar firma externamente
5. publicar junto al artefacto.

## Seguridad y riesgos

No permitir firma arbitraria de cualquier archivo solicitado por worker. Ensayar pérdida o compromiso de clave sin depender de ella para anunciar reemplazo.

## Criterios de aceptación

Aceptar archivo alterado rechazado y firma válida reproduciblemente comprobable.

## Rendimiento y evidencia

Medir tiempo de custodia y rotación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [047 — Package Signing System](047_W4_OS_PACKAGE_SIGNING_SYSTEM.md)
- [168 — Code Signing Model](168_W4_OS_CODE_SIGNING_MODEL.md)
- [270 — Release Engineering](270_W4_OS_RELEASE_ENGINEERING.md)

## Roadmap y condiciones de evolución

Proceso definido antes de primera descarga pública.

---

[Anterior](271_W4_OS_RELEASE_QUALIFICATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](273_W4_OS_RELEASE_ROLLOUT.md)
