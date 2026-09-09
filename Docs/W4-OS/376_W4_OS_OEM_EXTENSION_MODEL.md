# 376 · W4 OS — OEM Extension Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Extensibilidad · **Responsabilidad propuesta:** Integración de extensiones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Extender equipos OEM mediante perfiles que sobrevivan a actualizaciones comunes.

## Alcance, arquitectura y decisiones

Delta OEM incluye recursos, paquetes aprobados y configuración por modelo; no scripts de fábrica permanentes con privilegios abiertos. La base conserva misma línea de mantenimiento.

## Componentes y flujo operativo

1. Aplicar perfil
2. generalizar
3. probar hardware
4. fabricar
5. actualizar con release común
6. retirar recursos si cambia propiedad.

## Seguridad y riesgos

No incluir acceso remoto oculto ni certificados clonados. Extensiones deben declarar datos enviados al proveedor y autorización.

## Criterios de aceptación

Aceptar dos equipos únicos y update sin perder soporte de hardware.

## Rendimiento y evidencia

Medir delta y regresiones por perfil.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [039 — OEM Installation Mode](039_W4_OS_OEM_INSTALLATION_MODE.md)
- [338 — OEM Partnership Model](338_W4_OS_OEM_PARTNERSHIP_MODEL.md)
- [350 — OEM Image Strategy](350_W4_OS_OEM_IMAGE_STRATEGY.md)
- [375 — Vendor Extension Model](375_W4_OS_VENDOR_EXTENSION_MODEL.md)

## Roadmap y condiciones de evolución

Perfil piloto después de calificación de hardware y revisión de licencias.

---

[Anterior](375_W4_OS_VENDOR_EXTENSION_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](377_W4_OS_CONFIGURATION_ARCHITECTURE.md)
