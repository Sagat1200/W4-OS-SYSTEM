# 210 · W4 OS — Support Bundle System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Diagnóstico y privacidad · **Responsabilidad propuesta:** Diagnóstico y privacidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Crear paquetes de soporte que el usuario pueda revisar antes de compartir.

## Alcance, arquitectura y decisiones

Bundle con inventario mínimo, logs seleccionados, versiones y resultados de diagnóstico; manifiesto lista archivos y redacciones. Excluir documentos, credenciales y memoria por defecto.

## Componentes y flujo operativo

1. Elegir problema
2. recopilar con límites
3. redactar
4. mostrar contenido y tamaño
5. exportar localmente
6. compartir sólo con autorización.

## Seguridad y riesgos

Probar redacción con secretos sintéticos y rutas personales. El archivo puede seguir siendo sensible y debe tener permisos y caducidad definidos.

## Criterios de aceptación

Aceptar bundle útil para fallo de actualización sin token de prueba ni archivo personal.

## Rendimiento y evidencia

Medir tamaño y tiempo de preparación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [202 — System Logging](202_W4_OS_SYSTEM_LOGGING.md)
- [204 — Crash Reporting](204_W4_OS_CRASH_REPORTING.md)
- [205 — Diagnostics System](205_W4_OS_DIAGNOSTICS_SYSTEM.md)
- [226 — Remote Diagnostics](226_W4_OS_REMOTE_DIAGNOSTICS.md)

## Roadmap y condiciones de evolución

Exportación local V1; carga a soporte cuando exista endpoint y contrato.

---

[Anterior](209_W4_OS_OPT_IN_TELEMETRY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](211_W4_OS_BUSINESS_ARCHITECTURE.md)
