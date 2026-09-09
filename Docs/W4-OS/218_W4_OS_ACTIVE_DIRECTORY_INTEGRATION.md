# 218 · W4 OS — Active Directory Integration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Administración Business · **Responsabilidad propuesta:** Plataforma empresarial  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Integrar Active Directory para acceso y políticas compatibles, con límites publicados.

## Alcance, arquitectura y decisiones

Proponer realmd/SSSD y Kerberos según paquetes de Debian; unión, grupos y homes se prueban. No prometer soporte completo de GPO de Windows.

## Componentes y flujo operativo

1. Validar DNS/reloj
2. unir equipo
3. resolver usuario
4. autenticar
5. aplicar roles permitidos
6. probar renovación y salida.

## Seguridad y riesgos

No conservar contraseña de administrador de dominio. Mantener cuenta local de recuperación y tratar credenciales cacheadas según riesgo.

## Criterios de aceptación

Aceptar login de dominio, cambio de contraseña, controlador caído y baja del equipo.

## Rendimiento y evidencia

Medir tiempos y compatibilidad de políticas declaradas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [185 — Enterprise Networking](185_W4_OS_ENTERPRISE_NETWORKING.md)
- [215 — Enterprise User Management](215_W4_OS_ENTERPRISE_USER_MANAGEMENT.md)
- [216 — Directory Services](216_W4_OS_DIRECTORY_SERVICES.md)

## Roadmap y condiciones de evolución

Piloto AD acotado antes de V1 Business general.

---

[Anterior](217_W4_OS_LDAP_INTEGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](219_W4_OS_SSO_ARCHITECTURE.md)
