# 409 · W4 OS — V1 Business Scope

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Alcance y roadmap · **Responsabilidad propuesta:** Producto y arquitectura  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Cerrar V1 Business con administración mínima operable y aislamiento probado.

## Alcance, arquitectura y decisiones

Perfil añade inscripción, políticas esenciales, inventario mínimo, actualización por ventana y auditoría. `Business V1` toma `KDE Plasma` como interfaz gráfica predeterminada según `ADR-013`, con variantes controladas solo cuando el medio las califique. Directorio empresarial se limita a configuración de referencia; soporte remoto asistido sólo si está calificado.

## Componentes y flujo operativo

1. Preparar organización
2. inscribir piloto
3. aplicar política
4. actualizar cohorte
5. operar offline
6. retirar equipo
7. verificar revocación.

## Seguridad y riesgos

No vender gestión masiva antes de probar aislamiento y recuperación. SLA, cumplimiento y acceso desatendido requieren procesos adicionales.

## Criterios de aceptación

Aceptar ciclo empresarial completo y pruebas de mensajes duplicados, organización ajena y certificado vencido.

## Rendimiento y evidencia

Medir convergencia y soporte.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [012 — Business Edition](012_W4_OS_BUSINESS_EDITION.md)
- [211 — Business Architecture](211_W4_OS_BUSINESS_ARCHITECTURE.md)
- [284 — Enterprise Testing](284_W4_OS_ENTERPRISE_TESTING.md)
- [290 — Business Certification](290_W4_OS_BUSINESS_CERTIFICATION.md)
- [406 — V1 Scope](406_W4_OS_V1_SCOPE.md)

## Roadmap y condiciones de evolución

Piloto reducido antes de disponibilidad general y compromisos contractuales.

---

[Anterior](408_W4_OS_V1_HOME_SCOPE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](410_W4_OS_V1_RELEASE_PLAN.md)
