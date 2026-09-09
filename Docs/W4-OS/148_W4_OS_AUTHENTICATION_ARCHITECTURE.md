# 148 · W4 OS — Authentication Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Unificar autenticación local y empresarial sin reemplazar mecanismos probados.

## Alcance, arquitectura y decisiones

PAM coordina acceso local; proveedor de identidad empresarial se integra por componentes mantenidos. Autenticación, autorización y desbloqueo de disco son funciones distintas.

## Componentes y flujo operativo

1. Recibir identidad
2. seleccionar proveedor
3. verificar factor
4. evaluar cuenta
5. crear sesión; fallos producen mensaje útil sin enumeración indebida.

## Seguridad y riesgos

No enviar contraseñas locales a W4 Cloud ni confundir token web con contraseña Unix. Evitar rutas de fallback que omitan controles obligatorios.

## Criterios de aceptación

Aceptar proveedor caído, contraseña inválida y cuenta bloqueada con decisiones correctas.

## Rendimiento y evidencia

Medir latencia y disponibilidad local.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [149 — Login Security](149_W4_OS_LOGIN_SECURITY.md)
- [153 — Session Management](153_W4_OS_SESSION_MANAGEMENT.md)
- [216 — Directory Services](216_W4_OS_DIRECTORY_SERVICES.md)

## Roadmap y condiciones de evolución

Local primero; directorio y SSO requieren matriz de fallos separada.

---

[Anterior](147_W4_OS_GROUP_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](149_W4_OS_LOGIN_SECURITY.md)
