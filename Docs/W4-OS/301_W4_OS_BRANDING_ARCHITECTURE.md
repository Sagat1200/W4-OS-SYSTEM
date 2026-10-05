# 301 · W4 OS — Branding Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Identidad y nombres · **Responsabilidad propuesta:** Experiencia e identidad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Aplicar marca W4 de forma consistente sin alterar atribuciones técnicas o legales.

## Alcance, arquitectura y decisiones

Recursos de identidad se distribuyen en paquetes separados de componentes funcionales. Marca abarca instalador, arranque, escritorio y documentación; no justifica forks de infraestructura. El aterrizaje actual de `MX-007` para `Home V1` ya baja este principio a una politica de branding reversible sobre `GNOME + GDM`, separando base comun, branding de `Home` y limites explicitos para login, tema, iconos y defaults de escritorio.

## Componentes y flujo operativo

1. Definir recursos
2. comprobar licencias
3. empaquetar
4. integrar por interfaces de tema
5. verificar pantallas y fallback.

## Seguridad y riesgos

No sustituir avisos de autoría upstream ni usar logos de terceros como aval. Los recursos no contienen código privilegiado innecesario.

## Criterios de aceptación

Aceptar imagen con identidad coherente y atribuciones preservadas.

## Rendimiento y evidencia

Medir peso de recursos y costo de mantenimiento.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [302 — Visual Identity](302_W4_OS_VISUAL_IDENTITY.md)
- [303 — Boot Branding](303_W4_OS_BOOT_BRANDING.md)
- [304 — Installer Branding](304_W4_OS_INSTALLER_BRANDING.md)
- [305 — Desktop Branding](305_W4_OS_DESKTOP_BRANDING.md)

## Roadmap y condiciones de evolución

Marca mínima V1; ampliar sólo sobre funciones estables.

---

[Anterior](300_W4_OS_PERFORMANCE_BUDGETS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](302_W4_OS_VISUAL_IDENTITY.md)
