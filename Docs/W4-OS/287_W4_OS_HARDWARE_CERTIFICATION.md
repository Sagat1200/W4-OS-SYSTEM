# 287 · W4 OS — Hardware Certification

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Pruebas y calificación · **Responsabilidad propuesta:** Calidad y validación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Otorgar compatibilidad de hardware como declaración limitada a pruebas fechadas.

## Alcance, arquitectura y decisiones

Expediente por modelo/revisión con instalación, dispositivos, energía, actualización y recuperación. Diferenciar prueba interna de certificación externa.

## Componentes y flujo operativo

1. Recibir equipo
2. registrar configuración
3. ejecutar matriz
4. revisar límites
5. aprobar etiqueta
6. repetir ante cambios relevantes.

## Seguridad y riesgos

No usar logo de proveedor ni término certificado por tercero sin acuerdo. Revisiones distintas no heredan aprobación automáticamente.

## Criterios de aceptación

Aceptar expediente completo por equipo anunciado y mecanismo de retirar etiqueta ante regresión.

## Rendimiento y evidencia

Medir cobertura y caducidad.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [024 — Device Compatibility Model](024_W4_OS_DEVICE_COMPATIBILITY_MODEL.md)
- [279 — Hardware Testing](279_W4_OS_HARDWARE_TESTING.md)
- [339 — Hardware Vendor Program](339_W4_OS_HARDWARE_VENDOR_PROGRAM.md)

## Roadmap y condiciones de evolución

Programa interno pequeño V1; convenios OEM después.

## Validez del expediente de hardware

La ficha pública identifica revisión del equipo y firmware probado, además de versión de W4. Un cambio de tarjeta Wi-Fi dentro del mismo nombre comercial puede invalidar conectividad certificada; el proveedor debe notificarlo y repetirse la prueba afectada. Se enumeran funciones no probadas como lector biométrico, hibernación o dock, evitando que una etiqueta general las implique. Ante una regresión del kernel se conserva evidencia anterior, se limita temporalmente la afirmación de soporte y se prueba una corrección antes de restaurar el estado.

---

[Anterior](286_W4_OS_AUTOMATED_QA.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](288_W4_OS_COMPATIBILITY_CERTIFICATION.md)
