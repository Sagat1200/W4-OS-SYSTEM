# 277 · W4 OS — Integration Testing

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Pruebas y calificación · **Responsabilidad propuesta:** Calidad y validación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Verificar que componentes acuerdan esquemas, permisos y estados de error.

## Alcance, arquitectura y decisiones

Probar UI/backend, agente/política, APT/coordinador y almacenamiento/snapshots con servicios reales de laboratorio cuando sea posible.

## Componentes y flujo operativo

1. Arrancar dependencias
2. enviar petición
3. observar efecto
4. inyectar timeout o respuesta inválida
5. comprobar reconciliación.

## Seguridad y riesgos

No sustituir todas las dependencias por mocks que oculten permisos del sistema. Aislar credenciales de prueba y redes.

## Criterios de aceptación

Aceptar versión de API incompatible y pérdida de conexión sin efecto duplicado.

## Rendimiento y evidencia

Medir fallos por interfaz.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [214 — Configuration Policy Engine](214_W4_OS_CONFIGURATION_POLICY_ENGINE.md)
- [366 — API Architecture](366_W4_OS_API_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Contratos mínimos antes de unir imagen completa.

## Qué debe observar un test de integración

Un test de política no termina al recibir respuesta de éxito: consulta el valor efectivo en el servicio responsable. Un test de actualización comprueba estado durable y paquetes, no sólo que la UI cerró el diálogo. Un test de permisos usa un sujeto sin autoridad real para verificar el rechazo. Se guardan logs correlacionados de ambos lados de la interfaz para distinguir defecto del cliente, contrato o backend. Las pruebas de timeout verifican si hubo efecto antes de decidir que el reintento es seguro.

---

[Anterior](276_W4_OS_UNIT_TESTING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](278_W4_OS_SYSTEM_TESTING.md)
