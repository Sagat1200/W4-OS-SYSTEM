# 255 · W4 OS — Podman Docker Support

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Desarrollo · **Responsabilidad propuesta:** Plataforma de desarrollo  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Documentar Podman y Docker sin habilitar dos autoridades conflictivas por defecto.

## Alcance, arquitectura y decisiones

Evaluar Podman rootless como propuesta principal; Docker opcional por compatibilidad de proyectos, con advertencia concreta de privilegios del daemon/grupo.

## Componentes y flujo operativo

1. Seleccionar motor
2. instalar desde origen aprobado
3. verificar red y almacenamiento
4. probar proyecto
5. documentar diferencias.

## Seguridad y riesgos

No tratar membresía docker como permiso inocuo. Publicación de puertos debe respetar revisión de firewall y exposición efectiva.

## Criterios de aceptación

Aceptar contenedor de prueba, volumen y puerto publicado con alcance previsto.

## Rendimiento y evidencia

Medir compatibilidad y recursos ociosos.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [147 — Group Management](147_W4_OS_GROUP_MANAGEMENT.md)
- [159 — Firewall System](159_W4_OS_FIREWALL_SYSTEM.md)
- [254 — Container Strategy](254_W4_OS_CONTAINER_STRATEGY.md)

## Roadmap y condiciones de evolución

Un motor de referencia V1; segundo sólo por necesidad verificada.

## Compatibilidad y exposición de puertos

La compatibilidad con una interfaz de comandos no garantiza equivalencia de red, permisos o almacenamiento entre motores. El ejemplo de proyecto debe especificar el motor probado y evitar mezclar su estado. Una publicación de puerto se valida desde otra máquina de laboratorio para comprobar dirección e interfaz efectivas, no sólo por respuesta dentro del contenedor. La desinstalación del motor conserva o retira volúmenes mediante elección explícita: eliminar paquetes no debe destruir bases de datos de desarrollo por una limpieza automática.

---

[Anterior](254_W4_OS_CONTAINER_STRATEGY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](256_W4_OS_VIRTUALIZATION_ARCHITECTURE.md)
