# 207 · W4 OS — Privacy Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Diagnóstico y privacidad · **Responsabilidad propuesta:** Diagnóstico y privacidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Dar al usuario control sobre datos locales, exportaciones y servicios opcionales.

## Alcance, arquitectura y decisiones

Inventario de datos por propósito, destinatario, retención y eliminación. Cuenta local funciona sin W4 Cloud; separar sincronización voluntaria de administración organizacional.

## Componentes y flujo operativo

1. Presentar propósito
2. obtener elección
3. recopilar mínimo
4. permitir inspección
5. retirar autorización
6. ejecutar eliminación aplicable.

## Seguridad y riesgos

No combinar aceptación de términos con consentimiento opcional ni usar casillas preseleccionadas. Empresas necesitan políticas claras para dispositivos personales.

## Criterios de aceptación

Aceptar uso básico sin cuenta y revocación que detiene flujo opcional.

## Rendimiento y evidencia

Medir datos sin propósito y controles inaccesibles.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [201 — Telemetry Architecture](201_W4_OS_TELEMETRY_ARCHITECTURE.md)
- [209 — Opt In Telemetry](209_W4_OS_OPT_IN_TELEMETRY.md)
- [351 — Privacy Architecture](351_W4_OS_PRIVACY_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Privacidad de diseño desde MVP; revisión específica antes de nuevos servicios.

---

[Anterior](206_W4_OS_HEALTH_MONITOR.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](208_W4_OS_TELEMETRY_PRIVACY_POLICY.md)
