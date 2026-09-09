# 069 · W4 OS — ISO Build Pipeline

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Construcción y artefactos · **Responsabilidad propuesta:** Infraestructura de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Producir medios instalables que funcionen sin depender de servicios W4.

## Alcance, arquitectura y decisiones

ISO contiene instalador, base requerida y recuperación documentada. Opcionales descargables se identifican; elección de herramienta de imagen queda en ADR con prueba UEFI.

## Componentes y flujo operativo

1. Construir raíz live
2. incluir instalador
3. ensamblar ISO
4. verificar manifiesto
5. probar arranque y escritura a USB en laboratorio.

## Seguridad y riesgos

No publicar una ISO sin validación de arranque ni mezclar claves de prueba con producción. Mostrar verificación del medio al usuario.

## Criterios de aceptación

Aceptar instalación desconectada y detección de medio corrupto.

## Rendimiento y evidencia

Medir tiempo de arranque live, memoria y capacidad del USB requerida.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [031 — Installer Architecture](031_W4_OS_INSTALLER_ARCHITECTURE.md)
- [068 — Image Build System](068_W4_OS_IMAGE_BUILD_SYSTEM.md)
- [087 — Recovery Environment](087_W4_OS_RECOVERY_ENVIRONMENT.md)

## Roadmap y condiciones de evolución

ISO amd64 V1; otros formatos tras calificación independiente.

---

[Anterior](068_W4_OS_IMAGE_BUILD_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](070_W4_OS_RELEASE_ARTIFACT_SYSTEM.md)
