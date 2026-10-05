# W4 Linux Base

## 368 — W4 Agent Integration Architecture

**Documento:** `368_W4_AGENT_INTEGRATION_ARCHITECTURE.md`  
**Bloque:** XXXI — W4 Agent  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Integración V1: el contrato local es obligatorio; capacidades avanzadas dependen del componente externo y de su matriz de soporte.

## 1. Decisión y responsabilidad

Agent es puente remoto y cliente local, no segundo administrador de Debian.

Responsable: **Equipo Agent y servicios locales**. Dependencias de implementación: API W4, canal autenticado y almacenamiento de identidad. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Capabilities remotas se traducen a métodos API y permisos de identidad de servicio.

Modelo del dominio: device_id, command_id, expiry, capability, operation_id, acknowledgement

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Validar canal y comando, persistir deduplicación, llamar API como identidad Agent y reportar resultado observado.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

Inscripción explícita; credenciales por dispositivo; cola con límites y capacidades remotas desactivadas si no hay política.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

Sin shell remoto genérico; órdenes vencidas, de otro tenant o repetidas no generan nuevos efectos.

**Caso de fallo específico:** Agent caído no bloquea administración local.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Detener Agent y verificar CLI, GUI y recuperación operativas.

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
- [367_PRODUCT_COMPATIBILITY.md](367_PRODUCT_COMPATIBILITY.md)
- [369_AGENT_SYSTEM_API_INTEGRATION.md](369_AGENT_SYSTEM_API_INTEGRATION.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Protocolo lógico de orden remota

```json
{
  "protocol_version": 1,
  "command_id": "cmd-924b5c",
  "device_id": "dev-71deda",
  "tenant_id": "tenant-lab",
  "issued_at": "2026-09-29T15:00:00Z",
  "expires_at": "2026-09-29T15:05:00Z",
  "policy_revision": "policy-42",
  "action": "services.restart",
  "params": {"unit": "ssh.service"}
}
```

Ejemplo de estructura, no orden emitida. El canal autentica servidor/dispositivo; el envelope se verifica según protocolo empresarial elegido. No se diseña criptografía propia. El Agent comprueba destinatario, tenant, vigencia, schema, política y clave de deduplicación antes de solicitar autorización local. Si el reloj no es confiable, órdenes con expiración no se aceptan ciegamente; se diagnostica el problema y se conserva operación local.

La identidad Enterprise se conserva como delegación verificable. La API local autentica al proceso Agent mediante su credencial de servicio; no acepta que cualquier cliente añada `tenant_id` para convertirse en Agent. Sólo un canal interno/identidad de servicio autorizada puede adjuntar el contexto delegado.

La recepción genera ACK de admisión remota, luego operation_id local y finalmente resultado terminal. Estos tres estados no se confunden. Antes de invocar API se persiste command_id y digest; tras crash se consulta la operación local por clave estable. Si el resultado sigue unknown, se reporta incertidumbre sin ejecutar otra intención.

El host continúa operativo cuando Enterprise está desconectado. Se conserva última política válida con conducta de expiración definida: restricciones de seguridad no se convierten en permisos adicionales; nuevas mutaciones remotas se bloquean si no puede verificarse autoridad vigente. Cola y telemetría tienen cuotas separadas; comandos expirados se descartan con evento, no se ejecutan al reconectar.
