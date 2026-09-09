# 312 · W4 OS — Third Party License Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Licencias y distribución · **Responsabilidad propuesta:** Cumplimiento de distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Controlar licencias de terceros incluyendo componentes no libres y recursos multimedia.

## Alcance, arquitectura y decisiones

Ficha por proveedor/versión con permiso de redistribución, territorio cuando aplique, avisos, restricciones y fecha de revisión. Excluir del medio lo no autorizado y ofrecer ruta externa legítima.

## Componentes y flujo operativo

1. Evaluar componente
2. registrar evidencia
3. aprobar inclusión o enlace
4. comprobar artefacto
5. revisar al actualizar.

## Seguridad y riesgos

No incluir codecs, fuentes o drivers por disponibilidad técnica sin derechos. Un contrato expirado puede impedir nuevas distribuciones.

## Criterios de aceptación

Aceptar imagen sin recursos de autorización desconocida y avisos correctos.

## Rendimiento y evidencia

Medir vencimientos y dependencias comerciales.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [020 — Firmware Management](020_W4_OS_FIRMWARE_MANAGEMENT.md)
- [137 — Media Codec Strategy](137_W4_OS_MEDIA_CODEC_STRATEGY.md)
- [309 — Licensing Strategy](309_W4_OS_LICENSING_STRATEGY.md)

## Roadmap y condiciones de evolución

Catálogo antes de V1; revisión por versión y mercado de venta.

---

[Anterior](311_W4_OS_GPL_COMPLIANCE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](313_W4_OS_SOURCE_CODE_PUBLICATION_POLICY.md)
