# 200 · W4 OS — Performance Profiles

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Energía · **Responsabilidad propuesta:** Hardware y energía  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ofrecer perfiles de rendimiento con alcance y reversión visibles.

## Alcance, arquitectura y decisiones

Propuesta equilibrado, ahorro y rendimiento cuando el hardware lo permita; un único gestor controla parámetros. Aplicaciones pueden solicitar temporalmente un perfil sin imponerlo globalmente.

## Componentes y flujo operativo

1. Seleccionar
2. comprobar capacidad y política
3. aplicar
4. medir
5. volver a perfil previo al terminar operación.

## Seguridad y riesgos

No alterar mitigaciones de seguridad ni límites térmicos. Business puede restringir perfiles por política energética.

## Criterios de aceptación

Aceptar cambio de perfil y cierre anormal de app sin configuración persistente inesperada.

## Rendimiento y evidencia

Medir energía, latencia y temperatura.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [145 — Gaming Driver Profile](145_W4_OS_GAMING_DRIVER_PROFILE.md)
- [196 — Power Management](196_W4_OS_POWER_MANAGEMENT.md)
- [291 — Performance Architecture](291_W4_OS_PERFORMANCE_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Equilibrado de referencia V1; otros perfiles después de demostrar beneficio.

---

[Anterior](199_W4_OS_THERMAL_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](201_W4_OS_TELEMETRY_ARCHITECTURE.md)
