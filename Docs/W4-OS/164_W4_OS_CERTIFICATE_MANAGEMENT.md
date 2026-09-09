# 164 · W4 OS — Certificate Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Controlar certificados de sistema, usuario y organización con procedencia visible.

## Alcance, arquitectura y decisiones

Separar trust store del sistema, certificados personales y credenciales de dispositivo. La CA empresarial se instala sólo por autoridad autorizada y con alcance documentado.

## Componentes y flujo operativo

1. Solicitar
2. validar cadena y propósito
3. instalar
4. vigilar vencimiento
5. renovar
6. retirar confianza cuando se revoca.

## Seguridad y riesgos

No aceptar cualquier CA para resolver un error TLS. Una CA de inspección permite observar tráfico y debe quedar visible según política aplicable.

## Criterios de aceptación

Aceptar certificado vencido, nombre incorrecto y revocación según mecanismo usado.

## Rendimiento y evidencia

Medir expiraciones no atendidas y tiempo de renovación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [163 — Secret Storage](163_W4_OS_SECRET_STORAGE.md)
- [185 — Enterprise Networking](185_W4_OS_ENTERPRISE_NETWORKING.md)
- [220 — Enterprise Certificates](220_W4_OS_ENTERPRISE_CERTIFICATES.md)

## Roadmap y condiciones de evolución

Inventario y renovación de certificados Business antes del piloto amplio.

---

[Anterior](163_W4_OS_SECRET_STORAGE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](165_W4_OS_SECURITY_UPDATE_POLICY.md)
