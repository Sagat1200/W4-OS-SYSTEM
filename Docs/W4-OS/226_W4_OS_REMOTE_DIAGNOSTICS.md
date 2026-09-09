# 226 · W4 OS — Remote Diagnostics

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Recoger diagnóstico remoto con alcance predefinido y mínimo acceso a datos.

## Alcance, arquitectura y decisiones

Agente ejecuta checks tipados y genera bundle revisable; servidor recibe sólo campos autorizados. Diagnóstico no incluye shell interactiva ni lectura libre del home.

## Componentes y flujo operativo

1. Solicitar categoría
2. validar rol y política
3. recopilar
4. redactar
5. transferir por canal autenticado
6. caducar copia.

## Seguridad y riesgos

No incluir contenido personal para resolver fallos genéricos. Cada solicitud registra quién, motivo y datos obtenidos.

## Criterios de aceptación

Aceptar solicitud fuera de alcance rechazada y token sintético ausente del bundle.

## Rendimiento y evidencia

Medir volumen y tiempo hasta evidencia útil.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [205 — Diagnostics System](205_W4_OS_DIAGNOSTICS_SYSTEM.md)
- [210 — Support Bundle System](210_W4_OS_SUPPORT_BUNDLE_SYSTEM.md)
- [225 — Remote Support System](225_W4_OS_REMOTE_SUPPORT_SYSTEM.md)
- [355 — Enterprise Privacy Controls](355_W4_OS_ENTERPRISE_PRIVACY_CONTROLS.md)

## Roadmap y condiciones de evolución

Checks de sólo lectura en piloto; ampliar por necesidad demostrada.

---

[Anterior](225_W4_OS_REMOTE_SUPPORT_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](227_W4_OS_INVENTORY_SYSTEM.md)
