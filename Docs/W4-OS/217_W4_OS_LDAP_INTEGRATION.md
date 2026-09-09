# 217 · W4 OS — LDAP Integration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Usar LDAP para consultas de identidad con transporte y esquema definidos.

## Alcance, arquitectura y decisiones

Definir atributos, búsqueda de grupos, certificados y alcance de bind. LDAP por sí solo no se presenta como SSO completo ni reemplaza toda autenticación.

## Componentes y flujo operativo

1. Configurar servidor y CA
2. validar nombre TLS
3. consultar cuenta/grupos
4. mapear identidad
5. comprobar permisos.

## Seguridad y riesgos

Prohibir bind con secretos por transporte sin protección. Cuenta de consulta tiene privilegios mínimos y rotación, sin permisos de administración del directorio.

## Criterios de aceptación

Aceptar CA inválida rechazada y grupos anidados según alcance probado.

## Rendimiento y evidencia

Medir latencia y límites de consulta.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [164 — Certificate Management](164_W4_OS_CERTIFICATE_MANAGEMENT.md)
- [216 — Directory Services](216_W4_OS_DIRECTORY_SERVICES.md)
- [219 — SSO Architecture](219_W4_OS_SSO_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Integración piloto con esquema documentado; no prometer todos los directorios LDAP.

---

[Anterior](216_W4_OS_DIRECTORY_SERVICES.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](218_W4_OS_ACTIVE_DIRECTORY_INTEGRATION.md)
