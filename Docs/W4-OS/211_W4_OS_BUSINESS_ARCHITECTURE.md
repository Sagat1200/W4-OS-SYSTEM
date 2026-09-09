# 211 · W4 OS — Business Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir administración empresarial como servicios adicionales sobre la base común.

## Alcance, arquitectura y decisiones

Plano de control gestiona identidad de dispositivo, políticas, inventario y operaciones; agente local verifica y aplica por APIs limitadas. Portal de administración no es upstream del sistema.

## Componentes y flujo operativo

1. Inscribir
2. sincronizar política
3. evaluar estado
4. ejecutar operación autorizada
5. reportar
6. funcionar con última política válida sin red.

## Seguridad y riesgos

Separar organizaciones y roles; un administrador no accede automáticamente a archivos personales. Caída del plano de control no bloquea arranque local.

## Criterios de aceptación

Aceptar dos organizaciones aisladas y agente desconectado operativo.

## Rendimiento y evidencia

Medir convergencia y carga por dispositivo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [012 — Business Edition](012_W4_OS_BUSINESS_EDITION.md)
- [212 — Enterprise Device Management](212_W4_OS_ENTERPRISE_DEVICE_MANAGEMENT.md)
- [213 — Central Policy System](213_W4_OS_CENTRAL_POLICY_SYSTEM.md)
- [221 — Fleet Management](221_W4_OS_FLEET_MANAGEMENT.md)

## Roadmap y condiciones de evolución

Piloto con servicios mínimos; alta disponibilidad antes de SLA comercial.

---

[Anterior](210_W4_OS_SUPPORT_BUNDLE_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](212_W4_OS_ENTERPRISE_DEVICE_MANAGEMENT.md)
