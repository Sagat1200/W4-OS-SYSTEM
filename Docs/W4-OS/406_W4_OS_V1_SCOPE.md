# 406 · W4 OS — V1 Scope

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Alcance y roadmap · **Responsabilidad propuesta:** Producto y arquitectura  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Fijar alcance V1 alrededor de una experiencia gráfica instalable, mantenible y recuperable.

## Alcance, arquitectura y decisiones

Incluye base Debian fijada, amd64 UEFI de referencia, `GNOME` como interfaz gráfica predeterminada de `Home` segun `ADR-006`, un catálogo gráfico aprobado para variantes adicionales, dos perfiles de producto, repositorio firmado, actualización offline, recuperación probada y respaldo externo. Si el instalador expone selección de interfaz gráfica, esa selección debe limitarse a variantes explícitamente calificadas. Business se libera por piloto calificado.

## Componentes y flujo operativo

1. Construir núcleo
2. instalar
3. usar
4. actualizar
5. recuperar
6. respaldar/restaurar
7. evaluar Home y Business
8. calificar.

## Seguridad y riesgos

Excluir de compromiso inicial atomicidad no probada, compatibilidad Windows universal, arm64 general, cloud obligatorio y certificaciones no obtenidas.

## Criterios de aceptación

Aceptar tareas críticas y pruebas de fallo con evidencia.

## Rendimiento y evidencia

Medir recursos y soporte antes de fijar objetivos comerciales.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [407 — V1 Mvp](407_W4_OS_V1_MVP.md)
- [408 — V1 Home Scope](408_W4_OS_V1_HOME_SCOPE.md)
- [409 — V1 Business Scope](409_W4_OS_V1_BUSINESS_SCOPE.md)
- [410 — V1 Release Plan](410_W4_OS_V1_RELEASE_PLAN.md)

## Roadmap y condiciones de evolución

Scope propuesto sujeto a ADR de selección; no añadir funciones sin revisar costo y riesgos.

---

[Anterior](405_W4_OS_EXPERIMENTAL_FEATURE_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](407_W4_OS_V1_MVP.md)
