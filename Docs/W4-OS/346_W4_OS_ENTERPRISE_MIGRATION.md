# 346 · W4 OS — Enterprise Migration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Migración · **Responsabilidad propuesta:** Migración y compatibilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Migrar organizaciones por pilotos y cohortes con continuidad del trabajo.

## Alcance, arquitectura y decisiones

Plan incluye aplicaciones críticas, identidad, red, hardware, soporte y reversión. Cohortes por función y compatibilidad, no sólo porcentaje arbitrario.

## Componentes y flujo operativo

1. Descubrir
2. probar piloto
3. resolver bloqueos
4. capacitar
5. respaldar
6. migrar lote
7. verificar
8. ampliar o pausar.

## Seguridad y riesgos

No migrar equipos críticos sin plan de retorno y ventana acordada. Datos empresariales se copian por canales y destinos autorizados.

## Criterios de aceptación

Aceptar piloto que completa jornada de tareas y retorno ensayado.

## Rendimiento y evidencia

Medir productividad, incidentes y tiempo por equipo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [040 — Unattended Installation](040_W4_OS_UNATTENDED_INSTALLATION.md)
- [221 — Fleet Management](221_W4_OS_FLEET_MANAGEMENT.md)
- [264 — Cross Platform Application Support](264_W4_OS_CROSS_PLATFORM_APPLICATION_SUPPORT.md)
- [348 — Business Continuity](348_W4_OS_BUSINESS_CONTINUITY.md)

## Roadmap y condiciones de evolución

Después de Business calificado; expansión condicionada a evidencia del piloto.

---

[Anterior](345_W4_OS_USER_DATA_MIGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](347_W4_OS_DISASTER_RECOVERY.md)
