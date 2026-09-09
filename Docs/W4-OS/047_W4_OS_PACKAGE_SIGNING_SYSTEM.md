# 047 · W4 OS — Package Signing System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Paquetes y repositorios · **Responsabilidad propuesta:** Mantenimiento y distribución  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Autenticar el repositorio W4 y separar la firma de publicación del entorno de build.

## Alcance, arquitectura y decisiones

APT verifica metadatos Release/InRelease y hashes encadenados; no se presenta esto como firma individual obligatoria de cada .deb. Proponer claves separadas por entorno y rotación planificada.

## Componentes y flujo operativo

1. Promover artefactos verificados
2. generar índices
3. firmar en servicio restringido
4. publicar
5. validar desde cliente limpio.

## Seguridad y riesgos

Un worker no posee la clave de producción. Ensayar revocación y transición de confianza sin desactivar verificación.

## Criterios de aceptación

Aceptar alteración de paquete o índice con rechazo del cliente y rotación ensayada.

## Rendimiento y evidencia

Medir tiempo de publicación y propagación de nueva clave.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [051 — Repository Architecture](051_W4_OS_REPOSITORY_ARCHITECTURE.md)
- [168 — Code Signing Model](168_W4_OS_CODE_SIGNING_MODEL.md)
- [272 — Release Signing](272_W4_OS_RELEASE_SIGNING.md)

## Roadmap y condiciones de evolución

Diseñar custodia antes del primer repositorio público; revisar accesos periódicamente.

## Referencias técnicas contrastadas

Consulta: 2026-09-08. Las fuentes describen mecanismos externos; los requisitos y elecciones W4 son propuestas de esta colección.

- [APT — apt-secure(8)](https://manpages.debian.org/trixie/apt/apt-secure.8.en.html). APT autentica metadatos del archivo y su cadena de hashes. La separación de custodia propuesta en W4 añade controles operativos propios.

---

[Anterior](046_W4_OS_PACKAGE_DEPENDENCY_POLICY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](048_W4_OS_PACKAGE_BUILD_PIPELINE.md)
