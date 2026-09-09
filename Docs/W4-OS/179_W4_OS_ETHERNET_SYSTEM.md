# 179 · W4 OS — Ethernet System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Red · **Responsabilidad propuesta:** Integración de conectividad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Gestionar Ethernet, direccionamiento estático y cambios de enlace de forma predecible.

## Alcance, arquitectura y decisiones

Perfil por dispositivo o conexión con DHCP por defecto y configuración manual validada. VLAN y agregación sólo se anuncian con soporte probado.

## Componentes y flujo operativo

1. Detectar cable
2. seleccionar perfil
3. configurar dirección/ruta/DNS
4. verificar
5. informar duplicidad o falta de enlace.

## Seguridad y riesgos

Evitar rutas por defecto inesperadas al conectar adaptador USB. Configuración remota debe conservar un camino de administración o revertirse.

## Criterios de aceptación

Aceptar desconexión, cambio de adaptador y dirección inválida con diagnóstico claro.

## Rendimiento y evidencia

Medir negociación y recuperación.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [176 — Network Architecture](176_W4_OS_NETWORK_ARCHITECTURE.md)
- [177 — NetworkManager Integration](177_W4_OS_NETWORKMANAGER_INTEGRATION.md)
- [185 — Enterprise Networking](185_W4_OS_ENTERPRISE_NETWORKING.md)

## Roadmap y condiciones de evolución

Ethernet básica MVP; topologías empresariales según demanda certificada.

## Pruebas de configuración estática

El formulario valida prefijo, gateway y servidores DNS por familia de direcciones, pero la validez sintáctica no garantiza conectividad. Se comprueba enlace, ruta y resolución separadamente. Un perfil sin gateway puede ser correcto para una red local aislada: no se modifica automáticamente para obtener internet. La prueba incluye adaptador USB retirado y reinsertado en otro puerto, para confirmar que la política de selección no depende únicamente del nombre temporal de interfaz.

---

[Anterior](178_W4_OS_WIFI_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](180_W4_OS_BLUETOOTH_SYSTEM.md)
