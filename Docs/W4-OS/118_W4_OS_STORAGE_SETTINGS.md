# 118 · W4 OS — Storage Settings

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Centro de control · **Responsabilidad propuesta:** Configuración y experiencia  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Hacer visible el espacio de datos, cachés y snapshots antes de ofrecer limpieza.

## Alcance, arquitectura y decisiones

Panel usa categorías medibles y distingue espacio lógico, compartido y recuperable. Acciones delegan a gestores de almacenamiento y snapshots.

## Componentes y flujo operativo

1. Analizar
2. mostrar candidatos concretos
3. previsualizar cantidad y consecuencia
4. limpiar autorizados
5. recalcular.

## Seguridad y riesgos

No sumar tamaños de snapshots como si fueran copias completas ni borrar último estado bueno para cumplir una cifra de ahorro.

## Criterios de aceptación

Aceptar disco lleno con recomendaciones que conservan datos personales y recuperación.

## Rendimiento y evidencia

Medir tiempo de análisis y error de estimación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [081 — Snapshot Architecture](081_W4_OS_SNAPSHOT_ARCHITECTURE.md)
- [084 — Automatic Snapshot Policy](084_W4_OS_AUTOMATIC_SNAPSHOT_POLICY.md)
- [186 — Storage Architecture](186_W4_OS_STORAGE_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Vista básica V1; cuotas y análisis avanzado después de validar métricas.

---

[Anterior](117_W4_OS_UPDATE_SETTINGS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](119_W4_OS_APPLICATION_SETTINGS.md)
