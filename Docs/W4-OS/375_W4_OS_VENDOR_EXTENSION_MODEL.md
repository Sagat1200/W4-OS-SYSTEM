# 375 · W4 OS — Vendor Extension Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Extensibilidad · **Responsabilidad propuesta:** Integración de extensiones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Admitir extensiones de proveedores sin transferirles control ilimitado del sistema.

## Alcance, arquitectura y decisiones

Integración por paquete o API pública, con licencia, soporte y compatibilidad por versión. Drivers privilegiados siguen revisión de kernel; recursos visuales no requieren privilegios.

## Componentes y flujo operativo

1. Proveedor entrega
2. revisar
3. construir/verificar
4. probar
5. publicar con origen
6. mantener
7. retirar si termina soporte.

## Seguridad y riesgos

No aceptar instaladores opacos que cambien repositorios, cuentas o telemetría. Claves de proveedor no reciben confianza global automática.

## Criterios de aceptación

Aceptar actualización W4 con extensión y desactivación segura cuando es incompatible.

## Rendimiento y evidencia

Medir dependencia y tiempo de respuesta del proveedor.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [021 — Driver Architecture](021_W4_OS_DRIVER_ARCHITECTURE.md)
- [126 — Third Party Application Support](126_W4_OS_THIRD_PARTY_APPLICATION_SUPPORT.md)
- [372 — Extension Architecture](372_W4_OS_EXTENSION_ARCHITECTURE.md)
- [339 — Hardware Vendor Program](339_W4_OS_HARDWARE_VENDOR_PROGRAM.md)

## Roadmap y condiciones de evolución

Acuerdos limitados después de V1; certificación por extensión.

---

[Anterior](374_W4_OS_SYSTEM_MODULES.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](376_W4_OS_OEM_EXTENSION_MODEL.md)
