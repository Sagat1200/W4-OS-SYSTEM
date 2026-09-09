# 005 · W4 OS — Debian Base Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Usar Debian Stable como único upstream de distribución y controlar explícitamente su versión.

## Alcance, arquitectura y decisiones

El manifiesto debe fijar codename, arquitectura y origen; no seguir automáticamente el alias stable. Propuesta inicial: evaluar trixie como base candidata, confirmando paquetes y soporte al congelar la release.

## Componentes y flujo operativo

Importar metadatos autenticados, resolver paquetes y registrar versiones; una nueva Debian Stable abre un proyecto de transición, no una actualización automática de origen.

## Seguridad y riesgos

No mezclar testing, unstable, Ubuntu o repositorios RPM con la base. Los backports requieren selección y soporte asignado.

## Criterios de aceptación

Aceptar si reconstruir el manifiesto conserva orígenes y versiones; rechazar dependencias resueltas fuera de los repositorios permitidos.

## Rendimiento y evidencia

Medir divergencia frente a Debian.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [006 — Upstream Integration Strategy](006_W4_OS_UPSTREAM_INTEGRATION_STRATEGY.md)
- [391 — Upstream Sync Process](391_W4_OS_UPSTREAM_SYNC_PROCESS.md)
- [397 — Debian Release Transition](397_W4_OS_DEBIAN_RELEASE_TRANSITION.md)

## Roadmap y condiciones de evolución

Validar base candidata y mantener registro de cobertura por paquete durante toda la vida del producto.

## Manifiesto de base propuesto

```yaml
# Ejemplo de contrato; no es una configuración ejecutable de repositorio.
schema_version: 1
product_line: w4-v1
upstream:
  distribution: debian
  suite: trixie
  policy: fixed-codename
architectures:
  reference: amd64
  experimental: [arm64]
mix_other_distributions: false
security_source_required: true
```

Al congelar una release se añade el inventario exacto de paquetes y la identidad del snapshot de repositorio utilizado. El codename evita que un cambio del alias `stable` altere silenciosamente la plataforma. No sustituye el seguimiento de correcciones: la línea fijada continúa recibiendo mantenimiento compatible mediante el flujo de seguridad y actualizaciones.

## Política de excepciones

Una solicitud de paquete más reciente debe explicar la función bloqueada, los equipos afectados y por qué no basta una aplicación aislada o configuración. Si exige reconstrucción, se registra el delta, responsable y prueba. Si arrastra bibliotecas centrales de otra suite, se rechaza la incorporación puntual y se evalúa como cambio de plataforma. La aceptación de un backport no autoriza habilitar indiscriminadamente todos los paquetes de su repositorio.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [Debian — Releases](https://www.debian.org/releases/). La página oficial identifica Debian 13 trixie como Stable a la fecha de consulta. Fijar codename, no el alias mutable, es una decisión de diseño W4.

---

[Anterior](004_W4_OS_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](006_W4_OS_UPSTREAM_INTEGRATION_STRATEGY.md)
