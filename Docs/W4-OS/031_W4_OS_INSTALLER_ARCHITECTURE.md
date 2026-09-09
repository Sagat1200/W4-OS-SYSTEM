# 031 · W4 OS — Installer Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Construir un instalador que haga explícitos los cambios de disco antes de ejecutarlos.

## Alcance, arquitectura y decisiones

Proponer reutilización de un motor existente compatible con Debian, seleccionado por prueba técnica. Separar UI, planificador de almacenamiento y ejecutor privilegiado; no inventar un motor desde cero.

## Componentes y flujo operativo

1. Detectar
2. elaborar plan
3. mostrar discos afectados
4. obtener confirmación
5. instalar
6. verificar arranque
7. ofrecer reinicio.

## Seguridad y riesgos

El plan se vincula a identificadores estables y se revalida justo antes de escribir. Si cambia un disco, invalidar el plan; no continuar por nombre de dispositivo.

## Criterios de aceptación

Aceptar instalación en VM vacía y rechazo de plan obsoleto.

## Rendimiento y evidencia

Medir duración por etapa y espacio mínimo real.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [032 — Installation Flow](032_W4_OS_INSTALLATION_FLOW.md)
- [033 — Partitioning System](033_W4_OS_PARTITIONING_SYSTEM.md)
- [037 — Encrypted Installation](037_W4_OS_ENCRYPTED_INSTALLATION.md)

## Roadmap y condiciones de evolución

Comparar motores en prototipo, elegir por ADR y congelar flujo destructivo antes de V1.

---

[Anterior](030_W4_OS_SHUTDOWN_PIPELINE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](032_W4_OS_INSTALLATION_FLOW.md)
