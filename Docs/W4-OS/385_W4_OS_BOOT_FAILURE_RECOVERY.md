# 385 · W4 OS — Boot Failure Recovery

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Rescate y fallos · **Responsabilidad propuesta:** Recuperación de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Detectar fallos de arranque y evitar bucles sobre el mismo candidato.

## Alcance, arquitectura y decisiones

Registro de intentos y estado bueno vinculado a generación; mecanismo exacto depende del cargador elegido y debe probar persistencia con cortes de energía.

## Componentes y flujo operativo

1. Intentar candidato
2. comprobar hito de salud
3. contar fallo confirmado según política
4. ofrecer anterior
5. registrar incidente
6. impedir loop.

## Seguridad y riesgos

No interpretar apagado voluntario como corrupción automáticamente. Un contador no autoriza volver a versión incompatible con datos persistentes.

## Criterios de aceptación

Aceptar candidato que falla repetidamente con recuperación accesible y sin bucle infinito.

## Rendimiento y evidencia

Medir número de intentos y tiempo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [018 — Kernel Update Policy](018_W4_OS_KERNEL_UPDATE_POLICY.md)
- [025 — Boot Architecture](025_W4_OS_BOOT_ARCHITECTURE.md)
- [076 — Update Health Check](076_W4_OS_UPDATE_HEALTH_CHECK.md)
- [088 — Boot Recovery](088_W4_OS_BOOT_RECOVERY.md)

## Roadmap y condiciones de evolución

Recuperación manual V1; fallback automático sólo tras validar contadores y datos.

---

[Anterior](384_W4_OS_EMERGENCY_SHELL.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](386_W4_OS_FILESYSTEM_RECOVERY.md)
