# 213 · W4 OS — Central Policy System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Resolver políticas con precedencia y conflictos visibles.

## Alcance, arquitectura y decisiones

Esquema distingue requisito obligatorio, default y preferencia; fuentes incluyen base, edición, organización y excepción autorizada. Política inválida no sustituye última válida.

## Componentes y flujo operativo

1. Descargar versión
2. autenticar
3. validar esquema
4. detectar conflictos
5. calcular diff
6. aplicar
7. reportar efectivo.

## Seguridad y riesgos

No permitir que una política rebaje controles innegociables de autenticidad o amplíe acciones privilegiadas sin revisión. Registrar quién impuso cada clave.

## Criterios de aceptación

Aceptar políticas incompatibles con rechazo explicable y conservación de versión previa.

## Rendimiento y evidencia

Medir convergencia y claves desconocidas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [120 — Business Policy Settings](120_W4_OS_BUSINESS_POLICY_SETTINGS.md)
- [214 — Configuration Policy Engine](214_W4_OS_CONFIGURATION_POLICY_ENGINE.md)
- [380 — Configuration Layering](380_W4_OS_CONFIGURATION_LAYERING.md)
- [381 — Policy Override Model](381_W4_OS_POLICY_OVERRIDE_MODEL.md)

## Roadmap y condiciones de evolución

Conjunto pequeño de claves V1 Business; crecer por contratos versionados.

## Ejemplo ilustrativo de política

```yaml
# Contrato de diseño; no representa una API W4 implementada.
schema_version: 1
policy_id: lab-baseline
revision: 7
scope:
  organization: lab-organization
  device_group: pilot
settings:
  screen_lock.required:
    kind: mandatory
    value: true
  telemetry.product.enabled:
    kind: restriction
    value: false
```

El ejemplo demuestra tipos de intención, no define todavía todos los nombres de claves públicos. El esquema real se congela con el backend. Una política puede prohibir un flujo opcional; no debe crear consentimiento del usuario para una finalidad ajena mediante una clave booleana.

## Resolución de conflictos

Dos restricciones obligatorias incompatibles no se resuelven por orden de llegada. El motor rechaza la nueva versión o marca el conjunto conflictivo según el contrato, manteniendo la última configuración válida. El resultado incluye las claves y fuentes en conflicto, sin incluir secretos. Una preferencia de usuario que contradice una restricción válida permanece almacenada si el modelo lo permite, pero no se aplica mientras exista la restricción; la UI explica el origen.

La prueba de aceptación debe incluir política válida, política malformada, replay de revisión antigua, desconexión, excepción vigente y excepción vencida. El equipo desconectado no se etiqueta automáticamente como conforme: se muestra la última evidencia y su antigüedad.

---

[Anterior](212_W4_OS_ENTERPRISE_DEVICE_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](214_W4_OS_CONFIGURATION_POLICY_ENGINE.md)
