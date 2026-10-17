# W4 OS · Estrategia de transicion de `System API` y Linux Base hacia Rust post-V1

Documento de decision para dejar asentada la estrategia de lenguaje y runtime del proyecto en dos etapas:

1. cerrar la V1 de `W4 OS Home`, `W4 OS Business` y `W4 OS Server` con la base actual en PHP,
2. y ejecutar despues una migracion progresiva de `System API`, `W4 Linux Base` y estructuras nucleares del proyecto hacia Rust.

## 1. Decision ejecutiva

La decision vigente del proyecto es:

- **corto plazo / cierre V1**: parchear o forkear `pecl/DBus` para `PHP 8.4` con el objetivo de no frenar la validacion V1 de las tres ediciones del sistema operativo,
- **mediano plazo / post-V1**: reemplazar gradualmente la implementacion principal en PHP de `System API`, `W4 Linux Base` y demas estructuras de plataforma por una implementacion en Rust.

Esta decision convierte el fork de `pecl/DBus` en una medida transitoria de cierre de producto, no en la apuesta arquitectonica final del proyecto.

## 2. Motivo de la decision

La evidencia operativa ya confirma que:

- el proyecto si puede correr sobre un Linux moderno real con `PHP 8.4`,
- pero `pecl/DBus 0.1.1` falla en `make` por incompatibilidad con la API moderna de PHP,
- y reabrir en este momento una migracion total de lenguaje retrasaria innecesariamente el cierre V1 de `Home`, `Business` y `Server`.

Por eso la estrategia elegida es separar los horizontes:

- **V1 del producto** primero,
- **migracion de plataforma a Rust** despues.

## 3. Alcance de la etapa transitoria V1

Hasta declarar validada la V1 de las ediciones:

- `W4 OS Home`
- `W4 OS Business`
- `W4 OS Server`

el proyecto mantiene PHP como lenguaje operativo principal para:

- `System API`,
- `w4ctl`,
- validadores,
- runners de build y QA,
- tooling de `W4 Linux Base`,
- y automatizaciones actuales del repositorio.

En esta etapa, el trabajo autorizado sobre D-Bus nativa en PHP es:

- parchear o forkear `pecl/DBus`,
- estabilizarlo contra `PHP 8.4`,
- usarlo solo como puente para completar validaciones V1,
- y evitar expandir su alcance mas alla de lo estrictamente necesario para el cierre de release.

## 4. Restriccion arquitectonica de esta etapa

Aunque el fork de `pecl/DBus` se adopte para completar V1, debe tratarse como una **solucion puente**. Eso implica:

- no considerarlo base definitiva del runtime nativo,
- no extender innecesariamente el acoplamiento de nuevas capacidades a esa extension,
- no asumir mantenimiento indefinido de la estrategia PHP + extension C legacy como arquitectura final,
- y documentar siempre que el objetivo post-V1 es migrar el nucleo de plataforma a Rust.

## 5. Disparador de la migracion post-V1

La migracion principal a Rust debe comenzar **una vez validada la V1** de las ediciones del sistema operativo.

Ese disparador significa:

- V1 validada funcional y operativamente,
- expediente de release V1 consolidado,
- y verticales bloqueantes de producto resueltas para `Home`, `Business` y `Server`.

Antes de ese punto, Rust no es la ruta principal para destrabar la V1; despues de ese punto, si pasa a ser la ruta prioritaria de evolucion de plataforma.

## 6. Alcance previsto de la migracion a Rust

Terminada la validacion V1, la hoja de evolucion del proyecto pasa a priorizar migracion hacia Rust en:

- `System API`,
- runtime nativo D-Bus,
- estructuras base de `W4 Linux Base`,
- servicios y daemons de control local,
- y otras piezas estructurales del proyecto donde hoy PHP cumple un rol de plataforma o runtime.

La migracion no implica descartar inmediatamente toda la capa PHP, sino convertirla de capa principal a capa transitoria, de compatibilidad o de orchestration segun corresponda.

## 7. Interpretacion correcta de esta decision

La lectura correcta de esta estrategia es:

- **no** se abandona ahora PHP como base de ejecucion del proyecto,
- **si** se acepta que la arquitectura destino para Linux Base y la `System API` no sera PHP,
- **si** se autoriza un fork o patch de `pecl/DBus` para cerrar V1,
- y **si** se deja comprometida desde ahora la migracion post-V1 a Rust como direccion oficial del proyecto.

## 8. Resumen ejecutivo

`pecl/DBus` parcheado/forkeado para `PHP 8.4` queda autorizado como estrategia de transicion para completar la V1 de `W4 OS Home`, `W4 OS Business` y `W4 OS Server`.

Una vez validada esa V1, la direccion oficial del proyecto sera migrar `System API`, `W4 Linux Base` y las estructuras nucleares hoy implementadas en PHP hacia Rust.
