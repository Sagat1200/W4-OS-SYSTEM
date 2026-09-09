# 132 · W4 OS — Browser Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Mantener un navegador con correcciones oportunas y políticas empresariales comprobadas.

## Alcance, arquitectura y decisiones

Propuesta inicial Firefox ESR desde la base Debian cuando su cobertura resulte adecuada; alternativa requiere ADR. Perfil W4 contiene favoritos y defaults mínimos, sin extensiones invasivas.

## Componentes y flujo operativo

1. Instalar
2. crear perfil limpio
3. importar datos opcionalmente
4. actualizar por canal soportado
5. comprobar navegación y políticas.

## Seguridad y riesgos

No desactivar aislamiento ni validación TLS para compatibilidad. Extensiones empresariales se autorizan por identificador y origen; secretos no van en imagen.

## Criterios de aceptación

Aceptar navegación, videollamada, descarga y actualización conservando perfil.

## Rendimiento y evidencia

Medir arranque frío, memoria y demora de correcciones.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [131 — Default Applications](131_W4_OS_DEFAULT_APPLICATIONS.md)
- [165 — Security Update Policy](165_W4_OS_SECURITY_UPDATE_POLICY.md)
- [345 — User Data Migration](345_W4_OS_USER_DATA_MIGRATION.md)

## Roadmap y condiciones de evolución

Evaluar candidato en V1 y revisar soporte durante el ciclo.

---

[Anterior](131_W4_OS_DEFAULT_APPLICATIONS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](133_W4_OS_OFFICE_SUITE_STRATEGY.md)
