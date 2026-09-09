# 257 · W4 OS — KVM QEMU Integration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Desarrollo · **Responsabilidad propuesta:** Plataforma de desarrollo  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Integrar KVM/QEMU con configuración mantenida y permisos mínimos.

## Alcance, arquitectura y decisiones

Usar paquetes Debian y libvirt según modo elegido; imágenes de disco en directorios con permisos definidos. Red NAT como propuesta inicial, bridge administrado por excepción.

## Componentes y flujo operativo

1. Verificar virtualización
2. definir invitado
3. arrancar
4. comprobar red
5. apagar
6. validar disco y recursos liberados.

## Seguridad y riesgos

No conceder acceso completo a dispositivos del host por defecto. ISOs de invitados provienen del usuario o proveedor autorizado.

## Criterios de aceptación

Aceptar invitado aislado de archivos del host y arranque tras actualización W4.

## Rendimiento y evidencia

Medir CPU, E/S y memoria.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [256 — Virtualization Architecture](256_W4_OS_VIRTUALIZATION_ARCHITECTURE.md)
- [179 — Ethernet System](179_W4_OS_ETHERNET_SYSTEM.md)
- [186 — Storage Architecture](186_W4_OS_STORAGE_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Matriz de invitados reducida V1; redes avanzadas después de pruebas.

---

[Anterior](256_W4_OS_VIRTUALIZATION_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](258_W4_OS_DEVELOPMENT_ENVIRONMENTS.md)
