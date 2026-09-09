# 129 · W4 OS — Application Permissions

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Dar al usuario y al administrador control sobre acceso de aplicaciones a recursos.

## Alcance, arquitectura y decisiones

Permisos se expresan por cámara, micrófono, archivos, dispositivos y comunicaciones según backend. Mostrar concesiones persistentes separadas de acceso puntual.

## Componentes y flujo operativo

1. Consultar permisos
2. explicar recurso
3. conceder o revocar
4. aplicar mediante backend
5. verificar efecto en nueva solicitud.

## Seguridad y riesgos

No fingir control granular donde la tecnología sólo ofrece acceso amplio. Políticas empresariales pueden restringir, pero no omitir auditoría de cambios.

## Criterios de aceptación

Aceptar revocación de cámara y acceso a archivo seleccionado sin acceso general al directorio.

## Rendimiento y evidencia

Medir permisos amplios sin justificación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [119 — Application Settings](119_W4_OS_APPLICATION_SETTINGS.md)
- [123 — Application Sandboxing](123_W4_OS_APPLICATION_SANDBOXING.md)
- [213 — Central Policy System](213_W4_OS_CENTRAL_POLICY_SYSTEM.md)

## Roadmap y condiciones de evolución

UI de permisos principales V1; ampliar sólo capacidades verificables.

---

[Anterior](128_W4_OS_APPLICATION_REPOSITORY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](130_W4_OS_APPLICATION_LIFECYCLE.md)
