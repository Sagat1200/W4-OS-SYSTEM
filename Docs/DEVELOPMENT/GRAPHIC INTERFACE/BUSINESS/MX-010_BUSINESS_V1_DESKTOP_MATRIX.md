# MX-010 · Matriz comparativa V1 de interfaces gráficas para Business

Fecha: 2026-10-04
Estado: En analisis
Alcance: `W4 OS Business` sobre la base ya instalada, actualizable y endurecida del proyecto

Decisión de producto vigente:
- `KDE Plasma` queda fijado como interfaz gráfica predeterminada de `Business`.
- `GNOME`, `XFCE` y `Cinnamon` permanecen como variantes evaluables dentro del catálogo gráfico de `Business`, sujetas al alcance final de V1.
- ADR aprobada: `ADR-013_BUSINESS_GRAPHIC_CATALOG_AND_SELECTOR.md`

## Objetivo

Comparar `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon` como interfaces gráficas candidatas para `Business V1`, usando criterios tecnicos y de producto que minimicen fractura del repositorio y dejen una direccion clara para futuras capas de politicas, branding, configuracion central y soporte empresarial. El objetivo adicional de este corte es decidir si `Business` puede exponer estas opciones durante la instalación como perfiles gráficos soportados.

## Supuestos de trabajo

- `Business V1` debe priorizar una experiencia administrable, estable y rica en configuracion sin introducir un fork profundo del escritorio.
- `Server` ya queda separado como composicion headless y no debe contaminar esta decision.
- `Home` y `Business` pueden compartir base comun, pero no tienen por que compartir la misma interfaz predeterminada.
- La decision debe favorecer control operativo, mantenibilidad y flexibilidad de politicas por encima de una UX puramente domestica.
- Si el instalador ofrece selector de interfaz, cada opcion debe mantenerse como perfil gráfico explícito y no como variante improvisada.

## Criterios de evaluacion

Escala usada:
- `1`: desfavorable para V1
- `3`: aceptable con tradeoffs
- `5`: favorable para V1

| Criterio | Peso | KDE Plasma | GNOME | XFCE | Cinnamon | Comentario |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| Coherencia UX para entorno empresarial | 15 | 5 | 4 | 3 | 4 | KDE y Cinnamon ofrecen una metáfora de escritorio más familiar para adopción corporativa; GNOME es viable pero más opinionado; XFCE prioriza austeridad. |
| Densidad de configuracion util para soporte | 15 | 5 | 3 | 3 | 4 | KDE ofrece una superficie de configuracion mas amplia sin recurrir tan pronto a herramientas paralelas. |
| Base util para branding y defaults corporativos | 15 | 5 | 3 | 4 | 4 | KDE permite ajustar defaults y layout con menos friccion; XFCE y Cinnamon tambien son razonables; GNOME penaliza antes con extensiones o cambios mas delicados. |
| Base util para settings y control center futuro | 15 | 5 | 3 | 2 | 3 | KDE deja mejor terreno para settings modulares y politicas visibles; GNOME y Cinnamon cubren una parte; XFCE queda mas austero. |
| Accesibilidad y madurez general | 10 | 4 | 4 | 3 | 4 | KDE, GNOME y Cinnamon ofrecen una base madura; XFCE requiere validar mas la experiencia percibida. |
| Consumo de ISO y dependencias | 5 | 3 | 4 | 5 | 4 | XFCE sigue siendo el mas liviano; KDE arrastra mas piezas. |
| Consumo de memoria en idle | 5 | 3 | 4 | 5 | 4 | XFCE destaca en hardware ajustado; GNOME y Cinnamon quedan mejor contenidos; KDE puede variar mas. |
| Mantenibilidad del MVP sobre Debian Stable | 10 | 4 | 3 | 4 | 4 | KDE, XFCE y Cinnamon permiten un MVP razonable con menos necesidad de extensiones profundas que GNOME. |
| Riesgo de deriva frente al upstream | 5 | 4 | 3 | 5 | 4 | XFCE y KDE toleran mejor personalizacion moderada; GNOME es el mas sensible; Cinnamon queda equilibrado. |
| Costo extra de soportarlo como opcion instalable | 5 | 3 | 3 | 4 | 4 | XFCE y Cinnamon son mas faciles de justificar como variantes adicionales; KDE y GNOME tienen mayor peso de pruebas y paquetes. |

## Resultado ponderado

| Opcion | Puntaje ponderado |
| --- | ---: |
| KDE Plasma | 450 |
| GNOME | 340 |
| XFCE | 360 |
| Cinnamon | 395 |

## Lectura ejecutiva

`KDE Plasma` sale mejor posicionado para `Business` por la combinacion de:

- mayor densidad de configuracion util para soporte y administracion,
- mejor encaje para branding y defaults corporativos sin fork profundo,
- mejor base para un futuro `control center` o capa de settings W4,
- y una UX clasica que reduce friccion en despliegues organizacionales.

`GNOME` sigue siendo una opcion madura y contenida, pero para `Business` pesa menos la opinion UX cerrada y mas la superficie de administracion visible.

`XFCE` conserva valor como alternativa ligera y de bajo consumo, pero exige aceptar una experiencia mas austera y menos rica como ruta principal.

`Cinnamon` queda como opcion equilibrada y mas cercana a escritorio clasico, pero con menos amplitud nativa de configuracion que KDE.

## Recomendacion de esta matriz

Propuesta para ADR:

- `Business V1` admite catálogo de interfaces gráficas elegibles durante instalación.
- Interfaz predeterminada de `Business`: `KDE Plasma`
- Opciones adicionales documentadas para evaluación/soporte: `GNOME`, `XFCE` y `Cinnamon`
- Display manager preferido para la ruta KDE: `SDDM`
- Sesion primaria objetivo para KDE/GNOME: `Wayland`
- Fallback operativo aceptable: `X11`

La ADR ya fue aprobada y deja asentado que `KDE Plasma` es el default de producto para `Business`, mientras `GNOME`, `XFCE` y `Cinnamon` quedan como variantes controladas del catalogo. El documento resultante es `ADR-013_BUSINESS_GRAPHIC_CATALOG_AND_SELECTOR.md`.

## Implicaciones para el proyecto

Si se aprueba un catálogo curado con `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon`:

- `Business` puede tomar `KDE Plasma` como ruta base de branding, settings y soporte.
- Las futuras capas de politicas y configuracion remota deben tratar `KDE` como referencia primaria de UX.
- `GNOME`, `XFCE` y `Cinnamon` quedan como variantes posibles, no como base equivalente.
- `Home` puede conservar `GNOME` como default sin forzar convergencia artificial de UX entre ediciones.

## Riesgos a vigilar antes del ADR

- validar impacto real en tamano de ISO y manifiesto final de paquetes,
- medir memoria en idle sobre una VM fresca de laboratorio,
- fijar si `Wayland` sera default real o solo objetivo con fallback seguro,
- delimitar cuanto branding corporativo entra en V1,
- evitar sobreprometer paridad de soporte entre variantes,
- y no anunciar en el selector variantes que no hayan sido calificadas en arranque, sesion, updates y recovery.

## Siguiente paso recomendado

1. Delimitar `w4-desktop-meta` común y los metapaquetes `w4-desktop-kde-meta`, `w4-desktop-gnome-meta`, `w4-desktop-xfce-meta` y `w4-desktop-cinnamon-meta`.
2. Definir en qué medios reales apareceran las variantes no predeterminadas.
3. Traducir la decisión a la futura ruta de `Business` sobre `KDE Plasma` como base.
