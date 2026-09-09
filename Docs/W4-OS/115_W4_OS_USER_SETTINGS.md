# 115 · W4 OS — User Settings

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Centro de control · **Responsabilidad propuesta:** Configuración y experiencia  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Administrar identidad local y preferencias sin mezclar cuenta de sesión con cuenta W4.

## Alcance, arquitectura y decisiones

Usuarios locales, grupos y roles tienen operaciones distintas de la vinculación cloud. El panel distingue cambiar contraseña, cerrar sesiones y eliminar cuenta.

## Componentes y flujo operativo

1. Leer identidades permitidas
2. proponer cambio
3. autorizar
4. ejecutar mediante gestor
5. comprobar permisos y acceso.

## Seguridad y riesgos

Eliminar cuenta requiere plan sobre archivos y sesiones; no borrar home por defecto ni permitir que un usuario eleve su propio rol sin autorización.

## Criterios de aceptación

Aceptar cambio de contraseña y baja con datos conservados según elección.

## Rendimiento y evidencia

Medir consistencia de la lista de sesiones.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [146 — User Management](146_W4_OS_USER_MANAGEMENT.md)
- [147 — Group Management](147_W4_OS_GROUP_MANAGEMENT.md)
- [241 — W4 Account Integration](241_W4_OS_W4_ACCOUNT_INTEGRATION.md)

## Roadmap y condiciones de evolución

Administración local V1; directorios empresariales con controles específicos.

---

[Anterior](114_W4_OS_NETWORK_SETTINGS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](116_W4_OS_SECURITY_SETTINGS.md)
