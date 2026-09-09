# 155 · W4 OS — Parental Control Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ayudar a familias a gestionar horarios y aplicaciones sin prometer vigilancia infalible.

## Alcance, arquitectura y decisiones

Políticas por cuenta local con horarios y categorías acotadas; explicar límites de filtrado y cifrado. Priorizar controles transparentes y adecuados a la edad.

## Componentes y flujo operativo

1. Tutor configura regla
2. sistema muestra alcance
3. aplica al usuario objetivo
4. registra eventos mínimos
5. permite revisión y excepción.

## Seguridad y riesgos

No capturar mensajes, contraseñas o pantalla de forma oculta. Un administrador local puede modificar controles; no afirmar resistencia absoluta.

## Criterios de aceptación

Aceptar regla horaria y restricción de app sin bloquear cuentas ajenas.

## Rendimiento y evidencia

Medir falsos bloqueos y facilidad de recuperación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [146 — User Management](146_W4_OS_USER_MANAGEMENT.md)
- [234 — Family User Model](234_W4_OS_FAMILY_USER_MODEL.md)
- [354 — User Consent Model](354_W4_OS_USER_CONSENT_MODEL.md)

## Roadmap y condiciones de evolución

Investigación posterior al MVP; lanzamiento exige revisión de privacidad y usabilidad familiar.

---

[Anterior](154_W4_OS_GUEST_SESSION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](156_W4_OS_SECURITY_ARCHITECTURE.md)
