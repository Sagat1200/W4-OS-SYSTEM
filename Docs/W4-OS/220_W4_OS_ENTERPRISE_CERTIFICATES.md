# 220 · W4 OS — Enterprise Certificates

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Emitir y renovar certificados de dispositivos y redes organizacionales.

## Alcance, arquitectura y decisiones

PKI empresarial externa o servicio definido por contrato; cada certificado tiene propósito, identidad y vencimiento. Clave privada permanece protegida en destino cuando sea posible.

## Componentes y flujo operativo

1. Inscribir
2. generar clave
3. solicitar certificado
4. validar cadena
5. activar
6. renovar antes de vencer
7. revocar al retirar.

## Seguridad y riesgos

No compartir certificado entre equipos clonados ni usar uno de VPN para API sin propósito autorizado. Renovación debe tolerar desconexión temporal.

## Criterios de aceptación

Aceptar renovación, revocación y pérdida de red con avisos y recuperación.

## Rendimiento y evidencia

Medir certificados próximos a vencer y fallos por CA.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [164 — Certificate Management](164_W4_OS_CERTIFICATE_MANAGEMENT.md)
- [185 — Enterprise Networking](185_W4_OS_ENTERPRISE_NETWORKING.md)
- [222 — Device Enrollment](222_W4_OS_DEVICE_ENROLLMENT.md)

## Roadmap y condiciones de evolución

Ciclo completo en piloto; almacenamiento TPM opcional después.

---

[Anterior](219_W4_OS_SSO_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](221_W4_OS_FLEET_MANAGEMENT.md)
