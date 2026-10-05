# W4 Linux Base

## 12 — Architecture Decision Records

**Documento:** `12_ARCHITECTURE_DECISION_RECORDS.md`  
**Bloque:** I — Contexto y arquitectura  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Registrar ADR inmutable por decisión; las revisiones posteriores lo sustituyen sin borrar contexto.

Responsable: **Equipo de arquitectura W4**. Dependencias de implementación: Debian estándar, Linux y contratos W4. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

ADR contiene estado, contexto, opciones, decisión, consecuencias, responsable y pruebas de hipótesis.

Modelo del dominio: capability, owner, dependencies, lifecycle, support_level

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Producto → cliente → API → servicio → provider → Debian. Las dependencias de compilación apuntan hacia contratos, sin importar clientes desde servicios.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

El manifiesto de plataforma fija suite Debian y providers; los perfiles sólo seleccionan capacidades compatibles.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

Evitar una autoridad implícita: ninguna GUI, perfil o llamada remota recibe privilegios por su nombre.

**Caso de fallo específico:** Una decisión superseded no sigue gobernando documentación sin referencia a su reemplazo.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Recorrer ADRs y comprobar que cada decisión vigente tiene dueño, alcance y documento consumidor.

La evidencia debe incluir versión Debian, arquitectura, perfil, versiones del backend, entradas saneadas, estado inicial, resultado observado y estado final. La prueba debe verificar efectos en el backend y no sólo el texto presentado por `w4ctl`. Si la función no está soportada, la prueba exige una respuesta `NOT_SUPPORTED` y ausencia de cambios parciales; no se considera éxito funcional.

Para cerrar el documento en una release se vinculan requisito, implementación y evidencia en el reporte de pruebas. Esta entrega documental define esos criterios; no afirma que las pruebas del futuro sistema operativo ya se hayan ejecutado.

## 7. Referencias y navegación

- [00_PROJECT_CONTEXT.md](00_PROJECT_CONTEXT.md)
- [01_V1_SCOPE_AND_OBJECTIVES.md](01_V1_SCOPE_AND_OBJECTIVES.md)
- [02_LINUX_BASE_ARCHITECTURE.md](02_LINUX_BASE_ARCHITECTURE.md)
- [11_TECHNOLOGY_DECISIONS.md](11_TECHNOLOGY_DECISIONS.md)
- [13_W4_CORE_ARCHITECTURE.md](13_W4_CORE_ARCHITECTURE.md)
- [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md)
- [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md)
- [50_PROVIDER_CONTRACT.md](50_PROVIDER_CONTRACT.md)
- [61_CONFIGURATION_ARCHITECTURE.md](61_CONFIGURATION_ARCHITECTURE.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Registro inicial de decisiones de esta edición

| ADR | Decisión | Consecuencia | Reconsiderar cuando |
|---|---|---|---|
| W4-ADR-001 | Debian estándar como base V1 | No reemplazar kernel, init ni gestor de paquetes | Un requisito medido no pueda resolverse por integración |
| W4-ADR-002 | API local sobre D-Bus del sistema | Clientes sin shell administrativo; no puerto TCP W4 por defecto | Un caso probado requiera transporte adicional |
| W4-ADR-003 | Servicios coordinan y providers adaptan | Separar políticas de mecanismos | No se elimina; se refina granularidad con evidencia |
| W4-ADR-004 | Operaciones largas durables | Seguimiento después de perder cliente | Medidas demuestren mecanismo alternativo igual de recuperable |
| W4-ADR-005 | NetworkManager de referencia | Ownership por interfaz obligatorio | Otro backend supere la suite y tenga dueño operativo |
| W4-ADR-006 | Sin rollback global obligatorio V1 | Reportar parcialidad de APT/dpkg | Exista unidad de consistencia y restauración probadas |
| W4-ADR-007 | Agent como cliente de API | Sin lógica Debian duplicada ni shell remoto genérico | Nuevas acciones se añaden como contratos tipados |
| W4-ADR-008 | Perfiles declarativos | Misma base para tres productos | Compatibilidad exija major nuevo, con migración |
| W4-ADR-009 | Estado W4 local transaccional | SQLite propuesto, separado de autoridad Debian | Pruebas de durabilidad/carga justifiquen otra opción |

Estado: **decisiones de diseño propuestas por esta especificación**, subordinadas a 00 y 01. Antes de implementación, el repositorio registra revisión y responsable de aceptación de cada ADR. Esto no impide desarrollar prototipos ni implica que el usuario deba aprobar cada tarea rutinaria; es trazabilidad de ingeniería del producto.

Cada ADR posterior debe incluir problema reproducible, alternativas descartadas, impacto sobre seguridad y compatibilidad, estimación de mantenimiento, prueba de éxito y criterio de abandono. Un cambio cosmético o preferencia por tecnología propia no basta para sustituir componentes maduros.
