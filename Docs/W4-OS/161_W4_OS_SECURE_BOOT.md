# 161 · W4 OS — Secure Boot

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener una cadena de arranque verificada sin afirmar firma propia disponible.

## Alcance, arquitectura y decisiones

Propuesta amd64 reutiliza ruta Debian admitida por firmware cuando sea compatible. Cualquier binario W4 o módulo externo exige estrategia de firma, custodia y reconocimiento explícita.

## Componentes y flujo operativo

1. Verificar estado firmware
2. validar cargador/kernel/módulos
3. arrancar
4. reportar cadena efectiva; cambios se ensayan en equipo certificado.

## Seguridad y riesgos

No desactivar Secure Boot silenciosamente ni presentar checksum como firma de arranque. Un firmware con claves alteradas cambia el modelo de confianza.

## Criterios de aceptación

Aceptar binario no autorizado rechazado y actualización válida que sigue arrancando.

## Rendimiento y evidencia

Medir cobertura por hardware y módulos externos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [025 — Boot Architecture](025_W4_OS_BOOT_ARCHITECTURE.md)
- [026 — Bootloader Strategy](026_W4_OS_BOOTLOADER_STRATEGY.md)
- [168 — Code Signing Model](168_W4_OS_CODE_SIGNING_MODEL.md)

## Roadmap y condiciones de evolución

Prueba técnica previa a V1; firma propia sólo con proceso operativo aprobado.

---

[Anterior](160_W4_OS_DISK_ENCRYPTION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](162_W4_OS_TPM_INTEGRATION.md)
