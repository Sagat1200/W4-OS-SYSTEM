# 354 · W4 OS — User Consent Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Privacidad de datos · **Responsabilidad propuesta:** Privacidad y gobierno de datos  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Registrar consentimiento por propósito de forma libre, específica y reversible.

## Alcance, arquitectura y decisiones

Modelo guarda versión de aviso, decisión, momento y alcance mínimo; rechazo no bloquea funciones no relacionadas. Gestión empresarial puede usar bases distintas y se explica separadamente.

## Componentes y flujo operativo

1. Presentar
2. permitir aceptar/rechazar
3. guardar
4. comprobar antes de uso
5. revocar
6. detener flujo
7. informar eliminación aplicable.

## Seguridad y riesgos

No mezclar finalidades ni renovar por silencio. Cambiar versión de aviso no permite ampliar automáticamente datos autorizados.

## Criterios de aceptación

Aceptar revocación offline y cambio de finalidad que requiere nueva decisión.

## Rendimiento y evidencia

Medir consentimientos incoherentes con flujos activos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [209 — Opt In Telemetry](209_W4_OS_OPT_IN_TELEMETRY.md)
- [351 — Privacy Architecture](351_W4_OS_PRIVACY_ARCHITECTURE.md)
- [352 — Data Collection Policy](352_W4_OS_DATA_COLLECTION_POLICY.md)

## Roadmap y condiciones de evolución

Control previo a telemetría o servicio opcional, con revisión jurídica contextual.

---

[Anterior](353_W4_OS_DATA_RETENTION_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](355_W4_OS_ENTERPRISE_PRIVACY_CONTROLS.md)
