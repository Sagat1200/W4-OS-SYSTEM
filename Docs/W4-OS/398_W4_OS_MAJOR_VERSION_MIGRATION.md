# 398 · W4 OS — Major Version Migration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Evolución y mantenimiento · **Responsabilidad propuesta:** Mantenimiento de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Migrar versiones mayores de W4 con evaluación previa y conservación de datos.

## Alcance, arquitectura y decisiones

Ruta por versión origen, arquitectura, layout y paquetes externos; actualización in situ sólo si está calificada, alternativa instalación limpia/restauración.

## Componentes y flujo operativo

1. Analizar compatibilidad
2. comprobar respaldo restaurable
3. mostrar bloqueos
4. preparar
5. migrar
6. validar
7. mantener plan de recuperación aplicable.

## Seguridad y riesgos

No ofrecer rollback simple si cambió esquema persistente de manera incompatible. Repositorios de terceros se revisan antes de resolver.

## Criterios de aceptación

Aceptar piloto desde cada origen soportado y rechazo de layout no calificado.

## Rendimiento y evidencia

Medir duración, fallos y datos preservados.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)
- [274 — End Of Life Policy](274_W4_OS_END_OF_LIFE_POLICY.md)
- [397 — Debian Release Transition](397_W4_OS_DEBIAN_RELEASE_TRANSITION.md)

## Roadmap y condiciones de evolución

Diseñar antes de segunda línea W4; V1 no promete migración universal futura.

---

[Anterior](397_W4_OS_DEBIAN_RELEASE_TRANSITION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](399_W4_OS_BACKWARD_COMPATIBILITY_POLICY.md)
