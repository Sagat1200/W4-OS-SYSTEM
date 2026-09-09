# 349 · W4 OS — Factory Image Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Continuidad y OEM · **Responsabilidad propuesta:** Continuidad y fabricación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener imagen de fábrica recuperable sin datos ni identidad del usuario.

## Alcance, arquitectura y decisiones

Imagen maestra deriva de release firmada y perfil de hardware; primeras credenciales se generan en destino. Reinstalación de fábrica no equivale a restaurar archivos personales.

## Componentes y flujo operativo

1. Seleccionar imagen compatible
2. verificar firma
3. aplicar a destino confirmado
4. generar identidad
5. actualizar a estado soportado
6. iniciar onboarding.

## Seguridad y riesgos

No distribuir una imagen vieja vulnerable como estado final sin ruta de actualización. Evitar credenciales OEM y claves compartidas.

## Criterios de aceptación

Aceptar dos instalaciones con identidades distintas y actualización desde imagen almacenada.

## Rendimiento y evidencia

Medir antigüedad y tiempo de preparación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [039 — OEM Installation Mode](039_W4_OS_OEM_INSTALLATION_MODE.md)
- [068 — Image Build System](068_W4_OS_IMAGE_BUILD_SYSTEM.md)
- [089 — Factory Reset System](089_W4_OS_FACTORY_RESET_SYSTEM.md)
- [350 — OEM Image Strategy](350_W4_OS_OEM_IMAGE_STRATEGY.md)

## Roadmap y condiciones de evolución

Imagen V1 de referencia; mantenimiento por ciclo de producto.

---

[Anterior](348_W4_OS_BUSINESS_CONTINUITY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](350_W4_OS_OEM_IMAGE_STRATEGY.md)
