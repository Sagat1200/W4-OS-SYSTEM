# 085 · W4 OS — Rollback Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Actualización y recuperación · **Responsabilidad propuesta:** Plataforma y recuperación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Coordinar reversión de raíz y arranque sin confundirla con restauración de datos.

## Alcance, arquitectura y decisiones

El objetivo es un manifiesto de sistema coherente y compatible con datos persistentes actuales. La recuperación selecciona raíz y artefactos de arranque verificados, no ejecuta una reversión arbitraria de archivos.

## Componentes y flujo operativo

1. Comprobar objetivo
2. evaluar esquemas
3. preparar entrada
4. reiniciar
5. validar paquetes y sesión
6. confirmar estado recuperado.

## Seguridad y riesgos

Certificados revocados y políticas de seguridad recientes no deben resucitar sin reconciliación. Si el objetivo es inseguro, usarlo temporalmente para reparación controlada.

## Criterios de aceptación

Aceptar fallo de nueva generación y conservación de documentos posteriores; demostrar que dpkg describe los binarios arrancados.

## Rendimiento y evidencia

Medir tiempo hasta sesión recuperada y disponibilidad de documentos recientes; verificar también coherencia entre versión de paquete y hash del binario arrancado.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [077 — Update Rollback System](077_W4_OS_UPDATE_ROLLBACK_SYSTEM.md)
- [083 — System State Model](083_W4_OS_SYSTEM_STATE_MODEL.md)
- [088 — Boot Recovery](088_W4_OS_BOOT_RECOVERY.md)
- [281 — Rollback Testing](281_W4_OS_ROLLBACK_TESTING.md)

## Roadmap y condiciones de evolución

V1 manual documentado; fallback automático depende de pruebas con cortes de energía.

---

[Anterior](084_W4_OS_AUTOMATIC_SNAPSHOT_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](086_W4_OS_RECOVERY_ARCHITECTURE.md)
