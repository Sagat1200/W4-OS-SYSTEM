# 334 · W4 OS — Security Support Policy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Soporte · **Responsabilidad propuesta:** Operación de soporte  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Publicar cobertura de correcciones de seguridad por línea y componente.

## Alcance, arquitectura y decisiones

Distinguir base Debian, cambios W4 y terceros; establecer objetivos internos y excepciones antes de compromisos contractuales. Mantener lista de paquetes sin cobertura completa.

## Componentes y flujo operativo

1. Detectar aviso
2. evaluar afectación
3. corregir/mitigar
4. comunicar
5. distribuir
6. verificar adopción
7. actualizar cobertura.

## Seguridad y riesgos

No prometer correcciones para software sin fuente o proveedor comprometido. EOL y componentes externos requieren avisos y alternativas.

## Criterios de aceptación

Aceptar aviso con versiones afectadas/corregidas y acción clara.

## Rendimiento y evidencia

Medir demora, cobertura y dispositivos pendientes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [165 — Security Update Policy](165_W4_OS_SECURITY_UPDATE_POLICY.md)
- [267 — LTS Strategy](267_W4_OS_LTS_STRATEGY.md)
- [326 — Security Response Team](326_W4_OS_SECURITY_RESPONSE_TEAM.md)
- [392 — Debian Security Sync](392_W4_OS_DEBIAN_SECURITY_SYNC.md)

## Roadmap y condiciones de evolución

Política antes de lanzamiento y revisión al cambiar soporte upstream.

---

[Anterior](333_W4_OS_REMOTE_SUPPORT_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](335_W4_OS_BUSINESS_MODEL.md)
