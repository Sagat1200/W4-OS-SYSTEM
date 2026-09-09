# 072 · W4 OS — Update Engine

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Implementar un coordinador durable que sobreviva al reinicio durante una actualización.

## Alcance, arquitectura y decisiones

Servicio propuesto con operation_id, estado persistente, manifiesto destino y resultado; UI consulta progreso sin ejecutar APT. Exclusión mutua con otras operaciones del sistema.

## Componentes y flujo operativo

1. Registrar intención antes de modificar
2. avanzar mediante transiciones válidas
3. persistir resultado
4. reconciliar estado observado tras reinicio.

## Seguridad y riesgos

No inferir éxito por ausencia de proceso. Ante discrepancia entre journal de operación y dpkg, pasar a diagnóstico y bloquear nuevas mutaciones.

## Criterios de aceptación

Aceptar reenvío de la misma solicitud sin duplicación y reinicio en transición.

## Rendimiento y evidencia

Medir recuperación del coordinador y tamaño del registro.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [041 — Package System](041_W4_OS_PACKAGE_SYSTEM.md)
- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [369 — Update API](369_W4_OS_UPDATE_API.md)

## Roadmap y condiciones de evolución

Implementar máquina de estados mínima y probar idempotencia antes de agregar planificación remota.

---

[Anterior](071_W4_OS_UPDATE_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](073_W4_OS_ATOMIC_UPDATE_MODEL.md)
