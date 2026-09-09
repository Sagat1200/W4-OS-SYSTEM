# 040 · W4 OS — Unattended Installation

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Instalación y layout · **Responsabilidad propuesta:** Instalación y almacenamiento  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Permitir despliegue repetible con un perfil de instalación verificable.

## Alcance, arquitectura y decisiones

Esquema de respuestas versionado con selector de destino inequívoco, idioma, perfil y referencias a secretos efímeros. No guardar contraseñas en archivos públicos.

## Componentes y flujo operativo

1. Validar perfil
2. autenticar origen
3. comprobar disco esperado
4. ejecutar
5. registrar resultado
6. revocar credenciales de instalación.

## Seguridad y riesgos

Abortar si el selector coincide con cero o varios discos. Una instalación desatendida no autoriza borrar medios adicionales detectados.

## Criterios de aceptación

Aceptar repetición sobre hardware de prueba y rechazo de perfil inválido antes de modificar discos.

## Rendimiento y evidencia

Medir tasa de instalaciones completas y tiempo por lote.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [031 — Installer Architecture](031_W4_OS_INSTALLER_ARCHITECTURE.md)
- [222 — Device Enrollment](222_W4_OS_DEVICE_ENROLLMENT.md)
- [346 — Enterprise Migration](346_W4_OS_ENTERPRISE_MIGRATION.md)

## Roadmap y condiciones de evolución

V1 Business piloto; ampliar selección de hardware tras pruebas de seguridad.

---

[Anterior](039_W4_OS_OEM_INSTALLATION_MODE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](041_W4_OS_PACKAGE_SYSTEM.md)
