# 189 · W4 OS — USB Storage Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Almacenamiento y backup · **Responsabilidad propuesta:** Protección y recuperación de datos  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Aplicar restricciones a almacenamiento USB sin bloquear indiscriminadamente periféricos.

## Alcance, arquitectura y decisiones

Política diferencia almacenamiento de entrada, audio y otros usos; permitir, sólo lectura o bloquear por alcance. Identificadores de hardware no se consideran prueba fuerte de identidad.

## Componentes y flujo operativo

1. Detectar clase
2. consultar política
3. aplicar permisos/montaje
4. notificar razón
5. auditar acción mínima.

## Seguridad y riesgos

No usar lista de seriales como única barrera contra suplantación. Mantener teclado y recuperación accesibles al restringir medios.

## Criterios de aceptación

Aceptar almacenamiento bloqueado y teclado funcional, además de excepción con caducidad.

## Rendimiento y evidencia

Medir falsos bloqueos y retraso de política.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [174 — Business Security Profile](174_W4_OS_BUSINESS_SECURITY_PROFILE.md)
- [187 — Storage Device Manager](187_W4_OS_STORAGE_DEVICE_MANAGER.md)
- [213 — Central Policy System](213_W4_OS_CENTRAL_POLICY_SYSTEM.md)

## Roadmap y condiciones de evolución

Controles Business piloto; ampliación después de pruebas de dispositivos compuestos.

---

[Anterior](188_W4_OS_EXTERNAL_STORAGE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](190_W4_OS_MOUNT_SYSTEM.md)
