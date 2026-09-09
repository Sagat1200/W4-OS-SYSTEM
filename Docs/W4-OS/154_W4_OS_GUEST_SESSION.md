# 154 · W4 OS — Guest Session

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Ofrecer uso temporal sólo si puede garantizarse aislamiento y limpieza verificable.

## Alcance, arquitectura y decisiones

Perfil invitado deshabilitado por defecto en Business; entorno efímero sin acceso a homes existentes ni credenciales guardadas. No se basa en reutilizar una cuenta permanente compartida.

## Componentes y flujo operativo

1. Crear entorno temporal
2. iniciar con permisos reducidos
3. usar aplicaciones permitidas
4. cerrar
5. eliminar estado temporal
6. comprobar residuos.

## Seguridad y riesgos

La limpieza lógica no es borrado físico certificado. Prohibir acceso administrativo y advertir que archivos no exportados se pierden al cerrar.

## Criterios de aceptación

Aceptar sesión nueva sin historial de la anterior ni acceso a otros usuarios.

## Rendimiento y evidencia

Medir limpieza y espacio temporal.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [146 — User Management](146_W4_OS_USER_MANAGEMENT.md)
- [153 — Session Management](153_W4_OS_SESSION_MANAGEMENT.md)
- [240 — Home Privacy Profile](240_W4_OS_HOME_PRIVACY_PROFILE.md)

## Roadmap y condiciones de evolución

Función posterior a V1 inicial, condicionada a pruebas de aislamiento.

---

[Anterior](153_W4_OS_SESSION_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](155_W4_OS_PARENTAL_CONTROL_ARCHITECTURE.md)
