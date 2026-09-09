# 146 · W4 OS — User Management

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Administrar cuentas locales con propiedad de archivos y recuperación de acceso explícitas.

## Alcance, arquitectura y decisiones

Utilizar cuentas y herramientas de Debian; separar usuario estándar, administrador y cuentas de servicio. UID/GID se asignan sin colisiones con directorios empresariales.

## Componentes y flujo operativo

1. Crear identidad
2. preparar home con permisos
3. asignar rol mínimo
4. verificar acceso; baja bloquea login antes de decidir retención de archivos.

## Seguridad y riesgos

No compartir cuentas administrativas ni guardar contraseñas en scripts. Eliminar identidad no implica borrar automáticamente datos de su propietario.

## Criterios de aceptación

Aceptar alta, bloqueo y baja conservando atribución de archivos.

## Rendimiento y evidencia

Medir cuentas huérfanas y colisiones de identificadores.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [147 — Group Management](147_W4_OS_GROUP_MANAGEMENT.md)
- [148 — Authentication Architecture](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)
- [215 — Enterprise User Management](215_W4_OS_ENTERPRISE_USER_MANAGEMENT.md)

## Roadmap y condiciones de evolución

Gestión local MVP; reglas de coexistencia con directorio antes de Business.

---

[Anterior](145_W4_OS_GAMING_DRIVER_PROFILE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](147_W4_OS_GROUP_MANAGEMENT.md)
