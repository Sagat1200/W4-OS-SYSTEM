# 307 · W4 OS — Package Naming

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Identidad y nombres · **Responsabilidad propuesta:** Experiencia e identidad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Nombrar paquetes W4 sin colisionar con upstream ni crear alias ambiguos.

## Alcance, arquitectura y decisiones

Prefijo w4- para componentes propios y metapaquetes; conservar nombre upstream al aplicar delta cuando lo requiera semántica de upgrade, con versión W4 identificable.

## Componentes y flujo operativo

1. Proponer nombre
2. consultar colisiones
3. definir función
4. validar dependencias
5. publicar registro.

## Seguridad y riesgos

No usar nombres de paquetes conocidos para distribuir contenido no relacionado. Un cambio de nombre requiere transición y tratamiento de configuración.

## Criterios de aceptación

Aceptar upgrade de paquete renombrado con dependencias correctas y datos conservados.

## Rendimiento y evidencia

Medir colisiones y nombres sin dueño.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [042 — DEB Package Architecture](042_W4_OS_DEB_PACKAGE_ARCHITECTURE.md)
- [045 — Meta Package System](045_W4_OS_META_PACKAGE_SYSTEM.md)
- [266 — Versioning Policy](266_W4_OS_VERSIONING_POLICY.md)

## Roadmap y condiciones de evolución

Registro inicial V1 y revisión antes de cada paquete nuevo.

---

[Anterior](306_W4_OS_SYSTEM_NAMING.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](308_W4_OS_REPOSITORY_NAMING.md)
