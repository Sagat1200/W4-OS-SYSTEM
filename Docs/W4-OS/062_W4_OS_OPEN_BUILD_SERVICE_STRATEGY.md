# 062 · W4 OS — Open Build Service Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Construcción y artefactos · **Responsabilidad propuesta:** Infraestructura de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Evaluar Open Build Service como infraestructura sin adoptar openSUSE como upstream.

## Alcance, arquitectura y decisiones

OBS se considera orquestador de builds Debian con herramientas y repositorios Debian fijados. No importar políticas RPM, zypper ni paquetes de openSUSE en W4.

## Componentes y flujo operativo

1. Prototipo de un paquete Debian
2. comparar con pipeline de referencia
3. evaluar aislamiento, reproducibilidad y promoción
4. decidir mediante ADR.

## Seguridad y riesgos

El servicio no debe recibir claves de distribución en workers. Evitar dependencia operativa imposible de mantener por el equipo W4.

## Criterios de aceptación

Aceptar fuentes idénticas con resultados explicables en OBS y referencia.

## Rendimiento y evidencia

Medir administración, cola y facilidad de exportar artefactos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [048 — Package Build Pipeline](048_W4_OS_PACKAGE_BUILD_PIPELINE.md)
- [061 — Build Infrastructure](061_W4_OS_BUILD_INFRASTRUCTURE.md)
- [064 — Build Reproducibility](064_W4_OS_BUILD_REPRODUCIBILITY.md)

## Roadmap y condiciones de evolución

Prueba técnica en V1; adopción condicionada a ventajas medibles y plan de salida.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [OBS — Build Process](https://openbuildservice.org/help/manuals/obs-user-guide/cha-obs-build-process). OBS documenta construcción de paquetes Debian mediante dpkg-buildpackage. Su adopción por W4 permanece como evaluación de infraestructura.

---

[Anterior](061_W4_OS_BUILD_INFRASTRUCTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](063_W4_OS_BUILD_WORKERS.md)
