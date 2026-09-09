# 380 · W4 OS — Configuration Layering

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Configuración y políticas · **Responsabilidad propuesta:** Configuración de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Resolver capas de configuración con una regla coherente y procedencia consultable.

## Alcance, arquitectura y decisiones

Defaults base → edición → administrador local → política organizacional aplicable → preferencia de usuario sólo donde esté permitida; restricciones obligatorias se evalúan aparte y dominan preferencias. Excepciones tienen autoridad definida.

## Componentes y flujo operativo

1. Validar cada capa
2. combinar por clave
3. detectar contradicción
4. calcular efectivo
5. exponer origen
6. aplicar.

## Seguridad y riesgos

No usar simple último archivo gana para controles de seguridad. Una política inválida no borra configuración válida ni eleva privilegios.

## Criterios de aceptación

Aceptar conflicto de preferencia y restricción con resultado explicado, más retirada de capa que recupera fallback.

## Rendimiento y evidencia

Medir costo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [009 — Layer Model](009_W4_OS_LAYER_MODEL.md)
- [213 — Central Policy System](213_W4_OS_CENTRAL_POLICY_SYSTEM.md)
- [377 — Configuration Architecture](377_W4_OS_CONFIGURATION_ARCHITECTURE.md)
- [381 — Policy Override Model](381_W4_OS_POLICY_OVERRIDE_MODEL.md)

## Roadmap y condiciones de evolución

Semántica fijada antes de motor Business; nuevos tipos de merge por ADR.

---

[Anterior](379_W4_OS_SYSTEM_DEFAULTS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](381_W4_OS_POLICY_OVERRIDE_MODEL.md)
