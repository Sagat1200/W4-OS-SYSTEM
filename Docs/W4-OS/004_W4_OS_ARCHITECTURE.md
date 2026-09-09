# 004 · W4 OS — Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Fundación y ediciones · **Responsabilidad propuesta:** Arquitectura de plataforma  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Fijar límites entre upstream, plataforma, edición, aplicaciones y servicios externos.

## Alcance, arquitectura y decisiones

Debian aporta ABI, paquetes y actualizaciones originales. W4 Linux Base integra arranque, instalación, seguridad y recuperación. Los perfiles Home/Business consumen esa base; servicios cloud son adaptadores opcionales.

## Componentes y flujo operativo

1. Fuente verificada
2. compilación aislada
3. repositorio candidato
4. imagen
5. pruebas
6. promoción. En el dispositivo: política
7. operación autorizada
8. resultado auditable.

## Seguridad y riesgos

Separar el proceso gráfico sin privilegios del ejecutor del sistema. Evitar que una caída cloud impida iniciar sesión local o recuperar el equipo.

## Criterios de aceptación

Aceptar si cada componente tiene propietario, interfaz y modo de fallo definido; simular pérdida de red, actualización fallida y política inválida sin perder datos de usuario.

## Rendimiento y evidencia

Medir tiempo de arranque local sin red, latencia de operaciones autorizadas y tiempo hasta recuperación útil tras fallo de actualización.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [008 — Shared Core Architecture](008_W4_OS_SHARED_CORE_ARCHITECTURE.md)
- [009 — Layer Model](009_W4_OS_LAYER_MODEL.md)
- [071 — Update Architecture](071_W4_OS_UPDATE_ARCHITECTURE.md)
- [156 — Security Architecture](156_W4_OS_SECURITY_ARCHITECTURE.md)

## Roadmap y condiciones de evolución

Congelar interfaces mínimas antes de ampliar servicios; revisar arquitectura con evidencia del MVP.

## Contratos entre capas

| Capa | Entrada | Salida | Responsable funcional propuesto |
|---|---|---|---|
| Debian fijado | Repositorios autenticados y fuente | Paquetes y cobertura upstream registrada | Integración upstream |
| W4 Linux Base | Paquetes Debian y deltas W4 | Manifiesto común, servicios y recuperación | Plataforma |
| Edición | Manifiesto base y perfil | Imagen Home o Business | Producto e integración |
| Dispositivo | Imagen y configuración autorizada | Estado observado y operaciones locales | Servicios del sistema |
| Plano Business | Identidad y política organizacional | Estado deseado y tareas tipadas | Gestión empresarial |
| Servicios opcionales | Consentimiento/autoridad y token acotado | Función adicional y estado de sincronización | Adaptador del servicio |

La dependencia permitida apunta hacia abajo en el sistema local: una aplicación utiliza APIs de la sesión, la sesión utiliza servicios y éstos utilizan mecanismos de la base. El plano empresarial puede solicitar cambios, pero no reemplaza la autorización del servicio local. El motor de paquetes no acepta instrucciones de un plugin de tienda como comandos de shell.

## Ejemplo de fallo transversal

Una actualización de controlador impide iniciar el escritorio. El arranque registra que la versión candidata no cumple salud; el usuario accede a una entrada conocida o al medio de recuperación. El recuperador verifica que el kernel, initramfs, raíz y base dpkg pertenezcan al objetivo seleccionado. Conserva documentos creados antes del reinicio en el volumen de datos. Si existe una migración persistente incompatible, el sistema no promete revertirla por snapshot: ofrece restauración específica o reparación. El incidente queda disponible en logs que no retroceden con la raíz.

Este recorrido conecta arquitectura con pruebas reales. Si el prototipo no puede demostrarlo, la interfaz no debe ofrecer recuperación automática ni publicitar atomicidad. Una ruta manual bien definida puede satisfacer el MVP mientras se investiga una mejora posterior.

---

[Anterior](003_W4_OS_PRODUCT_FAMILY.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](005_W4_OS_DEBIAN_BASE_STRATEGY.md)
