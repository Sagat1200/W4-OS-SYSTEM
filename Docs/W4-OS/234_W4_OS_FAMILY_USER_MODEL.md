# 234 · W4 OS — Family User Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Experiencia Home · **Responsabilidad propuesta:** Producto Home  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Permitir varios miembros del hogar con datos y permisos separados.

## Alcance, arquitectura y decisiones

Cada persona usa cuenta propia; rol administrador se reserva a responsables elegidos. Recursos compartidos se crean por carpeta/grupo explícito, no por permisos abiertos del home.

## Componentes y flujo operativo

1. Agregar miembro
2. definir rol
3. iniciar sesión
4. compartir carpeta autorizada
5. retirar acceso cuando cambie necesidad.

## Seguridad y riesgos

No conceder acceso automático a archivos privados entre familiares. Controles parentales no justifican captura oculta de actividad.

## Criterios de aceptación

Aceptar dos cuentas sin lectura mutua de home y carpeta compartida funcional.

## Rendimiento y evidencia

Medir errores de permisos y facilidad de alta.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [146 — User Management](146_W4_OS_USER_MANAGEMENT.md)
- [147 — Group Management](147_W4_OS_GROUP_MANAGEMENT.md)
- [155 — Parental Control Architecture](155_W4_OS_PARENTAL_CONTROL_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Multiusuario V1; controles familiares avanzados después de revisión de privacidad.

---

[Anterior](233_W4_OS_HOME_ACCOUNT_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](235_W4_OS_HOME_BACKUP.md)
