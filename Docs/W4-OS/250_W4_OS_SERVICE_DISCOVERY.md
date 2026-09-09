# 250 · W4 OS — Service Discovery

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Servicios W4 opcionales · **Responsabilidad propuesta:** Integración de servicios  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Descubrir endpoints sin convertir respuestas de red en autoridad de configuración.

## Alcance, arquitectura y decisiones

Fuentes permitidas: configuración empaquetada, política autenticada o metadatos verificados del proveedor. Descubrimiento local sólo para servicios explícitos y sin ejecutar recursos anunciados.

## Componentes y flujo operativo

1. Resolver descriptor
2. validar origen y esquema
3. comprobar versión
4. cachear con caducidad
5. conectar con autenticación.

## Seguridad y riesgos

Proteger contra redirección a servidor atacante, SSRF y downgrade. No transmitir tokens a un endpoint cuyo origen cambió sin validación.

## Criterios de aceptación

Aceptar descriptor malformado, caducado y redirección no permitida con rechazo.

## Rendimiento y evidencia

Medir caché y tiempo de descubrimiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [164 — Certificate Management](164_W4_OS_CERTIFICATE_MANAGEMENT.md)
- [242 — W4 Service Integration](242_W4_OS_W4_SERVICE_INTEGRATION.md)
- [366 — API Architecture](366_W4_OS_API_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Configuración estática segura primero; descubrimiento dinámico posterior.

---

[Anterior](249_W4_OS_W4_SUPPORT_INTEGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](251_W4_OS_DEVELOPER_PLATFORM.md)
