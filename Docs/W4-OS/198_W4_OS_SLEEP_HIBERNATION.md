# 198 · W4 OS — Sleep Hibernation

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Energía · **Responsabilidad propuesta:** Hardware y energía  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Soportar suspensión e hibernación sólo con rutas probadas por hardware y cifrado.

## Alcance, arquitectura y decisiones

Suspensión se califica por modelo; hibernación requiere swap y reanudación compatibles con cifrado, kernel y Secure Boot. No se habilita universalmente por configuración de escritorio.

## Componentes y flujo operativo

1. Bloquear sesión
2. comprobar inhibidores
3. sincronizar
4. suspender/hibernar
5. reanudar
6. verificar red, GPU y bloqueo.

## Seguridad y riesgos

No reanudar imagen incompatible tras cambiar kernel. Evitar exposición del escritorio al despertar y datos sensibles en swap no protegida.

## Criterios de aceptación

Aceptar ciclos repetidos y batería crítica en matriz certificada.

## Rendimiento y evidencia

Medir latencia, energía suspendida y fallos de reanudación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [099 — Lock Screen System](099_W4_OS_LOCK_SCREEN_SYSTEM.md)
- [160 — Disk Encryption](160_W4_OS_DISK_ENCRYPTION.md)
- [196 — Power Management](196_W4_OS_POWER_MANAGEMENT.md)

## Roadmap y condiciones de evolución

Suspensión V1 por hardware; hibernación opcional después de pruebas específicas.

---

[Anterior](197_W4_OS_BATTERY_MANAGEMENT.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](199_W4_OS_THERMAL_MANAGEMENT.md)
