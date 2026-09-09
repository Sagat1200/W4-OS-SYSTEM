# 381 · W4 OS — Policy Override Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Configuración y políticas · **Responsabilidad propuesta:** Configuración de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Permitir excepciones de política limitadas, auditables y con caducidad.

## Alcance, arquitectura y decisiones

Excepción identifica clave, dispositivo/grupo, valor permitido, motivo, aprobador y vencimiento. No es autorización para desactivar autenticidad de actualizaciones ni controles estructurales.

## Componentes y flujo operativo

1. Solicitar
2. evaluar impacto
3. autorizar alcance
4. aplicar
5. registrar
6. avisar vencimiento
7. volver a política vigente.

## Seguridad y riesgos

No aceptar excepción firmada por rol insuficiente ni reutilizarla en otra organización. Falta de red al vencer sigue política local definida.

## Criterios de aceptación

Aceptar excepción expirada y replay rechazados con retorno verificable.

## Rendimiento y evidencia

Medir excepciones activas y vencidas sin resolver.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [120 — Business Policy Settings](120_W4_OS_BUSINESS_POLICY_SETTINGS.md)
- [213 — Central Policy System](213_W4_OS_CENTRAL_POLICY_SYSTEM.md)
- [214 — Configuration Policy Engine](214_W4_OS_CONFIGURATION_POLICY_ENGINE.md)
- [380 — Configuration Layering](380_W4_OS_CONFIGURATION_LAYERING.md)

## Roadmap y condiciones de evolución

Piloto Business después de precedencia estable y auditoría disponible.

---

[Anterior](380_W4_OS_CONFIGURATION_LAYERING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](382_W4_OS_FAILSAFE_ARCHITECTURE.md)
