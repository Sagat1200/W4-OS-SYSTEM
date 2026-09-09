# 126 · W4 OS — Third Party Application Support

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Permitir software externo con límites claros de compatibilidad y responsabilidad.

## Alcance, arquitectura y decisiones

Catálogo distingue recomendado, probado y no evaluado. Ofrecer rutas documentadas por formato; no prometer ejecución de cualquier paquete dirigido a otra distribución.

## Componentes y flujo operativo

1. Identificar proveedor y versión
2. validar origen
3. comprobar base y dependencias
4. instalar en entorno de prueba
5. registrar limitaciones.

## Seguridad y riesgos

No convertir automáticamente scripts curl-pipe-shell en instalación confiable. Terceros no pueden modificar repositorios del sistema sin autorización visible.

## Criterios de aceptación

Aceptar una app externa compatible y rechazo explicable de paquete para base distinta.

## Rendimiento y evidencia

Medir tickets atribuibles a terceros.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [124 — Flatpak Strategy](124_W4_OS_FLATPAK_STRATEGY.md)
- [125 — Native Application Strategy](125_W4_OS_NATIVE_APPLICATION_STRATEGY.md)
- [264 — Cross Platform Application Support](264_W4_OS_CROSS_PLATFORM_APPLICATION_SUPPORT.md)

## Roadmap y condiciones de evolución

Soporte inicial acotado; certificación voluntaria después de V1.

---

[Anterior](125_W4_OS_NATIVE_APPLICATION_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](127_W4_OS_APPLICATION_STORE.md)
