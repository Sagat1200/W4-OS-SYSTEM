# 281 · W4 OS — Rollback Testing

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Pruebas y calificación · **Responsabilidad propuesta:** Calidad y validación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Demostrar que rollback recupera el sistema y conserva datos excluidos.

## Alcance, arquitectura y decisiones

Fixtures colocan archivos en raíz, home, logs y base de datos persistente; manifiesto registra kernel e initramfs. Probar compatibilidad de esquemas y credenciales revocadas.

## Componentes y flujo operativo

1. Crear estado bueno
2. introducir actualización defectuosa
3. recuperar
4. comprobar archivos, paquetes y arranque
5. reconciliar identidad.

## Seguridad y riesgos

No aprobar sólo porque aparece login. Un dato persistente migrado puede impedir aplicaciones aunque raíz haya retrocedido.

## Criterios de aceptación

Aceptar binarios/dpkg coherentes, documento reciente conservado y incompatibilidad de esquema bloqueada.

## Rendimiento y evidencia

Medir RTO observado.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [077 — Update Rollback System](077_W4_OS_UPDATE_ROLLBACK_SYSTEM.md)
- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [085 — Rollback Architecture](085_W4_OS_ROLLBACK_ARCHITECTURE.md)
- [088 — Boot Recovery](088_W4_OS_BOOT_RECOVERY.md)

## Roadmap y condiciones de evolución

Obligatoria antes de botón de rollback o automatización.

---

[Anterior](280_W4_OS_UPDATE_TESTING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](282_W4_OS_SECURITY_TESTING.md)
