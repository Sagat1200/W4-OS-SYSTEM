# 063 · W4 OS — Build Workers

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Construcción y artefactos · **Responsabilidad propuesta:** Infraestructura de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ejecutar compilaciones con entornos desechables y recursos acotados.

## Alcance, arquitectura y decisiones

Workers por arquitectura arrancan de una imagen identificada; disco temporal, límites de CPU/memoria y red restringida. Ningún worker se reutiliza conservando secretos o residuos del proyecto.

## Componentes y flujo operativo

1. Asignar trabajo
2. preparar entorno
3. compilar
4. exportar resultados permitidos
5. destruir espacio
6. verificar limpieza.

## Seguridad y riesgos

Tratar fuentes como código no confiable. Montajes del host y credenciales de publicación no están disponibles en el trabajo.

## Criterios de aceptación

Aceptar build que intenta exceder recursos o leer otro trabajo con bloqueo.

## Rendimiento y evidencia

Medir saturación y frecuencia de limpieza fallida.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [061 — Build Infrastructure](061_W4_OS_BUILD_INFRASTRUCTURE.md)
- [065 — Multi Arch Build System](065_W4_OS_MULTI_ARCH_BUILD_SYSTEM.md)
- [169 — Supply Chain Security](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)

## Roadmap y condiciones de evolución

Workers aislados desde MVP; ampliación automática sólo con límites de costo y cola.

---

[Anterior](062_W4_OS_OPEN_BUILD_SERVICE_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](064_W4_OS_BUILD_REPRODUCIBILITY.md)
