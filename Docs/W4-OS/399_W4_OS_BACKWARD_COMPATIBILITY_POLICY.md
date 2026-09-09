# 399 · W4 OS — Backward Compatibility Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Evolución y mantenimiento · **Responsabilidad propuesta:** Mantenimiento de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir compatibilidad de APIs, configuración, paquetes y datos por contrato.

## Alcance, arquitectura y decisiones

Cada interfaz declara versiones admitidas y ventana de deprecación; compatibilidad binaria Debian no cubre automáticamente servicios W4. Datos persistentes tienen reglas propias de lectura/escritura.

## Componentes y flujo operativo

1. Proponer cambio
2. identificar consumidores
3. probar versiones soportadas
4. ofrecer adaptador o migración
5. anunciar retirada
6. eliminar después de ventana.

## Seguridad y riesgos

No conservar indefinidamente rutas inseguras por compatibilidad. Excepciones de ruptura por seguridad se documentan y coordinan.

## Criterios de aceptación

Aceptar cliente anterior soportado que sigue operando y versión no admitida rechazada con guía.

## Rendimiento y evidencia

Medir consumidores pendientes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [266 — Versioning Policy](266_W4_OS_VERSIONING_POLICY.md)
- [366 — API Architecture](366_W4_OS_API_ARCHITECTURE.md)
- [377 — Configuration Architecture](377_W4_OS_CONFIGURATION_ARCHITECTURE.md)
- [404 — Deprecation Policy](404_W4_OS_DEPRECATION_POLICY.md)

## Roadmap y condiciones de evolución

Contratos mínimos V1; ventanas concretas al existir clientes y capacidad de soporte.

---

[Anterior](398_W4_OS_MAJOR_VERSION_MIGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](400_W4_OS_LONG_TERM_MAINTENANCE.md)
