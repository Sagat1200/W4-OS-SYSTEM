# 285 · W4 OS — QA Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Pruebas y calificación · **Responsabilidad propuesta:** Calidad y validación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Convertir QA en una decisión de publicación con evidencia y responsables.

## Alcance, arquitectura y decisiones

Catálogo de puertas por componente e imagen; cada fallo tiene severidad, reproducción y dueño. Excepciones no se ocultan tras porcentaje agregado de éxito.

## Componentes y flujo operativo

1. Recibir candidato
2. ejecutar matriz
3. revisar resultados
4. priorizar
5. verificar corrección
6. emitir decisión de calificación.

## Seguridad y riesgos

Separar quien produce cambio de quien aprueba excepción crítica cuando haya equipo suficiente; en equipo pequeño registrar revisión independiente disponible.

## Criterios de aceptación

Aceptar release con todos los bloqueantes resueltos o excepción formal específica.

## Rendimiento y evidencia

Medir escapes, repetición de defectos y tiempo de decisión.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [049 — Package QA System](049_W4_OS_PACKAGE_QA_SYSTEM.md)
- [271 — Release Qualification](271_W4_OS_RELEASE_QUALIFICATION.md)
- [275 — Testing Architecture](275_W4_OS_TESTING_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Puertas simples desde MVP; escalar organización junto al producto.

---

[Anterior](284_W4_OS_ENTERPRISE_TESTING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](286_W4_OS_AUTOMATED_QA.md)
