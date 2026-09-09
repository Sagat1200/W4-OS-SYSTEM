# 387 · W4 OS — Secure Update Supply Chain

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Suministro verificable · **Responsabilidad propuesta:** Seguridad de construcción  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Cerrar la cadena de confianza de actualizaciones desde revisión hasta cliente.

## Alcance, arquitectura y decisiones

Manifiesto enlaza fuente, build, pruebas, artefacto, firma y asignación de canal; el cliente verifica autenticidad y compatibilidad antes de aplicar. Controles separados evitan autopromoción del worker.

## Componentes y flujo operativo

1. Revisar
2. construir
3. adjuntar SBOM/procedencia
4. calificar
5. autorizar
6. firmar
7. publicar
8. verificar
9. observar.

## Seguridad y riesgos

Mitigar sustitución, replay de metadatos y mezcla de releases; rotación de confianza es proceso propio. HTTPS no sustituye firma del archivo.

## Criterios de aceptación

Aceptar artefacto sin procedencia requerida o índice alterado bloqueado antes de instalación.

## Rendimiento y evidencia

Medir eslabones sin evidencia.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [047 — Package Signing System](047_W4_OS_PACKAGE_SIGNING_SYSTEM.md)
- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [169 — Supply Chain Security](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
- [388 — SBOM Strategy](388_W4_OS_SBOM_STRATEGY.md)
- [390 — Build Provenance](390_W4_OS_BUILD_PROVENANCE.md)

## Roadmap y condiciones de evolución

Controles mínimos V1; endurecimiento de claves antes de escala pública.

---

[Anterior](386_W4_OS_FILESYSTEM_RECOVERY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](388_W4_OS_SBOM_STRATEGY.md)
