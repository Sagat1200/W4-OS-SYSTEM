# 209 · W4 OS — Opt In Telemetry

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Diagnóstico y privacidad · **Responsabilidad propuesta:** Diagnóstico y privacidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Implementar consentimiento de telemetría como control efectivo y reversible.

## Alcance, arquitectura y decisiones

Estado local por propósito y versión de aviso, desactivado inicialmente. La ausencia de respuesta significa no autorizado; cambios de alcance requieren nueva elección.

## Componentes y flujo operativo

1. Mostrar elección no bloqueante
2. guardar decisión
3. comprobar antes de encolar y enviar
4. revocar
5. vaciar cola opcional.

## Seguridad y riesgos

No condicionar instalación o seguridad a participar. Evitar reactivar tras reinstalar perfil, migrar edición o restaurar configuración antigua.

## Criterios de aceptación

Aceptar no respuesta, rechazo y revocación offline sin transmisión posterior indebida.

## Rendimiento y evidencia

Medir coherencia entre UI, cola y transporte.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [201 — Telemetry Architecture](201_W4_OS_TELEMETRY_ARCHITECTURE.md)
- [207 — Privacy Model](207_W4_OS_PRIVACY_MODEL.md)
- [354 — User Consent Model](354_W4_OS_USER_CONSENT_MODEL.md)

## Roadmap y condiciones de evolución

Requisito previo a piloto de telemetría, no función necesaria del MVP.

---

[Anterior](208_W4_OS_TELEMETRY_PRIVACY_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](210_W4_OS_SUPPORT_BUNDLE_SYSTEM.md)
