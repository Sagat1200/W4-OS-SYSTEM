# 239 · W4 OS — Home Media Profile

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Experiencia Home · **Responsabilidad propuesta:** Producto Home  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Preparar consumo y creación multimedia ligera sin sobrecargar el perfil básico.

## Alcance, arquitectura y decisiones

Reproductor, audio, captura autorizada y dispositivos frecuentes; edición pesada es opcional. El perfil usa la misma pila multimedia del sistema.

## Componentes y flujo operativo

1. Conectar auriculares
2. reproducir formato permitido
3. seleccionar entrada
4. realizar llamada
5. recuperar tras suspensión.

## Seguridad y riesgos

La cámara y el micrófono no se activan por onboarding. Plugins y codecs siguen catálogo de origen verificado.

## Criterios de aceptación

Aceptar reproducción y videollamada en equipos de referencia sin cortes persistentes.

## Rendimiento y evidencia

Medir CPU y sincronía audiovisual.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [136 — Multimedia System](136_W4_OS_MULTIMEDIA_SYSTEM.md)
- [137 — Media Codec Strategy](137_W4_OS_MEDIA_CODEC_STRATEGY.md)
- [180 — Bluetooth System](180_W4_OS_BLUETOOTH_SYSTEM.md)

## Roadmap y condiciones de evolución

Consumo multimedia V1; herramientas creativas a demanda.

## Perfil de dispositivos de medios

Guardar selección de salida, entrada y permisos por usuario, respetando dispositivos disponibles. El sistema no deduce permiso de cámara por haber conectado un dispositivo USB. El corpus de aceptación incluye un vídeo local, una llamada de prueba y una reproducción con subtítulos, con formatos anunciados en el catálogo. La pérdida de aceleración se identifica como degradación de rendimiento y no se oculta con una promesa de soporte completo. Nuevos codecs o herramientas de edición se evalúan fuera del perfil mínimo.

---

[Anterior](238_W4_OS_HOME_GAMING_PROFILE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](240_W4_OS_HOME_PRIVACY_PROFILE.md)
