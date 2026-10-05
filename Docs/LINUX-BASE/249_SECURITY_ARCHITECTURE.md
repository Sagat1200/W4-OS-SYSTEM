# W4 Linux Base

## 249 — Security Architecture

**Documento:** `249_SECURITY_ARCHITECTURE.md`  
**Bloque:** XXI — Seguridad  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Seguridad transversal exige fronteras de confianza entre cliente, Core, ejecutor y backend.

Responsable: **Security Service y responsables de cada dominio**. Dependencias de implementación: PAM, polkit, permisos Unix, nftables y hardening systemd. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Modelo de amenaza cubre cliente hostil, paquete malicioso, política inválida y root comprometido fuera de protección absoluta.

Modelo del dominio: principal, action, resource, decision, reason, policy_revision

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Autenticar origen, evaluar autorización y restricciones, registrar decisión, ejecutar con privilegio mínimo y auditar resultado.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

Denegación predeterminada; acciones administrativas explícitas y ninguna regla polkit permisiva para todo el bus W4.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

Separar autenticación de autorización; no confiar en UID o tenant enviados como argumentos por un cliente.

**Caso de fallo específico:** No afirmar que auditoría local es inviolable ante root.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Pruebas de denegación demuestran cero efectos y revisión documenta límites del modelo.

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
- [248_IDENTITY_EVENTS.md](248_IDENTITY_EVENTS.md)
- [250_SECURITY_TRUST_MODEL.md](250_SECURITY_TRUST_MODEL.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Matriz mínima de autorización

| Principal | Consultas | Mutaciones | Restricciones |
|---|---|---|---|
| Usuario local ordinario | Datos no sensibles permitidos | Sólo acciones expresamente concedidas | No secretos ni acceso implícito a otros usuarios |
| Administrador autenticado | Diagnóstico autorizado | Acciones administrativas por dominio | Validaciones y protecciones de recurso siguen aplicando |
| W4 Agent | Scope del enrolamiento y política | Allowlist de acciones y recursos | Sin shell; delegación auditada; expiry y tenant válidos |
| Worker/provider | Sólo datos necesarios | Sólo plan autorizado por servicio | No inventa identidad ni amplía scope |
| Recovery offline | Acceso autorizado al destino | Plan de reparación explícito | Discos/secretos identificados; preservar evidencia |

Lectura no significa acceso público global: inventario detallado, usuarios, logs y operaciones pueden identificar personas o equipos. Los permisos deben filtrarse por campo además de método cuando corresponda.

## 9. Amenazas y mitigaciones verificables

| Amenaza | Control | Prueba |
|---|---|---|
| Cliente falsifica UID | Identidad obtenida del bus | Payload UID falso no altera actor |
| Confused deputy | Acción/recurso tipados y allowlist | No aceptar ruta/comando genérico |
| TOCTOU de plan | Revisión e identidad comprobadas antes del efecto | Cambiar disco/perfil entre plan y apply |
| Replay remoto | command_id, expiry, scope y ledger | Reenvío no crea trabajo nuevo |
| Inyección de argumentos | argv fijo y validación por dominio | Metacaracteres no ejecutan shell |
| Lectura de secreto | secret_ref y filtrado previo a logs | Token fixture ausente de todos los outputs |
| Denegación de servicio | Cuotas, límites de payload, workers acotados | Carga rechazada no agota servicio |
| Paquete comprometido | Confianza de distribución y provenance | Artefacto no aprobado bloqueado |

W4 no puede garantizar integridad del host frente a root o kernel completamente comprometidos. La recuperación de ese caso requiere reconstrucción y revocación desde fuentes confiables. El objetivo V1 sí incluye proteger frente a clientes ordinarios hostiles y evitar privilegios innecesarios en interfaces.

Polkit se usa para autorización de acciones y desafío del sistema, conforme a su [manual oficial](https://polkit.pages.freedesktop.org/polkit/polkit.8.html). Las acciones W4 se declaran en archivos `.policy`; no se instala una regla general que autorice cualquier llamada W4. Para acciones cuya decisión dependa del recurso no se presupone que una autorización temporal de otra solicitud cubra nuevos detalles; se reevalúan las restricciones locales en cada operación.
