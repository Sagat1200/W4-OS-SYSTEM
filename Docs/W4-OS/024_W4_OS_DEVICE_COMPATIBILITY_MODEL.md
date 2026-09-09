# 024 · W4 OS — Device Compatibility Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Kernel y hardware · **Responsabilidad propuesta:** Plataforma y habilitación de hardware  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Expresar soporte por modelo y función, evitando una etiqueta binaria engañosa.

## Alcance, arquitectura y decisiones

Matriz con estados certificado, probado, comunitario y desconocido; cada entrada incluye versión W4, firmware, funciones y evidencia fechada.

## Componentes y flujo operativo

Registrar resultado de prueba, revisar fallos, asignar nivel y publicar limitaciones; invalidar o repetir resultados tras cambios relevantes.

## Seguridad y riesgos

No reutilizar certificación entre revisiones de hardware con componentes diferentes. Evitar publicar números de serie del laboratorio.

## Criterios de aceptación

Aceptar que cada afirmación de compatibilidad enlace prueba reproducible y versión exacta.

## Rendimiento y evidencia

Medir cobertura por función crítica.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [019 — Hardware Enablement Layer](019_W4_OS_HARDWARE_ENABLEMENT_LAYER.md)
- [279 — Hardware Testing](279_W4_OS_HARDWARE_TESTING.md)
- [287 — Hardware Certification](287_W4_OS_HARDWARE_CERTIFICATION.md)

## Roadmap y condiciones de evolución

Publicar matriz inicial antes de V1 y caducar evidencia cuando cambie la plataforma.

---

[Anterior](023_W4_OS_HARDWARE_DETECTION_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](025_W4_OS_BOOT_ARCHITECTURE.md)
