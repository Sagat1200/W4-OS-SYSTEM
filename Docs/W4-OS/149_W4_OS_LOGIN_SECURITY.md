# 149 · W4 OS — Login Security

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Proteger acceso interactivo sin bloquear recuperación legítima del equipo.

## Alcance, arquitectura y decisiones

Política de intentos, bloqueo de pantalla y permisos de acceso se aplica en PAM y gestor de sesión. Límites consideran ataques y riesgo de denegación de servicio.

## Componentes y flujo operativo

1. Intento
2. verificación
3. registrar resultado mínimo
4. aplicar demora o restricción
5. permitir recuperación administrativa documentada.

## Seguridad y riesgos

Evitar bloqueo permanente por intentos remotos contra una cuenta conocida. No registrar contraseñas ni revelar existencia de usuarios en interfaz pública.

## Criterios de aceptación

Aceptar intentos repetidos, reloj incorrecto y recuperación autorizada.

## Rendimiento y evidencia

Medir retrasos legítimos y eventos de abuso.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [098 — Login Manager](098_W4_OS_LOGIN_MANAGER.md)
- [099 — Lock Screen System](099_W4_OS_LOCK_SCREEN_SYSTEM.md)
- [148 — Authentication Architecture](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Baseline V1; factores adicionales tras validar accesibilidad.

---

[Anterior](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](150_W4_OS_BIOMETRIC_AUTHENTICATION.md)
