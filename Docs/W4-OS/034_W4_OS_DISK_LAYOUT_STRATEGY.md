# 034 · W4 OS — Disk Layout Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Fijar un layout que permita rollback coherente sin revertir documentos personales.

## Alcance, arquitectura y decisiones

Propuesta: ESP separada; raíz Btrfs con /usr, /etc y /var/lib/dpkg en la misma generación; subvolúmenes explícitos para /home, logs, cachés y datos grandes. /boot permanece coordinado mediante manifiesto.

## Componentes y flujo operativo

Instalar layout, registrar exclusiones y validar montaje después de reiniciar; antes del snapshot comprobar que no existen subvolúmenes inesperados dentro del estado versionado.

## Seguridad y riesgos

No excluir todo /var: desincronizar dpkg de /usr rompe recuperación. Datos de aplicaciones excluidos requieren compatibilidad de esquema o respaldo propio.

## Criterios de aceptación

Aceptar rollback con coherencia entre binarios y base dpkg y conservación de un archivo de usuario.

## Rendimiento y evidencia

Medir espacio compartido y exclusivo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [035 — Btrfs Architecture](035_W4_OS_BTRFS_ARCHITECTURE.md)
- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [085 — Rollback Architecture](085_W4_OS_ROLLBACK_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Probar layout en MVP; congelarlo antes de soportar actualización entre releases.

## Tabla de persistencia del layout propuesto

| Ruta o recurso | Relación con generación | Regla de recuperación |
|---|---|---|
| `/usr` y librerías del sistema | Dentro de raíz versionada | Retroceden con paquetes |
| `/etc` | Dentro de raíz versionada | Retrocede; identidad y revocaciones se reconcilian |
| `/var/lib/dpkg` | Dentro de raíz versionada | Debe describir los binarios de esa raíz |
| Estado APT necesario | Dentro de raíz versionada | Conservar selección y configuración coherentes |
| `/home` | Subvolumen separado | No se revierte por rollback de sistema |
| `/var/log` | Separado | Conserva evidencia del fallo y recuperación |
| Cachés declaradas | Separadas, regenerables | Pueden limpiarse con límites y sin afectar operaciones activas |
| Datos de contenedores/VM | Separados y respaldados por su motor | No presumir consistencia sólo por snapshot raíz |
| `/boot` | Artefactos coordinados | Asociar kernel e initramfs con cada generación admitida |
| ESP | Partición separada | Conservar entradas/archivos necesarios y reparar por protocolo propio |

No se fija una partición por cada fila: la tabla expresa propiedad y semántica. Las capacidades mínimas se obtienen midiendo ISO, instalación, kernel retenido, staging y crecimiento de snapshots. El espacio de actualización se calcula antes de iniciar la etapa offline. El instalador debe rechazar un layout que pueda instalarse pero no actualizarse con margen razonable.

## Prueba de coherencia

1. Crear un archivo de prueba en `/home` y otro en un directorio de configuración propio del sistema.
2. Registrar paquete, versión y hash de un binario antes de un cambio.
3. Crear el estado recuperable y aplicar una actualización de prueba.
4. Modificar el archivo personal después del snapshot.
5. Recuperar el sistema y verificar simultáneamente binario, versión en dpkg, configuración y archivo personal reciente.
6. Confirmar que el log de la actualización fallida continúa disponible.

La prueba se ejecuta en VM o dispositivo de laboratorio. No se entrega un comando de particionado genérico porque el destino debe resolverse y confirmarse a partir del inventario real.

---

[Anterior](033_W4_OS_PARTITIONING_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](035_W4_OS_BTRFS_ARCHITECTURE.md)
