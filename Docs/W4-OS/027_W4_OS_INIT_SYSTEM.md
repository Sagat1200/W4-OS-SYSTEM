# 027 · W4 OS — Init System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Arranque y servicios · **Responsabilidad propuesta:** Integración de arranque  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Adoptar un único sistema de inicio para reducir variantes operativas.

## Alcance, arquitectura y decisiones

systemd de Debian coordina servicios, sesiones y apagado. No sustituir init; los servicios W4 se añaden como unidades empaquetadas con dependencias mínimas.

## Componentes y flujo operativo

1. Instalación de unidad
2. habilitación declarada
3. inicio condicionado
4. supervisión
5. parada ordenada; fallos exponen estado comprensible al diagnóstico.

## Seguridad y riesgos

Evitar unidades privilegiadas genéricas capaces de ejecutar texto recibido de red. Usar usuarios dedicados y límites de acceso.

## Criterios de aceptación

Aceptar inicio y parada repetidos sin procesos huérfanos.

## Rendimiento y evidencia

Medir servicios activados innecesariamente y tiempo de espera por dependencia.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [028 — systemd Integration](028_W4_OS_SYSTEMD_INTEGRATION.md)
- [029 — Startup Pipeline](029_W4_OS_STARTUP_PIPELINE.md)
- [030 — Shutdown Pipeline](030_W4_OS_SHUTDOWN_PIPELINE.md)

## Roadmap y condiciones de evolución

Unidades mínimas en MVP; endurecer y probar reinicio de cada servicio antes de V1.

---

[Anterior](026_W4_OS_BOOTLOADER_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](028_W4_OS_SYSTEMD_INTEGRATION.md)
