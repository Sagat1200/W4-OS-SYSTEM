# W4 OS · Matriz de brechas entre `W4-Linux-Base` y estado actual

## Objetivo

Registrar de forma trazable las discrepancias mas relevantes entre:

- la especificacion arquitectonica de `C:\W4\W4-Linux-Base\docs`, y
- la implementacion/evidencia actual de `C:\W4\Packages\W4-OS SYSTEM`.

Este documento no reemplaza la especificacion base ni la matriz maestra del proyecto.
Su funcion es evitar que la documentacion objetivo se lea como si ya fuera evidencia de implementacion cerrada.

## Alcance de esta pasada

La comparacion se concentro en los contratos que afectan directamente:

- `W4 Linux Base` como base comun,
- la relacion entre Home, Business y Server,
- el estado real de `System API`, `w4ctl`, GUI, Agent y politicas,
- y la trazabilidad ejecutiva actual (`MX-006`, `MX-009`, `MX-010`, `MX-012`).

## Convenciones

- `Alineado`: la documentacion objetivo y el estado real ya convergen razonablemente.
- `Parcial`: existe direccion comun, pero la evidencia real aun no alcanza lo prometido por la documentacion.
- `Desalineado`: la documentacion puede inducir a una lectura falsa del estado actual.

## Resumen ejecutivo

La especificacion de `W4-Linux-Base` describe correctamente la direccion arquitectonica de largo plazo, pero hoy el repositorio `W4 OS System` solo tiene evidencia cerrada en:

- supply/build,
- instalacion,
- update y recovery,
- baseline de seguridad,
- y bootstrap Server headless.

Siguen pendientes o en fase anterior de madurez:

- `W4 System API`,
- `w4ctl`,
- GUI/Home integration real,
- Agent/Business piloto,
- y la paridad operativa plena entre Home, Business y Server bajo un mismo contrato de plataforma, aunque las tres ediciones ya cuentan con ancla explicita de politica.

## Matriz de brechas

| ID | Area | Documento base | Estado esperado por la especificacion | Estado real del repo | Evaluacion | Impacto | Accion recomendada |
| --- | --- | --- | --- | --- | --- | --- | --- |
| GAP-001 | Plataforma V1 | `01_V1_SCOPE_AND_OBJECTIVES.md` | V1 ya se define alrededor de `W4 System API`, `W4 Services`, `W4 Providers`, `W4 CLI`, `W4 Recovery` y ejemplos `w4ctl` | El repo actual tiene evidencia fuerte en build/install/update/security/Server, pero no una `System API` materializada ni `w4ctl` operativo como frente validado | Desalineado | Alto | Marcar expresamente en la documentacion de desarrollo que `W4-Linux-Base` representa contrato objetivo y que `System API`/`w4ctl` siguen fuera del estado tecnico validado del repo |
| GAP-002 | Integracion Home | `362_W4_OS_HOME_INTEGRATION.md` | Home conecta `Settings` y experiencia de escritorio a una API comun; incluso se propone validar updates desde GUI y seguir el mismo trabajo por CLI | El proyecto todavia no ha fijado el escritorio oficial V1; `MX-006` sigue en analisis y `MX-007`, `MX-008`, `MX-009` siguen sin iniciar | Desalineado | Alto | Mantener Home integration como objetivo futuro y no como capacidad ya disponible; usar esta matriz como guardrail para la documentacion UX hasta que cierre `MX-006` |
| GAP-003 | Perfil Business | `359_BUSINESS_PROFILE.md` | Business ya se describe con Agent y politicas, con instalacion sin enrolar pero con comportamiento empresarial delimitado | El manifiesto actual solo declara preparacion (`device-enrollment-ready`, `inventory-ready`) y aclara que no habilita gestion remota arbitraria; `MX-010` sigue `No iniciado` | Desalineado | Alto | Reforzar en docs/roadmap que Business actual es base preparada, no piloto materializado; no promocionar Agent/politicas como evidencia implementada antes de abrir y cerrar `MX-010` |
| GAP-004 | Base comun Home/Business/Server | `361_W4_OS_INTEGRATION_ARCHITECTURE.md` y manifiestos actuales | La especificacion pide una base comun real para los tres productos | El repo ya hace heredar Server desde `w4-linux-base`, pero el manifiesto base aun anota que la base reusable es para Home y Business, lo que ya no refleja la realidad del proyecto | Parcial | Medio | Corregir la redaccion del manifiesto base para declarar explicitamente que `w4-linux-base` es base reusable de Home, Business y Server |
| GAP-005 | Paridad entre ediciones | `361_W4_OS_INTEGRATION_ARCHITECTURE.md` y `536_V1_RELEASE_CRITERIA.md` | Misma base, tres productos, clientes coherentes, hashes compartidos, misma operacion vista por GUI/CLI/Agent y desacoplamiento comprobado | Las tres ediciones ya cuentan con ancla explicita de politica y base compartida; aun asi, la equivalencia funcional sigue siendo parcial porque solo Server usa hoy esa politica para un contrato runtime diferenciado y Home/Business siguen sin clientes GUI/API/Agent materializados | Parcial | Medio | Tratar la paridad actual como parcial: la capa declarativa ya converge mejor, pero la equivalencia funcional completa sigue pendiente |

## Evidencia principal usada

### Especificacion `W4-Linux-Base`

- `C:\W4\W4-Linux-Base\docs\01_V1_SCOPE_AND_OBJECTIVES.md`
- `C:\W4\W4-Linux-Base\docs\359_BUSINESS_PROFILE.md`
- `C:\W4\W4-Linux-Base\docs\360_SERVER_PROFILE.md`
- `C:\W4\W4-Linux-Base\docs\361_W4_OS_INTEGRATION_ARCHITECTURE.md`
- `C:\W4\W4-Linux-Base\docs\362_W4_OS_HOME_INTEGRATION.md`
- `C:\W4\W4-Linux-Base\docs\536_V1_RELEASE_CRITERIA.md`

### Estado real `W4 OS System`

- `README.md`
- `manifests/w4-linux-base.manifest.json`
- `manifests/w4-os-business.profile.json`
- `manifests/w4-os-server.profile.json`
- `config/editions/home/policy.json`
- `config/editions/business/policy.json`
- `config/editions/server/policy.json`
- `tests/ServerIsoArtifactTest.php`
- `tests/ServerVmPreflightTest.php`
- `Docs/DEVELOPMENT/DEVELOPMENT_MATRIX.md`
- `Docs/DEVELOPMENT/EXECUTIVE_IMPLEMENTATION_PLAN.md`
- `Docs/DEVELOPMENT/DEVELOPMENT_VERSIONS.md`

## Lectura operativa recomendada

Para trabajo tecnico diario, la lectura mas segura hoy es:

1. usar `W4-Linux-Base` como contrato de direccion;
2. usar `README.md` + `DEVELOPMENT_MATRIX.md` + `DEVELOPMENT_VERSIONS.md` como estado real del repositorio;
3. no asumir que `System API`, `w4ctl`, GUI Home o Agent Business existen solo porque la especificacion ya los define;
4. tratar Server como el producto con mayor formalizacion operativa reciente dentro del tronco comun.

## Siguiente uso recomendado

Esta matriz debe servir como insumo para:

- depurar `MX-001` y futuras ADRs de plataforma;
- evitar sobrepromesas documentales durante `MX-006`, `MX-009` y `MX-010`;
- y decidir que partes de `W4-Linux-Base` deben rebajarse a "target architecture" hasta que exista evidencia equivalente en el repo operativo.
