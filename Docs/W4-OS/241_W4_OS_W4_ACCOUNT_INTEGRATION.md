# 241 · W4 OS — W4 Account Integration

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Servicios W4 opcionales · **Responsabilidad propuesta:** Integración de servicios  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Vincular una identidad W4 opcional sin reemplazar cuentas del sistema.

## Alcance, arquitectura y decisiones

Contrato propuesto de identidad web con tokens de alcance limitado y almacén seguro. No se presupone un servicio W4 Account disponible ni un endpoint real.

## Componentes y flujo operativo

1. Iniciar vinculación
2. autenticar en proveedor definido
3. validar retorno
4. almacenar token
5. mostrar cuenta
6. permitir revocar y desvincular.

## Seguridad y riesgos

No usar contraseña Unix para login web. Protección contra CSRF, redirect indebido y tokens de audiencia incorrecta forma parte del contrato.

## Criterios de aceptación

Aceptar proveedor simulado caído y desvinculación con operación local intacta.

## Rendimiento y evidencia

Medir renovación y errores de vinculación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [163 — Secret Storage](163_W4_OS_SECRET_STORAGE.md)
- [219 — SSO Architecture](219_W4_OS_SSO_ARCHITECTURE.md)
- [233 — Home Account System](233_W4_OS_HOME_ACCOUNT_SYSTEM.md)

## Roadmap y condiciones de evolución

Diseñar contrato en V1; implementar sólo al existir proveedor y política de datos.

---

[Anterior](240_W4_OS_HOME_PRIVACY_PROFILE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](242_W4_OS_W4_SERVICE_INTEGRATION.md)
