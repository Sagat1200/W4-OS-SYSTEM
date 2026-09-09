# 001 · W4 OS — Project Context

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Establecer el mandato de W4 OS como plataforma de escritorio para hogares y organizaciones, separada del producto servidor W4 Cloud Linux.

## Alcance, arquitectura y decisiones

La decisión confirmada es Debian Stable → W4 Linux Base → Home y Business. W4 mantiene integración, repositorios propios y experiencia; no mantiene inicialmente una distribución de paquetes completa ni un kernel independiente.

## Componentes y flujo operativo

Una necesidad de producto se convierte en requisito, decisión de arquitectura y prueba de entrega. El contexto conserva las restricciones aceptadas y el motivo de cualquier cambio.

## Seguridad y riesgos

Riesgo: confundir visión comercial con capacidades existentes. Este conjunto es una especificación inicial propuesta; ninguna función W4, certificación ni SLA se considera implementada por estar documentada.

## Criterios de aceptación

Aceptar cuando ambos perfiles se construyan desde un manifiesto de base común y cada requisito obligatorio tenga responsable y prueba.

## Rendimiento y evidencia

Medir esfuerzo de mantenimiento por paquete modificado.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [004 — Architecture](004_W4_OS_ARCHITECTURE.md)
- [005 — Debian Base Strategy](005_W4_OS_DEBIAN_BASE_STRATEGY.md)
- [406 — V1 Scope](406_W4_OS_V1_SCOPE.md)
- [415 — Final Architecture Overview](415_W4_OS_FINAL_ARCHITECTURE_OVERVIEW.md)

## Roadmap y condiciones de evolución

Primero aprobar alcance y responsables; después construir la imagen mínima y validar recuperación.

## Mandato y vocabulario

**Base confirmada.** Debian Stable es el upstream de distribución. W4 Linux Base reúne la integración compartida. W4 OS Home y W4 OS Business son los productos de escritorio. W4 Cloud Linux, si se desarrolla, es un producto separado para servidor/cloud y no una dependencia de este proyecto.

**Naturaleza de esta entrega.** Los 415 documentos forman una especificación de diseño inicial, redactada en español y organizada con los nombres originales del índice. Una decisión marcada como propuesta expresa una recomendación técnica concreta que debe contrastarse mediante prototipo; no significa que el documento esté vacío ni que exista ya el software descrito. Los criterios de aceptación son pruebas a ejecutar sobre la futura implementación, no resultados obtenidos en esta entrega.

| Término | Uso en W4 |
|---|---|
| Confirmado | Decisión arquitectónica indicada por el usuario |
| Propuesto | Diseño recomendado en esta edición, pendiente de validación técnica y adopción |
| Experimental | Hipótesis que no puede anunciarse como capacidad soportada |
| Calificado | Estado futuro que requiere evidencia de pruebas de una versión concreta |
| Soportado | Capacidad calificada con responsables y cobertura operativa publicados |

La prioridad no es reemplazar un porcentaje creciente de Debian. Es reducir problemas de uso y operación con el menor mantenimiento adicional sostenible. Un componente upstream mantenido puede permanecer indefinidamente si satisface el contrato de producto.

## Entregables que habilitan implementación

El equipo necesita, en este orden, un manifiesto de base, decisiones de escritorio/arranque, un pipeline de paquetes e imagen, un layout validado y un recorrido de actualización/recuperación. Después se amplían aplicaciones y gestión empresarial. Ningún servicio W4 remoto aparece como dependencia obligatoria del escritorio local. Las personas responsables, dominios de producción, precios y fechas de lanzamiento deben establecerse en la operación del proyecto; esta edición no inventa esos datos.

---

[Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](002_W4_OS_PRODUCT_VISION.md)
