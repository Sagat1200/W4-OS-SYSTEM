# MX-010 · Business piloto V1 · Contrato operativo inicial

Estado: activo con `Slice A` validado y `Slice B` materializado sobre `live-output`
Fecha: 2026-10-10
Alcance: `W4 OS Business V1`
Dependencias: `MX-003`, `MX-004`, `MX-005`, `ADR-013_BUSINESS_GRAPHIC_CATALOG_AND_SELECTOR.md`

## Objetivo

Traducir la politica ya aprobada de `Business` a un frente operativo inicial y verificable, sin vender todavia una plataforma empresarial completa ni una gestion remota generalista.

Este frente ya no debe discutir:

- si `Business` usa `KDE Plasma` o no como ruta principal;
- si `Home` y `Business` comparten la misma UX visible;
- ni si el piloto V1 equivale ya a soporte comercial pleno.

Debe partir de la base local ya validada del proyecto y abrir un primer contrato acotado para:

1. una ruta visible base de `Business` sobre `KDE Plasma`;
2. identidad de dispositivo e inscripcion como capacidad piloto;
3. politica local cacheada y aplicable sin red;
4. operacion remota acotada a inventario, estado y updates ya alineados con el motor local.

## Referencias de producto

La bajada de `MX-010` debe mantenerse coherente con:

- `Docs/W4-OS/012_W4_OS_BUSINESS_EDITION.md`
- `Docs/W4-OS/211_W4_OS_BUSINESS_ARCHITECTURE.md`
- `Docs/W4-OS/213_W4_OS_CENTRAL_POLICY_SYSTEM.md`
- `Docs/W4-OS/222_W4_OS_DEVICE_ENROLLMENT.md`
- `Docs/W4-OS/224_W4_OS_REMOTE_UPDATE_MANAGEMENT.md`
- `Docs/W4-OS/409_W4_OS_V1_BUSINESS_SCOPE.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/BUSINESS/MX-010_BUSINESS_V1_DESKTOP_MATRIX.md`
- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/BUSINESS/ADR-013_BUSINESS_GRAPHIC_CATALOG_AND_SELECTOR.md`

## Regla de alcance

Este frente si abre:

- `KDE Plasma` como ruta visible base y prioritaria de `Business`;
- un piloto con inscripcion acotada, politica basica e inventario minimo;
- continuidad offline sobre la ultima politica valida;
- reuse del motor local de update/recovery ya validado en `MX-004`.

Este frente no abre todavia:

- gestion masiva multi-tenant completa;
- soporte remoto desatendido general;
- portal empresarial final;
- paridad garantizada de `GNOME`, `XFCE` o `Cinnamon`;
- ni promesas de compliance o soporte comercial.

## Alcance operativo V1

Para este primer aterrizaje, `MX-010` se divide en cuatro bloques:

1. baseline visible de `Business` sobre `KDE Plasma + SDDM`;
2. identidad de dispositivo e inscripcion piloto;
3. politica central reducida con cache local y precedencia explicable;
4. gestion remota limitada a inventario, estado y updates por ventana.

## Lo que entra ahora

### E-1 · Baseline visible empresarial

La primera capa verificable de `Business` debe tratar como ruta base:

- `KDE Plasma` como shell visible;
- `SDDM` como login manager preferido de la ruta KDE;
- defaults de branding y accesos empresariales sin fork profundo del escritorio;
- una frontera clara entre base comun de desktop y la composicion especifica de `Business`.

### E-2 · Identidad de dispositivo piloto

`Business V1` debe abrir la nocion de equipo inscrito con:

- identidad revocable;
- token de alta acotado;
- clave propia del dispositivo;
- y separacion entre identidad corporativa y datos locales del usuario.

### E-3 · Politica local con ultima version valida

La politica empresarial de V1 debe ser pequena y explicable.

El piloto debe asumir:

- versionado de politica;
- validacion de esquema;
- rechazo de politica invalida sin romper el equipo;
- y continuidad local usando la ultima politica valida cuando no exista conectividad.

### E-4 · Reuse del motor local ya validado

`Business` no debe abrir un motor remoto paralelo para updates.

La capa empresarial debe apoyarse en lo ya validado en `MX-004`:

- plan local,
- ventana,
- health checks,
- y reconciliacion posterior.

El plano remoto solo decide cohortes, ventanas o destino; no reemplaza el motor local.

## Primer slice tecnico recomendado

### Slice A · Base `Business` materializable sobre `KDE Plasma`

El primer slice tecnico de `MX-010` debe abrir la ruta visible real de `Business` sobre artefactos del pipeline.

Este slice debe:

1. delimitar la composicion `w4-desktop-meta` + `w4-desktop-kde-meta`;
2. fijar `KDE Plasma + SDDM` como baseline visible del perfil `Business`;
3. congelar el minimo de branding/defaults de `Business` sin bifurcar `KDE`;
4. dejar un contrato validable sobre `build-input`, `rootfs` y `live-output`.

Ese primer aterrizaje ya deja ahora una base implementada en el repo:

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/BUSINESS/MX-010_BUSINESS_PILOT_V1_SLICE_A_KDE_BASELINE.md`
- `manifests/w4-os-business.profile.json`
- `config/editions/business/desktop-defaults.json`
- `src/Business/BusinessKdeLiveOutputToolkit.php`
- `scripts/validate_business_kde_live_output.php`
- `tests/BusinessKdeLiveOutputCliTest.php`
- `build/inputs/w4-os-business.build-input.json`
- `build/rootfs/w4-os-business/rootfs-manifest.json`
- `build/overlays/w4-os-business/overlay-manifest.json`
- `build/live/w4-os-business/live-manifest.json`

Con ello, `MX-010` ya no depende solo de la ADR para su base visible: `Business` ya cuenta con composicion KDE propia en manifiestos y bundles del pipeline, con un validador ejecutable para el gate final y con cierre materializado sobre `build/live-output/w4-os-business/`. `scripts/validate_business_kde_live_output.php --profile w4-os-business --format text` ya confirma en verde `graphical.target`, el baseline `KDE Plasma + SDDM`, los paquetes clave del shell/login manager, los defaults visibles de `Business` y la ausencia de payload heredado de `Home`/`GNOME` en el `system-overlay`.

### Slice B · Enrollment readiness del piloto

Una vez exista base visible real, el siguiente slice tecnico debe abrir readiness de inscripcion piloto.

Este slice debe:

1. dejar trazada la identidad local del equipo;
2. fijar el contrato minimo de token, clave y credencial;
3. dejar visible donde vive la ultima politica valida;
4. distinguir readiness local de cualquier backend empresarial completo.

Ese siguiente aterrizaje ya deja ahora una primera base ejecutable en el repo:

- `Docs/DEVELOPMENT/GRAPHIC INTERFACE/BUSINESS/MX-010_BUSINESS_PILOT_V1_SLICE_B_ENROLLMENT_READINESS.md`
- `config/editions/business/enrollment-readiness.json`
- `src/Business/BusinessEnrollmentReadinessToolkit.php`
- `scripts/validate_business_enrollment_readiness.php`
- `tests/BusinessEnrollmentReadinessCliTest.php`
- `src/Business/BusinessEnrollmentReadinessLiveOutputToolkit.php`
- `scripts/validate_business_enrollment_readiness_live_output.php`
- `tests/BusinessEnrollmentReadinessLiveOutputCliTest.php`
- `build/overlays/w4-os-business/files/etc/w4/business-enrollment-readiness.json`
- `build/install/w4-os-business/edition-policy.json`
- `build/install/w4-os-business/runtime/install.env`

Con ello, `Slice B` ya no depende solo del documento `222`: `Business` ya cuenta con un contrato fuente de readiness local, una copia runtime en el overlay, un bundle de instalacion que vuelve a publicar `edition-policy.json` y un validador ejecutable que confirma la frontera `unenrolled-ready`, el cache local de politica, el estado local de inventario y el puente de politica entre bundle y runtime. Sigue siendo deliberadamente local: todavia no equivale a enrollment real, backend de tokens ni sincronizacion remota.

Ese mismo slice ya deja ademas una proyeccion materializada sobre `build/live-output/w4-os-business/`: el `system-overlay` ya publica `image-root/system-overlay/etc/w4/business-enrollment-readiness.json`, y `src/Business/BusinessEnrollmentReadinessLiveOutputToolkit.php`, expuesto por `scripts/validate_business_enrollment_readiness_live_output.php`, ya valida en verde sobre el arbol real `w4-business-live`, `profile.env`, el contrato runtime de readiness, la cache local de politica y el anclaje local de inventario. La recomposicion limpia completa del artefacto queda todavia pendiente de rerun cuando el mirror Debian deje de devolver `Hash Sum mismatch` en `apt-get update`, pero el gate funcional de `Slice B` ya no queda limitado a `overlay/install`.

### Slice C · Politica y update empresarial acotados

Solo despues de la base visible y del readiness de inscripcion, el frente debe abrir la integracion piloto con politica y updates.

Este slice debe:

1. reutilizar el motor local ya validado;
2. limitar el plano remoto a inventario, estado, cohortes y ventana;
3. dejar explicito que no existe todavia control remoto generico.

## Entregables obligatorios de apertura

1. contrato operativo propio de `MX-010` para `Business V1`;
2. definicion del primer slice tecnico ejecutable;
3. trazabilidad ejecutiva alineada para tratar `Business` como siguiente frente visible;
4. una siguiente accion concreta que no dependa de reinterpretar la ADR en cada ciclo.

## Criterios de apertura correcta

`MX-010` puede considerarse correctamente abierto cuando:

1. `ADR-013` deje de ser solo politica grafica y ya exista un contrato operativo asociado;
2. la ruta `KDE Plasma` quede tratada como base de `Business`, no como posibilidad abstracta;
3. el piloto empresarial quede delimitado por slices pequenos y verificables;
4. la trazabilidad deje claro que `Business` sigue siendo piloto y no GA empresarial.

## Riesgos a contener

### R-1 · Sobrealcance empresarial

Si el frente intenta cerrar de una vez:

- enrollment completo,
- portal,
- control remoto,
- compliance,
- soporte comercial,
- y variantes graficas equivalentes,

`MX-010` perdera foco y volvera a quedar solo en discurso.

### R-2 · Reabrir la base local ya cerrada

`MX-010` no debe reabrir:

- instalacion base,
- update/recovery,
- ni seguridad baseline,

salvo donde el piloto empresarial necesite anclarse explicitamente a esas capacidades ya validadas.

### R-3 · Vender `Business` como producto terminado

La regla de esta fase es simple:
solo se eleva a piloto de `Business` lo que tenga contrato, slice tecnico y evidencia futura claramente prevista.

## Resultado esperado del frente

Con la apertura correcta de `MX-010`, `Business` deja de ser solo una ADR de escritorio y pasa a tener:

- una ruta base visible sobre `KDE Plasma`,
- un piloto delimitado por identidad, politica e inventario minimo,
- continuidad offline apoyada en la ultima politica valida,
- y un siguiente slice tecnico claro sin prometer todavia gestion empresarial completa.

Tras esta pasada, ese avance ya se traduce en una base KDE real sobre `manifests`, `build-input`, `rootfs`, `overlay`, `live` y `live-output`, con `Slice B` ya proyectado tambien sobre el artefacto final materializado. El siguiente gate natural pasa ahora a ser el `Slice C` de politica local cacheada e inventario minimo.
