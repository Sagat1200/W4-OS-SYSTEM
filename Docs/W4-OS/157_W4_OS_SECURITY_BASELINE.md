# 157 · W4 OS — Security Baseline

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Establecer una configuración mínima de seguridad común a Home y Business.

## Alcance, arquitectura y decisiones

Propuesta: actualización autenticada, cuentas estándar, bloqueo, servicios mínimos, firewall con entrada restringida y cifrado ofrecido de forma visible. Valores se prueban por perfil.

## Componentes y flujo operativo

1. Construir imagen
2. inspeccionar servicios y permisos
3. comparar baseline
4. justificar diferencias
5. bloquear release si falta control obligatorio.

## Seguridad y riesgos

No activar endurecimiento que impida instalación accesible o recuperación sin alternativa. Excepciones tienen motivo, dueño y fecha de revisión.

## Criterios de aceptación

Aceptar imagen limpia sin puertos inesperados y usuario estándar sin privilegios indebidos.

## Rendimiento y evidencia

Medir desviaciones y servicios activados.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [149 — Login Security](149_W4_OS_LOGIN_SECURITY.md)
- [158 — Apparmor Architecture](158_W4_OS_APPARMOR_ARCHITECTURE.md)
- [159 — Firewall System](159_W4_OS_FIREWALL_SYSTEM.md)
- [160 — Disk Encryption](160_W4_OS_DISK_ENCRYPTION.md)

## Roadmap y condiciones de evolución

Baseline MVP y validación continua en cada imagen.

---

[Anterior](156_W4_OS_SECURITY_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](158_W4_OS_APPARMOR_ARCHITECTURE.md)
