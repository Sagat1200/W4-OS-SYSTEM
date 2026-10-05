# W4 Linux Base

## 350 — Product Profile Architecture

**Documento:** `350_PRODUCT_PROFILE_ARCHITECTURE.md`  
**Bloque:** XXIX — Perfiles  
**Estado:** Especificación de ingeniería, revisión 1.0; comportamiento requerido, no certificación de una implementación existente.  
**Alcance:** Especificación V1, limitada por el alcance del documento 01. Las capacidades opcionales se anuncian explícitamente y no se simulan.

## 1. Decisión y responsabilidad

Perfiles seleccionan experiencia y defaults sobre una sola plataforma base.

Responsable: **Equipo de productos y plataforma**. Dependencias de implementación: Paquetes de perfil y configuración W4. W4 Linux Base administra la máquina local; W4 Enterprise Base comparte capacidades empresariales y W4 Enterprise Platform coordina sistemas remotos mediante W4 Agent. Ningún contrato de este documento traslada el control local a una dependencia obligatoria de la nube.

## 2. Contrato específico

Contrato común evita forks de servicios por Home/Business/Server.

Modelo del dominio: profile_id, version, packages, services, features, defaults

Los nombres de campos aquí definidos son requisitos del modelo lógico. El transporte y la envoltura se rigen por [27_SYSTEM_API_CONTRACT.md](27_SYSTEM_API_CONTRACT.md). Ausencia de dato se expresa como `null` acompañada de causa; no se convierte en cero, éxito ni recurso vacío. Un campo agregado opcionalmente no cambia el significado de los existentes. Los ejemplos describen la interfaz que debe implementarse.

## 3. Flujo y dependencias

Validar manifiesto, resolver dependencias, aplicar paquetes y defaults y verificar contra el mismo contrato base.

El responsable valida primero las precondiciones específicas del contrato. Las consultas etiquetan fecha y origen de la observación; las mutaciones se autorizan antes de invocar el provider. El servicio conserva la correlación entre solicitud, efecto y resultado. Cuando el backend cambia fuera de W4 se vuelve a observar su estado: el registro W4 no reemplaza la autoridad de Debian sobre sus recursos.

Para artefactos de build, pruebas o documentación, este flujo representa una actividad de ingeniería y sus evidencias, no un nuevo método privilegiado del sistema. Sus cambios se revisan en el repositorio; sólo el software instalado ejecuta operaciones sobre el host.

## 4. Configuración y operación

home, business y server son perfiles versionados; un perfil principal por instalación en V1.

Una revisión de configuración se valida completa antes de activarse. Se registra su identificador sin copiar secretos a logs. Una opción desconocida se rechaza en los manifiestos W4; el adaptador conserva las opciones ajenas que no administra. Los valores predeterminados de esta edición son decisiones de diseño que deben probarse en la base Debian fijada en el manifiesto de release.

## 5. Seguridad y fallos

El perfil no puede habilitar una capacidad insegura ni rebajar la autorización del Core.

**Caso de fallo específico:** Perfil no cambia significado de action_id o error público.

Las operaciones devuelven errores estructurados de [32_SYSTEM_API_ERROR_MODEL.md](32_SYSTEM_API_ERROR_MODEL.md) con causa saneada y acción de recuperación. Un timeout del cliente no demuestra que el backend haya detenido su trabajo. No se repite una mutación de resultado incierto antes de reconciliar el estado real. Los datos que permitan identificar personas o equipos se filtran según [517_DATA_CLASSIFICATION.md](517_DATA_CLASSIFICATION.md).

## 6. Pruebas y aceptación

**Prueba de aceptación específica:** Ejecutar mismos vectores API en los tres perfiles y comparar semántica.

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
- [349_UNATTENDED_INSTALLATION.md](349_UNATTENDED_INSTALLATION.md)
- [351_PRODUCT_PROFILE_MODEL.md](351_PRODUCT_PROFILE_MODEL.md)
- [426_TESTING_ARCHITECTURE.md](426_TESTING_ARCHITECTURE.md)
- [Índice completo](../INDEX.md)
- [Fuentes técnicas y alcance de verificación](../REFERENCES.md)

## 8. Manifiesto de perfil de referencia

```json
{
  "schema_version": 1,
  "profile_id": "server",
  "profile_version": "1.0.0",
  "base_api_major": 1,
  "features": {
    "graphical_client": false,
    "agent_integration": true,
    "automatic_suspend": false
  },
  "configuration": {
    "telemetry.remote_enabled": false,
    "updates.automatic_reboot": false
  }
}
```

Este extracto define estructura y defaults; el manifiesto instalable de cada release además contiene listas resueltas de paquetes y unidades. No se inventa un desktop, runtime de contenedores o credencial de enrolamiento obligatorio para completar el ejemplo. Esa selección pertenece al producto y debe fijarse antes del build.

| Área | Home | Business | Server |
|---|---|---|---|
| API/Core/providers | Misma versión base | Misma versión base | Misma versión base |
| CLI | Incluida | Incluida | Incluida y suficiente sin GUI |
| Desktop | Requerido para experiencia Home | Según experiencia Business definida | Opcional |
| Agent | Disponible según selección | Integración incluida | Según administración del despliegue |
| Enrolamiento | Explícito | Explícito | Explícito |
| Telemetría externa | Desactivada sin política | Política empresarial explícita | Política explícita |
| Suspensión automática | Política de usuario/producto | Política administrada | Desactivada por defecto |
| Autorización privilegiada | Mismo baseline | Mismo baseline más restricciones | Mismo baseline |

El perfil determina selección y defaults, no soporte hardware ficticio. `graphical_client=true` no convierte GPU no soportada en funcional. `agent_integration=true` instala la integración, pero no la inscribe ni concede permiso para todas las acciones.

La comprobación de base compartida compara digests de paquetes Core/API/providers y schemas. Diferencias legítimas se limitan a paquetes de perfil/producto y configuración autorizada. Si un producto necesita modificar un servicio común, el cambio se integra en base o se expresa como capacidad compatible; no se copia el servicio al producto.
