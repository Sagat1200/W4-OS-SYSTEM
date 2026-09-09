# 414 · W4 OS — Roadmap

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Alcance y roadmap · **Responsabilidad propuesta:** Producto y arquitectura  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Conectar documentos, implementación futura y pruebas en un roadmap por dependencias.

## Alcance, arquitectura y decisiones

Secuencia: decisiones → build/repositorio → instalación/layout → actualización/recuperación → escritorio/Home → gestión Business → pilotos → release → evolución. Trabajo paralelo técnico requiere interfaces acordadas.

## Componentes y flujo operativo

1. Identificar entregable
2. comprobar dependencias
3. asignar dueño
4. ejecutar
5. adjuntar evidencia
6. cerrar puerta
7. actualizar prioridades.

## Seguridad y riesgos

No marcar completada una función por estar redactada. Estado documental y estado de implementación se registran separadamente.

## Criterios de aceptación

Aceptar cada hito con entrada, salida y prueba.

## Rendimiento y evidencia

Medir bloqueos y capacidad real para fijar calendario.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [401 — Architecture Decision Records](401_W4_OS_ARCHITECTURE_DECISION_RECORDS.md)
- [402 — Risk Register](402_W4_OS_RISK_REGISTER.md)
- [406 — V1 Scope](406_W4_OS_V1_SCOPE.md)
- [410 — V1 Release Plan](410_W4_OS_V1_RELEASE_PLAN.md)
- [415 — Final Architecture Overview](415_W4_OS_FINAL_ARCHITECTURE_OVERVIEW.md)

## Roadmap y condiciones de evolución

Roadmap inicial sin fechas; actualizar tras MVP y recursos aprobados.

## Hitos y dependencias operativas

| Hito | Entradas indispensables | Salida comprobable |
|---|---|---|
| H0 — Decisiones | Contexto, candidatos y recursos del equipo | ADRs de base fijada, escritorio, boot y layout |
| H1 — Suministro mínimo | H0 y fuentes autorizadas | Paquete e imagen construidos, firmados y trazables |
| H2 — MVP recuperable | H1 y medio de instalación | Instalar, actualizar, fallar y recuperar sin perder archivo de prueba |
| H3 — Home utilizable | H2 y aplicaciones seleccionadas | Tareas domésticas y accesibilidad calificadas |
| H4 — Business piloto | H2, identidad y política tipada | Alta, gestión, offline y baja con aislamiento probado |
| H5 — Candidato V1 | H3/H4 dentro del alcance aprobado | Expediente QA, licencias, fuentes, runbooks y soporte |
| H6 — Disponibilidad | H5 y distribución verificada | Promoción por anillos y operación de incidentes |
| H7 — Evolución | Evidencia de V1 y capacidad de mantenimiento | Propuestas V2 priorizadas y migración evaluada |

La colección documental está redactada; los hitos técnicos no están ejecutados por esta entrega. El seguimiento debe mantener dos columnas distintas: estado del documento y estado de implementación/evidencia. Una actualización de texto puede cerrar una revisión documental sin cambiar el estado de H2, por ejemplo.

Las fechas se calculan con capacidad real, dependencias externas y resultados del MVP. Si hardware o recuperación bloquean, se reduce el alcance anunciado antes de rebajar garantías de datos. La ampliación de arquitecturas, servicios y extensiones se programa después de contar con mantenimiento para cada nueva combinación.

---

[Anterior](413_W4_OS_LONG_TERM_VISION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](415_W4_OS_FINAL_ARCHITECTURE_OVERVIEW.md)
