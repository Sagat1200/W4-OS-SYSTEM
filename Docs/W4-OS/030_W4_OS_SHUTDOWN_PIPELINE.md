# 030 · W4 OS — Shutdown Pipeline

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Arranque y servicios · **Responsabilidad propuesta:** Integración de arranque  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Apagar con datos sincronizados y operaciones críticas en un estado conocido.

## Alcance, arquitectura y decisiones

Los servicios declaran parada, plazo y persistencia de estado. Actualizador y respaldo usan inhibidores limitados con explicación visible, no bloqueos indefinidos.

## Componentes y flujo operativo

1. Solicitar apagado
2. avisar tareas activas
3. cerrar sesiones
4. detener servicios
5. sincronizar
6. desmontar; registrar razón de espera excedida.

## Seguridad y riesgos

No terminar una actualización de firmware arbitrariamente. Una aplicación no privilegiada no debe impedir indefinidamente el apagado de todos los usuarios.

## Criterios de aceptación

Aceptar apagado con respaldo activo y servicio no cooperativo sin corrupción en el siguiente arranque.

## Rendimiento y evidencia

Medir demora y escrituras pendientes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [027 — Init System](027_W4_OS_INIT_SYSTEM.md)
- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Definir plazos por operación y probar cortes de energía en laboratorio.

---

[Anterior](029_W4_OS_STARTUP_PIPELINE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](031_W4_OS_INSTALLER_ARCHITECTURE.md)
