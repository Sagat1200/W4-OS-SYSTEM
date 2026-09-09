# 124 · W4 OS — Flatpak Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Usar Flatpak sin transferir implícitamente soporte de terceros a W4.

## Alcance, arquitectura y decisiones

Remotos permitidos y aplicaciones recomendadas se registran con propietario y cobertura. Runtimes tienen ciclo propio, seguimiento de fin de soporte y política de retirada.

## Componentes y flujo operativo

1. Agregar remoto autorizado
2. verificar metadatos
3. instalar app/runtime
4. revisar permisos
5. actualizar
6. limpiar runtimes no usados.

## Seguridad y riesgos

No habilitar repositorios externos silenciosamente. La firma de origen no equivale a revisión de código ni a soporte W4.

## Criterios de aceptación

Aceptar actualización de runtime y retirada de app preservando datos según elección.

## Rendimiento y evidencia

Medir almacenamiento duplicado y runtimes obsoletos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [121 — Application Model](121_W4_OS_APPLICATION_MODEL.md)
- [128 — Application Repository](128_W4_OS_APPLICATION_REPOSITORY.md)
- [130 — Application Lifecycle](130_W4_OS_APPLICATION_LIFECYCLE.md)

## Roadmap y condiciones de evolución

Catálogo reducido V1; ampliación condicionada a mantenimiento y permisos.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [Flatpak — Sandbox Permissions](https://docs.flatpak.org/en/latest/sandbox-permissions.html). Referencia de permisos y portales. El catálogo permitido y las garantías de soporte W4 se definen por aplicación.

---

[Anterior](123_W4_OS_APPLICATION_SANDBOXING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](125_W4_OS_NATIVE_APPLICATION_STRATEGY.md)
