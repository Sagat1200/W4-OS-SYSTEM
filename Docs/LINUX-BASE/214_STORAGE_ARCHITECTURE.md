# W4 Linux Base

## 214 — Storage Architecture

**Documento:** `214_STORAGE_ARCHITECTURE.md`  
**Bloque:** XVIII — Almacenamiento  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Storage Service comienza con inventario confiable y operaciones básicas limitadas.

Responsable: **Storage Service**. Dependencias de implementación: udev, util-linux, herramientas de filesystem y LVM/cryptsetup opcionales. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Árbol expresa discos, particiones, capas cifradas/LVM, filesystems y mounts.

Modelo del dominio: device_id, major_minor, uuid, size_bytes, mount, health

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Inventariar árbol de bloques, comprobar dependencias, producir plan, confirmar identidad y ejecutar sólo operaciones soportadas.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

Inventario y montaje básico V1; modificaciones destructivas exigen plan exacto, autorización específica y política de protección.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

No usar /dev/sdX como identidad persistente; no formatear discos root, montados o con dependencias activas inadvertidamente.

**Caso de fallo específico:** No inferir que nodo no montado está libre si tiene holders activos.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Disco con volumen activo se protege ante formato solicitado.

La evidencia debe incluir versión Debian, arquitectura, perfil, versiones del backend, entradas saneadas, estado inicial, resultado observado y estado final. La prueba debe verificar efectos en el backend y no sólo el texto presentado por `w4ctl`. Si la función no está soportada, la prueba exige una respuesta `NOT_SUPPORTED` y ausencia de cambios parciales; no se considera éxito funcional.

Para cerrar el documento en una release se vinculan requisito, implementación y evidencia en el reporte de pruebas. Esta entrega documental define esos criterios; no afirma que las pruebas del futuro sistema operativo ya se hayan ejecutado.

## 7. Referencias y navegación

- [00_PROJECT_CONTEXT.md](00_PROJECT_CONTEXT.md)
- [01_V1_SCOPE_AND_OBJECTIVES.md](01_V1_SCOPE_AND_OBJECTIVES.md)
- [02_LINUX_BASE_ARCHITECTURE.md](02_LINUX_BASE_ARCHITECTURE.md)
- [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md)
- [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md)
- [50_PROVIDER_CONTRACT.md](50_PROVIDER_CONTRACT.md)
- [61_CONFIGURATION_ARCHITECTURE.md](61_CONFIGURATION_ARCHITECTURE.md)
- [213_NETWORK_RECOVERY.md](213_NETWORK_RECOVERY.md)
- [215_STORAGE_DEVICE_MODEL.md](215_STORAGE_DEVICE_MODEL.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Matriz de capacidad storage V1

| Acción | V1 | Condición |
|---|---|---|
| Inventario de discos/particiones/montajes | Obligatorio | Fuentes kernel/udev y permisos de lectura |
| Capacidad y uso | Obligatorio donde observable | Unidades bytes y cobertura declarada |
| Montar/desmontar volumen existente | Básico, según tipo soportado | Identidad, permisos, opciones y no uso crítico |
| SMART/salud avanzada | Opcional | Hardware y herramienta compatibles |
| Crear partición/formatear | Capacidad restringida de instalación o administración específica | Plan destructivo y target explícito |
| Redimensionar LVM/filesystem | Opcional | Combinación concreta ensayada |
| Snapshots/rollback integral | Futuro/opcional | Garantía real del backend y unidad de consistencia |

## 9. Plan destructivo

El plan identifica disco mediante resource_id, generación de presencia, tamaño y atributos persistentes disponibles. También registra major/minor actual, tabla de particiones, mounts, holders, dependencias y exclusiones. No basta con `removable=true` ni con ruta `/dev/sdb`.

La confirmación se vincula al hash de plan y al actor. Antes del primer write se vuelve a observar identidad y uso del dispositivo. Se rechaza si pasó a root, aloja estado W4 necesario, aparece un holder o cambió contenido relevante. La protección de root admite únicamente instalación/recovery offline con destino inequívoco; no una excepción oculta en GUI.

El respaldo de tabla de particiones es evidencia útil, no backup de archivos. Formatear puede destruir datos irreversiblemente. El servicio no promete compensación salvo que exista copia completa verificada y procedimiento ensayado. Los tests usan discos virtuales desechables y comprueban que un cambio de identidad entre plan y apply produce cero escrituras.
