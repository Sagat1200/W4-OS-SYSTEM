# 131 · W4 OS — Default Applications

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Seleccionar un conjunto pequeño que cubra tareas comunes y tenga mantenimiento claro.

## Alcance, arquitectura y decisiones

Candidatos: navegador mantenido, LibreOffice, visor PDF, cliente de archivos y reproducción multimedia compatibles con Debian. La selección final se congela con licencias, soporte y pruebas.

## Componentes y flujo operativo

1. Definir tarea
2. comparar candidato
3. probar formatos reales
4. revisar accesibilidad
5. añadir al perfil
6. documentar reemplazo.

## Seguridad y riesgos

No incluir aplicaciones por acuerdos comerciales que oculten telemetría o permisos. Evitar dos apps equivalentes por defecto sin motivo.

## Criterios de aceptación

Aceptar navegar, editar documento, abrir PDF y reproducir medios de la matriz en instalación nueva.

## Rendimiento y evidencia

Medir tamaño y tiempo de primer uso.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [132 — Browser Strategy](132_W4_OS_BROWSER_STRATEGY.md)
- [133 — Office Suite Strategy](133_W4_OS_OFFICE_SUITE_STRATEGY.md)
- [135 — PDF System](135_W4_OS_PDF_SYSTEM.md)
- [136 — Multimedia System](136_W4_OS_MULTIMEDIA_SYSTEM.md)

## Roadmap y condiciones de evolución

Catálogo MVP mínimo; extras se instalan a demanda.

---

[Anterior](130_W4_OS_APPLICATION_LIFECYCLE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](132_W4_OS_BROWSER_STRATEGY.md)
