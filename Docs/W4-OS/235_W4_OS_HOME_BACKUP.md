# 235 · W4 OS — Home Backup

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Experiencia Home · **Responsabilidad propuesta:** Producto Home  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Hacer respaldo doméstico comprensible y comprobable sin suscripción obligatoria.

## Alcance, arquitectura y decisiones

Destino externo como primera ruta; selección de carpetas, recordatorio de conexión y prueba de restauración. Mostrar última copia verificada, no sólo último intento.

## Componentes y flujo operativo

1. Elegir destino
2. seleccionar datos
3. proteger clave
4. ejecutar copia
5. verificar
6. restaurar archivo de prueba
7. recordar futuras copias.

## Seguridad y riesgos

No guardar la única clave en el mismo disco respaldado. Un destino desconectado no debe aparecer como actualizado.

## Criterios de aceptación

Aceptar restauración de foto y documento en otro perfil o equipo.

## Rendimiento y evidencia

Medir antigüedad de copia y archivos excluidos por error.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)
- [194 — Restore System](194_W4_OS_RESTORE_SYSTEM.md)
- [195 — Cloud Backup Strategy](195_W4_OS_CLOUD_BACKUP_STRATEGY.md)

## Roadmap y condiciones de evolución

Ruta externa V1; cloud como alternativa posterior.

---

[Anterior](234_W4_OS_FAMILY_USER_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](236_W4_OS_HOME_RECOVERY.md)
