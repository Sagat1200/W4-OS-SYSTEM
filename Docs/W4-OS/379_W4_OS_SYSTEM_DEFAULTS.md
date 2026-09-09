# 379 · W4 OS — System Defaults

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Configuración y políticas · **Responsabilidad propuesta:** Configuración de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Distribuir valores iniciales sin sobrescribir elecciones del usuario.

## Alcance, arquitectura y decisiones

Defaults empaquetados se separan de configuración mutable; cada clave declara si sólo se aplica al crear perfil o permanece como fallback. Cambios de default necesitan impacto documentado.

## Componentes y flujo operativo

1. Instalar
2. resolver ausencia de valor
3. aplicar default
4. registrar preferencia si usuario cambia
5. actualizar conservando elección.

## Seguridad y riesgos

No reactivar telemetría ni acceso remoto mediante nuevos defaults. Controles obligatorios se modelan como política, no como preferencia escondida.

## Criterios de aceptación

Aceptar actualización con usuario personalizado y usuario nuevo recibiendo resultados correctos.

## Rendimiento y evidencia

Medir cambios inesperados.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [100 — Desktop Configuration](100_W4_OS_DESKTOP_CONFIGURATION.md)
- [157 — Security Baseline](157_W4_OS_SECURITY_BASELINE.md)
- [377 — Configuration Architecture](377_W4_OS_CONFIGURATION_ARCHITECTURE.md)
- [380 — Configuration Layering](380_W4_OS_CONFIGURATION_LAYERING.md)

## Roadmap y condiciones de evolución

Defaults mínimos V1; revisión por release de cambios sensibles.

---

[Anterior](378_W4_OS_CONFIGURATION_SCHEMA.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](380_W4_OS_CONFIGURATION_LAYERING.md)
