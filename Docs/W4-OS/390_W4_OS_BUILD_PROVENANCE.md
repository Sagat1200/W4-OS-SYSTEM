# 390 · W4 OS — Build Provenance

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Suministro verificable · **Responsabilidad propuesta:** Seguridad de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Registrar procedencia verificable de compilación sin exponer secretos del entorno.

## Alcance, arquitectura y decisiones

Attestation propuesta enlaza sujeto/hash, fuentes, builder, materiales fijados y parámetros relevantes; formato se elige por consumidores. Firma y custodia separadas del trabajo no confiable.

## Componentes y flujo operativo

1. Capturar materiales
2. ejecutar build
3. generar declaración
4. comprobar coherencia
5. firmar con autoridad adecuada
6. adjuntar a release.

## Seguridad y riesgos

No aceptar declaración autoafirmada del paquete como prueba suficiente. Eliminar tokens y variables sensibles de metadatos.

## Criterios de aceptación

Aceptar artefacto cambiado que no coincide con sujeto y build trazable a commit exacto.

## Rendimiento y evidencia

Medir materiales no fijados.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [063 — Build Workers](063_W4_OS_BUILD_WORKERS.md)
- [070 — Release Artifact System](070_W4_OS_RELEASE_ARTIFACT_SYSTEM.md)
- [169 — Supply Chain Security](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
- [388 — SBOM Strategy](388_W4_OS_SBOM_STRATEGY.md)

## Roadmap y condiciones de evolución

Procedencia básica V1; niveles de garantía avanzados sólo con evaluación formal.

---

[Anterior](389_W4_OS_REPRODUCIBLE_BUILDS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](391_W4_OS_UPSTREAM_SYNC_PROCESS.md)
