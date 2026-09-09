# 216 · W4 OS — Directory Services

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Seleccionar componentes de directorio por interoperabilidad y mantenimiento.

## Alcance, arquitectura y decisiones

Proponer SSSD/realmd cuando corresponda al proveedor; separar descubrimiento, unión, autenticación y autorización. No implementar LDAP/Kerberos propios.

## Componentes y flujo operativo

1. Descubrir dominio
2. validar red y reloj
3. unir con credencial acotada
4. verificar usuario/grupos
5. probar acceso desconectado
6. retirar unión.

## Seguridad y riesgos

No persistir credencial privilegiada de unión. Proteger caché y definir expiración; revocación offline tiene límites que deben documentarse.

## Criterios de aceptación

Aceptar unión y retirada repetibles sin credenciales residuales ni UID cambiado.

## Rendimiento y evidencia

Medir login online/offline.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [185 — Enterprise Networking](185_W4_OS_ENTERPRISE_NETWORKING.md)
- [215 — Enterprise User Management](215_W4_OS_ENTERPRISE_USER_MANAGEMENT.md)
- [217 — LDAP Integration](217_W4_OS_LDAP_INTEGRATION.md)
- [218 — Active Directory Integration](218_W4_OS_ACTIVE_DIRECTORY_INTEGRATION.md)

## Roadmap y condiciones de evolución

Calificar un proveedor V1 Business; otras combinaciones por matriz.

---

[Anterior](215_W4_OS_ENTERPRISE_USER_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](217_W4_OS_LDAP_INTEGRATION.md)
