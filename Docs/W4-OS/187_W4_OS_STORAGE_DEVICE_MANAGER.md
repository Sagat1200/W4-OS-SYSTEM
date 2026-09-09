# 187 · W4 OS — Storage Device Manager

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Almacenamiento y backup · **Responsabilidad propuesta:** Protección y recuperación de datos  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener inventario de almacenamiento con identidad estable y capacidades reales.

## Alcance, arquitectura y decisiones

Registro incluye bus, tamaño, tabla, filesystem, montajes y salud disponible. Seriales se muestran sólo donde sean útiles y se redactan al exportar.

## Componentes y flujo operativo

1. Descubrir
2. normalizar
3. correlacionar montajes
4. emitir cambio
5. invalidar planes que dependían de un dispositivo retirado.

## Seguridad y riesgos

Nombres como /dev/sdb no son identidad suficiente para una operación destructiva. Evitar carreras al reconectar discos.

## Criterios de aceptación

Aceptar intercambio de dos dispositivos entre lectura y acción con rechazo del plan viejo.

## Rendimiento y evidencia

Medir frescura y eventos perdidos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [023 — Hardware Detection System](023_W4_OS_HARDWARE_DETECTION_SYSTEM.md)
- [033 — Partitioning System](033_W4_OS_PARTITIONING_SYSTEM.md)
- [186 — Storage Architecture](186_W4_OS_STORAGE_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Base de operaciones de disco desde MVP.

---

[Anterior](186_W4_OS_STORAGE_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](188_W4_OS_EXTERNAL_STORAGE.md)
