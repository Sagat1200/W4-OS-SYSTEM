# 360 · W4 OS — Enterprise Compliance Profiles

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Alineación de controles · **Responsabilidad propuesta:** Seguridad y cumplimiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Empaquetar perfiles empresariales de cumplimiento con alcance y excepciones.

## Alcance, arquitectura y decisiones

Perfil versionado contiene controles técnicos, evidencia requerida, requisitos externos y limitaciones; no incluye texto normativo no autorizado. Organización elige alcance pertinente.

## Componentes y flujo operativo

1. Seleccionar perfil
2. evaluar brechas
3. simular cambios
4. aplicar autorizados
5. verificar
6. registrar excepciones
7. reevaluar.

## Seguridad y riesgos

No imponer controles que destruyan datos o dejen equipo inaccesible sin plan. No afirmar cumplimiento legal universal al activar un perfil.

## Criterios de aceptación

Aceptar controles efectivos, desconocidos y exceptuados diferenciados con versión visible.

## Rendimiento y evidencia

Medir deriva y excepciones vencidas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [174 — Business Security Profile](174_W4_OS_BUSINESS_SECURITY_PROFILE.md)
- [228 — Compliance System](228_W4_OS_COMPLIANCE_SYSTEM.md)
- [356 — Compliance Architecture](356_W4_OS_COMPLIANCE_ARCHITECTURE.md)
- [359 — CIS Benchmark Strategy](359_W4_OS_CIS_BENCHMARK_STRATEGY.md)

## Roadmap y condiciones de evolución

Pilotos específicos después de baseline Business estable.

---

[Anterior](359_W4_OS_CIS_BENCHMARK_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](361_W4_OS_OBSERVABILITY_ARCHITECTURE.md)
