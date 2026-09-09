# 180 · W4 OS — Bluetooth System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Red · **Responsabilidad propuesta:** Integración de conectividad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Integrar Bluetooth para audio y entrada con emparejamiento controlado.

## Alcance, arquitectura y decisiones

Proponer BlueZ y frontend del escritorio; registrar dispositivos por usuario o sistema según necesidad. Descubribilidad se limita a la ventana de emparejamiento.

## Componentes y flujo operativo

1. Activar búsqueda
2. seleccionar dispositivo
3. verificar código cuando aplique
4. emparejar
5. conectar perfil
6. permitir olvidar.

## Seguridad y riesgos

No aceptar emparejamiento no solicitado ni mantener equipo visible indefinidamente. Identificadores de dispositivos son datos personales potenciales.

## Criterios de aceptación

Aceptar auriculares y mando de referencia, pérdida de enlace y suspensión.

## Rendimiento y evidencia

Medir latencia, autonomía y reconexión.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [136 — Multimedia System](136_W4_OS_MULTIMEDIA_SYSTEM.md)
- [144 — Gamepad Support](144_W4_OS_GAMEPAD_SUPPORT.md)
- [178 — Wi-Fi System](178_W4_OS_WIFI_SYSTEM.md)

## Roadmap y condiciones de evolución

Perfiles comunes V1; funciones especiales sólo por matriz de dispositivos.

## Gestión de confianza de dispositivos

Emparejar y conectar son estados diferentes. Olvidar un dispositivo elimina la relación de confianza local pertinente y exige nuevo emparejamiento para volver a usarla; no promete borrar información almacenada dentro del periférico. Los auriculares pueden cambiar entre perfiles con distinta calidad y disponibilidad de micrófono, por lo que la UI muestra el perfil activo cuando sea útil. El ensayo de videollamada verifica entrada y salida simultáneas y comprueba que desconectar auriculares devuelve audio a un destino previsto, sin activar un micrófono inesperado.

---

[Anterior](179_W4_OS_ETHERNET_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](181_W4_OS_VPN_ARCHITECTURE.md)
