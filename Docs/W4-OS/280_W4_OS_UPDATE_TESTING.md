# 280 · W4 OS — Update Testing

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Pruebas y calificación · **Responsabilidad propuesta:** Calidad y validación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Probar actualizaciones con interrupciones y estados iniciales realistas.

## Alcance, arquitectura y decisiones

Matriz incluye disco casi lleno, red cortada, dpkg ocupado, configuración modificada y energía interrumpida en laboratorio. Validar desde cada versión soportada relevante.

## Componentes y flujo operativo

1. Preparar origen
2. guardar hashes y estado
3. iniciar actualización
4. inyectar fallo por etapa
5. reiniciar
6. evaluar salud y reparación.

## Seguridad y riesgos

No demostrar atomicidad con una única ejecución exitosa. Comprobar que la base dpkg y los binarios corresponden al mismo estado.

## Criterios de aceptación

Aceptar fallo en descarga y aplicación con rutas de salida documentadas.

## Rendimiento y evidencia

Medir tiempo offline y recuperación por etapa.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [072 — Update Engine](072_W4_OS_UPDATE_ENGINE.md)
- [075 — Update Staging System](075_W4_OS_UPDATE_STAGING_SYSTEM.md)
- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)

## Roadmap y condiciones de evolución

Puerta obligatoria V1; ampliar a generaciones experimentales después.

---

[Anterior](279_W4_OS_HARDWARE_TESTING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](281_W4_OS_ROLLBACK_TESTING.md)
