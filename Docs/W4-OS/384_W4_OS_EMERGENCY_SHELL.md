# 384 · W4 OS — Emergency Shell

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Rescate y fallos · **Responsabilidad propuesta:** Recuperación de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Proporcionar consola de emergencia para administración autorizada con límites claros.

## Alcance, arquitectura y decisiones

Usar mecanismo mantenido del entorno de rescate; acceso a raíz cifrada exige credencial. Herramientas y montajes se explican antes de modificar.

## Componentes y flujo operativo

1. Arrancar rescate
2. autenticar/desbloquear
3. identificar raíz correcta
4. inspeccionar
5. aplicar reparación específica
6. desmontar
7. reiniciar.

## Seguridad y riesgos

No publicar contraseña maestra ni abrir shell root por caída de un servicio. Consola física y Secure Boot no sustituyen cifrado de datos.

## Criterios de aceptación

Aceptar diagnóstico de raíz y reparación autorizada sin acceso con clave incorrecta.

## Rendimiento y evidencia

Medir tiempo hasta consola y herramientas faltantes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [087 — Recovery Environment](087_W4_OS_RECOVERY_ENVIRONMENT.md)
- [160 — Disk Encryption](160_W4_OS_DISK_ENCRYPTION.md)
- [383 — Safe Mode](383_W4_OS_SAFE_MODE.md)

## Roadmap y condiciones de evolución

Runbook V1; ejemplos destructivos sólo sobre destinos identificados de laboratorio.

---

[Anterior](383_W4_OS_SAFE_MODE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](385_W4_OS_BOOT_FAILURE_RECOVERY.md)
