# 099 · W4 OS — Lock Screen System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Proteger una sesión existente durante ausencia y suspensión.

## Alcance, arquitectura y decisiones

Utilizar bloqueo upstream con autenticación PAM; política controla inactividad y bloqueo antes de suspensión. Pantalla bloqueada limita notificaciones y acciones sensibles.

## Componentes y flujo operativo

1. Solicitar bloqueo
2. confirmar estado protegido
3. apagar pantalla o suspender
4. despertar
5. autenticar
6. restaurar sesión.

## Seguridad y riesgos

No reemplazar bloqueo real por una ventana temática. Verificar múltiples monitores y capturas remotas durante bloqueo.

## Criterios de aceptación

Aceptar bloqueo por atajo, inactividad y suspensión sin revelar escritorio en monitor reconectado.

## Rendimiento y evidencia

Medir demora hasta estado protegido.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [149 — Login Security](149_W4_OS_LOGIN_SECURITY.md)
- [153 — Session Management](153_W4_OS_SESSION_MANAGEMENT.md)
- [198 — Sleep Hibernation](198_W4_OS_SLEEP_HIBERNATION.md)

## Roadmap y condiciones de evolución

Validación obligatoria V1 y tras cambios del compositor o gestor de acceso.

---

[Anterior](098_W4_OS_LOGIN_MANAGER.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](100_W4_OS_DESKTOP_CONFIGURATION.md)
