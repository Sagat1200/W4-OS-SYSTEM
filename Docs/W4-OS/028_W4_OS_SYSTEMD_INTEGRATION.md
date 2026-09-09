# 028 · W4 OS — systemd Integration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Arranque y servicios · **Responsabilidad propuesta:** Integración de arranque  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Integrar servicios W4 sin modificar unidades upstream directamente.

## Alcance, arquitectura y decisiones

Utilizar unidades propias y drop-ins empaquetados; definir usuario, directorios escribibles, capacidades y comportamiento de reinicio por servicio.

## Componentes y flujo operativo

Validar sintaxis, recargar configuración, probar inicio y fallo, recoger journal y retirar override si el paquete se elimina.

## Seguridad y riesgos

Un endurecimiento excesivo puede impedir funciones legítimas; probar cada restricción y prohibir secretos en variables visibles o argumentos de procesos.

## Criterios de aceptación

Aceptar actualización y eliminación del paquete sin residuos de override; comparar memoria ociosa y límites aplicados al proceso.

## Rendimiento y evidencia

Registrar memoria ociosa, reinicios del servicio y tiempo de parada; comparar cada restricción de sandbox con las funciones que el servicio necesita.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [027 — Init System](027_W4_OS_INIT_SYSTEM.md)
- [156 — Security Architecture](156_W4_OS_SECURITY_ARCHITECTURE.md)
- [202 — System Logging](202_W4_OS_SYSTEM_LOGGING.md)

## Roadmap y condiciones de evolución

Publicar plantilla de servicio y adoptar activación bajo demanda cuando sea viable.

---

[Anterior](027_W4_OS_INIT_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](029_W4_OS_STARTUP_PIPELINE.md)
