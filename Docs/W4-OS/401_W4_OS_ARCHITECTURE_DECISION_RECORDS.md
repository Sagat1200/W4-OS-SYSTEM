# 401 · W4 OS — Architecture Decision Records

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Decisiones y riesgos · **Responsabilidad propuesta:** Arquitectura y gobernanza  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Registrar decisiones arquitectónicas con contexto, alternativas y consecuencias.

## Alcance, arquitectura y decisiones

ADR por identificador, estado, decisión, motivos, evidencia y reemplazos. Confirmado en esta edición: Debian Stable → W4 Linux Base → Home/Business; openSUSE sólo referencia técnica.

## Componentes y flujo operativo

1. Plantear cuestión
2. evaluar alternativas
3. documentar propuesta
4. revisar
5. aceptar o rechazar
6. enlazar documentos
7. sustituir mediante nuevo ADR.

## Seguridad y riesgos

No reescribir una decisión histórica para ocultar cambio ni presentar una propuesta del autor como aprobación del usuario.

## Criterios de aceptación

Aceptar toda decisión transversal con estado y dependencias, más enlaces a pruebas cuando existan.

## Rendimiento y evidencia

Medir decisiones pendientes que bloquean V1.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [001 — Project Context](001_W4_OS_PROJECT_CONTEXT.md)
- [004 — Architecture](004_W4_OS_ARCHITECTURE.md)
- [325 — Governance Model](325_W4_OS_GOVERNANCE_MODEL.md)
- [402 — Risk Register](402_W4_OS_RISK_REGISTER.md)

## Roadmap y condiciones de evolución

Registro inicial en esta colección; la decisión de escritorio de `Home` ya queda cerrada mediante `ADR-006`, mientras cargador y atomicidad siguen propuestas.

## Registro inicial de decisiones

| ADR | Estado en esta edición | Decisión o cuestión |
|---|---|---|
| ADR-001 | Confirmado por solicitud | Debian Stable como upstream de distribución |
| ADR-002 | Confirmado por solicitud | Base común W4 Linux Base y dos ediciones Home/Business |
| ADR-003 | Confirmado por solicitud | openSUSE como referencia técnica, sin upstream openSUSE |
| ADR-004 | Propuesto | Codename fijado; trixie candidato inicial |
| ADR-005 | Propuesto | amd64 UEFI como referencia inicial; arm64 experimental |
| ADR-006 | Confirmado por solicitud | `GNOME` como interfaz predeterminada de `Home`; `KDE Plasma`, `XFCE` y `Cinnamon` como variantes controladas; el instalador puede exponer selector solo cuando el medio incluya variantes calificadas |
| ADR-007 | Propuesto | GRUB Debian como candidato de arranque amd64 |
| ADR-008 | Propuesto | Btrfs y contrato de estado del documento 083 |
| ADR-009 | Propuesto | Actualización offline recuperable para V1 |
| ADR-010 | Experimental | Generaciones aisladas y atomicidad de actualización |
| ADR-011 | Propuesto | Cuenta local suficiente y servicios cloud opcionales |
| ADR-012 | Evaluación | OBS como orquestador frente a pipeline Debian de referencia |

Cada aceptación futura debe adjuntar alternativas evaluadas, pruebas y consecuencias. Una decisión reemplazada conserva su estado histórico y enlaza a la que la sustituye. Los documentos de producto se actualizan para reflejar el resultado; no se mantienen dos afirmaciones incompatibles bajo el mismo estado de aprobación.

Detalle aprobado del `ADR-006`:
- [ADR-006 · Catalogo grafico y selector de interfaz para Home](../DEVELOPMENT/GRAPHIC%20INTERFACE/HOME/ADR-006_HOME_GRAPHIC_CATALOG_AND_SELECTOR.md)

---

[Anterior](400_W4_OS_LONG_TERM_MAINTENANCE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](402_W4_OS_RISK_REGISTER.md)
