# 177 · W4 OS — NetworkManager Integration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Red · **Responsabilidad propuesta:** Integración de conectividad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Usar la API de NetworkManager como fuente de verdad para conexiones.

## Alcance, arquitectura y decisiones

Perfiles persistentes pertenecen a usuario o sistema según alcance; secretos se delegan a agente autorizado. W4 no edita simultáneamente archivos detrás del servicio.

## Componentes y flujo operativo

1. Crear perfil tipado
2. validar
3. activar
4. observar estado
5. confirmar
6. persistir; usar checkpoint o reversión equivalente para cambios riesgosos.

## Seguridad y riesgos

Una app no autorizada no puede cambiar perfiles globales ni extraer contraseñas. Política Business restringe modificaciones por acción.

## Criterios de aceptación

Aceptar activación fallida con retorno al perfil anterior y estado UI consistente.

## Rendimiento y evidencia

Medir latencia y eventos duplicados.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [114 — Network Settings](114_W4_OS_NETWORK_SETTINGS.md)
- [176 — Network Architecture](176_W4_OS_NETWORK_ARCHITECTURE.md)
- [367 — System Service API](367_W4_OS_SYSTEM_SERVICE_API.md)

## Roadmap y condiciones de evolución

Integración esencial V1; operaciones remotas sólo con rollback de red probado.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [NetworkManager — Reference Manual](https://www.networkmanager.dev/docs/api/latest/). Referencia de API y capacidades. La documentación latest puede diferir de la versión de Debian: comprobar capacidades al congelar release.

---

[Anterior](176_W4_OS_NETWORK_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](178_W4_OS_WIFI_SYSTEM.md)
