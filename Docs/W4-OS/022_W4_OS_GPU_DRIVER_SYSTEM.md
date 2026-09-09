# 022 · W4 OS — GPU Driver System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Kernel y hardware · **Responsabilidad propuesta:** Plataforma y habilitación de hardware  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Dar soporte gráfico sin mezclar versiones incompatibles de kernel y espacio de usuario.

## Alcance, arquitectura y decisiones

Mantener Mesa y módulos de la base como ruta general; paquetes propietarios sólo con compatibilidad, licencia y Secure Boot comprobados. Registrar GPU híbrida y renderizador activo.

## Componentes y flujo operativo

1. Detectar GPU
2. resolver perfil
3. comprobar módulo
4. iniciar sesión
5. ejecutar renderizado y suspensión; recuperar modo gráfico básico si falla.

## Seguridad y riesgos

DKMS y claves de módulos necesitan tratamiento explícito. No desactivar Secure Boot silenciosamente para instalar un controlador.

## Criterios de aceptación

Aceptar monitor externo, vídeo, suspensión y cambio de GPU donde esté soportado; comparar estabilidad y fotogramas con la base aprobada.

## Rendimiento y evidencia

Registrar frame time, estabilidad del compositor y consumo con cada perfil gráfico certificado; comparar también vídeo y suspensión, no sólo un benchmark de renderizado.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [019 — Hardware Enablement Layer](019_W4_OS_HARDWARE_ENABLEMENT_LAYER.md)
- [095 — Wayland Architecture](095_W4_OS_WAYLAND_ARCHITECTURE.md)
- [145 — Gaming Driver Profile](145_W4_OS_GAMING_DRIVER_PROFILE.md)
- [161 — Secure Boot](161_W4_OS_SECURE_BOOT.md)

## Roadmap y condiciones de evolución

Certificar familias prioritarias; otras quedan con nivel de soporte visible.

---

[Anterior](021_W4_OS_DRIVER_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](023_W4_OS_HARDWARE_DETECTION_SYSTEM.md)
