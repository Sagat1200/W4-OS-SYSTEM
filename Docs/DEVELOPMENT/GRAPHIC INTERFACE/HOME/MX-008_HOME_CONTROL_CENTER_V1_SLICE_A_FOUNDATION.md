# MX-008 · Slice A de `Control Center` para `Home`

Fecha: 2026-10-07
Estado: Activo para implementacion incremental
Alcance: `W4 OS Home V1`
Dependencias cerradas: `MX-004`, `MX-007`, `TECH-1.138`, `TECH-1.139`
Dependencias abiertas: `MX-009`

## Objetivo

Seleccionar el primer subconjunto implementable de `MX-008` sobre la base real `GNOME + GDM` ya validada en `Home`.

Este corte no intenta cubrir todos los ajustes del sistema ni reemplazar `gnome-control-center`. Su objetivo es abrir una primera capa de `Control Center` con modulos de alto valor y anclaje tecnico verificable, de modo que el siguiente trabajo de codigo arranque sobre fuentes de verdad y fronteras de privilegio ya definidas.

## Seleccion del slice

El `Slice A` de `MX-008` incluye exactamente estos modulos:

1. `Sistema`
2. `Seguridad`
3. `Actualizaciones`
4. `Almacenamiento`

Se eligen estos cuatro porque ya cuentan con mejores anclajes tecnicos reales que el resto del mapa V1:

- `Sistema` ya tiene contratos documentales y superficies concretas como `hostname_prefix`, `edition-policy.env`, `desktop-defaults` y `branding`.
- `Seguridad` ya tiene evidencia operativa y verificadores en `SecurityBaselineToolkit`.
- `Actualizaciones` ya tiene flujo real y toolkit versionado en `UpdateToolkit`.
- `Almacenamiento` ya comparte conceptos visibles con instalacion, snapshots y `btrfs`.

Quedan fuera de este slice:

- `Red`
- `Usuarios`
- `Aplicaciones`

Esos modulos siguen dentro de `MX-008`, pero no entran en el primer corte de implementacion porque hoy tendrian menos superficie reutilizable o empujarian demasiado pronto hacia una UI mas grande.

## Principio de implementacion

Este slice se abre como `read-first`.

Eso significa:

- prioridad a lectura de estado efectivo;
- prioridad a procedencia del valor;
- prioridad a explicacion y evidencia;
- cambios de estado solo si ya existe backend autorizado y contrato suficientemente estable.

El objetivo inicial no es “tener muchos botones”, sino una superficie confiable que muestre:

- que esta pasando;
- de donde sale el valor;
- si el usuario puede cambiarlo;
- y que autoridad tendria que intervenir.

## Clasificacion de modulos del slice

### `Sistema`

- clase: `W4-augmented`
- modo inicial: lectura con cambios puntuales diferidos
- autorizacion: requerida para cambios de sistema
- uso de `GNOME`: deep-link a paneles nativos cuando el upstream ya cubre idioma, teclado, fecha o apariencia personal

#### Alcance V1 del modulo

- nombre del equipo efectivo;
- prefijo de identidad de la edicion;
- zona horaria efectiva;
- fuente de tiempo o sincronizacion;
- diferencia entre ajuste personal y ajuste de sistema;
- hostname de la sesion live frente al hostname instalado cuando aplique.

#### Fuentes tecnicas iniciales

- `config/editions/home/policy.json`
- `src/Installer/EditionPolicyToolkit.php`
- `scripts/generate_system_overlay.php`
- `scripts/generate_live_bundle.php`
- `/etc/hostname`
- `/etc/w4/edition-policy.env`

#### Regla de cambios

En este slice, `Sistema` puede abrirse primero en lectura y explicacion. Los cambios de hostname o sincronizacion no deben entrar hasta que exista controlador autorizado y relectura fiable del estado.

### `Seguridad`

- clase: `W4-augmented`
- modo inicial: lectura con evidencia
- autorizacion: requerida para cambios
- uso de `GNOME`: solo como complemento visual; la fuente de verdad debe seguir viniendo de la baseline y la politica efectiva

#### Alcance V1 del modulo

- estado visible de firewall;
- politica entrante/saliente efectiva;
- estado de AppArmor;
- presencia de perfiles `enforce`;
- estado de `ssh` por defecto;
- explicacion de procedencia de la politica por edicion.

#### Fuentes tecnicas iniciales

- `src/Security/SecurityBaselineToolkit.php`
- `scripts/generate_security_baseline_bundle.php`
- `build/security/<perfil>/security-baseline.json`
- `config/editions/home/policy.json`
- `/etc/w4/edition-policy.env`

#### Regla de cambios

Este slice no debe prometer todavia toggles completos de seguridad. Primero debe mostrar evidencia verificable y distinguir entre `estado`, `politica` y `capacidad de cambio`.

### `Actualizaciones`

- clase: `W4-augmented`
- modo inicial: lectura fuerte con opcion futura de accion controlada
- autorizacion: requerida para aplicar o reconciliar
- uso de `GNOME`: la UI propia puede resumir estado, pero no debe ocultar origen ni reemplazar el flujo validado del coordinador

#### Alcance V1 del modulo

- estado de actualizacion actual;
- ultima operacion conocida;
- version/objetivo visible;
- backend o modo de aplicacion;
- snapshot previo cuando exista;
- resultado final y necesidad de reinicio.

#### Fuentes tecnicas iniciales

- `src/Update/UpdateToolkit.php`
- `scripts/prepare_update_operation.php`
- `scripts/advance_update_operation.php`
- `scripts/reconcile_update_operation.php`
- `scripts/generate_update_executor.php`
- contratos y evidencias de `MX-004`

#### Regla de cambios

Las acciones deben abrirse solo sobre el mismo contrato ya validado de update. No se introduce una ruta paralela desde UI que ejecute pasos fuera del coordinador.

### `Almacenamiento`

- clase: `W4-augmented`
- modo inicial: lectura y resumen
- autorizacion: requerida para limpieza o acciones destructivas
- uso de `GNOME`: puede coexistir con herramientas del sistema, pero el modulo W4 debe centrarse en resumen, snapshots y evidencia

#### Alcance V1 del modulo

- layout base esperado de `btrfs`;
- visibilidad de subvolumenes principales;
- backend de snapshots disponible;
- capacidad de recuperacion visible;
- espacio total y recuperable de forma prudente;
- riesgos de limpieza y limites de las estimaciones.

#### Fuentes tecnicas iniciales

- `src/Installer/InstallerToolkit.php`
- `src/Update/UpdateToolkit.php`
- `config/editions/home/policy.json`
- manifiestos y contratos de instalacion `btrfs`

#### Regla de cambios

En este slice no deben entrar acciones destructivas. Primero debe existir un resumen de almacenamiento y snapshots con semantica clara y sin falsa precision.

## Modulos diferidos

### `Red`

Se difiere porque el slice inicial ya tiene suficiente volumen en `Sistema`, `Seguridad`, `Actualizaciones` y `Almacenamiento`, y porque mezclar conectividad con seguridad demasiado pronto puede volver confusa la frontera del backend.

### `Usuarios`

Se difiere porque `GNOME` ya cubre parte del terreno y cualquier desvio rapido hacia identidad, privilegios o enrolamiento rompe el principio incremental.

### `Aplicaciones`

Se difiere porque podria empujar al proyecto a un catalogo, marketplace o gestion profunda de permisos antes de cerrar el `Control Center` base.

## Contrato UI minimo del slice

El `Slice A` debe poder representarse con una home simple de `Settings` que muestre:

1. resumen del modulo;
2. estado principal;
3. procedencia del dato;
4. si el modulo es solo lectura o admite cambio;
5. deep-link a `GNOME` si el panel nativo ya cubre parte del caso.

La pantalla inicial no necesita grids complejos ni navegacion profunda. Debe priorizar claridad y evidencia.

## Contrato backend minimo del slice

Cada modulo debe exponer, como minimo:

- `summary`: estado principal legible;
- `source_of_truth`: archivo, politica, servicio o toolkit que manda;
- `authority`: usuario, sistema, politica o pipeline;
- `capabilities`: lectura, lectura/escritura, deep-link o bloqueado;
- `last_checked_at`: marca de lectura;
- `evidence`: pares clave/valor o artefactos de soporte;
- `actions`: lista vacia o acciones autorizadas conocidas.

## Secuencia recomendada de implementacion

1. definir adaptadores de lectura por modulo;
2. construir el resumen de `Sistema`;
3. añadir `Seguridad` sobre `SecurityBaselineToolkit`;
4. añadir `Actualizaciones` sobre `UpdateToolkit`;
5. añadir `Almacenamiento` con foco en `btrfs` y snapshots;
6. solo despues evaluar cambios autorizados puntuales.

## Criterios de cierre del slice

El `Slice A` puede considerarse correctamente aterrizado cuando existan estas condiciones:

1. los cuatro modulos ya quedan fijados como primer corte oficial de `MX-008`;
2. cada modulo tiene clase, alcance, fuentes tecnicas y regla de cambios documentadas;
3. el frente queda definido como `read-first`;
4. el repo ya refleja que el siguiente trabajo tecnico debe empezar por adaptadores/lecturas, no por una UI amplia;
5. `MX-009` queda protegido de absorcion prematura de settings no estabilizados.

## Siguiente paso inmediato

Con este slice ya fijado, el siguiente trabajo tecnico correcto es abrir la primera capa de adaptadores de lectura para `Sistema`, `Seguridad`, `Actualizaciones` y `Almacenamiento`, manteniendo los cambios de estado detras de contratos ya existentes o explicitamente diferidos.

## Referencias

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-008_HOME_CONTROL_CENTER_V1_CONTRACT.md`
- `Docs/W4-OS/112_W4_OS_SYSTEM_SETTINGS.md`
- `Docs/W4-OS/116_W4_OS_SECURITY_SETTINGS.md`
- `Docs/W4-OS/117_W4_OS_UPDATE_SETTINGS.md`
- `Docs/W4-OS/118_W4_OS_STORAGE_SETTINGS.md`
- `src/Installer/EditionPolicyToolkit.php`
- `src/Installer/InstallerToolkit.php`
- `src/Security/SecurityBaselineToolkit.php`
- `src/Update/UpdateToolkit.php`
- `scripts/generate_system_overlay.php`
- `scripts/generate_security_baseline_bundle.php`
- `scripts/prepare_update_operation.php`
- `scripts/advance_update_operation.php`
- `scripts/reconcile_update_operation.php`
