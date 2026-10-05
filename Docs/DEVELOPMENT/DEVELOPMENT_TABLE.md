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
| P1 | Catalogo grafico y selector | Amarillo documental, rojo operativo | 091, 092, 096, 097, 104, 283 | `GNOME` ya queda fijado como default de `Home`; falta calificar variantes y traducir la ADR a paquetes, defaults y selector por medio | `ADR-006` aprobada, matriz de variantes y primer contrato concreto de composicion `GNOME + GDM` / `w4-desktop-gnome-meta` con politica de medios calificados. El manifiesto de `Home` ya declara `w4-desktop-gnome-meta` y un baseline real de paquetes GNOME (`gdm3`, `gnome-shell`, `gnome-session`, `gnome-software`, `nautilus`, `xdg-desktop-portal-gnome`), pero aun falta cerrar la validacion materializada completa del artefacto live | Multiplicar carga de QA o anunciar variantes no calificadas como si tuvieran soporte equivalente |
| P1 | Shell y branding minimo | Amarillo | 096, 101, 102, 103, 301, 305 | Delimitar que personalizacion entra en V1 y que queda fuera | Contrato operativo de `GNOME + GDM` y `w4-desktop-gnome-meta` ya aterrizado en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_GNOME_GDM_BASE_CONTRACT.md`, junto con la politica minima de branding reversible en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_REVERSIBLE_BRANDING_POLICY.md`. Ademas, ya existe una primera materializacion tecnica real en `Docs/DEVELOPMENT/GRAPHIC INTERFACE/HOME/MX-007_HOME_DEFAULTS_LAYOUT_AND_OVERLAY_IMPLEMENTATION.md`, `config/editions/home/desktop-defaults.json` y `generate_system_overlay.php`, que ahora emiten defaults `dconf`, wallpaper W4 y metadata de desktop sin romper la sesion | Derivar demasiado del shell upstream y encarecer mantenimiento |
| P1 | Control Center | Amarillo | 111, 112, 116, 117, 118, 119, 368 | Definir modulos V1 y congelar contratos API minimos | Centro de control con modulos esenciales y backend autorizado solo donde haga falta | Convertir la UI en un lanzador de acciones privilegiadas sin contrato estable |
| P1 | Home utilizable | Amarillo | 011, 131, 132, 133, 136, 140, 231, 232, 408 | Cerrar conjunto minimo de apps y recorrido de onboarding | Imagen Home que cubra tareas domesticas basicas, backup externo y accesibilidad aceptable | Ampliar demasiado el alcance de aplicaciones y perder foco del MVP |
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
