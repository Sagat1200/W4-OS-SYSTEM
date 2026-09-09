# 394 · W4 OS — Patch Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Integración Debian · **Responsabilidad propuesta:** Mantenimiento upstream  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener parches con motivo, pruebas y condición de retirada.

## Alcance, arquitectura y decisiones

Registro por parche: fuente, versión, incidencia, autoría, riesgo, dueño y estado upstream. Favorecer configuración o extensión antes de modificar código.

## Componentes y flujo operativo

1. Crear delta mínimo
2. probar
3. enviar upstream cuando proceda
4. rebasar al actualizar
5. retirar al quedar incorporado.

## Seguridad y riesgos

No aplicar parches sin entender origen ni conservar correcciones locales que reviertan seguridad upstream. Firmas de fuente se verifican antes de editar.

## Criterios de aceptación

Aceptar parche que aplica y prueba en nueva versión, o bloqueo explícito si falla.

## Rendimiento y evidencia

Medir edad y costo de mantenimiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [006 — Upstream Integration Strategy](006_W4_OS_UPSTREAM_INTEGRATION_STRATEGY.md)
- [007 — Fork And Derivative Strategy](007_W4_OS_FORK_AND_DERIVATIVE_STRATEGY.md)
- [395 — W4 Patch Queue](395_W4_OS_W4_PATCH_QUEUE.md)
- [396 — Upstream Contribution Policy](396_W4_OS_UPSTREAM_CONTRIBUTION_POLICY.md)

## Roadmap y condiciones de evolución

Cola pequeña desde V1; revisión en cada actualización de fuente.

---

[Anterior](393_W4_OS_DEBIAN_PACKAGE_SYNC.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](395_W4_OS_W4_PATCH_QUEUE.md)
