# 144 · W4 OS — Gamepad Support

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Gaming · **Responsabilidad propuesta:** Compatibilidad de aplicaciones y gráficos  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Soportar mandos mediante mecanismos del kernel y mapeos mantenidos.

## Alcance, arquitectura y decisiones

Inventario por USB/Bluetooth, tipo de entrada, vibración y batería cuando estén disponibles. Reutilizar bases de mapeo de plataformas elegidas.

## Componentes y flujo operativo

1. Conectar
2. identificar
3. aplicar mapeo
4. probar ejes/botones
5. reconectar después de suspensión.

## Seguridad y riesgos

No otorgar acceso indiscriminado a dispositivos de entrada de otros usuarios. Los mapeos externos se validan como datos.

## Criterios de aceptación

Aceptar dos mandos, reconexión y calibración en modelos probados.

## Rendimiento y evidencia

Medir latencia y pérdidas de conexión.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [023 — Hardware Detection System](023_W4_OS_HARDWARE_DETECTION_SYSTEM.md)
- [180 — Bluetooth System](180_W4_OS_BLUETOOTH_SYSTEM.md)
- [141 — Gaming Architecture](141_W4_OS_GAMING_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Mandos de referencia en V1 Home; ampliar por pruebas comunitarias verificadas.

## Matriz funcional por mando

Registrar transporte, botones, ejes, gatillos, vibración y batería como capacidades independientes. Un mando puede ser apto para entrada y carecer de indicador de carga; la UI no inventa un porcentaje. Las pruebas incluyen dos dispositivos del mismo modelo para detectar asignación inestable de jugador, además de suspensión y reconexión. Los permisos deben permitir el uso en la sesión activa sin dar lectura de todo dispositivo de entrada a cualquier proceso. Mapeos personalizados se conservan por usuario y pueden restablecerse sin reinstalar el controlador.

---

[Anterior](143_W4_OS_PROTON_INTEGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](145_W4_OS_GAMING_DRIVER_PROFILE.md)
