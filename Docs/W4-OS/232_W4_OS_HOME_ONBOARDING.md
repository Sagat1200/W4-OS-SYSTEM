# 232 · W4 OS — Home Onboarding

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Experiencia Home · **Responsabilidad propuesta:** Producto Home  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Guiar primer inicio sin repetir instalación ni exigir decisiones innecesarias.

## Alcance, arquitectura y decisiones

Asistente distingue equipo recién instalado y OEM generalizado; idioma, cuenta, privacidad y respaldo son pasos independientes. Estado de progreso no contiene secretos.

## Componentes y flujo operativo

1. Detectar pasos pendientes
2. presentar explicación breve
3. guardar elección
4. permitir omitir opcionales
5. finalizar
6. no reaparecer sin motivo.

## Seguridad y riesgos

No preseleccionar consentimiento ni crear cuenta W4 automáticamente. Interrupción no debe dejar una cuenta de fábrica accesible.

## Criterios de aceptación

Aceptar cierre y reinicio a mitad del flujo con continuidad correcta.

## Rendimiento y evidencia

Medir tiempo y abandonos por paso.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [032 — Installation Flow](032_W4_OS_INSTALLATION_FLOW.md)
- [039 — OEM Installation Mode](039_W4_OS_OEM_INSTALLATION_MODE.md)
- [209 — Opt In Telemetry](209_W4_OS_OPT_IN_TELEMETRY.md)

## Roadmap y condiciones de evolución

Onboarding local V1; servicios online sólo cuando estén operativos.

---

[Anterior](231_W4_OS_HOME_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](233_W4_OS_HOME_ACCOUNT_SYSTEM.md)
