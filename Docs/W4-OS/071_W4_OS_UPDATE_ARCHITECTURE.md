# 071 · W4 OS — Update Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Coordinar actualizaciones de sistema con recuperación explícita y una sola operación activa.

## Alcance, arquitectura y decisiones

V1 propone APT/dpkg con aplicación offline y snapshot previo; esto no constituye por sí solo actualización atómica. El modelo transaccional de generaciones aisladas es experimental hasta calificarlo.

## Componentes y flujo operativo

1. Consultar
2. resolver
3. descargar
4. verificar
5. comprobar espacio
6. preparar snapshot
7. aplicar offline
8. arrancar
9. evaluar salud
10. confirmar o recuperar.

## Seguridad y riesgos

Conservar vínculo entre raíz, dpkg, kernel e initramfs. Firmware y datos externos al snapshot tienen recuperación separada; no prometer reversión universal.

## Criterios de aceptación

Aceptar interrupciones en cada etapa con diagnóstico y una ruta de arranque utilizable.

## Rendimiento y evidencia

Medir duración offline, fallos y recuperación efectiva.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [072 — Update Engine](072_W4_OS_UPDATE_ENGINE.md)
- [073 — Atomic Update Model](073_W4_OS_ATOMIC_UPDATE_MODEL.md)
- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [280 — Update Testing](280_W4_OS_UPDATE_TESTING.md)

## Roadmap y condiciones de evolución

MVP con recuperación manual probada; automatización y atomicidad sólo después de superar pruebas de fallo.

## Máquina de estados de referencia para V1

| Estado | Condición de entrada | Salida permitida ante fallo |
|---|---|---|
| `idle` | Sin operación mutante activa | Permanecer disponible |
| `planned` | Solución y origen validados | Invalidar plan sin cambios de paquetes |
| `downloading` | Espacio de caché y red/destino disponibles | Reanudar o cancelar descarga |
| `ready` | Paquetes completos y verificados | Revalidar si cambió el sistema |
| `prepared` | Snapshot y evidencia de arranque registrados | Cancelar antes de aplicación, conservando diagnóstico |
| `applying_offline` | Sesiones detenidas y exclusión obtenida | Diagnosticar estado parcial; no declarar éxito |
| `pending_health` | Aplicación concluida y arranque intentado | Seleccionar recuperación compatible |
| `confirmed` | Checks obligatorios aprobados | Retener estado bueno según política |
| `failed` | Fallo observado y registrado | Reparación o recuperación explícita |

El almacenamiento de operación debe ser durable y no quedar exclusivamente dentro del estado que se revierte. Sus metadatos mínimos son identificador, origen, destino, etapa, versión del esquema, snapshot previo, manifiesto de arranque y último error tipado. Los timestamps ayudan a soporte; no constituyen por sí solos prueba de orden cuando el reloj cambia.

## Concurrencia y cancelación

El coordinador consulta y respeta locks de gestores existentes. Nunca elimina un lock para ganar prioridad. Un conflicto real deja la solicitud pendiente con motivo visible. La descarga admite cancelación; la escritura de paquetes no se interrumpe arbitrariamente desde UI. Una pausa de flota significa no asignar nuevas operaciones, y no matar `dpkg` en equipos que ya aplican cambios.

Una operación que pierde su cliente sigue registrada. Al volver, el cliente consulta el mismo identificador en lugar de iniciar otra actualización. La idempotencia se comprueba con reenvío deliberado de solicitudes y reinicio del servicio.

---

[Anterior](070_W4_OS_RELEASE_ARTIFACT_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](072_W4_OS_UPDATE_ENGINE.md)
