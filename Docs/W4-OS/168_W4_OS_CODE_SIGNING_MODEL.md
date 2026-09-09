# 168 · W4 OS — Code Signing Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Separar firmas de repositorio, artefacto, arranque y procedencia por propósito.

## Alcance, arquitectura y decisiones

Cada dominio tiene claves, formato de firma, consumidores y rotación propios. No reutilizar clave de paquetes para identidad de dispositivos o acceso remoto.

## Componentes y flujo operativo

1. Generar clave bajo custodia
2. distribuir confianza
3. firmar objeto tipado
4. verificar antes de uso
5. rotar o revocar mediante procedimiento probado.

## Seguridad y riesgos

La clave privada no entra en fuentes ni workers. Compromiso de una clave activa un plan de invalidación y publicación de reemplazo.

## Criterios de aceptación

Aceptar firma con clave equivocada o contenido alterado rechazada.

## Rendimiento y evidencia

Medir inventario de claves, vencimientos y ejercicio de rotación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [047 — Package Signing System](047_W4_OS_PACKAGE_SIGNING_SYSTEM.md)
- [161 — Secure Boot](161_W4_OS_SECURE_BOOT.md)
- [169 — Supply Chain Security](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
- [272 — Release Signing](272_W4_OS_RELEASE_SIGNING.md)

## Roadmap y condiciones de evolución

Custodia definida antes de distribución; automatización con separación de funciones.

---

[Anterior](167_W4_OS_APPLICATION_TRUST_MODEL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
