# 252 · W4 OS — Developer Edition Profile

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Desarrollo · **Responsabilidad propuesta:** Plataforma de desarrollo  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir Developer como perfil instalable en Home o Business con límites de política.

## Alcance, arquitectura y decisiones

Metapaquete de utilidades, editor elegido y soporte de contenedores; Business puede restringir virtualización o red sin alterar identidad de edición.

## Componentes y flujo operativo

1. Instalar perfil
2. comprobar capacidad
3. crear entorno de usuario
4. probar compilación
5. retirar herramientas conservando proyectos.

## Seguridad y riesgos

No agregar usuario a grupos equivalentes a root sin explicación y autorización. Repositorios de lenguajes tienen confianza separada.

## Criterios de aceptación

Aceptar instalación en ambas ediciones y retirada sin eliminar código.

## Rendimiento y evidencia

Medir tamaño y servicios nuevos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [014 — Edition Package Profiles](014_W4_OS_EDITION_PACKAGE_PROFILES.md)
- [251 — Developer Platform](251_W4_OS_DEVELOPER_PLATFORM.md)
- [255 — Podman Docker Support](255_W4_OS_PODMAN_DOCKER_SUPPORT.md)

## Roadmap y condiciones de evolución

Perfil opcional tras base V1; no ampliar soporte comercial implícitamente.

---

[Anterior](251_W4_OS_DEVELOPER_PLATFORM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](253_W4_OS_DEVELOPMENT_TOOLCHAIN.md)
