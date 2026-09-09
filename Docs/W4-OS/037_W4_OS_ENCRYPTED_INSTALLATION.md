# 037 · W4 OS — Encrypted Installation

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ofrecer instalación cifrada con recuperación comprendida por el usuario.

## Alcance, arquitectura y decisiones

Propuesta LUKS2 para el volumen del sistema; ESP queda sin cifrar y no contiene secretos. Desbloqueo por frase de paso es referencia; TPM es una opción posterior.

## Componentes y flujo operativo

Crear contenedor, establecer credencial, preparar raíz, generar initramfs y verificar desbloqueo en reinicio; entregar procedimiento de respaldo de material de recuperación.

## Seguridad y riesgos

No almacenar frase de paso en logs ni activar desbloqueo automático sin explicar amenazas. La pérdida de credenciales puede impedir recuperación de datos.

## Criterios de aceptación

Aceptar frase incorrecta, distribución de teclado distinta y recuperación desde medio externo sin exponer claves.

## Rendimiento y evidencia

Medir costo de desbloqueo y memoria requerida.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [160 — Disk Encryption](160_W4_OS_DISK_ENCRYPTION.md)
- [162 — TPM Integration](162_W4_OS_TPM_INTEGRATION.md)
- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

V1 con cifrado por contraseña; TPM tras pruebas de actualización y recuperación.

---

[Anterior](036_W4_OS_FILESYSTEM_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](038_W4_OS_DUAL_BOOT_STRATEGY.md)
