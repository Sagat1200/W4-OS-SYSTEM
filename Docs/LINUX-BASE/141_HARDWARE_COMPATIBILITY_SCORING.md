# W4 Linux Base

## 141 — Hardware Compatibility Scoring

**Documento:** `141_HARDWARE_COMPATIBILITY_SCORING.md`  
**Bloque:** XII — Compatibilidad hardware  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Scoring ayuda a priorizar, pero un requisito crítico fallido bloquea aptitud.

Responsable: **Equipo de validación hardware**. Dependencias de implementación: Hardware Probe, imágenes de prueba y resultados medidos. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Peso por función y cobertura; desconocido no suma éxito; resultado incluye razones.

Modelo del dominio: hardware_fingerprint, test_suite, release, status, evidence_id

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Sondear sin modificaciones, ejecutar pruebas autorizadas y registrar resultados por release y configuración exacta.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

La base de compatibilidad se versiona; estados supported, partial, experimental, unsupported y unknown.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

No convertir mera detección en certificación; compartir identificadores sólo minimizados y con autorización.

**Caso de fallo específico:** Promedio alto no compensa ausencia de driver del disco de instalación.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Equipo con todo aprobado salvo almacenamiento debe quedar no apto.

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
- [137_HARDWARE_COMPATIBILITY_ARCHITECTURE.md](137_HARDWARE_COMPATIBILITY_ARCHITECTURE.md)
- [140_HARDWARE_COMPATIBILITY_DATABASE.md](140_HARDWARE_COMPATIBILITY_DATABASE.md)
- [142_HARDWARE_CERTIFICATION_MODEL.md](142_HARDWARE_CERTIFICATION_MODEL.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Algoritmo de referencia y límites

La aptitud no se decide por promedio solamente. Primero se evalúan requisitos esenciales para el perfil: CPU/arquitectura, memoria mínima declarada en release, disco instalable, boot soportado y, cuando la instalación requiera descarga, conectividad necesaria. Cada requisito se marca pass, fail o unknown. Un fail esencial produce `not_ready`; un unknown esencial produce `needs_verification`. Ninguno puede quedar oculto por un buen resultado de GPU o USB.

Después se calcula un indicador informativo sobre funciones opcionales probadas. Para cada función i se fija un peso `w_i > 0` en el manifiesto de pruebas del perfil. Se asigna `v_i=1` a pass, `v_i=0.5` a partial y `v_i=0` a fail; unknown queda fuera del numerador y del denominador de puntuación, pero reduce cobertura.

```text
score = 100 × sum(w_i × v_i para funciones evaluadas) / sum(w_i evaluados)
coverage = sum(w_i evaluados) / sum(w_i de todas las funciones previstas)
```

Si no hay funciones evaluadas, score es null. Siempre se muestran score y coverage juntos, sin un umbral comercial de certificación. Los pesos se versionan y no cambian entre equipos de una misma comparación. Ejemplo: dos funciones de peso igual, una pass y otra unknown, producen score=100 con coverage=0.5; el reporte no puede llamarlo compatibilidad completa.

Pruebas: equipo con disco esencial fallido y todos los opcionales aprobados resulta not_ready; equipo con funciones desconocidas conserva cobertura baja; cambiar orden de resultados no modifica cálculo; denominador vacío no produce división por cero. La certificación del documento 142 requiere evidencia adicional y no se deduce de esta puntuación.
