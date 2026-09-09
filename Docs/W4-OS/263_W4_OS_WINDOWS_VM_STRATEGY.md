# 263 · W4 OS — Windows Vm Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Compatibilidad Windows · **Responsabilidad propuesta:** Interoperabilidad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ejecutar Windows en VM cuando sea necesario y legalmente disponible.

## Alcance, arquitectura y decisiones

Usuario u organización aporta medio y licencia válidos; VM usa recursos limitados, firmware y TPM virtual si el invitado lo requiere. No incluir activación ni claves en W4.

## Componentes y flujo operativo

1. Comprobar recursos
2. crear VM
3. instalar desde medio autorizado
4. integrar carpeta compartida elegida
5. respaldar
6. actualizar invitado.

## Seguridad y riesgos

Windows conserva su propio ciclo de seguridad. Portapapeles y carpetas compartidas amplían exposición; habilitarlos por necesidad.

## Criterios de aceptación

Aceptar aplicación objetivo, red y recuperación de VM con límites medidos; registrar consumo y requisitos del invitado.

## Rendimiento y evidencia

Registrar memoria y CPU asignadas, tiempo de tarea objetivo y presión sobre el host; los resultados pertenecen al invitado, aplicación y hardware ensayados.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [256 — Virtualization Architecture](256_W4_OS_VIRTUALIZATION_ARCHITECTURE.md)
- [257 — KVM QEMU Integration](257_W4_OS_KVM_QEMU_INTEGRATION.md)
- [261 — Windows Compatibility Strategy](261_W4_OS_WINDOWS_COMPATIBILITY_STRATEGY.md)

## Roadmap y condiciones de evolución

Opcional posterior al escritorio; sin promesa de gaming o GPU passthrough.

---

[Anterior](262_W4_OS_WINE_INTEGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](264_W4_OS_CROSS_PLATFORM_APPLICATION_SUPPORT.md)
