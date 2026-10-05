# 032 · W4 OS — Installation Flow

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Guiar instalación sin ocultar cifrado, borrado ni limitaciones de conectividad.

## Alcance, arquitectura y decisiones

Pasos: idioma, accesibilidad, producto, interfaz gráfica cuando aplique, destino, particionado, cifrado, cuenta y resumen. En `Home` y `Business`, el instalador puede ofrecer una selección explícita de interfaz entre perfiles aprobados del medio; si no existe selección o el usuario no la cambia, la ruta predeterminada debe ser `GNOME` para `Home` y `KDE Plasma` para `Business`. En `Server`, la instalación estándar no debe ofrecer selector gráfico y debe resolver directamente la ruta `headless`. Descargas opcionales no deben hacer imposible una instalación base desde medio completo.

## Componentes y flujo operativo

Guardar elecciones no secretas, resolver producto y perfil gráfico elegido, calcular plan, confirmar resumen con capacidad y disco, ejecutar y validar; ante error presentar registro redactado y opciones de salida.

## Seguridad y riesgos

Nunca registrar contraseñas ni claves de recuperación. La confirmación se refiere al plan final, no a una aceptación genérica al comenzar.

## Criterios de aceptación

Aceptar navegación atrás sin perder ajustes y fallo de descarga sin reformateo automático.

## Rendimiento y evidencia

Medir abandonos y errores por pantalla.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [031 — Installer Architecture](031_W4_OS_INSTALLER_ARCHITECTURE.md)
- [104 — Accessibility System](104_W4_OS_ACCESSIBILITY_SYSTEM.md)
- [232 — Home Onboarding](232_W4_OS_HOME_ONBOARDING.md)

## Roadmap y condiciones de evolución

Probar usabilidad y traducción del resumen antes de ampliar personalización.

---

[Anterior](031_W4_OS_INSTALLER_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](033_W4_OS_PARTITIONING_SYSTEM.md)
