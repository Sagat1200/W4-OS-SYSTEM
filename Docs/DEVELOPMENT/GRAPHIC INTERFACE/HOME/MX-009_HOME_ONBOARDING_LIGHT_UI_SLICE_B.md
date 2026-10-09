# MX-009 · Home onboarding light UI · Slice B

Estado: abierto
Fecha: 2026-10-08
Alcance: `W4 OS Home V1`
Dependencias: `MX-008`, `MX-009_HOME_USABLE_V1_CONTRACT`, `MX-009_HOME_ONBOARDING_LOCAL_READINESS`

## Objetivo

Abrir el siguiente slice visible de `MX-009` con una UI ligera de onboarding local sobre la base ya materializada de `firstboot` y `live-prep`, sin convertir este subcorte en un wizard grande ni en una superficie paralela a `GNOME Settings`.

## Referencias

- `Docs/W4-OS/232_W4_OS_HOME_ONBOARDING.md`
- `Docs/W4-OS/408_W4_OS_V1_HOME_SCOPE.md`
- `Docs/W4-OS/104_W4_OS_ACCESSIBILITY_SYSTEM.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-009_HOME_USABLE_V1_CONTRACT.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-009_HOME_ONBOARDING_LOCAL_READINESS.md`

## Regla de alcance

Este slice si abre:

- una superficie visible y pequena de primer inicio;
- contenido breve con pasos claros y accionables;
- un cierre explicito de onboarding local sin reaparecer sin motivo;
- un handoff limpio hacia `W4 Settings`, updates y pasos diferidos.

Este slice no abre todavia:

- asistente multipantalla largo;
- creacion guiada de cuenta W4;
- telemetria opt-in interactiva completa;
- respaldo externo guiado completo;
- accesibilidad completa;
- una shell o centro de control alterno a `GNOME`.

## Contrato funcional

La UI ligera de onboarding de `Home` debe:

1. aparecer solo cuando el estado local de primer inicio indique que el recorrido aplica;
2. mostrar una explicacion breve del estado inicial del sistema;
3. exponer como maximo entre `3` y `5` pasos visibles;
4. reutilizar rutas ya materializadas de `Home` antes de abrir superficies nuevas;
5. permitir omitir pasos opcionales;
6. dejar una finalizacion explicita y no reaparecer sin razon operacional.

## Pasos visibles esperados

El primer aterrizaje de la UI ligera debe limitarse a pasos de bajo riesgo y alta claridad:

1. bienvenida corta y que esperar del equipo;
2. revision de privacidad local como paso informativo;
3. acceso visible a `W4 Settings`;
4. acceso visible a updates;
5. cierre del onboarding local.

Pasos como cuenta local guiada, respaldo externo guiado, telemetria opt-in completa o accesibilidad ampliada quedan diferidos.

## Contrato tecnico inicial

Este slice debe poder probarse sin depender de inspeccion manual extensa. Como minimo debe dejar verificable:

1. un artefacto UI pequeno y versionable;
2. una definicion canonica de pasos visibles y diferidos;
3. reglas de aparicion y no reaparicion;
4. handoff a rutas reales ya materializadas de `Home`;
5. una separacion explicita entre onboarding ligero y accesibilidad completa.

## Entregables obligatorios

1. contrato tecnico-operativo propio de `Slice B`;
2. un artefacto UI ligero de referencia o bundle visible pequeno;
3. una lectura estructurada de pasos visibles y pasos diferidos;
4. cobertura reproducible sobre workspace sintetico;
5. trazabilidad ejecutiva alineada con `MX-009`.

## Criterio de cierre

Este subcorte solo puede tratarse como materializado cuando:

1. exista una UI ligera visible y acotada para primer inicio;
2. los pasos visibles queden limitados y coherentes con `232` y `408`;
3. la superficie no duplique `GNOME Settings` ni rehaga `MX-008`;
4. el cierre del slice deje todavia diferidas la accesibilidad completa y el onboarding grande.

## Riesgo activo

El riesgo principal de este subcorte es mezclar tres frentes distintos en una misma pieza:

- onboarding ligero,
- onboarding completo,
- accesibilidad completa.

La proteccion del slice consiste en mantener la UI pequena, apoyada en entrypoints ya existentes y con backlog diferido explicito.
