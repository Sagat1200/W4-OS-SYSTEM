# 188 · W4 OS — External Storage

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Almacenamiento y backup · **Responsabilidad propuesta:** Protección y recuperación de datos  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Usar medios externos con montaje seguro y mensajes claros sobre compatibilidad.

## Alcance, arquitectura y decisiones

Política por usuario y perfil para montar, abrir y expulsar. Soportar sólo formatos probados y no ejecutar autorun; cifrado externo se gestiona aparte.

## Componentes y flujo operativo

1. Conectar
2. detectar
3. autorizar montaje
4. abrir si usuario lo pide
5. sincronizar
6. desmontar
7. indicar extracción segura.

## Seguridad y riesgos

No ejecutar contenido del dispositivo ni confiar en etiquetas como instrucciones. Medios de terceros pueden contener archivos maliciosos.

## Criterios de aceptación

Aceptar disco con permisos limitados, lleno y retirado durante copia sin falso éxito.

## Rendimiento y evidencia

Medir copia y tiempo de expulsión.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [036 — Filesystem Strategy](036_W4_OS_FILESYSTEM_STRATEGY.md)
- [140 — File Manager](140_W4_OS_FILE_MANAGER.md)
- [189 — USB Storage Policy](189_W4_OS_USB_STORAGE_POLICY.md)

## Roadmap y condiciones de evolución

Función Home V1; restricciones Business por política.

---

[Anterior](187_W4_OS_STORAGE_DEVICE_MANAGER.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](189_W4_OS_USB_STORAGE_POLICY.md)
