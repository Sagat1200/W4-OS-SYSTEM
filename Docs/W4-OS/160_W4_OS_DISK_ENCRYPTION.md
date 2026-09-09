# 160 · W4 OS — Disk Encryption

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Proteger datos en reposo con límites y recuperación explícitos.

## Alcance, arquitectura y decisiones

LUKS2 propuesto para instalación cifrada; claves separadas de datos y respaldo del encabezado protegido cuando corresponda. Cifrado no protege una sesión ya desbloqueada frente a procesos autorizados.

## Componentes y flujo operativo

1. Desbloquear
2. montar
3. operar
4. cerrar; rotar credencial mediante procedimiento que conserva al menos una ruta comprobada de acceso.

## Seguridad y riesgos

No prometer recuperación de clave perdida por soporte W4. Copias, swap y medios externos requieren revisión propia de cifrado.

## Criterios de aceptación

Aceptar arranque cifrado, credencial alternativa y recuperación de laboratorio sin exposición en logs.

## Rendimiento y evidencia

Medir rendimiento de E/S cifrada.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [037 — Encrypted Installation](037_W4_OS_ENCRYPTED_INSTALLATION.md)
- [162 — TPM Integration](162_W4_OS_TPM_INTEGRATION.md)
- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Cifrado por contraseña V1; integración de hardware después de validar actualización.

---

[Anterior](159_W4_OS_FIREWALL_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](161_W4_OS_SECURE_BOOT.md)
