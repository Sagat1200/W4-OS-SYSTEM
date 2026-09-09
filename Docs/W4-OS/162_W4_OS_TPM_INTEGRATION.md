# 162 · W4 OS — TPM Integration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Evaluar TPM para proteger claves sin eliminar recuperación independiente.

## Alcance, arquitectura y decisiones

Uso opcional para sellado de material de desbloqueo e identidad; política de medición debe tolerar actualizaciones autorizadas. No almacenar secretos recuperables sin ruta alternativa.

## Componentes y flujo operativo

1. Inscribir con recuperación disponible
2. sellar
3. verificar arranque
4. actualizar mediciones de forma controlada
5. recuperar si cambian condiciones.

## Seguridad y riesgos

Un TPM no garantiza que una sesión comprometida sea segura. Firmware, placa reemplazada o PCR distintos pueden impedir desbloqueo automático.

## Criterios de aceptación

Aceptar actualización de kernel, cambio de firmware y TPM ausente con recuperación documentada.

## Rendimiento y evidencia

Medir fallos de desbloqueo y soporte requerido.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [020 — Firmware Management](020_W4_OS_FIRMWARE_MANAGEMENT.md)
- [037 — Encrypted Installation](037_W4_OS_ENCRYPTED_INSTALLATION.md)
- [160 — Disk Encryption](160_W4_OS_DISK_ENCRYPTION.md)
- [161 — Secure Boot](161_W4_OS_SECURE_BOOT.md)

## Roadmap y condiciones de evolución

Experimental después de cifrado básico; activar por modelo certificado.

---

[Anterior](161_W4_OS_SECURE_BOOT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](163_W4_OS_SECRET_STORAGE.md)
