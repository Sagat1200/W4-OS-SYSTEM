# W4 Linux Base

## 426 — Testing Architecture

**Documento:** `426_TESTING_ARCHITECTURE.md`  
**Bloque:** XXXVII — Testing  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Testing vincula requisitos con pruebas de unidad, integración y sistema.

Responsable: **Equipo de calidad**. Dependencias de implementación: Fixtures, providers simulados y VMs Debian reales. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Cada caso tiene oracle externo, estado inicial, acción y evidencia final.

Modelo del dominio: test_id, requirement_id, environment, input, oracle, evidence

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Preparar estado conocido, ejecutar acción, contrastar resultado con backend y guardar evidencia reproducible.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

Suites separadas por nivel; pruebas privilegiadas sólo en entornos dedicados y reiniciables.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

Mocks no sustituyen integración; una prueba omitida no equivale a aprobada.

**Caso de fallo específico:** Cantidad de tests no sustituye cobertura de criterios V1.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Mapear los veinte criterios de 01 a pruebas ejecutables y resultados.

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
- [425_PRODUCT_MIGRATION.md](425_PRODUCT_MIGRATION.md)
- [427_UNIT_TESTING.md](427_UNIT_TESTING.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Matriz de niveles y oracles

| Nivel | Entorno | Oracle | Casos imprescindibles |
|---|---|---|---|
| Unidad | Sin root | Reglas/esquemas y fixtures independientes | Versiones, parser, resolución, autorización |
| Contrato | Provider falso instrumentado | Esquema y efectos registrados | Entradas inválidas, denegación, idempotencia |
| Integración | VM Debian fijada | Estado real de backend | dpkg, unidades, perfiles de red, permisos |
| Sistema | Imagen instalada | Boot, API y comportamiento funcional | Tres perfiles, operación offline, reinicio |
| Destructiva | VM/discos desechables | Datos protegidos y recuperación | Corte de energía, ENOSPC, configuración corrupta |
| Hardware | Equipo identificado | Prueba de función concreta | Red, storage, GPU y energía cuando soportados |
| Performance | Hardware de referencia | Distribución frente a baseline | Idle, concurrencia, boot y persistencia |

Toda prueba tiene `test_id`, `requirement_id`, commit, environment_id, fixtures, seed cuando use aleatoriedad, started_at, duración monotónica, verdict y referencias de evidencia. El verdict se limita a passed, failed, skipped o infrastructure_error; sólo passed acredita el requisito.

Los tests de seguridad no deben confirmar sólo que se imprimió DENIED: un provider instrumentado verifica cero llamadas mutantes y la prueba de integración contrasta estado antes/después. Los tests de recuperación no deben confirmar sólo que arrancó un daemon: comparan configuración, identidad, datos protegidos y funcionamiento del recurso.

Cualquier instalación o prueba destructiva falla de forma segura si no reconoce su VM/disco permitido. El mecanismo de protección se prueba como parte de la suite. Las evidencias se guardan fuera del volumen sometido a corrupción o falta de espacio.

No se ejecuta este conjunto sobre la máquina personal durante generación documental. Los procedimientos definen el trabajo de implementación y validación futura. El informe de esta entrega sólo verifica integridad y estructura de los documentos.
