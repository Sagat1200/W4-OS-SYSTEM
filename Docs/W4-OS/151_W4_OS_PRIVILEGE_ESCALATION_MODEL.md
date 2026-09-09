# 151 · W4 OS — Privilege Escalation Model

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Usuarios y autenticación · **Responsabilidad propuesta:** Identidad local  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Limitar privilegios elevados a acciones específicas y verificables.

## Alcance, arquitectura y decisiones

Polkit para acciones de servicios gráficos y sudo para administración explícita; procesos UI permanecen sin privilegios. Cada operación define recursos, parámetros y autorización.

## Componentes y flujo operativo

1. Validar solicitud
2. autenticar sujeto
3. comprobar política
4. ejecutar acción tipada
5. registrar resultado
6. liberar privilegios.

## Seguridad y riesgos

No aceptar cadenas de shell ni rutas arbitrarias desde clientes no confiables. Evitar comprobar permisos y usar un recurso distinto tras una carrera.

## Criterios de aceptación

Aceptar solicitud no autorizada y parámetros manipulados con rechazo sin efectos.

## Rendimiento y evidencia

Medir superficie de acciones privilegiadas.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [152 — Sudo Policy](152_W4_OS_SUDO_POLICY.md)
- [156 — Security Architecture](156_W4_OS_SECURITY_ARCHITECTURE.md)
- [367 — System Service API](367_W4_OS_SYSTEM_SERVICE_API.md)

## Roadmap y condiciones de evolución

Contrato mínimo en MVP; revisión de amenaza por cada acción nueva.

---

[Anterior](150_W4_OS_BIOMETRIC_AUTHENTICATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](152_W4_OS_SUDO_POLICY.md)
