# 356 · W4 OS — Compliance Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Alineación de controles · **Responsabilidad propuesta:** Seguridad y cumplimiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Relacionar controles W4 con marcos externos sin afirmar cumplimiento automático.

## Alcance, arquitectura y decisiones

Matriz control W4 → requisito externo → evidencia → responsable → brecha. El alcance incluye procesos organizacionales que un sistema operativo no puede resolver por sí solo.

## Componentes y flujo operativo

1. Seleccionar marco y versión
2. revisar derechos de uso
3. mapear
4. evaluar evidencia
5. tratar brechas
6. revisar formalmente.

## Seguridad y riesgos

No usar logos ni términos certificado/conforme sin evaluación aplicable. Una configuración técnica no sustituye gestión, personas y auditoría.

## Criterios de aceptación

Aceptar mapeo con brechas explícitas y evidencia verificable, sin casillas aprobadas por defecto.

## Rendimiento y evidencia

Medir cobertura real.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [228 — Compliance System](228_W4_OS_COMPLIANCE_SYSTEM.md)
- [357 — ISO 27001 Alignment](357_W4_OS_ISO_27001_ALIGNMENT.md)
- [358 — NIST Alignment](358_W4_OS_NIST_ALIGNMENT.md)
- [359 — CIS Benchmark Strategy](359_W4_OS_CIS_BENCHMARK_STRATEGY.md)

## Roadmap y condiciones de evolución

Alineación propuesta después de baseline; certificación requiere proyecto separado.

---

[Anterior](355_W4_OS_ENTERPRISE_PRIVACY_CONTROLS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](357_W4_OS_ISO_27001_ALIGNMENT.md)
