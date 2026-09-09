# 181 · W4 OS — VPN Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Red · **Responsabilidad propuesta:** Integración de conectividad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Proporcionar VPN con rutas y DNS explícitos y comportamiento ante desconexión.

## Alcance, arquitectura y decisiones

Elegir plugins mantenidos para protocolos requeridos; perfil define túnel completo o dividido, DNS y restricción de tráfico opcional. No anunciar kill switch sin probar rutas IPv4/IPv6.

## Componentes y flujo operativo

1. Importar perfil validado
2. guardar secretos
3. conectar
4. comprobar rutas y resolución
5. desconectar
6. restaurar estado previo.

## Seguridad y riesgos

No aceptar certificados inválidos para resolver conexión. Evitar fuga DNS y rutas residuales, especialmente al cambiar de Wi-Fi a cable.

## Criterios de aceptación

Aceptar caída del túnel y cambio de interfaz con política de tráfico cumplida.

## Rendimiento y evidencia

Medir reconexión y sobrecarga.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [163 — Secret Storage](163_W4_OS_SECRET_STORAGE.md)
- [182 — DNS Architecture](182_W4_OS_DNS_ARCHITECTURE.md)
- [184 — Network Security](184_W4_OS_NETWORK_SECURITY.md)

## Roadmap y condiciones de evolución

VPN seleccionadas V1 Business; ampliación por pruebas de interoperabilidad.

---

[Anterior](180_W4_OS_BLUETOOTH_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](182_W4_OS_DNS_ARCHITECTURE.md)
