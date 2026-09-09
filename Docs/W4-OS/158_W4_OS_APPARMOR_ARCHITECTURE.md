# 158 · W4 OS — Apparmor Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Aplicar AppArmor a servicios y aplicaciones seleccionados con pruebas funcionales.

## Alcance, arquitectura y decisiones

Reutilizar perfiles de Debian cuando sean adecuados; perfiles W4 se versionan por servicio. Modo de observación sólo durante evaluación, con promoción explícita a enforcement.

## Componentes y flujo operativo

1. Ejecutar casos legítimos y abusivos
2. revisar denegaciones
3. ajustar mínimo necesario
4. activar perfil
5. vigilar regresiones.

## Seguridad y riesgos

No resolver errores añadiendo acceso global al filesystem. Los logs de denegación pueden incluir rutas personales y requieren minimización.

## Criterios de aceptación

Aceptar lectura no autorizada bloqueada y flujo legítimo completo.

## Rendimiento y evidencia

Medir denegaciones falsas y costo del perfil.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [123 — Application Sandboxing](123_W4_OS_APPLICATION_SANDBOXING.md)
- [156 — Security Architecture](156_W4_OS_SECURITY_ARCHITECTURE.md)
- [172 — Security Hardening Profiles](172_W4_OS_SECURITY_HARDENING_PROFILES.md)

## Roadmap y condiciones de evolución

Servicios W4 privilegiados primero; ampliar por riesgo y mantenimiento.

---

[Anterior](157_W4_OS_SECURITY_BASELINE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](159_W4_OS_FIREWALL_SYSTEM.md)
