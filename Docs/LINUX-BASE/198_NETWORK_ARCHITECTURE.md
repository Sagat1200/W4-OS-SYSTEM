# W4 Linux Base

## 198 — Network Architecture

**Documento:** `198_NETWORK_ARCHITECTURE.md`  
**Bloque:** XVII — Red  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Un solo backend administra cada interfaz; NetworkManager es referencia V1.

Responsable: **Network Service**. Dependencias de implementación: NetworkManager por D-Bus, kernel y nftables para firewall. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

API muestra ownership y capacidades antes de ofrecer modificaciones.

Modelo del dominio: interface_id, connection_uuid, addresses, routes, dns, connectivity

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Consultar propietario de interfaz, preparar perfil, validar, activar con salvaguarda y confirmar conectividad observada.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

NetworkManager es provider predeterminado donde sea propietario; interfaces externas se reportan como unmanaged.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

No administrar una interfaz simultáneamente con dos backends; proteger credenciales Wi-Fi/VPN y la ruta de administración remota.

**Caso de fallo específico:** Interfaces administradas por otra herramienta quedan unmanaged/read-only.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** VM con interfaz externa recibe inventario correcto y rechazo de cambio W4.

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
- [197_SERVICE_EVENTS.md](197_SERVICE_EVENTS.md)
- [199_NETWORK_DEVICE_MODEL.md](199_NETWORK_DEVICE_MODEL.md)
- [249_SECURITY_ARCHITECTURE.md](249_SECURITY_ARCHITECTURE.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Operación de red segura

El adapter detecta ownership por dispositivo. Si NetworkManager no lo administra, la consulta lo muestra, pero la mutación no toma control implícitamente. Migrar ownership entre NetworkManager y otro mecanismo necesita plan propio, revisión de archivos y canal de recuperación; no forma parte de una llamada ordinaria para cambiar IP.

```json
{
  "connection_uuid": "bf1efad1-2bdb-45b2-8a26-7af8280b6b05",
  "expected_revision": "net-12",
  "ipv4": {"method": "manual", "addresses": ["192.0.2.10/24"], "gateway": "192.0.2.1"},
  "dns": {"servers": ["192.0.2.53"]},
  "safeguard": {"confirmation_timeout_seconds": 90}
}
```

Las direcciones son de documentación y no una configuración recomendada para un equipo real. El servicio valida prefijos, duplicados y coherencia del perfil; consulta política sobre red de administración y usa checkpoints sólo cuando el backend los soporta. [API oficial NetworkManager](https://networkmanager.dev/docs/api/latest/gdbus-org.freedesktop.NetworkManager.html).

Secuencia: preparar perfil candidato → crear checkpoint aplicable → activar → observar IP/ruta/DNS → comprobar destino de control permitido → confirmar desde cliente autorizado → retirar salvaguarda. Si expira confirmación, ejecutar rollback cubierto por backend y volver a observar. Si no existe esa capacidad, una modificación remota riesgosa se rechaza salvo procedimiento con consola alternativa expresamente previsto.

Un checkpoint no cubre reglas nftables ajenas, servidores DNS externos ni todas las modificaciones simultáneas. El resultado debe declarar alcance. El servicio serializa cambios sobre misma conexión y no presenta rollback como éxito hasta verificar estado restaurado.

Diagnóstico devuelve pruebas independientes: carrier, address, route, dns y reachability. No requiere contacto con un servicio público fijo. Un sitio puede bloquear ICMP o un portal cautivo permitir DNS: ninguna prueba aislada sustituye todas las demás.
