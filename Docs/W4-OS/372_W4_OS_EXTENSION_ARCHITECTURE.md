# 372 · W4 OS — Extension Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Extensibilidad · **Responsabilidad propuesta:** Integración de extensiones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Permitir extensiones sin convertirlas en modificaciones irrestrictas del sistema.

## Alcance, arquitectura y decisiones

Extensiones de UI, sesión y servicios se clasifican por privilegio; interfaces públicas versionadas y permisos declarados. V1 prioriza configuración y paquetes existentes sobre runtime nuevo.

## Componentes y flujo operativo

1. Registrar extensión
2. validar manifiesto/origen
3. comprobar compatibilidad
4. autorizar capacidades
5. activar
6. supervisar
7. retirar.

## Seguridad y riesgos

No cargar código de terceros dentro de proceso privilegiado por comodidad. Una extensión sin mantenimiento puede bloquear upgrades y debe desactivarse de forma segura.

## Criterios de aceptación

Aceptar extensión incompatible rechazada y fallo aislado sin perder control del sistema.

## Rendimiento y evidencia

Medir overhead y superficie de permisos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [366 — API Architecture](366_W4_OS_API_ARCHITECTURE.md)
- [373 — Plugin System](373_W4_OS_PLUGIN_SYSTEM.md)
- [374 — System Modules](374_W4_OS_SYSTEM_MODULES.md)
- [405 — Experimental Feature Policy](405_W4_OS_EXPERIMENTAL_FEATURE_POLICY.md)

## Roadmap y condiciones de evolución

Diseño posterior a núcleo V1; sólo extensiones necesarias en primer piloto.

---

[Anterior](371_W4_OS_REMOTE_MANAGEMENT_PROTOCOL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](373_W4_OS_PLUGIN_SYSTEM.md)
