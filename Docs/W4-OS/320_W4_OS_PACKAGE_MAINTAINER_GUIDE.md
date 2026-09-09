# 320 · W4 OS — Package Maintainer Guide

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Guías y documentación · **Responsabilidad propuesta:** Documentación técnica  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Dar al mantenedor un procedimiento completo de paquete Debian para W4.

## Alcance, arquitectura y decisiones

Guía cubre fuente, metadatos, versión, scripts de mantenimiento, pruebas, changelog y seguimiento upstream. La custodia de firma queda fuera del equipo de build.

## Componentes y flujo operativo

1. Importar
2. aplicar delta mínimo
3. construir limpio
4. probar install/upgrade/remove
5. revisar licencia
6. enviar artefacto candidato.

## Seguridad y riesgos

No descargar dependencias ocultas en scripts ni sobrescribir conffiles del usuario. Cambios privilegiados requieren revisión específica.

## Criterios de aceptación

Aceptar paquete que actualiza desde anterior y publica fuente correspondiente.

## Rendimiento y evidencia

Medir defectos de empaquetado y parches pendientes.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [042 — DEB Package Architecture](042_W4_OS_DEB_PACKAGE_ARCHITECTURE.md)
- [048 — Package Build Pipeline](048_W4_OS_PACKAGE_BUILD_PIPELINE.md)
- [049 — Package QA System](049_W4_OS_PACKAGE_QA_SYSTEM.md)
- [327 — Package Maintainer Model](327_W4_OS_PACKAGE_MAINTAINER_MODEL.md)

## Roadmap y condiciones de evolución

Plantilla y ejemplo desde primeros paquetes W4.

## Expediente de mantenimiento por paquete

| Campo | Contenido exigido |
|---|---|
| Fuente | Versión, hash y origen verificable |
| Delta W4 | Parches/configuración con motivo y dueño |
| Construcción | Entorno y dependencias fijados |
| Privilegios | Scripts, servicios y capacidades nuevas |
| Datos | Conffiles, directorios persistentes y migración |
| Pruebas | Instalación limpia, upgrade anterior y retirada |
| Derechos | Licencia, avisos y fuente correspondiente |
| Seguimiento | Incidencias upstream y cobertura de seguridad |

Si un upgrade modifica un conffile personalizado, el test debe representar esa personalización antes de instalar el candidato. Si el paquete crea usuario de servicio, se comprueban permisos y conservación/retirada de datos. Si genera initramfs o afecta kernel, se eleva a la matriz de arranque; no basta un test de instalación en chroot.

Cuando Debian incorpore la corrección local, se prueba el paquete sin el parche W4 y se retira el delta en vez de mantener dos implementaciones de la misma solución. La versión de paquete debe ordenar correctamente para que clientes actualicen; cambiar sólo una etiqueta comercial no modifica la semántica del resolver.

---

[Anterior](319_W4_OS_DEVELOPER_GUIDE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](321_W4_OS_RELEASE_ENGINEERING_GUIDE.md)
