# 184 · W4 OS — Network Security

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Red · **Responsabilidad propuesta:** Integración de conectividad  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Proteger comunicación y exposición local con controles coherentes entre interfaces.

## Alcance, arquitectura y decisiones

Clasificar perfiles públicos, privados y administrados; firewall, descubrimiento, VPN y certificados consumen esa clasificación. La confianza no se deduce sólo del nombre de red.

## Componentes y flujo operativo

1. Detectar cambio
2. aplicar perfil
3. ajustar servicios permitidos
4. verificar reglas y rutas
5. informar excepciones.

## Seguridad y riesgos

Evitar que conectar VPN habilite servicios en interfaz pública. Separar puertos de contenedores de permisos de escritorio.

## Criterios de aceptación

Aceptar transición de red empresarial a pública sin exposición residual.

## Rendimiento y evidencia

Medir puertos abiertos inesperados y cambios de política.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [159 — Firewall System](159_W4_OS_FIREWALL_SYSTEM.md)
- [176 — Network Architecture](176_W4_OS_NETWORK_ARCHITECTURE.md)
- [181 — VPN Architecture](181_W4_OS_VPN_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Perfil restrictivo común V1; excepciones por servicio documentado.

## Comprobación de exposición efectiva

El expediente de una regla debe identificar servicio, interfaz, direcciones de escucha y propósito. Habilitar una aplicación no implica abrir su puerto en todas las redes. Al cambiar de perfil se consulta el estado efectivo del firewall y de los sockets; la etiqueta pública o privada de la UI no basta. La matriz prueba IPv4 e IPv6 y la interacción con VPN o contenedores configurados, registrando excepciones donde un componente administra sus propias reglas.

---

[Anterior](183_W4_OS_PROXY_SYSTEM.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](185_W4_OS_ENTERPRISE_NETWORKING.md)
