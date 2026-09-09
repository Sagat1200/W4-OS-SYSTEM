# 073 · W4 OS — Atomic Update Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir con precisión qué significa atomicidad para W4 y qué queda fuera.

## Alcance, arquitectura y decisiones

Contrato experimental: un arranque selecciona una generación completa conocida; preparar una nueva no altera la activa. No se atribuye atomicidad a apt upgrade con snapshot previo.

## Componentes y flujo operativo

1. Construir generación aislada
2. validar contenido y artefactos de arranque
3. registrar candidata
4. activar referencia durable
5. arrancar y confirmar.

## Seguridad y riesgos

Cambios en ESP, firmware y datos persistentes no son una transacción Btrfs única. Se requieren protocolo de coordinación y compatibilidad de esquemas.

## Criterios de aceptación

Aceptar cortes antes y después del cambio de referencia con arranque íntegro de vieja o nueva generación; cero mezclas de /usr y dpkg.

## Rendimiento y evidencia

Medir espacio adicional de candidata, tiempo de preparación y tiempo de activación; registrar la distribución de resultados de cortes de energía por etapa.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [074 — Transactional Update Strategy](074_W4_OS_TRANSACTIONAL_UPDATE_STRATEGY.md)
- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [085 — Rollback Architecture](085_W4_OS_ROLLBACK_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Investigación V2 propuesta; V1 documenta límites de actualización offline recuperable.

## Fronteras de garantía

| Mecanismo | Garantía propuesta o límite |
|---|---|
| Snapshot previo + APT sobre raíz activa/offline | Punto de recuperación; no evita por sí solo cambios parciales |
| Instalación en raíz candidata aislada | Aísla preparación sólo si scripts y montajes no afectan activa |
| Cambio de referencia de arranque | Debe resistir interrupción y apuntar a artefactos completos |
| Confirmación de salud | Decide si conservar candidata; no comprueba toda función de usuario |
| Datos fuera de generación | Requieren compatibilidad hacia atrás o restauración independiente |
| Firmware | No está cubierto por atomicidad del filesystem |

La unidad de aceptación no es “el comando terminó”. Es que, tras un corte en cualquier punto ensayado del protocolo, el equipo dispone de una generación íntegra arrancable y de una explicación inequívoca de la selección. Un sistema donde el binario y su registro dpkg corresponden a versiones distintas falla esta condición.

Antes de promover esta función deben probarse paquetes con triggers, cambios en initramfs, conffiles personalizados y servicios que intentan arrancar durante instalación. Un prototipo que sólo cambia un archivo de texto no representa la semántica de actualización de una distribución Debian.

---

[Anterior](072_W4_OS_UPDATE_ENGINE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](074_W4_OS_TRANSACTIONAL_UPDATE_STRATEGY.md)
