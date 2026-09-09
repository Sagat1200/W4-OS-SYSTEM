# 298 · W4 OS — Power Performance

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Rendimiento · **Responsabilidad propuesta:** Rendimiento de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Comparar rendimiento energético con tareas y condiciones controladas.

## Alcance, arquitectura y decisiones

Protocolo fija brillo, radio, batería, carga y temperatura; separar consumo ocioso, videollamada y trabajo sostenido. No extrapolar un portátil a toda la flota.

## Componentes y flujo operativo

1. Cargar condiciones
2. ejecutar tarea
3. medir energía/tiempo
4. repetir
5. comparar perfil
6. registrar incertidumbre.

## Seguridad y riesgos

No desactivar bloqueo o controles para alargar autonomía. Respetar límites térmicos y estado real de batería.

## Criterios de aceptación

Aceptar resultados repetibles con equipo y condiciones documentados.

## Rendimiento y evidencia

Medir energía por tarea y consumo suspendido.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [196 — Power Management](196_W4_OS_POWER_MANAGEMENT.md)
- [197 — Battery Management](197_W4_OS_BATTERY_MANAGEMENT.md)
- [199 — Thermal Management](199_W4_OS_THERMAL_MANAGEMENT.md)
- [299 — Performance Benchmarks](299_W4_OS_PERFORMANCE_BENCHMARKS.md)

## Roadmap y condiciones de evolución

Baseline V1 en portátiles certificados; objetivos después de medición.

## Comparación entre perfiles de energía

Mantener brillo, volumen, radios, carga de batería y tareas equivalentes entre ensayos. La degradación de batería y temperatura ambiente se registran porque pueden dominar diferencias entre versiones. Medir tanto duración de la tarea como energía consumida: un perfil más lento puede ahorrar potencia instantánea y consumir más energía total. No extrapolar un resultado a equipos no probados. El informe publica rango de variación y condiciones, y reserva afirmaciones de autonomía comercial para experimentos representativos con metodología revisada.

---

[Anterior](297_W4_OS_APPLICATION_STARTUP_PERFORMANCE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](299_W4_OS_PERFORMANCE_BENCHMARKS.md)
