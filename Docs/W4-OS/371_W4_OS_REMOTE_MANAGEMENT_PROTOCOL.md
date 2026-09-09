# 371 · W4 OS — Remote Management Protocol

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** APIs y protocolos · **Responsabilidad propuesta:** Arquitectura de servicios  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Transportar gestión remota con identidad, replay controlado y resultados verificables.

## Alcance, arquitectura y decisiones

Propuesta de conexiones salientes autenticadas con credencial por dispositivo; mensajes contienen versión, operation_id, expiración, alcance y precondición. No requerir puerto entrante universal.

## Componentes y flujo operativo

1. Conectar
2. verificar servidor
3. recibir tarea
4. validar vigencia y permiso
5. ejecutar una vez lógicamente
6. reportar
7. renovar credencial.

## Seguridad y riesgos

Rechazar tareas antiguas, de otra organización o firmadas por autoridad desconocida. TLS no reemplaza autorización de cada operación.

## Criterios de aceptación

Aceptar desconexión, reenvío y reloj desviado con política documentada.

## Rendimiento y evidencia

Medir reintentos y latencia hasta convergencia.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [220 — Enterprise Certificates](220_W4_OS_ENTERPRISE_CERTIFICATES.md)
- [223 — Remote Configuration](223_W4_OS_REMOTE_CONFIGURATION.md)
- [224 — Remote Update Management](224_W4_OS_REMOTE_UPDATE_MANAGEMENT.md)
- [370 — Device Management API](370_W4_OS_DEVICE_MANAGEMENT_API.md)

## Roadmap y condiciones de evolución

Protocolo mínimo piloto; tolerancia y escala antes de producción.

---

[Anterior](370_W4_OS_DEVICE_MANAGEMENT_API.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](372_W4_OS_EXTENSION_ARCHITECTURE.md)
