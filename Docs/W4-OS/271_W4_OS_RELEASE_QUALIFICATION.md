# 271 · W4 OS — Release Qualification

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Releases y soporte de versiones · **Responsabilidad propuesta:** Ingeniería de releases  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir puertas de salida por riesgo y evidencia, no por cantidad de tests.

## Alcance, arquitectura y decisiones

Requisitos obligatorios: instalación, arranque, actualización, recuperación, datos preservados, autenticidad y tareas de edición. Hardware anunciado tiene resultados propios.

## Componentes y flujo operativo

1. Recibir candidato
2. ejecutar matriz
3. revisar fallos y cobertura
4. clasificar bloqueantes
5. aceptar o rechazar con evidencia.

## Seguridad y riesgos

No contar tests omitidos como aprobados ni usar pruebas sólo de VM para certificar portátil. Vulnerabilidad crítica conocida exige resolución o mitigación aceptada explícitamente.

## Criterios de aceptación

Aceptar expediente que permite repetir escenarios y conocer excepciones.

## Rendimiento y evidencia

Medir fallos escapados y cobertura de rutas críticas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [275 — Testing Architecture](275_W4_OS_TESTING_ARCHITECTURE.md)
- [279 — Hardware Testing](279_W4_OS_HARDWARE_TESTING.md)
- [280 — Update Testing](280_W4_OS_UPDATE_TESTING.md)
- [281 — Rollback Testing](281_W4_OS_ROLLBACK_TESTING.md)

## Roadmap y condiciones de evolución

Puertas MVP antes de release pública; ampliar con incidentes reales.

## Puertas mínimas propuestas de calificación

| Puerta | Evidencia mínima | Bloqueante |
|---|---|---|
| Artefacto | Hash, firma, manifiesto y fuentes aplicables | Identidad o autenticidad incoherentes |
| Instalación | Disco vacío de laboratorio, cifrado y arranque | Daño fuera del destino o instalación no arrancable |
| Actualización | Origen soportado y fallos inyectados | Estado sin recuperación o binarios/dpkg mezclados |
| Datos | Archivo personal antes/después y backup restaurado | Pérdida no declarada de datos |
| Escritorio | Tareas principales y accesibilidad | Login/bloqueo inaccesible o roto |
| Hardware | Matriz de cada modelo anunciado | Función crítica anunciada sin prueba |
| Business | Aislamiento de organizaciones y revocación | Acceso cruzado o privilegios indebidos |
| Operación | Runbook, responsables y plan de pausa | Sin capacidad de responder a fallo de distribución |

Un resultado se vincula a build-id y entorno. Si cambia kernel, controlador, instalador o layout, las pruebas afectadas se repiten aunque exista un resultado de una versión anterior. No se exige repetir pruebas irrelevantes por ritual: el análisis de impacto determina qué evidencia sigue vigente.

Las excepciones describen usuario afectado, condición de fallo, workaround probado, riesgo residual, responsable y versión prevista de corrección. Un porcentaje de tests aprobados no reemplaza esta revisión: fallar una sola prueba de aislamiento puede bloquear Business aunque el resto de la suite pase.

---

[Anterior](270_W4_OS_RELEASE_ENGINEERING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](272_W4_OS_RELEASE_SIGNING.md)
