# W4 Linux Base

## 02 — Linux Base Architecture

**Documento:** `02_LINUX_BASE_ARCHITECTURE.md`  
**Bloque:** I — Contexto y arquitectura  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Separar política y coordinación W4 de mecanismos Debian; los clientes sólo consumen contratos públicos.

Responsable: **Equipo de arquitectura W4**. Dependencias de implementación: Debian estándar, Linux y contratos W4. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Cada capacidad identifica servicio, provider, permiso y estado de soporte; el grafo de runtime no exige GUI ni Agent.

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

**Caso de fallo específico:** La caída del API no debe impedir boot ni herramientas Debian de rescate.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Detener W4 en una VM: Debian permanece administrable; reiniciar W4 reconcilia cambios externos.

La evidencia debe incluir versión Debian, arquitectura, perfil, versiones del backend, entradas saneadas, estado inicial, resultado observado y estado final. La prueba debe verificar efectos en el backend y no sólo el texto presentado por `w4ctl`. Si la función no está soportada, la prueba exige una respuesta `NOT_SUPPORTED` y ausencia de cambios parciales; no se considera éxito funcional.

Para cerrar el documento en una release se vinculan requisito, implementación y evidencia en el reporte de pruebas. Esta entrega documental define esos criterios; no afirma que las pruebas del futuro sistema operativo ya se hayan ejecutado.

## 7. Referencias y navegación

- [00_PROJECT_CONTEXT.md](00_PROJECT_CONTEXT.md)
- [01_V1_SCOPE_AND_OBJECTIVES.md](01_V1_SCOPE_AND_OBJECTIVES.md)
- [03_SYSTEM_COMPONENTS.md](03_SYSTEM_COMPONENTS.md)
- [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md)
- [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md)
- [50_PROVIDER_CONTRACT.md](50_PROVIDER_CONTRACT.md)
- [61_CONFIGURATION_ARCHITECTURE.md](61_CONFIGURATION_ARCHITECTURE.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Topología de referencia V1

```text
W4 OS Home / Business / Server
    GUI   w4ctl   Installer   Recovery   W4 Agent
                    │
            W4 System API v1 (system bus)
                    │
       w4-system-service: validación + autorización
                    │
       Core: contexto / operaciones / configuración
                    │
       Servicios de dominio y contratos Provider
                    │
       Ejecutores mínimos aislados cuando corresponda
                    │
 apt/dpkg  systemd  NetworkManager  udev  PAM  nftables
                    │
                 Debian / Linux
```

`w4-system-service` es el nombre propuesto para el coordinador de esta edición; los nombres de daemons enumerados en 00 son conceptuales. No se crea un proceso por cada uno. El coordinador no ejecuta trabajos bloqueantes en el hilo de recepción. Se extrae un worker cuando hay una frontera real de privilegio, bloqueo o fallo; el aislamiento debe ser observable y testeable.

| Componente | Autoridad | Datos propios | Puede fallar sin detener Debian |
|---|---|---|---|
| System API | Contrato y admisión | Sesiones y cuotas efímeras | Sí |
| Core | Seguimiento, contexto y configuración W4 | Journal de operaciones y revisiones | Sí |
| Package/Update Services | Plan y política local | Historial y planes | Sí; una operación activa exige reconciliación |
| Providers | Traducción del mecanismo | Caché y referencias de backend | Sí |
| Debian | Estado de paquetes, unidades, red y kernel | Almacenes nativos | Es la base operativa |
| Agent | Identidad remota y transporte | Credencial, cola y deduplicación | Sí |
| Enterprise Base/Platform | Política y coordinación empresarial | Estado central de flota | Sí; no es dependencia del boot local |

Los dominios pueden compartir proceso y bibliotecas sin compartir autoridad indiscriminadamente. La política de red no llama directamente a AptProvider para instalar plugins; solicita Package Service con contexto y permisos independientes. Package Service no modifica políticas de red para conseguir descargas. La falta de una dependencia produce diagnóstico y plan, no una cadena recursiva de cambios no autorizados.

## 9. Fronteras de persistencia

El estado instalado de paquetes pertenece a dpkg; el estado de unidades a systemd; los perfiles administrados por NetworkManager a ese backend. La base W4 registra intención, política, referencias y observación. No mantiene una copia supuestamente autoritativa de estos sistemas.

Se propone SQLite para operaciones y revisiones W4 locales, con esquema versionado, transacciones y durabilidad configurada de forma explícita. La selección es una decisión de esta edición que se valida con pruebas de interrupción y almacenamiento lleno; ningún cliente accede directamente a esa base. No se depende de una base distribuida para V1. La aceptación de una mutación se persiste antes de invocar al provider; si no puede persistirse, la solicitud se rechaza.

Un crash entre efecto externo y registro final siempre puede producir resultado incierto. Al reiniciar se busca el trabajo del backend o se consulta su estado; si no existe evidencia suficiente, se muestra `unknown` y se requiere resolución asistida. No se promete exactamente una ejecución de efectos externos ante cualquier fallo.
