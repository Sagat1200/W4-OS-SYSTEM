# 127 · W4 OS — Application Store

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Aplicaciones · **Responsabilidad propuesta:** Integración de aplicaciones  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ofrecer una tienda que explique origen, permisos y mantenimiento antes de instalar.

## Alcance, arquitectura y decisiones

Frontend sobre catálogos APT y Flatpak con identificador estable y backend visible; reseñas o recomendaciones no cambian reglas de confianza. Compra queda fuera del MVP.

## Componentes y flujo operativo

1. Buscar
2. comparar ficha
3. revisar permisos y tamaño
4. instalar
5. mostrar progreso real
6. lanzar o diagnosticar.

## Seguridad y riesgos

Descripciones y capturas se tratan como contenido no confiable. No ejecutar marcado activo ni ocultar variantes de origen distinto.

## Criterios de aceptación

Aceptar dos formatos de una misma app con distinción clara y cancelación de descarga.

## Rendimiento y evidencia

Medir precisión de búsqueda y tiempo de respuesta.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [122 — Application Installation System](122_W4_OS_APPLICATION_INSTALLATION_SYSTEM.md)
- [128 — Application Repository](128_W4_OS_APPLICATION_REPOSITORY.md)
- [129 — Application Permissions](129_W4_OS_APPLICATION_PERMISSIONS.md)

## Roadmap y condiciones de evolución

Catálogo curado V1; funciones comerciales requieren diseño independiente.

---

[Anterior](126_W4_OS_THIRD_PARTY_APPLICATION_SUPPORT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](128_W4_OS_APPLICATION_REPOSITORY.md)
