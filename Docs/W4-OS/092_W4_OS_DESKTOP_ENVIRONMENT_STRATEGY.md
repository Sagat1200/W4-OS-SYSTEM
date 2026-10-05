# 092 · W4 OS — Desktop Environment Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Escritorio y accesibilidad · **Responsabilidad propuesta:** Experiencia de escritorio  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir un catálogo curado de interfaces gráficas por accesibilidad, mantenimiento y hardware antes que por apariencia, incluyendo si la selección se expone en instalación.

## Alcance, arquitectura y decisiones

Propuesta de trabajo: evaluar `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon` de Debian como interfaces gráficas candidatas para `Home`, mediante matriz de pruebas. `ADR-006` ya fija `GNOME` como interfaz predeterminada de `Home`, conserva `KDE Plasma`, `XFCE` y `Cinnamon` como variantes controladas y permite selector en instalación solo cuando el medio incluya variantes calificadas. Como siguiente aterrizaje operativo de esa decisión, `MX-007` ya delimita la ruta base visible de `Home` como `GNOME + GDM`, con `w4-desktop-meta` reservado para la base desktop común y `w4-desktop-gnome-meta` como metapaquete específico de composición.

## Componentes y flujo operativo

1. Construir prototipos equivalentes
2. probar tareas y lectores de pantalla
3. medir recursos
4. comparar mantenimiento
5. definir catálogo soportado
6. calificar qué variantes pueden aparecer en el selector del medio
7. aprobar entorno.

## Seguridad y riesgos

No abrir un catálogo gráfico mayor que la capacidad real de prueba y mantenimiento del proyecto. Si V1 ofrece varias interfaces, cada una debe compartir base, seguridad y actualización, y además contar con su propia evidencia mínima de arranque, sesión, accesibilidad y recuperación. Evitar extensiones que sustituyan funciones de seguridad upstream.

## Criterios de aceptación

Alinear catálogo y política de selección con resultados de accesibilidad, suspensión, gráficos y soporte, más una regla explícita sobre qué interfaces se pueden elegir durante instalación y en qué perfiles.

## Rendimiento y evidencia

Medir esfuerzo de personalización necesario y costo incremental de soportar más de una interfaz gráfica en V1.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [091 — Desktop Architecture](091_W4_OS_DESKTOP_ARCHITECTURE.md)
- [104 — Accessibility System](104_W4_OS_ACCESSIBILITY_SYSTEM.md)
- [283 — Desktop Testing](283_W4_OS_DESKTOP_TESTING.md)

## Roadmap y condiciones de evolución

Resolver antes del cierre de imagen MVP y adaptar paquetes al catálogo gráfico elegido. Como `ADR-006` ya permite selección condicionada por medio, el roadmap debe incluir metapaquetes diferenciados, pruebas por variante y límites claros del soporte oficial.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [Debian 13 — What’s new](https://www.debian.org/releases/trixie/release-notes/whats-new.html). Debian documenta GNOME y KDE Plasma entre sus escritorios de referencia; XFCE y Cinnamon también forman parte del ecosistema empaquetado disponible para evaluación propia. La selección de interfaz W4 requiere comparación y pruebas propias.

---

[Anterior](091_W4_OS_DESKTOP_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](093_W4_OS_WINDOW_MANAGER_STRATEGY.md)
