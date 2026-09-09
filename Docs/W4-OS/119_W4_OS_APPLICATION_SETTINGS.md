# 119 · W4 OS — Application Settings

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Centro de control · **Responsabilidad propuesta:** Configuración y experiencia  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Configurar aplicaciones predeterminadas y permisos con alcance por usuario.

## Alcance, arquitectura y decisiones

Catálogo distingue DEB de sistema y Flatpak; asociaciones MIME se gestionan mediante mecanismos de escritorio. Permisos se consultan en el motor correspondiente.

## Componentes y flujo operativo

1. Elegir tipo de archivo
2. seleccionar aplicación compatible
3. actualizar asociación
4. probar apertura; permisos sensibles muestran efecto antes de cambiar.

## Seguridad y riesgos

No otorgar acceso a todo el home para resolver cualquier fallo de aplicación. Entradas de escritorio se validan sin ejecutar contenido al listarlas.

## Criterios de aceptación

Aceptar cambio de visor PDF y revocación de permiso Flatpak con persistencia tras reinicio.

## Rendimiento y evidencia

Medir inconsistencias de catálogo.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [121 — Application Model](121_W4_OS_APPLICATION_MODEL.md)
- [129 — Application Permissions](129_W4_OS_APPLICATION_PERMISSIONS.md)
- [140 — File Manager](140_W4_OS_FILE_MANAGER.md)

## Roadmap y condiciones de evolución

Asociaciones y permisos básicos V1; políticas empresariales después.

---

[Anterior](118_W4_OS_STORAGE_SETTINGS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](120_W4_OS_BUSINESS_POLICY_SETTINGS.md)
