# 233 · W4 OS — Home Account System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Experiencia Home · **Responsabilidad propuesta:** Producto Home  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener la cuenta local como identidad suficiente para usar Home.

## Alcance, arquitectura y decisiones

Cuenta de sesión separada de vinculación W4; nombre visible no se usa como identificador inmutable. Recuperación de contraseña local y cloud tiene procedimientos diferentes.

## Componentes y flujo operativo

1. Crear usuario
2. establecer credencial
3. guardar preferencias
4. vincular servicio opcional
5. permitir desvincular sin eliminar home.

## Seguridad y riesgos

No enviar contraseña local a servidores W4 ni condicionar actualizaciones de seguridad al login online.

## Criterios de aceptación

Aceptar desvinculación cloud con sesión, archivos y aplicaciones intactos.

## Rendimiento y evidencia

Medir incidencias de confusión entre identidades.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [146 — User Management](146_W4_OS_USER_MANAGEMENT.md)
- [148 — Authentication Architecture](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)
- [241 — W4 Account Integration](241_W4_OS_W4_ACCOUNT_INTEGRATION.md)

## Roadmap y condiciones de evolución

Cuenta local MVP; integración online posterior con pruebas de salida.

## Recuperación y desvinculación

La pantalla de identidad distingue claramente contraseña de sesión, credencial de cifrado y acceso a W4 Account. Cambiar una no afirma cambiar automáticamente las demás. Al desvincular servicios se revocan o retiran tokens según contrato y se explica qué copias locales se conservan. El usuario puede seguir abriendo archivos y actualizando el sistema. La prueba incluye proveedor remoto caído durante desvinculación: se elimina acceso local al token y se registra revocación remota pendiente sin bloquear el escritorio.

---

[Anterior](232_W4_OS_HOME_ONBOARDING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](234_W4_OS_FAMILY_USER_MODEL.md)
