# 397 · W4 OS — Debian Release Transition

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Evolución y mantenimiento · **Responsabilidad propuesta:** Mantenimiento de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Planificar cambio de Debian Stable como transición de plataforma con pruebas propias.

## Alcance, arquitectura y decisiones

Nueva base abre rama/proyecto de evaluación; revisar toolchain, ABI, escritorio, kernel, layout y cobertura. La base actual continúa hasta decisión de salida.

## Componentes y flujo operativo

1. Inventariar diferencias
2. reconstruir paquetes W4
3. resolver incompatibilidades
4. probar instalación y migración
5. calificar
6. publicar nueva línea.

## Seguridad y riesgos

No cambiar codename en clientes automáticamente. Cambios de datos o arranque pueden impedir rollback y requieren respaldo y plan de retorno.

## Criterios de aceptación

Aceptar matriz de compatibilidad y migración piloto antes de anunciar soporte.

## Rendimiento y evidencia

Medir paquetes bloqueados y esfuerzo de adaptación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [005 — Debian Base Strategy](005_W4_OS_DEBIAN_BASE_STRATEGY.md)
- [065 — Multi Arch Build System](065_W4_OS_MULTI_ARCH_BUILD_SYSTEM.md)
- [267 — LTS Strategy](267_W4_OS_LTS_STRATEGY.md)
- [398 — Major Version Migration](398_W4_OS_MAJOR_VERSION_MIGRATION.md)

## Roadmap y condiciones de evolución

Iniciar evaluación con anticipación al fin de soporte, sin fecha inventada.

---

[Anterior](396_W4_OS_UPSTREAM_CONTRIBUTION_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](398_W4_OS_MAJOR_VERSION_MIGRATION.md)
