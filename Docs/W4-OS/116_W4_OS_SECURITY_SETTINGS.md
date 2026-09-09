# 116 · W4 OS — Security Settings

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Centro de control · **Responsabilidad propuesta:** Configuración y experiencia  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mostrar controles de seguridad con estado verificable y lenguaje concreto.

## Alcance, arquitectura y decisiones

Agrupar cifrado, bloqueo, firewall, actualizaciones y permisos; el panel muestra evidencia y limitaciones, sin un sello universal de equipo seguro.

## Componentes y flujo operativo

1. Consultar controles
2. explicar estado y consecuencia
3. dirigir cambio a servicio autorizado
4. verificar
5. registrar resultado.

## Seguridad y riesgos

No ofrecer un único interruptor que desactive todas las protecciones. No inferir cifrado activo sólo porque existe un paquete instalado.

## Criterios de aceptación

Aceptar detección de volumen sin cifrar y firewall desactivado con corrección específica.

## Rendimiento y evidencia

Medir estados desconocidos y falsa confianza.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [156 — Security Architecture](156_W4_OS_SECURITY_ARCHITECTURE.md)
- [157 — Security Baseline](157_W4_OS_SECURITY_BASELINE.md)
- [365 — Health Score System](365_W4_OS_HEALTH_SCORE_SYSTEM.md)

## Roadmap y condiciones de evolución

Panel de estado V1; recomendaciones basadas en evidencia después del piloto.

---

[Anterior](115_W4_OS_USER_SETTINGS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](117_W4_OS_UPDATE_SETTINGS.md)
