# 117 · W4 OS — Update Settings

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Centro de control · **Responsabilidad propuesta:** Configuración y experiencia  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Permitir programar actualizaciones manteniendo visible quién controla cada decisión.

## Alcance, arquitectura y decisiones

Mostrar canal, versión, reinicio pendiente, ventana y política efectiva. Home puede posponer dentro de límites propuestos; Business recibe restricciones documentadas.

## Componentes y flujo operativo

1. Consultar plan
2. ajustar ventana permitida
3. preparar
4. notificar antes de reiniciar
5. aplicar
6. presentar resultado.

## Seguridad y riesgos

Una preferencia no desactiva autenticación de paquetes ni oculta actualización fallida. No reiniciar silenciosamente con trabajo abierto.

## Criterios de aceptación

Aceptar aplazamiento, política obligatoria y fallo de staging con mensajes diferentes.

## Rendimiento y evidencia

Medir reinicios inesperados y planes vencidos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [078 — Update Rings](078_W4_OS_UPDATE_RINGS.md)
- [120 — Business Policy Settings](120_W4_OS_BUSINESS_POLICY_SETTINGS.md)

## Roadmap y condiciones de evolución

Programación local V1; ventanas de flota tras integrar gestión.

## Explicación de estados al usuario

La vista distingue disponible, descargando, listo para reiniciar, aplicando, pendiente de comprobación y fallido. Un reinicio pendiente indica qué operación lo necesita y quién fijó su ventana. Si una política impide aplazar, se explica su procedencia; no se presenta el control como averiado. El historial muestra versión de destino y resultado, pero no expone URLs con credenciales. El ensayo de interfaz incluye servicio reiniciado durante descarga para comprobar que el progreso proviene del registro durable y no de un contador local de la ventana.

---

[Anterior](116_W4_OS_SECURITY_SETTINGS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](118_W4_OS_STORAGE_SETTINGS.md)
