# 123 · W4 OS — Application Sandboxing

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Reducir acceso de aplicaciones a datos y dispositivos mediante permisos mínimos.

## Alcance, arquitectura y decisiones

Flatpak y portales son ruta propuesta para apps compatibles; AppArmor protege componentes seleccionados. La política se define por aplicación y función, no sólo por formato.

## Componentes y flujo operativo

1. Declarar recursos
2. ejecutar con aislamiento
3. pedir acceso puntual vía portal
4. revocar
5. comprobar comportamiento.

## Seguridad y riesgos

Acceso al home completo, bus del sistema o dispositivos amplios requiere justificación. No permitir que un fallo de compatibilidad desactive globalmente el aislamiento.

## Criterios de aceptación

Aceptar app que abre un archivo seleccionado y no puede leer otro fuera de permiso.

## Rendimiento y evidencia

Medir fallos funcionales y costo del aislamiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [124 — Flatpak Strategy](124_W4_OS_FLATPAK_STRATEGY.md)
- [129 — Application Permissions](129_W4_OS_APPLICATION_PERMISSIONS.md)
- [158 — Apparmor Architecture](158_W4_OS_APPARMOR_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Perfiles de apps principales V1; ampliar tras pruebas de uso real.

---

[Anterior](122_W4_OS_APPLICATION_INSTALLATION_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](124_W4_OS_FLATPAK_STRATEGY.md)
