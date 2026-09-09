# 182 · W4 OS — DNS Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Red · **Responsabilidad propuesta:** Integración de conectividad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Resolver nombres con una autoridad documentada y soporte de DNS dividido.

## Alcance, arquitectura y decisiones

Elegir entre resolver integrado u otro mecanismo compatible con NetworkManager por ADR. Configuración de VPN, enlace y organización tiene precedencia explícita.

## Componentes y flujo operativo

1. Recibir consulta
2. seleccionar dominio/enlace
3. resolver
4. aplicar caché
5. registrar sólo errores necesarios.

## Seguridad y riesgos

No activar DNS externo cifrado ignorando dominios internos empresariales. Las consultas pueden revelar hábitos y no se exportan por defecto.

## Criterios de aceptación

Aceptar nombre interno por VPN y externo por ruta prevista, incluyendo IPv6.

## Rendimiento y evidencia

Medir latencia, fallos y caché negativa.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [176 — Network Architecture](176_W4_OS_NETWORK_ARCHITECTURE.md)
- [177 — NetworkManager Integration](177_W4_OS_NETWORKMANAGER_INTEGRATION.md)
- [181 — VPN Architecture](181_W4_OS_VPN_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Resolver seleccionado en MVP; DNS cifrado optativo tras validar split DNS.

---

[Anterior](181_W4_OS_VPN_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](183_W4_OS_PROXY_SYSTEM.md)
