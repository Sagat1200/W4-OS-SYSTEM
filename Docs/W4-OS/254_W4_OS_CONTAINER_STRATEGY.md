# 254 · W4 OS — Container Strategy

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Desarrollo · **Responsabilidad propuesta:** Plataforma de desarrollo  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Aislar aplicaciones de desarrollo y servicios de prueba con contenedores de usuario.

## Alcance, arquitectura y decisiones

Propuesta rootless como ruta predeterminada cuando sea compatible; imágenes fijadas por digest, montajes explícitos y redes limitadas. Contenedor no equivale a máquina virtual.

## Componentes y flujo operativo

1. Obtener imagen
2. verificar política de origen
3. crear entorno
4. montar sólo proyecto
5. ejecutar
6. detener
7. limpiar recursos elegibles.

## Seguridad y riesgos

No montar socket de motor privilegiado ni raíz del host por defecto. Imágenes no confiables pueden atacar kernel compartido.

## Criterios de aceptación

Aceptar proceso sin acceso a home ajeno y limpieza que conserva volúmenes elegidos.

## Rendimiento y evidencia

Medir memoria, disco y red.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [255 — Podman Docker Support](255_W4_OS_PODMAN_DOCKER_SUPPORT.md)
- [169 — Supply Chain Security](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
- [258 — Development Environments](258_W4_OS_DEVELOPMENT_ENVIRONMENTS.md)

## Roadmap y condiciones de evolución

Rootless opcional V1; servicios persistentes requieren operación y backup propios.

---

[Anterior](253_W4_OS_DEVELOPMENT_TOOLCHAIN.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](255_W4_OS_PODMAN_DOCKER_SUPPORT.md)
