# 046 · W4 OS — Package Dependency Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Paquetes y repositorios · **Responsabilidad propuesta:** Mantenimiento y distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Evitar ciclos, conflictos ocultos y sustituciones masivas de paquetes upstream.

## Alcance, arquitectura y decisiones

Dependencias se declaran por necesidad real; Conflicts, Breaks y Replaces requieren escenario de migración. Las bibliotecas del sistema se conservan de Debian salvo excepción.

## Componentes y flujo operativo

1. Analizar grafo
2. instalar perfil limpio
3. actualizar perfil anterior
4. comparar paquetes retirados y retenidos.

## Seguridad y riesgos

Una dependencia de tercero no puede elevar su repositorio a autoridad global. Revisar paquetes con scripts privilegiados o servicios nuevos.

## Criterios de aceptación

Aceptar grafo sin ciclos bloqueantes y solución consistente en ambas arquitecturas declaradas.

## Rendimiento y evidencia

Medir cierre transitivo y peso de dependencias.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [042 — DEB Package Architecture](042_W4_OS_DEB_PACKAGE_ARCHITECTURE.md)
- [043 — APT Integration](043_W4_OS_APT_INTEGRATION.md)
- [065 — Multi Arch Build System](065_W4_OS_MULTI_ARCH_BUILD_SYSTEM.md)

## Roadmap y condiciones de evolución

Validar cada cambio de perfil y reducir dependencias recomendadas innecesarias.

---

[Anterior](045_W4_OS_META_PACKAGE_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](047_W4_OS_PACKAGE_SIGNING_SYSTEM.md)
