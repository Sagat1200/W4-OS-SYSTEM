# 077 · W4 OS — Update Rollback System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Restaurar un estado de sistema anterior cuando la actualización causa regresión.

## Alcance, arquitectura y decisiones

El recuperador usa manifiesto de estado bueno y verifica raíz, kernel, initramfs y esquema persistente. Si datos migrados son incompatibles, necesita restauración de aplicación o reparación dirigida.

## Componentes y flujo operativo

1. Detectar fallo
2. detener nuevos cambios
3. mostrar estado recuperable
4. seleccionar objetivo
5. restaurar coordinación de arranque
6. verificar
7. marcar incidente.

## Seguridad y riesgos

Un rollback puede reintroducir vulnerabilidades y no revierte firmware. Registrar exposición y preparar corrección segura; no borrar archivos personales para forzar compatibilidad.

## Criterios de aceptación

Aceptar actualización defectuosa con retorno comprobado a sesión y versión de paquetes coherente.

## Rendimiento y evidencia

Medir tiempo de recuperación y pérdida de cambios del sistema.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [085 — Rollback Architecture](085_W4_OS_ROLLBACK_ARCHITECTURE.md)
- [175 — Incident Recovery Model](175_W4_OS_INCIDENT_RECOVERY_MODEL.md)

## Roadmap y condiciones de evolución

Recuperación manual V1; automatizar sólo escenarios certificados.

---

[Anterior](076_W4_OS_UPDATE_HEALTH_CHECK.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](078_W4_OS_UPDATE_RINGS.md)
