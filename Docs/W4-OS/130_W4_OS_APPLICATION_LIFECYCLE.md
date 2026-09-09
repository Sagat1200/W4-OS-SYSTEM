# 130 · W4 OS — Application Lifecycle

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Gestionar actualización y fin de soporte de aplicaciones sin destruir sus datos.

## Alcance, arquitectura y decisiones

Registro de app, formato, runtime, datos y migraciones. Desinstalar ejecutable y borrar datos son acciones separadas; versiones con esquema incompatible requieren respaldo.

## Componentes y flujo operativo

1. Detectar actualización
2. comprobar soporte
3. aplicar por backend
4. lanzar prueba mínima
5. marcar versión; al retirar, ofrecer conservar datos.

## Seguridad y riesgos

No garantizar downgrade si la app migró su base de datos. Los runtimes sin soporte generan aviso y plan de sustitución.

## Criterios de aceptación

Aceptar retirada y reinstalación conservando documentos y rechazo de reversión incompatible.

## Rendimiento y evidencia

Medir apps y runtimes fuera de soporte.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [050 — Package Lifecycle](050_W4_OS_PACKAGE_LIFECYCLE.md)
- [124 — Flatpak Strategy](124_W4_OS_FLATPAK_STRATEGY.md)
- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Ciclo visible V1; migración asistida por aplicaciones prioritarias.

---

[Anterior](129_W4_OS_APPLICATION_PERMISSIONS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](131_W4_OS_DEFAULT_APPLICATIONS.md)
