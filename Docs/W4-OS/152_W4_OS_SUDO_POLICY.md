# 152 · W4 OS — Sudo Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Configurar sudo de forma auditable sin equivalencias peligrosas de rol.

## Alcance, arquitectura y decisiones

Reglas se distribuyen mediante archivos validados y alcance mínimo; administración total sólo para cuentas designadas. Evitar NOPASSWD general y comodines sobre programas que escapan a shell.

## Componentes y flujo operativo

1. Proponer regla
2. revisar capacidades reales
3. validar sintaxis
4. instalar con ruta de recuperación
5. probar autorización y denegación.

## Seguridad y riesgos

Permitir editor, intérprete o gestor de paquetes puede equivaler a root. No presentar esas reglas como delegación limitada.

## Criterios de aceptación

Aceptar usuario estándar denegado y administrador funcional tras cambio de reglas.

## Rendimiento y evidencia

Medir excepciones sin vencimiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [146 — User Management](146_W4_OS_USER_MANAGEMENT.md)
- [147 — Group Management](147_W4_OS_GROUP_MANAGEMENT.md)
- [151 — Privilege Escalation Model](151_W4_OS_PRIVILEGE_ESCALATION_MODEL.md)

## Roadmap y condiciones de evolución

Política sencilla V1; delegación granular preferentemente mediante API de servicio.

---

[Anterior](151_W4_OS_PRIVILEGE_ESCALATION_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](153_W4_OS_SESSION_MANAGEMENT.md)
