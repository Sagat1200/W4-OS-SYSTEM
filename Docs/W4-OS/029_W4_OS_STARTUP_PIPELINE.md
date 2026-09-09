# 029 · W4 OS — Startup Pipeline

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Arranque y servicios · **Responsabilidad propuesta:** Integración de arranque  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ordenar el arranque para llegar al escritorio sin esperar servicios opcionales de red.

## Alcance, arquitectura y decisiones

Separar ruta crítica local de sincronización, inventario y telemetría. La identidad local y los archivos esenciales no dependen del servidor empresarial.

## Componentes y flujo operativo

1. Montar raíz
2. validar estado
3. iniciar servicios locales
4. mostrar acceso
5. abrir sesión; programar tareas remotas después de disponibilidad.

## Seguridad y riesgos

No considerar saludable una generación sólo porque aparece la pantalla de acceso; verificar también paquetes, almacenamiento y servicios esenciales.

## Criterios de aceptación

Aceptar inicio sin red y con servidor W4 caído.

## Rendimiento y evidencia

Medir tiempo desde cargador a pantalla y a escritorio con trazas por etapa.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [025 — Boot Architecture](025_W4_OS_BOOT_ARCHITECTURE.md)
- [076 — Update Health Check](076_W4_OS_UPDATE_HEALTH_CHECK.md)
- [292 — Boot Performance](292_W4_OS_BOOT_PERFORMANCE.md)

## Roadmap y condiciones de evolución

Instrumentar MVP y fijar presupuesto con hardware de referencia antes de optimizar.

---

[Anterior](028_W4_OS_SYSTEMD_INTEGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](030_W4_OS_SHUTDOWN_PIPELINE.md)
