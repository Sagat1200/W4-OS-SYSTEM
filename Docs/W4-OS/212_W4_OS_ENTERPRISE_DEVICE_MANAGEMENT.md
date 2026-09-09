# 212 · W4 OS — Enterprise Device Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Gestionar estado deseado de dispositivos sin depender de comandos remotos arbitrarios.

## Alcance, arquitectura y decisiones

Agente consulta tareas tipadas y políticas versionadas; servidor registra intención y resultado, no presume éxito al enviar. Cada equipo usa credencial única revocable.

## Componentes y flujo operativo

1. Recibir tarea
2. validar firma/identidad/versión
3. comprobar precondiciones
4. ejecutar backend
5. reportar resultado idempotente.

## Seguridad y riesgos

No habilitar un endpoint de shell root general para simplificar gestión. Restringir acciones por rol, organización y recurso.

## Criterios de aceptación

Aceptar duplicación, mensaje atrasado y dispositivo sin red sin ejecutar orden dos veces.

## Rendimiento y evidencia

Medir latencia y backlog.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [214 — Configuration Policy Engine](214_W4_OS_CONFIGURATION_POLICY_ENGINE.md)
- [222 — Device Enrollment](222_W4_OS_DEVICE_ENROLLMENT.md)
- [370 — Device Management API](370_W4_OS_DEVICE_MANAGEMENT_API.md)

## Roadmap y condiciones de evolución

Inventario y políticas básicas en piloto; acciones nuevas con prueba de abuso.

---

[Anterior](211_W4_OS_BUSINESS_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](213_W4_OS_CENTRAL_POLICY_SYSTEM.md)
