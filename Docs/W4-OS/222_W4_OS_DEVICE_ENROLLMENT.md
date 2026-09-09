# 222 · W4 OS — Device Enrollment

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Dar a cada equipo una identidad revocable mediante inscripción autorizada.

## Alcance, arquitectura y decisiones

Token de inscripción de un uso, alcance organizacional y caducidad; el dispositivo genera clave única y obtiene certificado o credencial equivalente. No clonar identidad OEM.

## Componentes y flujo operativo

1. Validar token
2. crear clave
3. registrar equipo
4. emitir credencial
5. descargar política inicial
6. consumir token
7. comprobar vinculación.

## Seguridad y riesgos

Evitar replay y registro en organización equivocada. La retirada revoca identidad y elimina secretos corporativos según política, sin borrar datos por inferencia.

## Criterios de aceptación

Aceptar token reutilizado rechazado, doble solicitud idempotente y dos equipos con claves distintas.

## Rendimiento y evidencia

Medir tiempo de alta.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [039 — OEM Installation Mode](039_W4_OS_OEM_INSTALLATION_MODE.md)
- [212 — Enterprise Device Management](212_W4_OS_ENTERPRISE_DEVICE_MANAGEMENT.md)
- [220 — Enterprise Certificates](220_W4_OS_ENTERPRISE_CERTIFICATES.md)

## Roadmap y condiciones de evolución

Flujo fundamental del piloto Business antes de acciones remotas.

## Ciclo de credencial de dispositivo

| Etapa | Material permitido | Regla |
|---|---|---|
| Imagen de fábrica | Configuración pública del producto | Sin certificado compartido de dispositivo |
| Inicio de inscripción | Token efímero y acotado | Un uso y organización definida |
| Identidad activa | Clave propia y credencial emitida | Protección local y rotación |
| Renovación | Prueba de identidad vigente | No depende de contraseña de administrador |
| Retirada | Registro de revocación | La credencial vieja deja de autorizar |
| Reinscripción | Nueva operación autorizada | No reutilizar identidad por nombre del equipo |

Si el cliente pierde respuesta después de consumir el token, el protocolo debe reconciliar mediante identificador de solicitud y prueba de posesión de su clave; no emite identidades ilimitadas al repetir. Si la organización de destino no coincide con la intención mostrada, se aborta y se informa sin aceptar políticas.

La prueba de clonación instala dos equipos desde el mismo artefacto y verifica claves, identificadores y credenciales diferentes. Cambiar hostname no debe crear un activo nuevo ni permitir apropiarse de otro. La retirada no es una orden implícita de borrado remoto: la política de datos se ejecuta por una acción separada con autoridad específica.

---

[Anterior](221_W4_OS_FLEET_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](223_W4_OS_REMOTE_CONFIGURATION.md)
