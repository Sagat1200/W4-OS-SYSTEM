# 174 · W4 OS — Business Security Profile

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Añadir restricciones empresariales según propiedad y riesgo del dispositivo.

## Alcance, arquitectura y decisiones

Perfil puede imponer cifrado, bloqueo, servicios permitidos, políticas de medios y auditoría. Cada restricción tiene autoridad organizacional y evidencia de aplicación.

## Componentes y flujo operativo

1. Inscribir
2. evaluar compatibilidad
3. aplicar política
4. comprobar controles
5. reportar desviaciones
6. corregir de forma planificada.

## Seguridad y riesgos

No habilitar soporte remoto oculto ni exportación ilimitada de archivos por pertenecer a Business. Separar roles de operador y auditor.

## Criterios de aceptación

Aceptar equipo fuera de política identificado y excepción vigente respetada.

## Rendimiento y evidencia

Medir convergencia y controles imposibles de aplicar.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [012 — Business Edition](012_W4_OS_BUSINESS_EDITION.md)
- [172 — Security Hardening Profiles](172_W4_OS_SECURITY_HARDENING_PROFILES.md)
- [213 — Central Policy System](213_W4_OS_CENTRAL_POLICY_SYSTEM.md)
- [228 — Compliance System](228_W4_OS_COMPLIANCE_SYSTEM.md)

## Roadmap y condiciones de evolución

Perfil piloto antes de comercialización; variantes por sector con revisión propia.

---

[Anterior](173_W4_OS_HOME_SECURITY_PROFILE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](175_W4_OS_INCIDENT_RECOVERY_MODEL.md)
