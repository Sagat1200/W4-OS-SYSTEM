# 388 · W4 OS — SBOM Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Suministro verificable · **Responsabilidad propuesta:** Seguridad de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Generar inventarios de componentes asociados a cada artefacto distribuido.

## Alcance, arquitectura y decisiones

Seleccionar formato SPDX o CycloneDX por interoperabilidad, versión fijada; incluir paquetes, versiones, origen, licencias y relaciones disponibles. Campos desconocidos se marcan, no se inventan.

## Componentes y flujo operativo

1. Extraer del build
2. reconciliar con imagen
3. validar esquema
4. asociar hash
5. publicar
6. usar en análisis de vulnerabilidades.

## Seguridad y riesgos

SBOM no demuestra ausencia de vulnerabilidades ni autenticidad por sí sola. Evitar incluir rutas internas sensibles o secretos.

## Criterios de aceptación

Aceptar lista que cubre todos los paquetes instalados y referencia exacta de imagen.

## Rendimiento y evidencia

Medir cobertura, campos desconocidos y frescura.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [010 — System Components](010_W4_OS_SYSTEM_COMPONENTS.md)
- [070 — Release Artifact System](070_W4_OS_RELEASE_ARTIFACT_SYSTEM.md)
- [169 — Supply Chain Security](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
- [387 — Secure Update Supply Chain](387_W4_OS_SECURE_UPDATE_SUPPLY_CHAIN.md)

## Roadmap y condiciones de evolución

SBOM mínima desde V1; ampliar dependencias embebidas progresivamente.

## Cobertura y calidad del inventario

La SBOM de una imagen comienza por el inventario instalado, pero debe distinguir dependencias empaquetadas de bibliotecas embebidas. Un ejecutable que incorpora código estático puede necesitar análisis adicional; no se declara cobertura completa sólo porque el gestor de paquetes enumera todos los `.deb`.

| Validación | Resultado esperado |
|---|---|
| Esquema seleccionado y versión | Documento válido para consumidor previsto |
| Identidad de artefacto | Hash coincide con imagen o paquete publicado |
| Paquetes instalados | Cada entrada del manifiesto tiene correspondencia |
| Licencias desconocidas | Marcadas y asignadas a revisión, no inventadas |
| Relación fuente/binario | Trazable mediante procedencia y archivo de fuente |
| Dependencias embebidas | Cobertura declarada y brechas registradas |

Un scanner consume la SBOM y produce candidatos de afectación; el equipo debe contrastar versiones Debian completas, parches y uso real. La ausencia de coincidencia no demuestra ausencia de vulnerabilidad. La SBOM acompaña un artefacto inmutable: si se recompila o cambia contenido, se genera y vincula una nueva declaración.

---

[Anterior](387_W4_OS_SECURE_UPDATE_SUPPLY_CHAIN.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](389_W4_OS_REPRODUCIBLE_BUILDS.md)
