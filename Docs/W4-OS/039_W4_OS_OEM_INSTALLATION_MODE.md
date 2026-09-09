# 039 · W4 OS — OEM Installation Mode

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Preparar equipos de fábrica sin compartir identidades o secretos entre compradores.

## Alcance, arquitectura y decisiones

La imagen OEM queda generalizada: sin usuario final, claves de dispositivo ni inscripción activa. El primer inicio crea identidad y completa configuración.

## Componentes y flujo operativo

1. Fabricar imagen
2. verificar hashes
3. instalar por lote
4. ejecutar autoprueba
5. sellar
6. iniciar onboarding del comprador.

## Seguridad y riesgos

No clonar machine-id, claves SSH ni certificados empresariales. El acceso de fábrica expira o se elimina antes de entrega.

## Criterios de aceptación

Aceptar dos equipos de una misma imagen con identidades diferentes y sin cuenta de producción accesible.

## Rendimiento y evidencia

Medir tiempo de preparación por unidad.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [232 — Home Onboarding](232_W4_OS_HOME_ONBOARDING.md)
- [349 — Factory Image Strategy](349_W4_OS_FACTORY_IMAGE_STRATEGY.md)
- [350 — OEM Image Strategy](350_W4_OS_OEM_IMAGE_STRATEGY.md)

## Roadmap y condiciones de evolución

Prototipo con proveedor piloto; habilitar volumen tras auditoría de generalización.

---

[Anterior](038_W4_OS_DUAL_BOOT_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](040_W4_OS_UNATTENDED_INSTALLATION.md)
