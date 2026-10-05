# W4 Linux Base

## 145 — Package Management Architecture

**Documento:** `145_PACKAGE_MANAGEMENT_ARCHITECTURE.md`  
**Bloque:** XIII — Paquetes  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Package Service ofrece operaciones comunes manteniendo APT/dpkg como autoridad.

Responsable: **Package Service**. Dependencias de implementación: APT y dpkg; catálogo y locks nativos. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

search/info/install/remove/upgrade/list-installed comparten plan y errores.

Modelo del dominio: name, architecture, version, origin, plan_id, operation_id

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Resolver plan con APT, validar política y autorización, adquirir serialización W4, respetar locks Debian, aplicar y reconciliar dpkg.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

Repositorios autorizados y política de conffiles explícita; un escritor de paquetes W4 por host.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

No borrar locks ni tratar operaciones dpkg como transacciones atómicas; scripts de paquetes tienen efectos que una desinstalación no revierte.

**Caso de fallo específico:** Base dpkg inconsistente bloquea nueva mutación hasta diagnóstico.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Romper paquete de prueba y comprobar reporte de reparación, no instalación ciega.

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
- [144_HARDWARE_COMPATIBILITY_REPORT.md](144_HARDWARE_COMPATIBILITY_REPORT.md)
- [146_PACKAGE_MODEL.md](146_PACKAGE_MODEL.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Plan de paquetes y fronteras de ejecución

| Fase | Estado permitido | Evidencia |
|---|---|---|
| Resolver | Sin cambios instalados | Catálogo, selección y dependencias |
| Validar | Sin cambios instalados | Política, permisos y espacio |
| Descargar | Sólo caché | Hashes de paquetes y origen |
| Aplicar | Efectos potencialmente irreversibles | Estado dpkg, scripts y progreso |
| Verificar | Observación posterior | Versiones, conffiles y checks del componente |
| Reconciliar | Tras interrupción | Estado real y efectos incompletos |

La serialización W4 evita dos workers propios, pero no sustituye locks APT/dpkg. Se comprueba el backend de nuevo al ejecutar; una simulación no reserva estado. Si cambian candidato, dependencias o removals frente al plan mostrado, apply devuelve CONFLICT y necesita nuevo plan. No se mantiene un lock de paquetes mientras se espera interacción humana indefinidamente.

Plan mínimo: plan_id, hash, actor_scope, catalog_revision, created_at, expires_at, requested_packages, installs, upgrades, removals, download_bytes, extra_disk_bytes, conffile_policy, protected_packages y reboot_estimate. Cada paquete se identifica por nombre, arquitectura, versión y origen. Valores de tamaño son estimaciones con margen y comprobación adicional en ejecución.

En no-interaction, conffile_policy debe ser explícita: conservar versión local, adoptar nueva donde esté permitido, o fallar para revisión. No forzar indiscriminadamente todas las respuestas de maintainer scripts. Un paquete que requiera entrada no contemplada termina en needs_attention o fallo controlado según fase; el ejecutor conserva diagnóstico y no bloquea indefinidamente.

La autenticidad de APT se basa en metadatos de archivo firmados y hashes de la cadena de índices/paquetes; no implica que cada `.deb` se verifique por una firma individual. [Referencia apt-secure](https://manpages.debian.org/bookworm/apt/apt-secure.8.en.html). W4 puede añadir procedencia de build, pero no sustituye esta verificación por TLS o por confiar en el nombre del repositorio.

## 9. Ensayo de interrupción obligatorio

Preparar repositorio fixture firmado y paquete que registre su paso de configuración sin tocar datos externos. Instalar versión inicial, crear plan de upgrade y guardar hashes de configuración. Interrumpir worker en cuatro puntos: antes de apply, después de unpack, durante configure y después del efecto antes de registrar resultado.

El oracle es dpkg más los datos de fixture. Al volver W4, ningún caso se convierte en succeeded por defecto. El último puede quedar unknown hasta observar el resultado; el caso durante configure puede quedar partial. La prueba exige que repetir la clave devuelva la operación existente y que cualquier repair sea una nueva intención enlazada a la original.
