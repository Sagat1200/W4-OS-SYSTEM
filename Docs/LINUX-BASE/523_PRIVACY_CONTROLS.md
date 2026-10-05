# W4 Linux Base

## 523 — Privacy Controls

**Documento:** `523_PRIVACY_CONTROLS.md`  
**Bloque:** XLVI — Privacidad  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Controles muestran qué se recoge, guarda y envía, con origen de política.

Responsable: **Responsables de datos y servicios**. Dependencias de implementación: Clasificación, filtros y controles de exportación W4. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Usuario autorizado puede consultar estado efectivo y administrar opciones permitidas.

Modelo del dominio: data_class, purpose, destination, retention, authorization, redaction

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Clasificar campo, minimizar, comprobar propósito y destino, exportar filtrado y registrar decisión.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

Inventario local separado de exportación; retención por clase y transmisión desactivada sin política explícita.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

No asumir que anonimizar hostname elimina identificación; combinaciones de hardware también pueden identificar equipos.

**Caso de fallo específico:** Control bloqueado por empresa explica autoridad, no aparenta apagado efectivo.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Desactivar exportación permitida y verificar cese de tráfico y manejo de cola.

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
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [516_PRIVACY_ARCHITECTURE.md](516_PRIVACY_ARCHITECTURE.md)
- [522_DATA_RETENTION.md](522_DATA_RETENTION.md)
- [524_SYSTEM_ADMINISTRATION_GUIDE.md](524_SYSTEM_ADMINISTRATION_GUIDE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)
