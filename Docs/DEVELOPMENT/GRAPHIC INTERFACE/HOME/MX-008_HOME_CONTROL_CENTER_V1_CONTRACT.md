# MX-008 · Control Center V1 de `Home` sobre `GNOME`

Fecha: 2026-10-07
Estado: Activo para aterrizaje operativo
Alcance: `W4 OS Home V1`
Dependencias cerradas: `MX-006`, `MX-007`, `ADR-006`
Dependencias abiertas: `MX-004`, `MX-009`

## Objetivo

Abrir una primera definicion operativa de `Control Center` para `Home` sin fracturar la base ya validada de `GNOME + GDM`.

El objetivo de `MX-008` no es construir todavia una suite completa de configuracion propia, sino fijar:

1. que modulos V1 entran realmente;
2. que parte se apoya en `gnome-control-center`;
3. que parte requiere una capa W4 minima;
4. que contratos API y de autorizacion deben congelarse antes de implementar UI;
5. que queda explicitamente fuera hasta `MX-009`.

## Principio rector

La regla de este frente es:

- reutilizar capacidades nativas de `GNOME` cuando ya resuelvan el caso;
- introducir una capa W4 solo donde haga falta visibilidad adicional, politica, autorizacion o integracion propia;
- evitar que `Control Center` se convierta en un lanzador privilegiado sin contrato estable.

## Resultado esperado del ciclo

Al cerrar `MX-008`, el proyecto debe contar con un contrato V1 suficiente para implementar un `Control Center` modular sobre `Home`, con una separacion clara entre:

- paneles nativos de `GNOME`;
- modulos W4 de lectura;
- modulos W4 con cambios autorizados;
- integraciones futuras que todavia no entran en V1.

## Dependencia base ya cerrada

`MX-007` deja como base validada:

- `GNOME + GDM` materializado en `rootfs -> overlay -> live`;
- `gnome-control-center` presente en el baseline real de `Home`;
- defaults `dconf` y branding reversible ya preservados en el artefacto final.

Con ello, `MX-008` ya no diseña contra un shell abstracto. Su referencia visible es el `Home` real sobre `GNOME`.

## Entregables exactos

### E-1 · Mapa de modulos V1

Debe quedar definido un primer set de modulos para `Home`:

1. `Sistema`
2. `Red`
3. `Usuarios`
4. `Seguridad`
5. `Actualizaciones`
6. `Almacenamiento`
7. `Aplicaciones`

Cada modulo debe declarar:

- si es `GNOME-native`, `W4-augmented` o `W4-native`;
- si es solo lectura o lectura/escritura;
- si requiere autorizacion;
- si depende de API W4 propia o de integracion local existente.

### E-2 · Regla de reutilizacion upstream

Debe quedar explicitado:

- que ajustes siguen viviendo en `gnome-control-center`;
- que ajustes solo necesitan deep-link o encapsulacion ligera;
- que ajustes necesitan una fachada W4 por procedencia, politicas o validacion adicional.

Regla minima:

si `GNOME` ya ofrece un panel suficiente para V1 y no hay politica W4 extra, se reutiliza antes de construir UI nueva.

### E-3 · Contrato minimo API/controlador

Debe quedar definido un contrato minimo para modulos con backend:

- lectura de estado efectivo;
- lectura de procedencia o autoridad del valor;
- validacion de cambios;
- autorizacion cuando aplique;
- aplicacion y relectura final del estado.

La UI no ejecuta shell arbitraria ni interpreta comandos sueltos del sistema.

### E-4 · Frontera de privilegios

Debe quedar fijado:

- que cambios son locales de usuario;
- que cambios afectan sistema completo;
- que cambios solo pueden mostrarse como bloqueados por politica;
- que acciones de `Business` o de gobierno central siguen fuera de `Home`.

### E-5 · Handoff hacia `MX-009`

Debe quedar claro que `MX-009` hereda:

- un `Control Center` ya acotado por modulos V1;
- una frontera concreta entre UX visible y backend autorizado;
- una lista de ajustes que pueden formar parte del recorrido inicial de `Home utilizable`.

## Clasificacion inicial propuesta

### Modulos `GNOME-native`

Estos deben reutilizar `gnome-control-center` como primera ruta:

- apariencia basica;
- teclado e idioma de usuario;
- pantalla y energia;
- dispositivos y perifericos comunes;
- cuentas locales si la necesidad V1 no exige logica W4 adicional.

### Modulos `W4-augmented`

Estos pueden apoyarse en integracion local existente, pero requieren una capa W4 para explicar estado, origen o politicas:

- `Sistema`: nombre del equipo, zona horaria, fuente de tiempo, identidad visible;
- `Red`: estado de conectividad y componentes gestionados;
- `Seguridad`: firewall, AppArmor, SSH por defecto y procedencia de politica;
- `Actualizaciones`: estado del sistema, ultimo resultado, disponibilidad y origen del flujo;
- `Almacenamiento`: resumen de volumenes, snapshots y estado visible del dispositivo.

### Modulos `W4-native`

No deben abrirse en V1 salvo que exista contrato y backend suficiente:

- politicas empresariales;
- gobierno multi-tenant;
- administracion remota arbitraria;
- catalogo complejo de aplicaciones con permisos profundos.

## Alcance minimo recomendado de V1

El primer corte implementable de `MX-008` debe ser conservador:

1. home principal de `Settings` con resumen de modulos;
2. deep-link a paneles `GNOME` donde el upstream ya cubre la necesidad;
3. modulos W4 iniciales de lectura para `Seguridad`, `Actualizaciones` y `Almacenamiento`;
4. acciones de cambio solo donde exista backend autorizado y criterio de evidencia.

Esto evita construir una UI grande antes de tener contratos y fuentes estables.

## Fuera de alcance en este ciclo

Queda fuera de `MX-008`:

- reimplementar por completo `gnome-control-center`;
- onboarding completo de `Home`;
- agente empresarial o politicas centralizadas;
- marketplace amplio de aplicaciones;
- operaciones privilegiadas sin contrato API versionado;
- paridad visual con `Business`.

## Riesgos a vigilar

### R-1 · Duplicar `GNOME` sin necesidad

Si se reescriben paneles que el upstream ya resuelve, aumenta la deuda y la deriva frente a Debian.

### R-2 · UI con privilegios implícitos

Si la interfaz mezcla resumen visual y acciones de sistema sin frontera clara, se vuelve fragil y dificil de auditar.

### R-3 · Abrir demasiados modulos a la vez

Si `MX-008` intenta cubrir todos los settings posibles, invade `MX-009` y reabre deuda de backend todavia no cerrada.

### R-4 · Acoplar `Home` a logica `Business`

Si se anticipan politicas empresariales dentro de la UX `Home`, el frente deja de ser incremental y modular.

## Criterios de cierre

`MX-008` puede considerarse correctamente aterrizado cuando existan estas condiciones:

1. modulos V1 identificados y clasificados como `GNOME-native`, `W4-augmented` o `W4-native`;
2. frontera de privilegios y de autorizacion congelada;
3. contrato minimo de lectura/aplicacion para modulos con backend definido;
4. dependencias de `MX-009` actualizadas para apoyarse en este frente;
5. trazabilidad ejecutiva del repo sincronizada con esta apertura.

## Siguiente paso inmediato

Con este marco, el siguiente corte tecnico correcto es:

- congelar el documento operativo de `MX-008` en la trazabilidad del repo;
- seleccionar el primer subconjunto implementable de modulos V1;
- y solo despues abrir cambios de codigo o UI sobre un contrato ya estable.

Ese siguiente subcorte ya queda aterrizado en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-008_HOME_CONTROL_CENTER_V1_SLICE_A_FOUNDATION.md`, que fija un `Slice A` conservador y `read-first` sobre `Sistema`, `Seguridad`, `Actualizaciones` y `Almacenamiento`. Con ello, `MX-008` ya no solo define el mapa completo de modulos V1, sino tambien el primer paquete concreto sobre el que deben arrancar los adaptadores de lectura y la primera home de `Settings`.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/ADR-006_HOME_GRAPHIC_CATALOG_AND_SELECTOR.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_SHELL_AND_BRANDING_MINIMO.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_DEFAULTS_LAYOUT_AND_OVERLAY_IMPLEMENTATION.md`
- `Docs/W4-OS/111_W4_OS_CONTROL_CENTER_ARCHITECTURE.md`
- `Docs/W4-OS/112_W4_OS_SYSTEM_SETTINGS.md`
- `Docs/W4-OS/113_W4_OS_HARDWARE_SETTINGS.md`
- `Docs/W4-OS/114_W4_OS_NETWORK_SETTINGS.md`
- `Docs/W4-OS/115_W4_OS_USER_SETTINGS.md`
- `Docs/W4-OS/116_W4_OS_SECURITY_SETTINGS.md`
- `Docs/W4-OS/117_W4_OS_UPDATE_SETTINGS.md`
- `Docs/W4-OS/118_W4_OS_STORAGE_SETTINGS.md`
- `Docs/W4-OS/119_W4_OS_APPLICATION_SETTINGS.md`
- `Docs/W4-OS/368_W4_OS_CONTROL_CENTER_API.md`
