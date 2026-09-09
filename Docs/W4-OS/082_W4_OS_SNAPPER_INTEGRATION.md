# 082 · W4 OS — Snapper Integration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Integrar Snapper para administrar snapshots sin atribuirle automáticamente toda la recuperación W4.

## Alcance, arquitectura y decisiones

Configurar raíz y política de limpieza compatibles con layout W4. El motor W4 coordina snapshots, dpkg y arranque; hooks duplicados de APT deben evitarse.

## Componentes y flujo operativo

1. Solicitar snapshot con metadatos
2. verificar creación
3. ejecutar cambio coordinado
4. registrar snapshot posterior cuando aporte diagnóstico.

## Seguridad y riesgos

La integración de GRUB de openSUSE no se presume en Debian. No ofrecer botón de rollback hasta comprobar el contrato de arranque y estado persistente.

## Criterios de aceptación

Aceptar snapshot pre/post asociado a una sola operación y limpieza que conserva último bueno.

## Rendimiento y evidencia

Medir costo de hooks y metadatos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [035 — Btrfs Architecture](035_W4_OS_BTRFS_ARCHITECTURE.md)
- [077 — Update Rollback System](077_W4_OS_UPDATE_ROLLBACK_SYSTEM.md)
- [081 — Snapshot Architecture](081_W4_OS_SNAPSHOT_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Evaluar paquetes y configuración sobre base fijada; habilitar recuperación sólo con pruebas propias.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [openSUSE — System recovery and snapshot management with Snapper](https://doc.opensuse.org/documentation/leap/reference/html/book-reference/cha-snapper.html). La integración descrita pertenece a openSUSE Leap 15.6. Sirve como referencia de diseño, sin asumir que Debian incluye su misma integración de arranque.

---

[Anterior](081_W4_OS_SNAPSHOT_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](083_W4_OS_SYSTEM_STATE_MODEL.md)
