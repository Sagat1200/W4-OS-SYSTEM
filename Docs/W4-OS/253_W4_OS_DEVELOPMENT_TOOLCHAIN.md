# 253 · W4 OS — Development Toolchain

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Desarrollo · **Responsabilidad propuesta:** Plataforma de desarrollo  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener compiladores y herramientas de proyecto sin contaminar el sistema estable.

## Alcance, arquitectura y decisiones

Toolchain host mínima de Debian; versiones alternativas en contenedores o gestores de usuario evaluados. Fijar versiones en manifiesto de proyecto.

## Componentes y flujo operativo

1. Definir requisitos
2. resolver entorno
3. verificar origen
4. compilar
5. registrar versión
6. actualizar mediante cambio revisable.

## Seguridad y riesgos

No usar instaladores externos elevados de forma genérica. Dependencias ejecutan código y necesitan aislamiento y revisión de origen.

## Criterios de aceptación

Aceptar dos versiones incompatibles del mismo lenguaje coexistiendo por proyecto.

## Rendimiento y evidencia

Medir arranque de entorno y caché.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [048 — Package Build Pipeline](048_W4_OS_PACKAGE_BUILD_PIPELINE.md)
- [251 — Developer Platform](251_W4_OS_DEVELOPER_PLATFORM.md)
- [259 — SDK Management](259_W4_OS_SDK_MANAGEMENT.md)

## Roadmap y condiciones de evolución

Toolchain Debian V1; versiones alternativas con rutas documentadas.

## Selección de versión y reproducibilidad

La versión activa de una herramienta se obtiene del manifiesto del proyecto o del entorno aislado, no del orden accidental del PATH global. El informe de build registra compilador, bibliotecas y variables relevantes sin secretos. Cambiar toolchain se revisa como un cambio de dependencia y ejecuta pruebas del proyecto; no se actualizan todas las versiones de todos los proyectos de forma implícita. El sistema W4 puede seguir recibiendo correcciones de seguridad del host independientemente de esa elección.

---

[Anterior](252_W4_OS_DEVELOPER_EDITION_PROFILE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](254_W4_OS_CONTAINER_STRATEGY.md)
