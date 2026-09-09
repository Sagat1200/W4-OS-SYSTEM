# 150 · W4 OS — Biometric Authentication

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Añadir biometría sólo para dispositivos y flujos soportados con alternativa de acceso.

## Alcance, arquitectura y decisiones

Proponer fprintd u otro componente mantenido según hardware; contraseña sigue siendo recuperación. Biometría de sesión no se presenta como cifrado del disco.

## Componentes y flujo operativo

1. Verificar dispositivo
2. enrolar con autorización
3. probar
4. habilitar para flujos permitidos
5. ofrecer retirada de plantilla.

## Seguridad y riesgos

No exportar plantillas a servicios W4 ni prometer que son irremplazables de forma segura. Sensores no certificados quedan sin soporte biométrico.

## Criterios de aceptación

Aceptar dedo no reconocido, sensor ausente y fallback de contraseña.

## Rendimiento y evidencia

Medir falsos rechazos en condiciones de prueba declaradas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [024 — Device Compatibility Model](024_W4_OS_DEVICE_COMPATIBILITY_MODEL.md)
- [148 — Authentication Architecture](148_W4_OS_AUTHENTICATION_ARCHITECTURE.md)
- [163 — Secret Storage](163_W4_OS_SECRET_STORAGE.md)

## Roadmap y condiciones de evolución

Opcional posterior al MVP y sólo en modelos evaluados.

---

[Anterior](149_W4_OS_LOGIN_SECURITY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](151_W4_OS_PRIVILEGE_ESCALATION_MODEL.md)
