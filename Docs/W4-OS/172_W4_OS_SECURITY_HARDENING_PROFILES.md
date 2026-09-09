# 172 · W4 OS — Security Hardening Profiles

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir endurecimiento por riesgo sin romper compatibilidad de forma opaca.

## Alcance, arquitectura y decisiones

Perfiles versionados enumeran controles, justificación, impactos y excepciones. Baseline común y restricciones Business se aplican mediante política, no imágenes divergentes.

## Componentes y flujo operativo

1. Evaluar compatibilidad
2. previsualizar delta
3. aplicar controles
4. probar tareas
5. registrar excepciones con vencimiento.

## Seguridad y riesgos

No desactivar recuperación o accesibilidad sin alternativa validada. Un perfil estricto no equivale a certificación de seguridad.

## Criterios de aceptación

Aceptar aplicación y retirada del perfil con estado efectivo comprobable.

## Rendimiento y evidencia

Medir fallos de aplicaciones y controles exceptuados.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [157 — Security Baseline](157_W4_OS_SECURITY_BASELINE.md)
- [158 — Apparmor Architecture](158_W4_OS_APPARMOR_ARCHITECTURE.md)
- [173 — Home Security Profile](173_W4_OS_HOME_SECURITY_PROFILE.md)
- [174 — Business Security Profile](174_W4_OS_BUSINESS_SECURITY_PROFILE.md)

## Roadmap y condiciones de evolución

Baseline V1; perfiles estrictos después de pruebas empresariales.

---

[Anterior](171_W4_OS_SECURITY_EVENT_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](173_W4_OS_HOME_SECURITY_PROFILE.md)
