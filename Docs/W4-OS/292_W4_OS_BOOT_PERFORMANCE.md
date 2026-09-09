# 292 · W4 OS — Boot Performance

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Rendimiento · **Responsabilidad propuesta:** Rendimiento de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Reducir tiempo de arranque observando la ruta crítica completa.

## Alcance, arquitectura y decisiones

Separar firmware, cargador, kernel, servicios y sesión; registrar pantalla de acceso y escritorio utilizable como hitos diferentes.

## Componentes y flujo operativo

1. Recoger trazas
2. identificar espera dominante
3. eliminar dependencia innecesaria
4. repetir en frío y caliente
5. comparar.

## Seguridad y riesgos

No ocultar servicios fallidos ni saltar verificación de almacenamiento para reducir tiempo. Red opcional no debe bloquear sesión local.

## Criterios de aceptación

Aceptar arranque sin red con servicios esenciales funcionales y mejora repetible.

## Rendimiento y evidencia

Medir percentiles por hardware.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [025 — Boot Architecture](025_W4_OS_BOOT_ARCHITECTURE.md)
- [029 — Startup Pipeline](029_W4_OS_STARTUP_PIPELINE.md)
- [291 — Performance Architecture](291_W4_OS_PERFORMANCE_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Medición MVP; presupuesto de salida V1 basado en piloto.

## Protocolo de medición de arranque

Registrar por separado firmware, cargador, kernel y servicios para no atribuir a W4 una demora anterior a su ejecución. La sesión útil requiere que el usuario pueda abrir la aplicación de referencia, no sólo ver un fondo. Se mide con red disponible y ausente, y se documenta si el sistema viene de apagado completo o reinicio. Los resultados incluyen varias repeticiones y condiciones del hardware. Una optimización se rechaza si adelanta la pantalla a costa de errores silenciosos o tareas esenciales incompletas.

---

[Anterior](291_W4_OS_PERFORMANCE_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](293_W4_OS_MEMORY_MANAGEMENT.md)
