# 114 · W4 OS — Network Settings

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Centro de control · **Responsabilidad propuesta:** Configuración y experiencia  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Configurar conectividad desde una interfaz coherente con NetworkManager.

## Alcance, arquitectura y decisiones

Panel manipula perfiles mediante API, incluyendo Wi-Fi, Ethernet, proxy y VPN soportada. Credenciales se guardan en almacén autorizado, separadas de presentación.

## Componentes y flujo operativo

1. Seleccionar conexión
2. validar parámetros
3. aplicar provisionalmente
4. comprobar ruta
5. confirmar o recuperar perfil anterior.

## Seguridad y riesgos

No mostrar contraseñas por defecto ni enviarlas en diagnóstico. Cambios remotos de red necesitan mecanismo de retorno si se pierde gestión.

## Criterios de aceptación

Aceptar contraseña errónea y configuración estática que corta acceso con restauración controlada.

## Rendimiento y evidencia

Medir tiempo de conexión.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [176 — Network Architecture](176_W4_OS_NETWORK_ARCHITECTURE.md)
- [177 — NetworkManager Integration](177_W4_OS_NETWORKMANAGER_INTEGRATION.md)
- [181 — VPN Architecture](181_W4_OS_VPN_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Funciones domésticas V1; política empresarial por integración posterior.

## Aplicación provisional de cambios

El backend guarda un punto de retorno antes de sustituir un perfil activo cuando la herramienta lo permita. La interfaz muestra que la conexión se está comprobando y no persiste éxito sólo por haber enviado la petición. Si el cambio corta el canal remoto, el equipo recupera el perfil anterior según plazo y política definidos en la implementación. En uso local se mantiene una vía para editar parámetros sin internet. Las pruebas comparan rutas y DNS antes/después, además del indicador gráfico de conexión.

---

[Anterior](113_W4_OS_HARDWARE_SETTINGS.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](115_W4_OS_USER_SETTINGS.md)
