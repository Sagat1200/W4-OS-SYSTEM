# 042 · W4 OS — DEB Package Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Paquetes y repositorios · **Responsabilidad propuesta:** Mantenimiento y distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Empaquetar cambios W4 con semántica Debian y evitar instalaciones opacas.

## Alcance, arquitectura y decisiones

Cada fuente incluye control, changelog, copyright, reglas y pruebas; separar archivos de paquete de datos creados en ejecución. Versiones W4 deben ordenar correctamente respecto al upstream.

## Componentes y flujo operativo

1. Construir fuente en entorno limpio
2. generar binarios
3. instalar, actualizar y retirar
4. comprobar archivos residuales.

## Seguridad y riesgos

Scripts de mantenimiento se ejecutan con privilegios: deben ser acotados, repetibles y no descargar código. Los secretos se crean en destino, nunca en build.

## Criterios de aceptación

Aceptar upgrade desde versión anterior y manejo de conffiles modificados sin pérdida silenciosa; revisar dependencias y tamaño instalado.

## Rendimiento y evidencia

Revisar tamaño instalado, dependencias y tiempo de scripts de mantenimiento; una instalación rápida no compensa pérdida de conffiles o estado inconsistente.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [044 — Package Metadata System](044_W4_OS_PACKAGE_METADATA_SYSTEM.md)
- [048 — Package Build Pipeline](048_W4_OS_PACKAGE_BUILD_PIPELINE.md)
- [320 — Package Maintainer Guide](320_W4_OS_PACKAGE_MAINTAINER_GUIDE.md)

## Roadmap y condiciones de evolución

Plantilla inicial y paquetes pequeños; automatizar política antes de crecer el catálogo.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [Debian Policy Manual](https://www.debian.org/doc/debian-policy/). Referencia para semántica y estructura de paquetes Debian; verificar la edición aplicable al congelar la base.

---

[Anterior](041_W4_OS_PACKAGE_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](043_W4_OS_APT_INTEGRATION.md)
