# 013 · W4 OS — Edition Differentiation Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Hacer visibles las diferencias de edición sin introducir privilegios comerciales dentro del núcleo.

## Alcance, arquitectura y decisiones

La matriz compara aplicaciones, defaults, administración y soporte. Seguridad base, cifrado y recuperación permanecen comunes; `Home` ya fija `GNOME` como default, `Business` fija `KDE Plasma` como default y `Server` fija una ruta `headless` sin interfaz gráfica por defecto, mientras Business activa además capacidades mediante perfil e inscripción autorizada.

## Componentes y flujo operativo

Generar diferencias entre manifiestos, revisar cambios inesperados y publicar matriz junto al lanzamiento.

## Seguridad y riesgos

Evitar que una licencia vencida bloquee lectura de datos o arranque local. Las restricciones comerciales afectan servicios contratados, con comportamiento de salida definido.

## Criterios de aceptación

Aceptar si cambiar el perfil produce únicamente diferencias documentadas y no degrada controles comunes.

## Rendimiento y evidencia

Medir deriva entre imágenes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [003 — Product Family](003_W4_OS_PRODUCT_FAMILY.md)
- [011 — Home Edition](011_W4_OS_HOME_EDITION.md)
- [012 — Business Edition](012_W4_OS_BUSINESS_EDITION.md)
- [015 — Edition Upgrade Strategy](015_W4_OS_EDITION_UPGRADE_STRATEGY.md)

## Roadmap y condiciones de evolución

Congelar matriz de V1 antes del instalador y mantenerla con cada incorporación.

---

[Anterior](012_W4_OS_BUSINESS_EDITION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](014_W4_OS_EDITION_PACKAGE_PROFILES.md)
