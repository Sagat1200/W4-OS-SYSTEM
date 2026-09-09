# 147 · W4 OS — Group Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir grupos como permisos concretos y revisar sus efectos indirectos.

## Alcance, arquitectura y decisiones

Catálogo de grupos incluye dueño, recurso y riesgo; pertenecer a grupos de contenedores o dispositivos puede conceder capacidades amplias. Roles W4 se traducen a permisos documentados.

## Componentes y flujo operativo

1. Solicitar acceso
2. comprobar necesidad
3. autorizar membresía
4. actualizar sesión
5. verificar recurso
6. revisar caducidad.

## Seguridad y riesgos

No usar un grupo universal de soporte. Retirar membresía puede requerir cierre de sesiones existentes para eliminar acceso efectivo.

## Criterios de aceptación

Aceptar alta y revocación comprobando sesión nueva y activa.

## Rendimiento y evidencia

Medir grupos privilegiados sin responsable.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [146 — User Management](146_W4_OS_USER_MANAGEMENT.md)
- [151 — Privilege Escalation Model](151_W4_OS_PRIVILEGE_ESCALATION_MODEL.md)
- [255 — Podman Docker Support](255_W4_OS_PODMAN_DOCKER_SUPPORT.md)

## Roadmap y condiciones de evolución

Catálogo mínimo V1; accesos temporales Business después de integrar auditoría.

---

[Anterior](146_W4_OS_USER_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)
