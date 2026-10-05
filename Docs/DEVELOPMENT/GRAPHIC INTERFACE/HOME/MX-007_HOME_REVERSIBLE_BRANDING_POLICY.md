# MX-007 · Politica minima de branding reversible para `Home`

Fecha: 2026-10-04
Estado: Propuesta operativa para implementacion
Alcance: `W4 OS Home V1` sobre `GNOME + GDM`
Dependencias: `MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`, `ADR-006`

## Proposito

Fijar el alcance minimo del branding visible de `Home V1` sin abrir un fork del shell ni una capa visual dificil de mantener.

Esta politica define:

- que branding puede entrar ya;
- como debe aplicarse;
- que preferencias puede tocar;
- y que limites deben respetarse para seguir siendo reversible.

## Principios de este corte

1. Branding por recursos y defaults, no por fork de binarios.
2. Las preferencias del usuario prevalecen despues del primer arranque o de la creacion del perfil.
3. Los defaults deben aplicarse por mecanismos upstream soportados.
4. Ninguna actualizacion debe reimponer branding contra una eleccion explicita del usuario.
5. `Home` puede tener identidad propia, pero sin contaminar otras rutas desktop.

## Decisiones operativas

### 1. Wallpaper por defecto

`Home V1` puede fijar un wallpaper W4 por defecto.

Reglas:

- debe distribuirse como recurso empaquetado;
- debe existir fallback claro si el asset falta;
- debe aplicarse solo como valor por defecto de perfil nuevo;
- no debe restaurarse en cada login ni en cada update si el usuario ya eligio otro fondo.

### 2. Tema por defecto

`Home V1` puede fijar una seleccion visual por defecto de bajo riesgo.

Reglas:

- debe existir una variante clara y una oscura coherentes con accesibilidad;
- el tema debe aplicarse mediante APIs o mecanismos soportados por `GNOME`;
- no debe forzarse sobre aplicaciones donde rompa controles, contraste o legibilidad;
- el sistema puede traer un tema W4 ligero o una configuracion W4 sobre tema upstream, pero no debe exigir un fork profundo del toolkit o de `gnome-shell`.

Decision de prudencia para V1:

la primera implementacion debe priorizar una capa ligera sobre assets, paleta y defaults antes que una tematizacion agresiva.

### 3. Iconos por defecto

`Home V1` puede fijar una preferencia de iconos, pero con alcance limitado.

Reglas:

- se debe partir de un set compatible con Debian y mantenible;
- W4 solo debe anadir iconos propios cuando sean realmente necesarios;
- no se debe sustituir todo el catalogo iconografico si eso aumenta demasiado mantenimiento;
- siempre debe existir fallback por nombres estandar.

Decision de prudencia para V1:

usar una base compatible con upstream y reservar iconos W4 para branding puntual, assets propios y puntos de identidad donde aporten valor real.

### 4. Assets de login sobre `GDM`

`Home V1` puede aplicar branding ligero a la pantalla de login, pero sin tocar el motor de autenticacion.

Permitido:

- fondo o asset visual de login;
- logo o identificador visual ligero;
- ajustes empaquetados por interfaces soportadas por `GDM`.

No permitido:

- alterar flujo PAM;
- parchear internals de `GDM`;
- introducir scripts no revisados;
- romper accesibilidad o el retorno seguro tras fallo de sesion.

Decision de prudencia para V1:

si el branding de login exige un camino fragil o poco portable, debe quedarse fuera de este corte.

### 5. Shell y sesion

`Home V1` puede fijar defaults reversibles de shell solo cuando sean de bajo costo de mantenimiento.

Permitido:

- fondo por defecto;
- tema por defecto;
- iconos por defecto;
- favoritos o lanzadores por defecto si se aplican solo en perfil nuevo;
- defaults pequenos de sesion cuando se puedan revertir por configuracion.

No permitido:

- dock obligatorio acoplado;
- extensiones propias como requisito de la experiencia;
- parches profundos a `gnome-shell`;
- reescritura del panel o del comportamiento base del shell.

## Mecanismo de aplicacion

La politica de branding reversible debe seguir esta prioridad:

1. defaults de edicion para perfil nuevo;
2. preferencias ya elegidas por el usuario;
3. restricciones validas solo si el producto las documenta explicitamente.

Reglas operativas:

- usar defaults del sistema o mecanismos upstream equivalentes;
- aplicar cambios a claves propias o a defaults de perfil nuevo;
- registrar version de esquema si se introduce una migracion;
- no copiar perfiles personales completos como plantilla global;
- no modificar preferencias del usuario en cada arranque.

## Separacion de capas

### Branding comun W4

Debe contener:

- identidad basica reutilizable;
- recursos visuales comunes;
- reglas de naming y atribucion compartidas;
- assets reutilizables por varias ediciones.

### Branding especifico de `Home`

Debe contener:

- wallpaper por defecto de `Home`;
- defaults visuales propios de `Home`;
- assets de escritorio o login que solo aplican a esta edicion;
- pequenas decisiones de identidad visibles en `GNOME + GDM`.

Regla:

la capa especifica de `Home` no debe migrar automaticamente a `Business` ni a variantes no predeterminadas.

## Limites de reversibilidad

Para que una personalizacion entre en `MX-007`, debe cumplir todas estas condiciones:

1. puede desactivarse sin romper login o sesion;
2. no exige fork profundo de shell, toolkit o login manager;
3. no sobrescribe una eleccion explicita del usuario en cada update;
4. tiene fallback claro si falta un recurso;
5. no incrementa de forma fuerte la carga de QA por variante.

## Reglas por area

### Login

- `GDM` sigue siendo el motor de autenticacion soportado;
- branding solo por configuracion y recursos;
- sin autologin por defecto en la ruta base documentada.

### Tema

- defaults claros/oscuros;
- contraste y foco visibles;
- sin tematizacion agresiva en dialogos criticos.

### Iconos

- nombres estandar y fallback;
- W4 solo donde aporte identidad real.

### Wallpaper y assets

- recursos empaquetados y versionados;
- fallback seguro;
- sin reemplazo continuo de preferencias del usuario.

## Fuera de alcance en este corte

Queda fuera:

- onboarding visual completo;
- shell altamente customizado;
- paquete extenso de extensiones GNOME propias;
- retocar toda la iconografia del sistema;
- politicas de personalizacion avanzadas por cuenta;
- settings visuales ricos propios de `MX-008`.

## Criterios de aceptacion

La politica queda bien cerrada cuando responda sin ambiguedad:

1. que branding entra en `Home V1`;
2. que branding queda fuera;
3. como se respetan las preferencias del usuario;
4. como se separa branding comun vs branding de `Home`;
5. que mecanismo de defaults debe usarse para implementar.

## Impacto en los siguientes pasos

Con esta politica ya definida, el siguiente paso tecnico razonable es bajar el contrato a implementacion mediante:

- manifests o composicion de paquetes,
- layout de recursos,
- mecanismo de defaults,
- y overlays o paquetes necesarios para materializar `Home` sin romper reversibilidad.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`
- `Docs/W4-OS/096_W4_OS_DESKTOP_SHELL.md`
- `Docs/W4-OS/098_W4_OS_LOGIN_MANAGER.md`
- `Docs/W4-OS/100_W4_OS_DESKTOP_CONFIGURATION.md`
- `Docs/W4-OS/101_W4_OS_THEME_SYSTEM.md`
- `Docs/W4-OS/102_W4_OS_ICON_SYSTEM.md`
- `Docs/W4-OS/301_W4_OS_BRANDING_ARCHITECTURE.md`
- `Docs/W4-OS/305_W4_OS_DESKTOP_BRANDING.md`
