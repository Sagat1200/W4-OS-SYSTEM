# 311 · W4 OS — GPL Compliance

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Licencias y distribución · **Responsabilidad propuesta:** Cumplimiento de distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Cumplir obligaciones GPL aplicables a cada componente distribuido.

## Alcance, arquitectura y decisiones

Registrar versión GPL, modificaciones, forma de entrega y fuente correspondiente necesaria. Distinguir agregación de componentes de una obra combinada; resolver enlaces y plugins caso por caso.

## Componentes y flujo operativo

1. Identificar binario GPL
2. conservar fuente/configuración/scripts pertinentes
3. elegir modalidad permitida
4. entregar acceso
5. verificar reconstrucción aplicable.

## Seguridad y riesgos

No afirmar que toda W4 deba usar una sola licencia ni que basta enlazar cualquier versión upstream. Ofertas escritas requieren cumplir sus términos reales.

## Criterios de aceptación

Aceptar descarga de fuente exacta asociada al binario y textos de licencia presentes.

## Rendimiento y evidencia

Medir solicitudes de fuente atendidas y correspondencia.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [309 — Licensing Strategy](309_W4_OS_LICENSING_STRATEGY.md)
- [310 — Open Source Compliance](310_W4_OS_OPEN_SOURCE_COMPLIANCE.md)
- [313 — Source Code Publication Policy](313_W4_OS_SOURCE_CODE_PUBLICATION_POLICY.md)

## Roadmap y condiciones de evolución

Revisión de cumplimiento previa a publicación; no sustituye análisis jurídico del producto final.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [GNU — GPL FAQ](https://www.gnu.org/licenses/gpl-faq.html.en). Referencia interpretativa de GNU sobre obligaciones GPL. Verificar además el texto y versión exactos de la licencia aplicable.

---

[Anterior](310_W4_OS_OPEN_SOURCE_COMPLIANCE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](312_W4_OS_THIRD_PARTY_LICENSE_MANAGEMENT.md)
