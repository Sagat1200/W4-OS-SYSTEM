# MX-012 · Matriz comparativa V1 de politica de interfaz para Server

Fecha: 2026-10-04
Estado: En analisis
Alcance: `W4 OS Server` sobre la base ya instalada, actualizable, endurecida y validada en modo headless

Decisión de producto vigente:
- `Server` no incluye interfaz gráfica por defecto.
- La ruta predeterminada de `Server` es `headless`.
- `Server` puede exponer durante la instalación opciones gráficas controladas cuando el medio las incluya y las califique, pero la selección inicial debe permanecer en `headless`.

## Objetivo

Comparar la ruta `headless` frente a alternativas gráficas como `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon`, para dejar claro por qué `Server V1` debe permanecer sin interfaz gráfica por defecto y al mismo tiempo permitir que el instalador exponga esas variantes como opciones controladas para escenarios locales o de baja experiencia operativa.

## Supuestos de trabajo

- `Server V1` prioriza operación remota, TTY, `SSH`, hardening, instalación reproducible y recuperación cifrada.
- La composición `Server` ya fue validada como perfil headless y no debe contaminarse con dependencias desktop.
- `Home` y `Business` ya tienen políticas gráficas propias; `Server` no necesita converger visualmente con ellas.
- Si el instalador expone un selector de interfaz para `Server`, la opcion inicial debe seguir siendo `headless` y las variantes gráficas deben aparecer como composiciones opcionales y controladas.

## Criterios de evaluación

Escala usada:
- `1`: desfavorable para V1
- `3`: aceptable con tradeoffs
- `5`: favorable para V1

| Criterio | Peso | Headless | KDE Plasma | GNOME | XFCE | Cinnamon | Comentario |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | --- |
| Coherencia con el producto Server | 20 | 5 | 1 | 1 | 2 | 1 | `Server` se define por operación remota y mínima superficie local; una GUI altera esa identidad base. |
| Menor superficie de ataque | 15 | 5 | 2 | 2 | 3 | 2 | Menos servicios gráficos, portales y sesiones reducen exposición operativa. |
| Alineación con evidencia operativa actual | 15 | 5 | 1 | 1 | 1 | 1 | La validación real de `Server` ya está cerrada en modo headless. |
| Simplicidad de mantenimiento V1 | 15 | 5 | 2 | 2 | 3 | 2 | Mantener una GUI en `Server` abriría una segunda familia de dependencias y QA. |
| Coherencia con instalación y recovery | 10 | 5 | 2 | 2 | 2 | 2 | El flujo actual de instalación, reboot cifrado y baseline ya está probado sin GUI. |
| Consumo de ISO y dependencias | 10 | 5 | 2 | 2 | 3 | 2 | `Headless` preserva la ISO autocontenida sin arrastrar piezas desktop. |
| Facilidad de soporte remoto | 10 | 5 | 3 | 3 | 3 | 3 | La administración remota principal ya está diseñada para `SSH`, no para sesiones gráficas. |
| Riesgo de deriva frente al contrato Base | 5 | 5 | 2 | 2 | 3 | 2 | Reintroducir desktop en `Server` tensiona el contrato headless ya cerrado. |

## Resultado ponderado

| Opcion | Puntaje ponderado |
| --- | ---: |
| Headless | 500 |
| KDE Plasma | 175 |
| GNOME | 175 |
| XFCE | 250 |
| Cinnamon | 175 |

## Lectura ejecutiva

La lectura es nítida: `Server V1` debe permanecer `headless`.

Las alternativas gráficas pueden servir para laboratorios, virtualización local, administradores con poca experiencia o despliegues donde se prefiera una operación inicial más asistida. Aun así, no deben cambiar el contrato principal del producto. En cambio, sí aumentan:

- superficie de ataque,
- tamaño de imagen,
- complejidad de QA,
- deriva frente al perfil `w4-os-server`,
- y ambigüedad de producto.

## Recomendacion de esta matriz

Propuesta para ADR:

- `Server V1` no incluye interfaz gráfica por defecto.
- El instalador de `Server` puede exponer selector de interfaz gráfica cuando el medio incluya variantes calificadas.
- Si el usuario no cambia la selección, `Server` debe instalarse en modo `headless`.
- La ruta base se resuelve mediante `w4-server-meta` sin `w4-desktop-meta`; si el usuario elige GUI, la composición debe añadir el metapaquete gráfico aprobado correspondiente.
- Las variantes gráficas aprobadas para selección son `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon`, siempre como opciones controladas y no como default.

La ADR asociada debe dejar claro que `Server` mantiene un default distinto al de `Home` y `Business`: puede compartir opciones gráficas disponibles, pero su selección inicial oficial sigue siendo `headless`.

## Implicaciones para el proyecto

- `Server` mantiene una identidad headless fuerte y separada, aunque pueda ofrecer variantes gráficas opcionales en instalación.
- `Home` y `Business` pueden evolucionar en UX sin contaminar la composición de `Server`.
- La política de medios debe impedir que un ISO de `Server` seleccione una GUI por defecto o arrastre variantes no calificadas por accidente.

## Riesgos a vigilar

- no reintroducir dependencias desktop en `w4-linux-base` que vuelvan a contaminar `Server`,
- no abrir “excepciones temporales” que terminen convertidas en soporte implícito,
- no hacer que el selector gráfico cambie el default real del producto,
- y no documentar interfaces gráficas para `Server` como si ya tuvieran evidencia operativa equivalente cuando solo estén calificadas como variantes.

## Siguiente paso recomendado

1. Aprobar ADR de política de interfaz para `Server`.
2. Mantener `w4-server-meta` y `w4-os-server` como ruta headless explícita.
3. Definir cómo se compondrán en instalación las variantes `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon` sin perder el default `headless`.
