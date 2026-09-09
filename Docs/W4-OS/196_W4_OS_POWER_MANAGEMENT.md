# 196 · W4 OS — Power Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Energía · **Responsabilidad propuesta:** Hardware y energía  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Coordinar energía usando componentes existentes sin gestores contradictorios.

## Alcance, arquitectura y decisiones

Seleccionar un servicio de perfiles compatible con escritorio y hardware; no activar simultáneamente herramientas que compitan por los mismos parámetros. Defaults priorizan estabilidad.

## Componentes y flujo operativo

1. Detectar batería o alimentación
2. aplicar perfil
3. observar estado
4. informar limitaciones
5. restaurar configuración tras cambio de sesión.

## Seguridad y riesgos

No permitir ajustes de frecuencia o voltaje fuera de soporte. Operaciones críticas declaran inhibidores acotados.

## Criterios de aceptación

Aceptar cambio de alimentación y suspensión con perfil coherente.

## Rendimiento y evidencia

Medir consumo ocioso y autonomía en hardware de referencia.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [197 — Battery Management](197_W4_OS_BATTERY_MANAGEMENT.md)
- [198 — Sleep Hibernation](198_W4_OS_SLEEP_HIBERNATION.md)
- [200 — Performance Profiles](200_W4_OS_PERFORMANCE_PROFILES.md)

## Roadmap y condiciones de evolución

Gestor único V1 y benchmarks antes de ajustes propios.

## Autoridad sobre parámetros de energía

Antes de seleccionar herramienta se inventarían los controles ya expuestos por firmware, kernel y escritorio. Si dos servicios escriben el mismo parámetro, se elige uno o se delimita explícitamente la propiedad; no se resuelve alternando ajustes cada pocos segundos. Los perfiles registran parámetros realmente aplicados y capacidades ausentes. Una máquina sin modo rendimiento mantiene el modo disponible y explica la limitación, en vez de mostrar un selector que no cambia nada. Las mediciones se repiten conectado y con batería.

---

[Anterior](195_W4_OS_CLOUD_BACKUP_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](197_W4_OS_BATTERY_MANAGEMENT.md)
