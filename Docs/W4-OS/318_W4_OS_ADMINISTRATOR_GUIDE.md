# 318 · W4 OS — Administrator Guide

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Guías y documentación · **Responsabilidad propuesta:** Documentación técnica  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Documentar operación Business desde incorporación hasta retirada de equipos.

## Alcance, arquitectura y decisiones

Guía por rol: preparar red/identidad, inscribir, asignar política, programar actualizaciones, revisar evidencias y recuperar. Diferenciar requisitos del cliente de servicios W4.

## Componentes y flujo operativo

1. Preparar entorno
2. ejecutar piloto
3. comprobar aislamiento
4. desplegar por cohorte
5. revisar
6. retirar credenciales y activos.

## Seguridad y riesgos

No incluir credenciales de ejemplo reutilizables ni recomendar administración total para todas las tareas. Acceso remoto tiene autorización separada.

## Criterios de aceptación

Aceptar administrador nuevo que opera laboratorio con guía y recupera fallo de política.

## Rendimiento y evidencia

Medir ambigüedades y pasos manuales.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [211 — Business Architecture](211_W4_OS_BUSINESS_ARCHITECTURE.md)
- [222 — Device Enrollment](222_W4_OS_DEVICE_ENROLLMENT.md)
- [224 — Remote Update Management](224_W4_OS_REMOTE_UPDATE_MANAGEMENT.md)
- [333 — Remote Support Policy](333_W4_OS_REMOTE_SUPPORT_POLICY.md)

## Roadmap y condiciones de evolución

Versión de piloto antes de Business general y actualización por API.

## Procedimiento de piloto empresarial

1. Preparar una organización de laboratorio con roles separados de administración, soporte y auditoría. Registrar quién puede inscribir y retirar equipos.
2. Validar red, DNS, reloj y certificados contra el directorio seleccionado. Probar acceso local de recuperación antes de unir el equipo.
3. Inscribir una cohorte pequeña y comprobar identidad única, organización correcta y credencial revocable.
4. Aplicar primero políticas no disruptivas. Revisar el diff y el estado efectivo por clave antes de probar restricciones de red o autenticación.
5. Programar una actualización en ventana acordada. Confirmar staging, salud y resultado por equipo; mantener desconectados como pendientes.
6. Retirar un equipo de prueba y comprobar que su credencial ya no autoriza gestión. Conservar o retirar datos conforme a la política específica, sin inferir borrado por baja de inventario.

El expediente del piloto contiene versiones, topología, políticas, resultados y defectos. No incluye contraseñas de dominio ni claves privadas. Antes de ampliar, el administrador debe poder pausar nuevas asignaciones y restaurar al menos un caso de fallo realista en laboratorio.

---

[Anterior](317_W4_OS_USER_DOCUMENTATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](319_W4_OS_DEVELOPER_GUIDE.md)
