# 125 · W4 OS — Native Application Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Reservar integración nativa para aplicaciones que necesiten servicios o capacidades del sistema.

## Alcance, arquitectura y decisiones

Los paquetes DEB se seleccionan por interoperabilidad, soporte y licencias; evitar reempaquetar binarios propietarios sin permiso. Configuración W4 se mantiene separada del ejecutable.

## Componentes y flujo operativo

1. Evaluar necesidad nativa
2. construir o importar paquete
3. comprobar dependencias
4. instalar en matriz
5. asignar mantenimiento.

## Seguridad y riesgos

Una app nativa comparte más acceso con la sesión; aplicar perfiles cuando sea viable y no ejecutarla como root para resolver permisos.

## Criterios de aceptación

Aceptar integración con escritorio y actualización sin sustituir bibliotecas base.

## Rendimiento y evidencia

Medir dependencias añadidas y superficie privilegiada.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [042 — DEB Package Architecture](042_W4_OS_DEB_PACKAGE_ARCHITECTURE.md)
- [123 — Application Sandboxing](123_W4_OS_APPLICATION_SANDBOXING.md)
- [131 — Default Applications](131_W4_OS_DEFAULT_APPLICATIONS.md)

## Roadmap y condiciones de evolución

Selección mínima V1; excepciones por requisito demostrado.

---

[Anterior](124_W4_OS_FLATPAK_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](126_W4_OS_THIRD_PARTY_APPLICATION_SUPPORT.md)
