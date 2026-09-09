# 383 · W4 OS — Safe Mode

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Rescate y fallos · **Responsabilidad propuesta:** Recuperación de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ofrecer modo seguro que reduzca componentes sin omitir autenticación.

## Alcance, arquitectura y decisiones

Arranque con servicios esenciales, gráficos básicos disponibles y extensiones opcionales desactivadas; selección explícita y reversible. Conserva cifrado y acceso autorizado.

## Componentes y flujo operativo

1. Elegir modo seguro
2. arrancar mínimo
3. recopilar diagnóstico
4. corregir componente
5. volver a normal
6. comprobar.

## Seguridad y riesgos

No habilitar root sin contraseña ni red de soporte abierta. Desactivar extensiones no implica eliminar sus datos.

## Criterios de aceptación

Aceptar shell gráfico fallido con entrada alternativa y retorno a sesión normal.

## Rendimiento y evidencia

Medir recursos mínimos y funciones de diagnóstico.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [086 — Recovery Architecture](086_W4_OS_RECOVERY_ARCHITECTURE.md)
- [091 — Desktop Architecture](091_W4_OS_DESKTOP_ARCHITECTURE.md)
- [382 — Failsafe Architecture](382_W4_OS_FAILSAFE_ARCHITECTURE.md)
- [384 — Emergency Shell](384_W4_OS_EMERGENCY_SHELL.md)

## Roadmap y condiciones de evolución

Modo de rescate documentado V1; automatización tras calificación.

---

[Anterior](382_W4_OS_FAILSAFE_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](384_W4_OS_EMERGENCY_SHELL.md)
