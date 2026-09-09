# 167 · W4 OS — Application Trust Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Distinguir autenticidad de origen, revisión de software y autorización de ejecución.

## Alcance, arquitectura y decisiones

Niveles de confianza se basan en origen verificado, mantenimiento, pruebas y permisos. Firma válida acredita procedencia del firmante, no ausencia de comportamiento dañino.

## Componentes y flujo operativo

1. Registrar aplicación
2. evaluar origen y soporte
3. asignar nivel
4. presentar al usuario
5. revisar si cambia propietario o permisos.

## Seguridad y riesgos

No transferir confianza de un catálogo a binarios con mismo nombre. Cambios de editor o capacidades sensibles requieren reevaluación.

## Criterios de aceptación

Aceptar suplantación de identificador detectada y aumento de permisos visible antes de aplicar.

## Rendimiento y evidencia

Medir apps sin evaluación vigente.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [126 — Third Party Application Support](126_W4_OS_THIRD_PARTY_APPLICATION_SUPPORT.md)
- [128 — Application Repository](128_W4_OS_APPLICATION_REPOSITORY.md)
- [168 — Code Signing Model](168_W4_OS_CODE_SIGNING_MODEL.md)

## Roadmap y condiciones de evolución

Catálogo V1 con niveles simples y criterios públicos.

---

[Anterior](166_W4_OS_MALWARE_PROTECTION_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](168_W4_OS_CODE_SIGNING_MODEL.md)
