# 098 · W4 OS — Login Manager

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Seleccionar un gestor de acceso integrado con el escritorio y accesibilidad.

## Alcance, arquitectura y decisiones

Usar display manager compatible y mantenido en Debian; pantalla W4 no reemplaza el motor de autenticación. Configuración y temas se distribuyen por paquete.

## Componentes y flujo operativo

1. Mostrar usuarios según política
2. elegir sesión
3. autenticar vía PAM
4. iniciar
5. recuperar pantalla tras cierre o fallo.

## Seguridad y riesgos

No permitir autologin por defecto en Business. Ocultar información de usuarios cuando la organización lo exija sin romper accesibilidad.

## Criterios de aceptación

Aceptar contraseña errónea, cambio de teclado y sesión que falla con retorno seguro a acceso.

## Rendimiento y evidencia

Medir tiempo de recuperación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [092 — Desktop Environment Strategy](092_W4_OS_DESKTOP_ENVIRONMENT_STRATEGY.md)
- [148 — Authentication Architecture](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)
- [149 — Login Security](149_W4_OS_LOGIN_SECURITY.md)

## Roadmap y condiciones de evolución

Selección junto al escritorio; autologin Home sólo como opción informada.

---

[Anterior](097_W4_OS_DESKTOP_SESSION_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](099_W4_OS_LOCK_SCREEN_SYSTEM.md)
