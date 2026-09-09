# 267 · W4 OS — LTS Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Releases y soporte de versiones · **Responsabilidad propuesta:** Ingeniería de releases  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir soporte prolongado según paquetes, equipo y compromisos verificables.

## Alcance, arquitectura y decisiones

Cobertura W4 combina upstream disponible y mantenimiento propio de componentes; LTS no significa todos los paquetes cubiertos igual. Publicar exclusiones y arquitecturas mantenidas.

## Componentes y flujo operativo

1. Inventariar cobertura
2. estimar recursos
3. aprobar periodo
4. vigilar cambios upstream
5. avisar transición
6. cerrar soporte de forma planificada.

## Seguridad y riesgos

No heredar automáticamente el calendario Debian como SLA W4 ni prometer ELTS sin proveedor/contrato. Paquetes sin soporte requieren mitigación o retirada.

## Criterios de aceptación

Aceptar matriz de cobertura por componente y plan antes de agotar soporte.

## Rendimiento y evidencia

Medir vulnerabilidades sin mantenedor.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [005 — Debian Base Strategy](005_W4_OS_DEBIAN_BASE_STRATEGY.md)
- [274 — End Of Life Policy](274_W4_OS_END_OF_LIFE_POLICY.md)
- [334 — Security Support Policy](334_W4_OS_SECURITY_SUPPORT_POLICY.md)
- [400 — Long Term Maintenance](400_W4_OS_LONG_TERM_MAINTENANCE.md)

## Roadmap y condiciones de evolución

Establecer capacidad durante V1; anunciar duración sólo con respaldo operativo.

---

[Anterior](266_W4_OS_VERSIONING_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](268_W4_OS_RELEASE_CHANNELS.md)
