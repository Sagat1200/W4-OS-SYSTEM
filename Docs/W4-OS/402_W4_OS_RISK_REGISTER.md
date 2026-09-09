# 402 · W4 OS — Risk Register

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Decisiones y riesgos · **Responsabilidad propuesta:** Arquitectura y gobernanza  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener riesgos accionables con desencadenante, impacto y mitigación.

## Alcance, arquitectura y decisiones

Registro inicial prioriza coherencia rollback/dpkg, compatibilidad hardware, custodia de firma, cobertura de seguridad, pérdida de datos y capacidad de soporte. Probabilidad/impacto se evalúan, no se inventan métricas reales.

## Componentes y flujo operativo

1. Identificar
2. asignar dueño
3. elegir mitigación
4. crear prueba
5. revisar evidencia
6. aceptar residual o bloquear entrega.

## Seguridad y riesgos

No reducir riesgo por tener un documento sin implementación. Riesgos de datos y autenticidad requieren evidencia antes de release.

## Criterios de aceptación

Aceptar cada riesgo crítico con responsable propuesto, condición de cierre y prueba vinculada.

## Rendimiento y evidencia

Medir riesgos vencidos y sin mitigación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [169 — Supply Chain Security](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
- [267 — LTS Strategy](267_W4_OS_LTS_STRATEGY.md)
- [271 — Release Qualification](271_W4_OS_RELEASE_QUALIFICATION.md)

## Roadmap y condiciones de evolución

Revisar en cada hito y después de incidentes; ampliar con descubrimientos del prototipo.

## Registro inicial priorizado

| ID | Riesgo y desencadenante | Mitigación y evidencia de cierre | Rol propuesto |
|---|---|---|---|
| R-01 | Rollback mezcla binarios con dpkg o datos incompatibles | Layout/estado congelado y prueba 281 | Plataforma |
| R-02 | ESP/kernel no corresponde a raíz recuperada | Manifiesto de boot y cortes ensayados | Arranque |
| R-03 | Clave de publicación comprometida | Custodia separada y simulacro de rotación | Seguridad/release |
| R-04 | Equipo anunciado tiene suspensión/gráficos defectuosos | Matriz por revisión y retiro de etiqueta | Hardware/QA |
| R-05 | Corrección Debian bloqueada por delta W4 | Cola pequeña, dueño y prueba de reintegración | Mantenedores |
| R-06 | Inscripción o API cruza organizaciones | Autorización por recurso y pruebas negativas | Business/seguridad |
| R-07 | Backup no puede restaurarse al perder equipo | Ensayo en destino distinto y clave disponible | Almacenamiento |
| R-08 | Soporte vendido supera capacidad real | Piloto medido y contrato acotado | Operación/producto |
| R-09 | Recursos redistribuidos sin derechos claros | Inventario de licencias y revisión por artefacto | Cumplimiento |
| R-10 | Telemetría/diagnóstico incluye secretos | Esquemas mínimos, redacción y pruebas sintéticas | Privacidad/servicios |

Esta tabla prioriza por potencial de daño y bloqueo de lanzamiento, no por probabilidades numéricas observadas. En la implementación se asignarán responsables concretos, fechas y evidencia. Un documento de mitigación no cierra el riesgo: lo cierra el control implementado y su prueba, o una aceptación residual formal y limitada donde sea admisible.

---

[Anterior](401_W4_OS_ARCHITECTURE_DECISION_RECORDS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](403_W4_OS_TECHNICAL_DEBT_POLICY.md)
