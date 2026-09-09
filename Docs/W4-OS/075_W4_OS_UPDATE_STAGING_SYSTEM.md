# 075 · W4 OS — Update Staging System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Preparar recursos antes de interrumpir al usuario y evitar reinicios que no puedan completar cambios.

## Alcance, arquitectura y decisiones

Staging guarda paquetes autenticados, plan resuelto, espacio estimado y compatibilidad de estado. El plan se invalida si cambian paquetes, política o repositorio objetivo.

## Componentes y flujo operativo

1. Descargar con reanudación
2. verificar hashes
3. reservar espacio lógico suficiente
4. volver a comprobar plan
5. declarar listo para ventana.

## Seguridad y riesgos

No ejecutar scripts durante descarga. Limpiar caché caducada sin eliminar paquetes de una operación comprometida ni estados recuperables.

## Criterios de aceptación

Aceptar red interrumpida y cambio del sistema durante staging con revalidación.

## Rendimiento y evidencia

Medir bytes repetidos y porcentaje de reinicios realmente listos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [072 — Update Engine](072_W4_OS_UPDATE_ENGINE.md)
- [080 — Offline Update System](080_W4_OS_OFFLINE_UPDATE_SYSTEM.md)

## Roadmap y condiciones de evolución

Preparación local V1; precarga por flota después de medir ancho de banda.

---

[Anterior](074_W4_OS_TRANSACTIONAL_UPDATE_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](076_W4_OS_UPDATE_HEALTH_CHECK.md)
