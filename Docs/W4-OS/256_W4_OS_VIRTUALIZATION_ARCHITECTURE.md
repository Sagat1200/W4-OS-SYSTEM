# 256 · W4 OS — Virtualization Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Desarrollo · **Responsabilidad propuesta:** Plataforma de desarrollo  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ofrecer virtualización local con recursos y datos del host protegidos.

## Alcance, arquitectura y decisiones

KVM/QEMU/libvirt propuestos cuando hardware lo soporte; interfaces de usuario sin privilegio global. VMs tienen disco, red, firmware y snapshots independientes del rollback del host.

## Componentes y flujo operativo

1. Comprobar capacidad
2. crear VM
3. asignar límites
4. instalar invitado
5. respaldar
6. detener y retirar con elección de datos.

## Seguridad y riesgos

No prometer aislamiento absoluto ni snapshots consistentes de bases de datos sin coordinación. Passthrough requiere evaluación adicional.

## Criterios de aceptación

Aceptar VM que arranca y se detiene sin afectar host ni consumir recursos ilimitados.

## Rendimiento y evidencia

Medir sobrecarga y presión de memoria.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [257 — KVM QEMU Integration](257_W4_OS_KVM_QEMU_INTEGRATION.md)
- [263 — Windows Vm Strategy](263_W4_OS_WINDOWS_VM_STRATEGY.md)
- [293 — Memory Management](293_W4_OS_MEMORY_MANAGEMENT.md)

## Roadmap y condiciones de evolución

VM básica opcional V1; passthrough fuera del alcance inicial.

---

[Anterior](255_W4_OS_PODMAN_DOCKER_SUPPORT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](257_W4_OS_KVM_QEMU_INTEGRATION.md)
