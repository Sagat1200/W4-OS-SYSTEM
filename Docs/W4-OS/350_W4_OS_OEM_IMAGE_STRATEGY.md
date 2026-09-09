# 350 · W4 OS — OEM Image Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Continuidad y OEM · **Responsabilidad propuesta:** Continuidad y fabricación  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Producir imágenes OEM con delta mínimo y trazabilidad de cada lote.

## Alcance, arquitectura y decisiones

Manifiesto combina release W4, hardware certificado y recursos autorizados; serial de fabricación se asigna fuera de imagen genérica. No bifurcar paquetes comunes por cliente sin necesidad.

## Componentes y flujo operativo

1. Crear perfil
2. construir
3. generalizar
4. probar muestra
5. firmar
6. registrar lote
7. mantener ruta de actualización.

## Seguridad y riesgos

Escanear secretos y cuentas de fábrica. Cualquier software de proveedor requiere licencia, soporte y revisión de privilegios.

## Criterios de aceptación

Aceptar lote con misma base, identidades únicas y ausencia de servicios no declarados.

## Rendimiento y evidencia

Medir deriva entre OEM y release común.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [039 — OEM Installation Mode](039_W4_OS_OEM_INSTALLATION_MODE.md)
- [338 — OEM Partnership Model](338_W4_OS_OEM_PARTNERSHIP_MODEL.md)
- [349 — Factory Image Strategy](349_W4_OS_FACTORY_IMAGE_STRATEGY.md)
- [376 — OEM Extension Model](376_W4_OS_OEM_EXTENSION_MODEL.md)

## Roadmap y condiciones de evolución

Piloto después de V1; ampliar perfiles con pruebas automatizadas.

---

[Anterior](349_W4_OS_FACTORY_IMAGE_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](351_W4_OS_PRIVACY_ARCHITECTURE.md)
