# 010 · W4 OS — System Components

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener un inventario técnico que permita saber qué forma parte de cada imagen.

## Alcance, arquitectura y decisiones

Cada componente registra nombre, origen, versión, licencia, dueño, interfaz, privilegios, datos persistentes y criticidad. Los nombres w4-* describen contratos propuestos, no paquetes ya disponibles.

## Componentes y flujo operativo

Extraer inventario del manifiesto construido, reconciliarlo con SBOM y señalar binarios sin propietario antes de publicar.

## Seguridad y riesgos

Los componentes privilegiados requieren modelo de amenaza y canal de correcciones; no incluir credenciales de servicios externos en metadatos.

## Criterios de aceptación

Aceptar si todos los paquetes instalados se vinculan a una entrada y no existen servicios escuchando sin justificación. Comparar tamaño y memoria por componente.

## Rendimiento y evidencia

Comparar tamaño instalado, memoria ociosa y servicios activados por componente; usar inventario y SBOM de la misma imagen para evitar diferencias de contexto.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [048 — Package Build Pipeline](048_W4_OS_PACKAGE_BUILD_PIPELINE.md)
- [070 — Release Artifact System](070_W4_OS_RELEASE_ARTIFACT_SYSTEM.md)
- [388 — SBOM Strategy](388_W4_OS_SBOM_STRATEGY.md)

## Roadmap y condiciones de evolución

Inventario mínimo automatizado en MVP; trazabilidad de componentes opcionales en V1.

---

[Anterior](009_W4_OS_LAYER_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](011_W4_OS_HOME_EDITION.md)
