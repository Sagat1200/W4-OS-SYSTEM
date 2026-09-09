# 083 · W4 OS — System State Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir qué datos retroceden juntos y cuáles requieren compatibilidad independiente.

## Alcance, arquitectura y decisiones

Generación incluye /usr, /etc, /var/lib/dpkg y estado APT relevante. /home, logs, cachés y datos grandes declarados se excluyen. Kernel/initramfs y ESP se enlazan mediante manifiesto de arranque.

## Componentes y flujo operativo

Antes de actualizar, listar montajes y versiones de esquema; clasificar cada migración como reversible, compatible hacia atrás o dependiente de respaldo.

## Seguridad y riesgos

No mover todo /var fuera de raíz ni revertir sólo binarios. /etc puede contener identidad: reconciliar certificados revocados al restaurar un estado antiguo.

## Criterios de aceptación

Aceptar matriz de archivos que demuestra qué cambia al revertir y coherencia paquete-binario.

## Rendimiento y evidencia

Medir cambios persistentes incompatibles.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [034 — Disk Layout Strategy](034_W4_OS_DISK_LAYOUT_STRATEGY.md)
- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [085 — Rollback Architecture](085_W4_OS_ROLLBACK_ARCHITECTURE.md)
- [377 — Configuration Architecture](377_W4_OS_CONFIGURATION_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Congelar contrato antes de MVP recuperable; toda nueva base de datos declara política de migración.

## Registro de estado persistente por componente

Cada servicio nuevo debe entregar una ficha con: ubicación de datos; propiedad y permisos; inclusión o exclusión en generación; versión de esquema; lectores/escritores compatibles; mecanismo de respaldo; efecto de downgrade; credenciales asociadas; y prueba de recuperación. La ficha forma parte de revisión antes de incorporar el servicio a la imagen.

| Cambio de datos | Acción admitida antes de actualizar |
|---|---|
| Sin migración | Snapshot del sistema y pruebas normales |
| Migración compatible hacia atrás | Verificar versión anterior leyendo datos nuevos |
| Migración reversible probada | Registrar reversión y copia previa independiente cuando proceda |
| Migración irreversible | Exigir respaldo restaurable, ventana y plan alternativo; no ofrecer rollback simple |
| Datos regenerables | Documentar cómo regenerar y costo; no clasificarlos así por conveniencia |

## Identidad después de rollback

Restaurar `/etc` puede devolver configuración de confianza antigua. Por ello, la reconciliación distingue configuración de software de decisiones de seguridad ya revocadas. Un certificado de dispositivo retirado no recupera autorización sólo porque reaparece en el disco: el servidor debe rechazarlo. Una política organizacional con revisión anterior se marca pendiente de reconciliación y no puede sobrescribir silenciosamente una política vigente de autoridad superior.

El estado de consentimiento necesita la misma atención: no se reactiva telemetría opcional por recuperar una copia antigua del perfil. El diseño debe conservar una marca de revocación adecuada o adoptar la opción más restrictiva cuando la vigencia sea incierta. Esta decisión se prueba expresamente al implementar persistencia y no se resuelve mediante una suposición sobre Btrfs.

---

[Anterior](082_W4_OS_SNAPPER_INTEGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](084_W4_OS_AUTOMATIC_SNAPSHOT_POLICY.md)
