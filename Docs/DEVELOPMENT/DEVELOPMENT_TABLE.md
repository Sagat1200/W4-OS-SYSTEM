# W4 OS · Development Table V1

Tabla priorizada de implementacion para convertir la coleccion documental en entregables tecnicos verificables. Esta tabla separa claramente decision pendiente, primer entregable tecnico y riesgo principal para evitar confundir estado documental con estado de producto.

La lectura transversal por edicion se resume en `Docs/DEVELOPMENT/EDITION_IMPLEMENTATION_STATUS_MATRIX.md`, para no mezclar el avance de `Home`, `Business` y `Server` cuando una misma fila del roadmap agrupa capacidades distintas.

## Criterios

- `P0`: bloquea MVP recuperable o decisiones base.
- `P1`: necesario para V1, pero depende de cierres P0.
- `P2`: importante para la evolucion o pilotos posteriores.
- `Madurez actual`: lectura ejecutiva del corpus documental, no evidencia de implementacion.

## Tabla priorizada

| Prioridad | Area | Madurez actual | Documentos base | Decision pendiente | Primer entregable tecnico | Riesgo principal |
| --- | --- | --- | --- | --- | --- | --- |
| P0 | ADRs y base de plataforma | Amarillo | 001, 003, 004, 401, 406, 414, 415 | Cerrar ADR de codename, amd64 UEFI inicial, bootloader, layout y update V1; `ADR-006`, `ADR-013` y `ADR-014` ya fijan las politicas de interfaz de `Home`, `Business` y `Server` | Paquete de ADRs aprobados con responsables, alcance y criterios de prueba por hito H0 | Iniciar implementacion sobre supuestos no congelados, reabrir decisiones a mitad del MVP o contaminar `Server` con una GUI fuera de su ADR |
| P0 | Supply, build y repositorios | Amarillo | 041, 047, 048, 051, 061, 068, 069, 070 | Elegir pipeline real de build y firma; confirmar si OBS entra o no en V1 | Primera imagen amd64 reproducible, firmada y trazable desde repositorio controlado | No poder demostrar procedencia, firma o repetibilidad del artefacto |
| P0 | Instalacion | Amarillo tirando a verde | 031, 032, 033, 034, 037, 040 | Elegir motor Debian-compatible del instalador y congelar flujo destructivo | Instalador funcional en VM vacia con resumen final, validacion de plan y primer boot correcto | Corrupcion de disco por plan obsoleto o flujo no revalidado |
| P0 | Modelo de estado, update y recovery | Amarillo | 071, 072, 077, 083, 085, 086, 088, 090 | Congelar contrato de estado y alcance real del rollback V1; no prometer atomicidad no probada | Coordinador de update durable con `operation_id`, snapshot previo, reinicio y diagnostico post-fallo | Mezclar binarios, `dpkg`, kernel o ESP en una recuperacion inconsistente |
| P0 | Seguridad baseline | Cerrado | 156, 157, 158, 159, 160, 165, 169 | Mantener playbook y evidencia en futuras regeneraciones de imagen | Perfil minimo validado en Home y Business: usuario estandar, firewall disponible y default deny, AppArmor activo con perfiles enforce, SSH no habilitado por defecto, permisos criticos, update autenticada por evidencia firmada y cifrado visible | Reabrir imagenes sin ejecutar el playbook `MX-005` o dejar excepciones SSH de laboratorio |
| P1 | Catalogo grafico y selector | Amarillo documental, verde operativo | 091, 092, 096, 097, 104, 283 | `GNOME` ya queda fijado como default de `Home`; falta calificar variantes y traducir la ADR al selector por medio cuando existan variantes realmente soportadas | `ADR-006` aprobada, matriz de variantes y contrato concreto de composicion `GNOME + GDM` / `w4-desktop-gnome-meta` ya materializados. El manifiesto de `Home` declara el baseline GNOME y la validacion operativa completa `rootfs -> overlay -> live` ya lo confirma en `build/live-output/w4-os-home/image-root/live/filesystem.manifest` con `gdm3`, `gnome-shell`, `gnome-session`, `gnome-control-center`, `gnome-terminal`, `gnome-software`, `nautilus` y `xdg-desktop-portal-gnome` | Multiplicar carga de QA o anunciar variantes no calificadas como si tuvieran soporte equivalente |
| P1 | Shell y branding minimo | Cerrado | 096, 101, 102, 103, 301, 305 | Mantener reversible el branding y no reabrir forks innecesarios del shell upstream | Contrato operativo de `GNOME + GDM` y `w4-desktop-gnome-meta` ya aterrizado en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`, junto con la politica minima de branding reversible en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_REVERSIBLE_BRANDING_POLICY.md`. La materializacion tecnica de `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_DEFAULTS_LAYOUT_AND_OVERLAY_IMPLEMENTATION.md`, `config/editions/home/desktop-defaults.json` y `generate_system_overlay.php` ya llega intacta al artefacto real: `system-overlay` conserva `desktop-defaults.json`, defaults `dconf` de usuario/login y el wallpaper W4, mientras el `filesystem.manifest` del live confirma el baseline GNOME completo | Derivar demasiado del shell upstream y encarecer mantenimiento |
| P1 | Control Center | Verde validado | 111, 112, 116, 117, 118, 119, 368 | Mantener `MX-008` como baseline estable de `Settings` para `Home V1`, sin reabrir una UX propia fuera de la ruta `GNOME-augmented` ya aceptada | `MX-008` ya cuenta con contrato operativo, `Slice A`, home/CLI `read-first`, snapshot persistido, respuesta API, bundle HTML de referencia, mapa `GNOME-augmented`, launchers `.desktop`, integracion real en overlay/live y validacion del `live-output` materializado. `config/editions/home/desktop-defaults.json` ya promociona `W4 Settings` y `Updates` como favoritos por defecto, y `src/ControlCenter/ControlCenterLiveOutputToolkit.php` ya congela ese contrato final en `desktop-defaults.json` y `dconf`. El baseline fue aceptado como suficiente para `Home V1` y ahora opera como dependencia estable del siguiente frente visible | Reabrir `Settings` sin necesidad objetiva y volver a absorber alcance de producto que ya paso a `MX-009` |
| P1 | Home utilizable | Amarillo activo | 011, 131, 132, 133, 136, 140, 231, 232, 408 | Usar el baseline ampliado de `Home V1` como base estable antes de abrir onboarding completo o accesibilidad total | `MX-009` ya deja de ser placeholder: `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-009_HOME_USABLE_V1_CONTRACT.md` abre el contrato operativo del bloque y `src/Home/HomeUsabilityLiveOutputToolkit.php`, expuesto por `scripts/validate_home_usability_live_output.php`, ya valida sobre el `live-output` real de `Home` el baseline utilizable de navegador (`firefox-esr`), documentos (`libreoffice`), PDF (`evince`), multimedia (`vlc`), archivos (`nautilus`), `W4 Settings`, `Updates` y favoritos visibles del shell. `manifests/w4-os-home.profile.json` ya promociona `evince` y `vlc` a `packages.required`, el rootfs fue recompuesto en WSL y el `live-output` final ya cerró en verde con ambos paquetes presentes en `filesystem.manifest` | Ampliar demasiado el alcance de aplicaciones y onboarding antes de cerrar el baseline utilizable minimo |
| P2 | Business piloto | Amarillo documental, rojo operativo | 012, 013, 211, 212, 213, 214, 221, 222, 224, 409 | Congelar politicas iniciales, enrollment y tareas tipadas del piloto; `ADR-013` ya fija `KDE Plasma` como default visual | Agente local + backend piloto multi-tenant con inventario, politica basica, `KDE Plasma` como ruta visible base y ejecucion idempotente | Terminar implementando un control remoto generico, una UX sin ancla clara o variantes no calificadas como si tuvieran soporte equivalente |
| P2 | Compliance, soporte y release final | Amarillo conceptual | 270, 271, 272, 317, 318, 329, 332, 402, 410 | Definir expediente de release V1, runbooks y alcance real de soporte | Checklist de candidato V1 con QA, licencias, fuentes publicables y plan de soporte | Declarar disponibilidad comercial sin capacidad operativa real |

## Orden recomendado de ejecucion

1. Cerrar `ADRs y base de plataforma`.
2. Construir `supply, build y repositorios`.
3. Entregar `instalacion`.
4. Demostrar `modelo de estado, update y recovery`.
5. Fijar `seguridad baseline`.
6. Traducir el `catalogo grafico` aprobado a `shell` y branding minimo.
7. Montar `control center` y `Home utilizable`.
8. Abrir `Business piloto` solo despues del MVP recuperable.

## Criterio de salida por hito

| Hito | Condicion minima de salida |
| --- | --- |
| H0 | ADRs base aprobados y dueños asignados |
| H1 | Imagen amd64 construida, firmada y trazable |
| H2 | Instalar, actualizar, fallar y recuperar sin perder archivo de prueba |
| H3 | Home utilizable con accesibilidad y apps minimas calificadas |
| H4 | Piloto Business con aislamiento, politica y operacion offline verificados |
| H5 | Candidato V1 con QA, licencias, fuentes, runbooks y soporte |

## Notas

- Esta tabla prioriza implementacion V1, no amplitud documental.
- `Atomicidad integral`, `cloud obligatorio`, `arm64 general` y `certificaciones` quedan fuera del compromiso inicial mientras no exista evidencia suficiente.
- Cada fila debe pasar de "documento" a "artefacto + prueba + responsable" antes de considerarse cerrada.
