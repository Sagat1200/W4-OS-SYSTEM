# 221 · W4 OS — Fleet Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Operar flotas por cohortes y estado observado, evitando asumir conectividad permanente.

## Alcance, arquitectura y decisiones

Modelo dispositivo, organización, grupo, política, versión y última observación. Acciones masivas se previsualizan por alcance y se ejecutan con límites de concurrencia.

## Componentes y flujo operativo

1. Seleccionar cohorte
2. revisar cantidad y cambios
3. programar
4. ejecutar por lotes
5. observar resultados
6. pausar o ampliar.

## Seguridad y riesgos

No cruzar organizaciones por filtro ambiguo. Equipos sin reporte se muestran desconocidos, no conformes por defecto ni fallidos automáticamente.

## Criterios de aceptación

Aceptar lote con éxito, desconectados y fallos parciales distinguibles.

## Rendimiento y evidencia

Medir convergencia, errores y carga del servidor.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [078 — Update Rings](078_W4_OS_UPDATE_RINGS.md)
- [212 — Enterprise Device Management](212_W4_OS_ENTERPRISE_DEVICE_MANAGEMENT.md)
- [224 — Remote Update Management](224_W4_OS_REMOTE_UPDATE_MANAGEMENT.md)
- [227 — Inventory System](227_W4_OS_INVENTORY_SYSTEM.md)

## Roadmap y condiciones de evolución

Flota piloto pequeña; escala tras pruebas de capacidad y aislamiento.

---

[Anterior](220_W4_OS_ENTERPRISE_CERTIFICATES.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](222_W4_OS_DEVICE_ENROLLMENT.md)
