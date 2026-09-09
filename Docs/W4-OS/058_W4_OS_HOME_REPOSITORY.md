# 058 · W4 OS — Home Repository

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Paquetes y repositorios · **Responsabilidad propuesta:** Mantenimiento y distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener selección Home sin duplicar paquetes comunes ni alterar su soporte.

## Alcance, arquitectura y decisiones

Preferir manifiesto Home sobre archivo común; repositorio separado sólo si hay contenido o distribución que lo justifique. Los paquetes compartidos mantienen un único origen W4.

## Componentes y flujo operativo

Resolver perfil Home, añadir aplicaciones aprobadas y comprobar catálogo y licencias de distribución.

## Seguridad y riesgos

No incluir software publicitario ni cuentas preconfiguradas. Terceros opcionales deben mostrar origen y permisos antes de habilitación.

## Criterios de aceptación

Aceptar imagen Home sin dependencias de gestión empresarial activas y con todas las aplicaciones localizables.

## Rendimiento y evidencia

Medir tamaño adicional del perfil.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [011 — Home Edition](011_W4_OS_HOME_EDITION.md)
- [014 — Edition Package Profiles](014_W4_OS_EDITION_PACKAGE_PROFILES.md)
- [131 — Default Applications](131_W4_OS_DEFAULT_APPLICATIONS.md)

## Roadmap y condiciones de evolución

V1 con componentes de perfil; separar archivo únicamente mediante ADR justificado.

---

[Anterior](057_W4_OS_HARDWARE_REPOSITORY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](059_W4_OS_BUSINESS_REPOSITORY.md)
