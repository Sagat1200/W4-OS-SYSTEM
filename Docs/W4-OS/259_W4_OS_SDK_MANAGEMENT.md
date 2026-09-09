# 259 · W4 OS — SDK Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Desarrollo · **Responsabilidad propuesta:** Plataforma de desarrollo  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener SDKs por proyecto con versiones, origen y fin de soporte identificables.

## Alcance, arquitectura y decisiones

Registro de SDK incluye lenguaje, versión, checksum o firma disponible, licencia y alcance. Herramientas host permanecen de Debian; SDKs alternativos no reemplazan /usr.

## Componentes y flujo operativo

1. Seleccionar versión
2. descargar de origen permitido
3. verificar
4. instalar en espacio definido
5. activar por proyecto
6. retirar si no usado.

## Seguridad y riesgos

No ejecutar instalador con root por conveniencia. SDK obsoleto se señala sin borrarlo automáticamente de un proyecto histórico.

## Criterios de aceptación

Aceptar proyecto que fija versión y otro que usa nueva sin conflicto.

## Rendimiento y evidencia

Medir espacio compartido y versiones sin soporte.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [253 — Development Toolchain](253_W4_OS_DEVELOPMENT_TOOLCHAIN.md)
- [258 — Development Environments](258_W4_OS_DEVELOPMENT_ENVIRONMENTS.md)
- [274 — End Of Life Policy](274_W4_OS_END_OF_LIFE_POLICY.md)

## Roadmap y condiciones de evolución

SDKs de referencia opcionales; catálogo ampliado según demanda.

---

[Anterior](258_W4_OS_DEVELOPMENT_ENVIRONMENTS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](260_W4_OS_DEVBOX_STRATEGY.md)
