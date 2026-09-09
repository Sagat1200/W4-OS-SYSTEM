# 367 · W4 OS — System Service API

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** APIs y protocolos · **Responsabilidad propuesta:** Arquitectura de servicios  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Exponer acciones locales del sistema sin conceder root al cliente.

## Alcance, arquitectura y decisiones

Servicio propuesto por D-Bus u otra interfaz local mantenida; acciones separadas para actualización, perfiles y diagnóstico. Autorización se evalúa en servidor por sujeto real.

## Componentes y flujo operativo

1. Recibir parámetros tipados
2. resolver recurso
3. autorizar
4. comprobar estado esperado
5. ejecutar backend
6. reportar resultado.

## Seguridad y riesgos

Evitar confianza en UID declarado por cliente, rutas intercambiadas y shell injection. Abrir recursos de forma segura antes de usarlos.

## Criterios de aceptación

Aceptar cliente no autorizado y recurso sustituido con rechazo.

## Rendimiento y evidencia

Medir cantidad de acciones y superficie privilegiada.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [151 — Privilege Escalation Model](151_W4_OS_PRIVILEGE_ESCALATION_MODEL.md)
- [214 — Configuration Policy Engine](214_W4_OS_CONFIGURATION_POLICY_ENGINE.md)
- [366 — API Architecture](366_W4_OS_API_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Pocas acciones necesarias V1; revisión de amenaza por cada ampliación.

---

[Anterior](366_W4_OS_API_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](368_W4_OS_CONTROL_CENTER_API.md)
