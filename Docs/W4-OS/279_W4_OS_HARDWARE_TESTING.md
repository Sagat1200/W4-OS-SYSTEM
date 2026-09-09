# 279 · W4 OS — Hardware Testing

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Pruebas y calificación · **Responsabilidad propuesta:** Calidad y validación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Validar funciones físicas que las máquinas virtuales no representan.

## Alcance, arquitectura y decisiones

Matriz por modelo, revisión, firmware, GPU, red, audio, energía y periféricos. Resultados indican versión exacta y función, no sólo arranca.

## Componentes y flujo operativo

1. Instalar
2. probar funciones
3. repetir suspensión/actualización
4. registrar evidencia
5. calificar o listar limitaciones.

## Seguridad y riesgos

No alterar firmware de equipos de usuario para obtener resultados. Laboratorio usa copias y rutas de recuperación del fabricante.

## Criterios de aceptación

Aceptar todas las funciones anunciadas por modelo y fallo claramente publicado.

## Rendimiento y evidencia

Medir cobertura y regresiones por revisión.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [024 — Device Compatibility Model](024_W4_OS_DEVICE_COMPATIBILITY_MODEL.md)
- [066 — amd64 Support](066_W4_OS_AMD64_SUPPORT.md)
- [067 — arm64 Support](067_W4_OS_ARM64_SUPPORT.md)
- [287 — Hardware Certification](287_W4_OS_HARDWARE_CERTIFICATION.md)

## Roadmap y condiciones de evolución

Pocos equipos certificados V1; expansión incremental.

---

[Anterior](278_W4_OS_SYSTEM_TESTING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](280_W4_OS_UPDATE_TESTING.md)
