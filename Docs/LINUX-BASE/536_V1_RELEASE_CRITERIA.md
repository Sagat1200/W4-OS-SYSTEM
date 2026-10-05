# W4 Linux Base

## 536 — V1 Release Criteria

**Documento:** `536_V1_RELEASE_CRITERIA.md`  
**Bloque:** XLVIII — Roadmap  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Cierre V1 exige los veinte criterios de 01 y tres pruebas arquitectónicas/producto/desacoplamiento.

Responsable: **Dirección técnica y responsables de release**. Dependencias de implementación: Criterios V1, pruebas y ADRs. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Gate registra instalación, boot, API, CLI, dominios, seguridad, recuperación, perfiles y repo.

Modelo del dominio: milestone, deliverable, dependency, acceptance, risk, owner

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Priorizar P0/P1, implementar contratos verticales, demostrar perfiles y estabilizar antes de ampliar independencia.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

Hitos condicionados a evidencia, sin fechas ficticias; funciones futuras requieren ADR y presupuesto de mantenimiento.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

No declarar terminado V1 por contar documentos; exige software instalable y pruebas reproducibles de los veinte criterios de 01.

**Caso de fallo específico:** Ningún documento ni mock sustituye evidencia end-to-end.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Construir tres perfiles desde misma base y ejecutar matriz de aceptación reproducible.

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
- [534_V1_IMPLEMENTATION_ROADMAP.md](534_V1_IMPLEMENTATION_ROADMAP.md)
- [535_V1_MILESTONES.md](535_V1_MILESTONES.md)
- [537_V2_SCOPE_PREVIEW.md](537_V2_SCOPE_PREVIEW.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Trazabilidad de los veinte criterios obligatorios de 01

| ID | Criterio | Evidencia para aprobar |
|---|---|---|
| V1-01 | Instalación | Instalación limpia desde medio y reporte sin fallos esenciales |
| V1-02 | Arranque | Boot desde disco sin medio de instalación, acceso operativo |
| V1-03 | Core | Inicio, readiness, reinicio y reconciliación comprobados |
| V1-04 | API | Vectores v1, errores, permisos y reconexión aprobados |
| V1-05 | w4ctl | Comandos fundamentales, JSON y no-interaction funcionales |
| V1-06 | Hardware | Inventario contrastado con fuentes y hardware declarado |
| V1-07 | Red | Consulta/configuración básicas, DNS y salvaguarda probadas |
| V1-08 | Storage | Inventario y capacidad correctos, protección de targets |
| V1-09 | Paquetes | Search/info/install/remove/upgrade/list con APT/dpkg reales |
| V1-10 | Updates | Check/plan/install/history/verify y reboot_required |
| V1-11 | Servicios | List/status/start/stop/restart/enable/disable contra systemd |
| V1-12 | Autorización | Matriz positiva/negativa y cero efectos al denegar |
| V1-13 | Logs y diagnóstico | Correlación de operación y bundle redactado útil |
| V1-14 | Telemetría local | Métricas tipadas, gaps y salud con cobertura |
| V1-15 | Recovery básico | Configuración, paquetes y boot diagnosticables/recuperables según escenarios |
| V1-16 | Tres perfiles | Home, Business y Server instalables con manifiestos |
| V1-17 | Base compartida | Mismos digests Core/API/providers en los tres productos |
| V1-18 | Repositorio W4 | Cliente limpio verifica índices e instala paquetes propios |
| V1-19 | Actualización por paquetes | Upgrade W4 conserva configuración y compatibilidad |
| V1-20 | Pruebas críticas automatizadas | Pipeline reproducible con reportes y artefactos |

Las pruebas se ejecutan en AMD64, arquitectura obligatoria V1. Cada release fija suite Debian y versiones; ARM64 no bloquea cierre. UEFI/BIOS y Secure Boot se declaran en matriz con resultados reales: no se anuncia soporte que no se haya probado. No se desactivan protecciones innecesariamente para lograr un PASS.

## 9. Tres demostraciones de arquitectura

**Misma operación, distintos clientes.** GUI, w4ctl y Agent envían una misma intención autorizada a la API sobre estados iniciales equivalentes. Los planes y efectos coinciden; diferencias de actor quedan en audit. Ningún cliente contiene una secuencia alternativa de comandos Debian para conseguirlo.

**Misma base, tres productos.** Se construyen Home, Business y Server desde el mismo conjunto de paquetes de plataforma, aplicando perfiles. Las diferencias se explican por manifest, sin forks ocultos de Core o providers. Server arranca y se administra sin desktop.

**Desacoplamiento comprobado.** Un provider de prueba compatible puede sustituirse en laboratorio sin cambiar clientes. La API sigue exponiendo mismos tipos y errores. Esta prueba no certifica un provider nuevo para producción, pero demuestra dirección de dependencias.

## 10. Decisión de release

El responsable de release adjunta manifest inmutable, matriz, resultados críticos, límites conocidos y plan de soporte. Un criterio esencial fallido bloquea V1. Funciones futuras como sistema inmutable, rollback integral, generación de drivers o fleet orchestration no se añaden como requisitos de cierre. No hay sustitución de pruebas por número de documentos ni por screenshots aislados.
