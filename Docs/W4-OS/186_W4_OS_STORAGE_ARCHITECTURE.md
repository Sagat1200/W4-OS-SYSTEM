# 186 · W4 OS — Storage Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Almacenamiento y backup · **Responsabilidad propuesta:** Protección y recuperación de datos  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Coordinar dispositivos, montajes, cuotas y datos sin duplicar autoridad.

## Alcance, arquitectura y decisiones

UDisks u otros servicios mantenidos administran dispositivos de escritorio; W4 presenta intención y políticas. Raíz, datos personales y medios extraíbles tienen responsabilidades distintas.

## Componentes y flujo operativo

1. Detectar
2. clasificar
3. evaluar permisos
4. montar o proponer operación
5. observar salud
6. desmontar seguro.

## Seguridad y riesgos

No formatear al detectar un formato desconocido. Toda mutación vincula recurso estable y vuelve a comprobar identidad.

## Criterios de aceptación

Aceptar medio desconocido, disco lleno y retirada durante uso con estado correcto.

## Rendimiento y evidencia

Medir latencia de enumeración y operaciones.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [187 — Storage Device Manager](187_W4_OS_STORAGE_DEVICE_MANAGER.md)
- [188 — External Storage](188_W4_OS_EXTERNAL_STORAGE.md)
- [190 — Mount System](190_W4_OS_MOUNT_SYSTEM.md)

## Roadmap y condiciones de evolución

Operaciones básicas V1; administración avanzada con planes previsualizables.

---

[Anterior](185_W4_OS_ENTERPRISE_NETWORKING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](187_W4_OS_STORAGE_DEVICE_MANAGER.md)
