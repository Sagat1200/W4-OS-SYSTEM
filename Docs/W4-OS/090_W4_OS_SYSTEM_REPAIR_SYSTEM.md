# 090 · W4 OS — System Repair System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Reparar inconsistencias delimitadas sin reinstalar todo el sistema.

## Alcance, arquitectura y decisiones

Catálogo de reparaciones con precondición, acciones permitidas y verificación: estado dpkg, archivos de paquete, initramfs y configuración específica. Inspección precede a modificación.

## Componentes y flujo operativo

1. Detectar
2. explicar causa probable
3. respaldar configuración afectada
4. aplicar reparación acotada
5. comprobar
6. escalar si persiste.

## Seguridad y riesgos

No ejecutar comandos descargados de internet ni aplicar reparaciones de filesystem montado sin soporte. El motor no acepta shell arbitraria como receta.

## Criterios de aceptación

Aceptar un paquete incompleto y un conffile inválido con acciones diferentes y trazables.

## Rendimiento y evidencia

Medir porcentaje resuelto sin reinstalación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [041 — Package System](041_W4_OS_PACKAGE_SYSTEM.md)
- [086 — Recovery Architecture](086_W4_OS_RECOVERY_ARCHITECTURE.md)
- [322 — Troubleshooting Guide](322_W4_OS_TROUBLESHOOTING_GUIDE.md)

## Roadmap y condiciones de evolución

Recetas iniciales revisadas manualmente; automatizar sólo diagnósticos deterministas.

---

[Anterior](089_W4_OS_FACTORY_RESET_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](091_W4_OS_DESKTOP_ARCHITECTURE.md)
