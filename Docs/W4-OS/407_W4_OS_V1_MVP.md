# 407 · W4 OS — V1 Mvp

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Alcance y roadmap · **Responsabilidad propuesta:** Producto y arquitectura  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir el MVP como demostración integral del ciclo de vida del sistema.

## Alcance, arquitectura y decisiones

Una imagen amd64, perfil mínimo, cuenta local, red, aplicación de trabajo, repositorio candidato y procedimiento de recuperación. No basta un escritorio con marca.

## Componentes y flujo operativo

1. Construir
2. instalar VM y equipo referencia
3. crear documento
4. actualizar paquete/kernel de prueba
5. recuperar fallo
6. restaurar documento desde externo.

## Seguridad y riesgos

MVP debe preservar datos y verificar origen; no usar credenciales de producción ni presentar prototipo como sistema comercial soportado.

## Criterios de aceptación

Aceptar recorrido repetible con hashes, logs y defectos documentados.

## Rendimiento y evidencia

Medir duración y recursos para presupuestos V1.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [031 — Installer Architecture](031_W4_OS_INSTALLER_ARCHITECTURE.md)
- [068 — Image Build System](068_W4_OS_IMAGE_BUILD_SYSTEM.md)
- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [193 — Backup Architecture](193_W4_OS_BACKUP_ARCHITECTURE.md)
- [406 — V1 Scope](406_W4_OS_V1_SCOPE.md)

## Roadmap y condiciones de evolución

Primer hito técnico; ampliar sólo después de cerrar fallos de ciclo completo.

---

[Anterior](406_W4_OS_V1_SCOPE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](408_W4_OS_V1_HOME_SCOPE.md)
