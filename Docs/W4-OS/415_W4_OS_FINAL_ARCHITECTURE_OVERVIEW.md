# 415 · W4 OS — Final Architecture Overview

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Alcance y roadmap · **Responsabilidad propuesta:** Producto y arquitectura  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Consolidar arquitectura final de referencia y límites de la primera edición documental.

## Alcance, arquitectura y decisiones

Decisión confirmada: Debian Stable → W4 Linux Base → W4 OS Home / W4 OS Business. openSUSE inspira Btrfs, Snapper, rollback, transacciones, OBS y administración; no aporta upstream de paquetes.

## Componentes y flujo operativo

1. Construir base común
2. aplicar perfil
3. distribuir artefacto firmado
4. operar localmente
5. actualizar y recuperar según contratos
6. integrar servicios opcionales.

## Seguridad y riesgos

No confundir especificación completa con implementación del sistema. V1 propone actualización offline recuperable; atomicidad integral, cloud y certificaciones siguen condicionados a evidencia.

## Criterios de aceptación

Aceptar colección con 415 nombres exactos y contratos centrales coherentes; para producto, exigir calificación de instalación, datos, firma y recuperación.

## Rendimiento y evidencia

Separar integridad documental de desempeño del producto: nombres y enlaces se verifican en la colección; arranque, recursos y recuperación requieren ejecutar el futuro MVP.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [001 — Project Context](001_W4_OS_PROJECT_CONTEXT.md)
- [004 — Architecture](004_W4_OS_ARCHITECTURE.md)
- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [401 — Architecture Decision Records](401_W4_OS_ARCHITECTURE_DECISION_RECORDS.md)
- [406 — V1 Scope](406_W4_OS_V1_SCOPE.md)
- [414 — Roadmap](414_W4_OS_ROADMAP.md)

## Roadmap y condiciones de evolución

Usar esta versión como base de implementación y mantener ADR, riesgos y pruebas al evolucionar.

## Lectura de cierre

```text
Debian Stable (codename fijado por release)
  └─ W4 Linux Base
       ├─ paquetes, arranque e instalación
       ├─ seguridad, actualización y recuperación
       ├─ contratos de configuración y servicios
       ├─ W4 OS Home: perfil local, apps y respaldo
       └─ W4 OS Business: perfil, identidad y gestión

Servicios W4 opcionales ── adaptadores autenticados ── dispositivo
openSUSE ── referencia de ideas y mecanismos ── evaluación W4 sobre Debian
```

## Condiciones para pasar de documentación a producto

El primer resultado de implementación debe ser una imagen que se pueda instalar, actualizar y recuperar con evidencia. Una captura de escritorio no satisface ese objetivo. El contrato de estado del documento 083 determina qué retrocede y qué datos permanecen; su prueba evita que la recuperación se reduzca a cambiar un subvolumen sin comprobar paquetes y arranque.

La independencia respecto a openSUSE significa que sus herramientas y patrones se estudian por función. OBS puede orquestar un build Debian; eso no cambia el upstream. Snapper puede gestionar snapshots; eso no implementa automáticamente un cargador de generaciones W4. Una administración empresarial puede tomar ideas de operación sin importar software o políticas de distribución incompatibles.

La entrega de estos 415 documentos completa el alcance documental solicitado. La implementación, calificación de hardware, auditorías, servicios remotos y compromisos comerciales permanecen como trabajo del proyecto, con criterios de aceptación descritos en la colección. El siguiente hito verificable es H0/H1 del roadmap y después el MVP integral, conservando siempre esta separación entre diseño y evidencia.

---

[Anterior](414_W4_OS_ROADMAP.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo)
