# 159 · W4 OS — Firewall System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Gestionar firewall con una sola autoridad de reglas y recuperación de conectividad.

## Alcance, arquitectura y decisiones

Seleccionar frontend mantenido sobre nftables mediante ADR; evitar coexistencia de gestores que reescriban reglas. Propuesta de entrada restrictiva y excepciones por servicio.

## Componentes y flujo operativo

1. Solicitar excepción
2. validar origen/puerto/interfaz
3. aplicar conjunto
4. comprobar
5. persistir; cambio remoto incluye retorno si se pierde gestión.

## Seguridad y riesgos

No abrir todos los puertos para diagnosticar. Contenedores y VPN pueden modificar filtrado; declarar integración y verificar reglas efectivas.

## Criterios de aceptación

Aceptar conexión permitida y denegada, reinicio y cambio de red.

## Rendimiento y evidencia

Medir aplicación de reglas y sobrecarga en tráfico.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [176 — Network Architecture](176_W4_OS_NETWORK_ARCHITECTURE.md)
- [184 — Network Security](184_W4_OS_NETWORK_SECURITY.md)
- [255 — Podman Docker Support](255_W4_OS_PODMAN_DOCKER_SUPPORT.md)

## Roadmap y condiciones de evolución

Política local V1; gestión remota después de prueba de retorno.

---

[Anterior](158_W4_OS_APPARMOR_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](160_W4_OS_DISK_ENCRYPTION.md)
