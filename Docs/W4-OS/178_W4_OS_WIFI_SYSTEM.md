# 178 · W4 OS — Wi-Fi System

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Red · **Responsabilidad propuesta:** Integración de conectividad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Conectar Wi-Fi con autenticación visible y recuperación ante credenciales erróneas.

## Alcance, arquitectura y decisiones

Soportar modos y chips calificados; perfiles distinguen red personal, abierta y empresarial. Portal cautivo es un estado de acceso, no un error de todo el sistema.

## Componentes y flujo operativo

1. Explorar
2. seleccionar
3. autenticar
4. obtener dirección
5. detectar portal cuando aplique
6. mostrar conexión efectiva.

## Seguridad y riesgos

No unirse automáticamente a redes abiertas por coincidencia de nombre. No registrar SSID identificable en telemetría sin necesidad.

## Criterios de aceptación

Aceptar contraseña incorrecta, señal perdida y reconexión tras suspensión.

## Rendimiento y evidencia

Medir tiempo de asociación y roaming en matriz.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [020 — Firmware Management](020_W4_OS_FIRMWARE_MANAGEMENT.md)
- [177 — NetworkManager Integration](177_W4_OS_NETWORKMANAGER_INTEGRATION.md)
- [185 — Enterprise Networking](185_W4_OS_ENTERPRISE_NETWORKING.md)

## Roadmap y condiciones de evolución

Redes personales V1; enterprise Wi-Fi tras pruebas de certificados.

---

[Anterior](177_W4_OS_NETWORKMANAGER_INTEGRATION.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](179_W4_OS_ETHERNET_SYSTEM.md)
