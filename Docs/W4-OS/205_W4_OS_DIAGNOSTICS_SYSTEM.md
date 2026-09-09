# 205 · W4 OS — Diagnostics System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Diagnóstico y privacidad · **Responsabilidad propuesta:** Diagnóstico y privacidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Diagnosticar síntomas mediante pruebas de lectura y evidencia vinculada a hipótesis.

## Alcance, arquitectura y decisiones

Catálogo de checks por red, disco, paquetes y sesión; cada prueba declara datos usados, costo y límites. Separar diagnóstico de reparación.

## Componentes y flujo operativo

1. Seleccionar síntoma
2. ejecutar checks aplicables
3. clasificar hallazgo y confianza
4. proponer acción
5. exportar resumen opcional.

## Seguridad y riesgos

No ejecutar comandos arbitrarios recibidos de un servidor como prueba. Las lecturas pueden exponer rutas y deben redactarse.

## Criterios de aceptación

Aceptar fallo DNS distinguido de falta de enlace y paquete incompleto de disco lleno.

## Rendimiento y evidencia

Medir precisión y duración por diagnóstico.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [090 — System Repair System](090_W4_OS_SYSTEM_REPAIR_SYSTEM.md)
- [191 — Disk Health System](191_W4_OS_DISK_HEALTH_SYSTEM.md)
- [206 — Health Monitor](206_W4_OS_HEALTH_MONITOR.md)
- [322 — Troubleshooting Guide](322_W4_OS_TROUBLESHOOTING_GUIDE.md)

## Roadmap y condiciones de evolución

Checks de problemas frecuentes V1; ampliar a partir de tickets reales.

---

[Anterior](204_W4_OS_CRASH_REPORTING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](206_W4_OS_HEALTH_MONITOR.md)
