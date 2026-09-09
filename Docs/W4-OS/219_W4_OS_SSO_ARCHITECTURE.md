# 219 · W4 OS — SSO Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Separar SSO web de autenticación del escritorio y definir puentes explícitos.

## Alcance, arquitectura y decisiones

OIDC/SAML para servicios según proveedor; Kerberos para recursos empresariales cuando aplique. Tokens de aplicación no se convierten automáticamente en credenciales root o desbloqueo de disco.

## Componentes y flujo operativo

1. Autenticar con proveedor
2. validar audiencia y estado
3. emitir sesión acotada
4. renovar
5. cerrar y revocar según capacidades.

## Seguridad y riesgos

No reutilizar tokens entre servicios con audiencias distintas. El logout local y remoto tienen límites que la UI debe explicar.

## Criterios de aceptación

Aceptar token de audiencia incorrecta, expiración y proveedor caído sin elevar privilegios.

## Rendimiento y evidencia

Medir renovaciones y sesiones huérfanas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [148 — Authentication Architecture](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)
- [163 — Secret Storage](163_W4_OS_SECRET_STORAGE.md)
- [241 — W4 Account Integration](241_W4_OS_W4_ACCOUNT_INTEGRATION.md)
- [247 — W4 Identity Integration](247_W4_OS_W4_IDENTITY_INTEGRATION.md)

## Roadmap y condiciones de evolución

SSO para servicios primero; integración de escritorio sólo por ADR específico.

---

[Anterior](218_W4_OS_ACTIVE_DIRECTORY_INTEGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](220_W4_OS_ENTERPRISE_CERTIFICATES.md)
