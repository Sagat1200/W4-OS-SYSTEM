# W4 OS · Development Guidelines

Guia operativa para trabajar durante el desarrollo del proyecto sin perder trazabilidad, foco tecnico ni coherencia con la documentacion de arquitectura.

## Objetivo

Establecer reglas comunes para que cada ciclo de desarrollo deje:

- avance tecnico real,
- evidencia verificable,
- documentacion actualizada,
- impacto visible en la planificacion.

## Reglas generales

1. No presentar una capacidad como implementada solo porque existe un documento.
2. No iniciar una iniciativa P1 o P2 mientras una dependencia P0 siga bloqueada sin aceptacion explicita.
3. Toda decision transversal debe reflejarse en ADR o en el documento canonico correspondiente.
4. Todo cambio tecnico relevante debe actualizar el conjunto de documentos de desarrollo.
5. Ningun cierre es valido sin evidencia.

## Secuencia obligatoria por ciclo

Por cada ciclo de trabajo:

1. revisar `EXECUTIVE_IMPLEMENTATION_PLAN.md`,
2. revisar `DEVELOPMENT_TABLE.md`,
3. actualizar `DEVELOPMENT_MATRIX.md` con el estado real,
4. ejecutar implementacion o prueba,
5. registrar el cambio en `DEVELOPMENT_VERSIONS.md`,
6. ajustar estas guidelines si se incorporo una nueva norma de trabajo.

## Definicion de evidencia valida

Se considera evidencia valida cualquiera de los siguientes elementos, segun el tipo de tarea:

- commit o conjunto de cambios identificable,
- build reproducible,
- artefacto firmado,
- log de prueba o validacion,
- captura o reporte tecnico de un flujo ejecutado,
- ADR aprobado,
- checklist de QA completado,
- resultado de prueba negativa o de recuperacion.

No se considera evidencia suficiente:

- una intencion,
- una afirmacion verbal,
- una captura aislada de UI sin prueba funcional asociada,
- un documento sin implementacion o validacion.

## Reglas para actualizar documentos de desarrollo

### `DEVELOPMENT_TABLE.md`

Actualizar cuando cambie:

- prioridad,
- orden de ejecucion,
- riesgo principal,
- primer entregable tecnico,
- decision pendiente relevante.

### `DEVELOPMENT_MATRIX.md`

Actualizar en cada ciclo si cambia:

- estado,
- responsable,
- evidencia,
- siguiente accion,
- riesgo activo.

### `DEVELOPMENT_VERSIONS.md`

Actualizar cuando exista:

- cambio de plan,
- hito tecnico,
- nueva version documental,
- candidato de release.

### `EXECUTIVE_IMPLEMENTATION_PLAN.md`

Actualizar cuando cambie:

- alcance V1,
- fases H0-H5,
- criterio de salida,
- ruta priorizada.

## Reglas de priorizacion

- `P0`: bloquea arquitectura base, imagen minima o MVP recuperable.
- `P1`: necesario para V1 despues de cerrar dependencias P0.
- `P2`: importante para pilotos, crecimiento o formalizacion final.

Cuando exista conflicto, prevalece siempre:

1. recuperacion de sistema,
2. integridad del artefacto,
3. seguridad minima,
4. mantenibilidad,
5. amplitud funcional.

## Reglas de calidad

- Toda nueva capacidad debe definir su responsable funcional.
- Toda capacidad persistente debe declarar su politica de datos, migracion y recuperacion.
- Todo servicio privilegiado debe explicitar su frontera de confianza.
- Toda funcionalidad Business debe probar aislamiento entre organizaciones.
- Todo cambio de UX que afecte seguridad o recovery requiere prueba negativa.

## Regla de bloqueo

Detener avance y replanificar cuando ocurra cualquiera de estas condiciones:

- un ADR base entra en conflicto con la implementacion iniciada,
- el prototipo contradice el contrato documental de recovery,
- una prueba demuestra perdida de datos no aceptada,
- un entregable no tiene responsable,
- se descubre que el alcance anunciado supera la capacidad real del proyecto.

## Criterio de cierre de trabajo

Una tarea solo puede marcarse como cerrada cuando:

1. existe artefacto tecnico o decision aprobada,
2. existe evidencia verificable,
3. la matriz fue actualizada,
4. el registro de versiones fue actualizado si aplica,
5. el cambio no deja inconsistencia entre documentos de desarrollo.

## Estado inicial de adopcion

Estas guidelines entran en vigor desde `2026-09-09` y aplican a los ciclos de desarrollo posteriores a la creacion de la base documental de `Docs/DEVELOPMENT`.
