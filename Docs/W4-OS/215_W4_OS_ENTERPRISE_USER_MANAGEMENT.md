# 215 · W4 OS — Enterprise User Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Integrar usuarios organizacionales sin borrar autonomía de recuperación local.

## Alcance, arquitectura y decisiones

Separar identidad de directorio, cuenta local de emergencia y asignación de roles. Mapear UID/GID de forma estable y no reutilizar home por coincidencia de nombre.

## Componentes y flujo operativo

1. Resolver identidad
2. autenticar
3. aplicar acceso
4. preparar home
5. registrar sesión; baja revoca acceso según política y trata datos aparte.

## Seguridad y riesgos

No otorgar sudo a todo usuario del dominio. El acceso offline necesita límites de caché y procedimiento para bajas sin conectividad.

## Criterios de aceptación

Aceptar identidad duplicada local/directorio y baja organizacional con comportamiento definido.

## Rendimiento y evidencia

Medir resolución y fallos de caché.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [146 — User Management](146_W4_OS_USER_MANAGEMENT.md)
- [148 — Authentication Architecture](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)
- [216 — Directory Services](216_W4_OS_DIRECTORY_SERVICES.md)

## Roadmap y condiciones de evolución

Un directorio de referencia en piloto; múltiples bosques después.

---

[Anterior](214_W4_OS_CONFIGURATION_POLICY_ENGINE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](216_W4_OS_DIRECTORY_SERVICES.md)
