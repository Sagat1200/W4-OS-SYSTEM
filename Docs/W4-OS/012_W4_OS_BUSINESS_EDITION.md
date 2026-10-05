# 012 · W4 OS — Business Edition

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir Business como escritorio administrable sin bifurcar el sistema base.

## Alcance, arquitectura y decisiones

Agregar inscripción de dispositivos, políticas declarativas, inventario, identidad empresarial y soporte controlado. `Business` toma `KDE Plasma` como interfaz gráfica predeterminada según `ADR-013`, manteniendo variantes controladas solo cuando el medio las califique. Operación local y caché de políticas sobreviven a indisponibilidad del servidor.

## Componentes y flujo operativo

1. Instalar
2. inscribir con identidad única
3. obtener política firmada
4. aplicar cambios autorizados
5. reportar estado mínimo; retirar inscripción revoca credenciales organizacionales.

## Seguridad y riesgos

No incluir acceso remoto permanente sin política y auditoría. Distinguir propiedad corporativa, dispositivo personal y consentimiento de sesión.

## Criterios de aceptación

Aceptar inscripción, rotación de certificado, aplicación de política y funcionamiento desconectado con directorio simulado.

## Rendimiento y evidencia

Medir convergencia por cohorte.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [211 — Business Architecture](211_W4_OS_BUSINESS_ARCHITECTURE.md)
- [222 — Device Enrollment](222_W4_OS_DEVICE_ENROLLMENT.md)
- [409 — V1 Business Scope](409_W4_OS_V1_BUSINESS_SCOPE.md)

## Roadmap y condiciones de evolución

MVP con piloto limitado; alta disponibilidad y compromisos comerciales después de pruebas de operación.

---

[Anterior](011_W4_OS_HOME_EDITION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](013_W4_OS_EDITION_DIFFERENTIATION_MODEL.md)
