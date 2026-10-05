# MX-006 · Matriz comparativa V1 de interfaces gráficas para Home

Fecha: 2026-10-04
Estado: En analisis
Alcance: `W4 OS Home` sobre la base ya instalada, actualizable y endurecida del proyecto

Decisión de producto vigente:
- `GNOME` queda fijado como interfaz gráfica predeterminada de `Home`.
- `KDE Plasma`, `XFCE` y `Cinnamon` permanecen como variantes evaluables dentro del catálogo gráfico, sujetas al alcance final de V1.
- ADR aprobada: `ADR-006_HOME_GRAPHIC_CATALOG_AND_SELECTOR.md`

## Objetivo

Comparar `KDE Plasma`, `GNOME`, `XFCE` y `Cinnamon` como interfaces gráficas candidatas para `Home V1`, usando criterios tecnicos y de producto que minimicen fractura del repositorio y dejen una direccion clara para `MX-007`, `MX-008` y `MX-009`. El objetivo adicional de este corte es decidir si `Home` puede exponer estas opciones durante la instalación como perfiles gráficos soportados.

## Supuestos de trabajo

- `Home V1` debe priorizar una experiencia visible, coherente y mantenible sobre personalizaciones profundas tempranas.
- `Server` ya queda separado como composicion headless y no debe contaminar esta decision.
- `Business` puede heredar aprendizajes del catálogo elegido, pero `MX-006` se decide primero para `Home`.
- La decision debe favorecer el menor drift posible frente a Debian Stable y reducir trabajo futuro en `shell`, branding y control center.
- Si el instalador ofrece selector de interfaz, cada opción debe mantenerse como perfil gráfico explícito y no como variante improvisada.

## Criterios de evaluacion

Escala usada:
- `1`: desfavorable para V1
- `3`: aceptable con tradeoffs
- `5`: favorable para V1

| Criterio | Peso | KDE Plasma | GNOME | XFCE | Cinnamon | Comentario |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| Coherencia UX inmediata para Home | 15 | 4 | 4 | 3 | 5 | Cinnamon se alinea muy bien con una UX doméstica clásica; KDE y GNOME también son viables; XFCE prioriza ligereza por encima de una UX rica por defecto. |
| Branding minimo sin fork profundo | 15 | 5 | 3 | 4 | 4 | KDE y XFCE permiten ajustar defaults con menos fricción; GNOME suele empujar antes a extensiones o cambios más delicados; Cinnamon queda en un punto medio. |
| Base util para `MX-007` Shell y branding | 15 | 5 | 3 | 3 | 4 | KDE ofrece más superficie nativa de personalización; Cinnamon permite una identidad reconocible sin tanto trabajo lateral; XFCE es más austero. |
| Base util para `MX-008` Control Center | 10 | 4 | 3 | 2 | 3 | KDE ya incluye configuración amplia; GNOME y Cinnamon cubren suficiente para V1; XFCE probablemente obliga antes a utilidades adicionales. |
| Accesibilidad y madurez general | 10 | 4 | 4 | 3 | 4 | KDE, GNOME y Cinnamon parten mejor para una experiencia general de escritorio; XFCE requiere validación más cuidadosa de accesibilidad percibida. |
| Consumo de ISO y dependencias | 10 | 3 | 4 | 5 | 4 | XFCE es el más liviano; GNOME y Cinnamon quedan relativamente contenidos; KDE suele arrastrar más piezas. |
| Consumo de memoria en idle | 10 | 3 | 4 | 5 | 4 | XFCE vuelve a ser el mejor posicionado para hardware ajustado; GNOME y Cinnamon quedan en medio; KDE puede variar más. |
| Mantenibilidad del MVP sobre Debian Stable | 10 | 4 | 3 | 4 | 4 | KDE, XFCE y Cinnamon permiten un MVP razonable con menos necesidad de extensiones profundas; GNOME penaliza más cuando se fuerza branding o shell propio. |
| Riesgo de deriva frente al upstream | 5 | 4 | 3 | 5 | 4 | XFCE y KDE toleran mejor personalización moderada; GNOME es el más sensible; Cinnamon queda equilibrado. |
| Costo extra de soportarlo como opción instalable | 10 | 3 | 3 | 4 | 4 | XFCE y Cinnamon son más fáciles de justificar como variantes adicionales por costo/beneficio; KDE y GNOME tienen mayor peso de pruebas y paquetes. |

## Resultado ponderado

| Opcion | Puntaje ponderado |
| --- | ---: |
| KDE Plasma | 450 |
| GNOME | 345 |
| XFCE | 385 |
| Cinnamon | 420 |

## Lectura ejecutiva

`GNOME` sigue ofreciendo una base limpia y razonablemente contenida para un MVP, especialmente si la prioridad fuera solo simplicidad de stack y una propuesta más opinionada.

`XFCE` aparece como la opción más austera en recursos y la más fácil de justificar para hardware contenido, pero exige aceptar una experiencia inicial menos rica y más sobria para `Home`.

`Cinnamon` destaca como opción doméstica muy coherente y con menor fricción de adopción para usuarios acostumbrados a paradigmas clásicos de escritorio.

Sin embargo, para este repositorio la restriccion dominante no es solo el consumo base. Tambien pesan:

- minimizar fractura respecto al upstream,
- habilitar branding ligero sin abrir un fork de shell,
- preparar `MX-007` y `MX-008` con menos trabajo lateral,
- sostener un `Home V1` visible sin obligar a introducir extensiones o customizaciones fragiles demasiado pronto,
- y controlar el costo adicional de soportar varias interfaces dentro del mismo instalador.

La lectura estrictamente ponderada sigue dejando a `KDE Plasma` con mejor puntaje técnico bruto. Sin embargo, la decisión de producto vigente fija `GNOME` como interfaz predeterminada de `Home`, priorizando una ruta de experiencia más opinionada y contenida como base visible del sistema. `KDE Plasma`, `XFCE` y `Cinnamon` se mantienen como variantes candidatas del catálogo, no como default.

## Recomendacion de esta matriz

Propuesta para ADR:

- `Home V1` admite catálogo de interfaces gráficas elegibles durante instalación.
- Interfaz predeterminada de `Home`: `GNOME`
- Opciones adicionales documentadas para evaluación/soporte: `KDE Plasma`, `XFCE` y `Cinnamon`
- Display manager preferido para la ruta GNOME: `GDM`
- Display manager preferido para la ruta KDE: `SDDM`
- Sesion primaria objetivo para GNOME/KDE: `Wayland`
- Fallback operativo aceptable: `X11`

La ADR ya fue aprobada y deja asentado que `GNOME` es el default de producto para `Home`, mientras `KDE Plasma`, `XFCE` y `Cinnamon` quedan como variantes controladas del catalogo. El documento resultante es `ADR-006_HOME_GRAPHIC_CATALOG_AND_SELECTOR.md`.

## Implicaciones para el proyecto

Si se aprueba un catálogo curado con `GNOME`, `KDE Plasma`, `XFCE` y `Cinnamon`:

- `MX-007` debe separar branding común de overrides específicos por interfaz, tomando `GNOME` como ruta base.
- `MX-008` debe definir qué parte del `control center` se apoya en capacidades nativas de cada interfaz y qué parte se abstrae.
- `MX-009` puede delimitar onboarding, apps base y experiencia Home con más flexibilidad, pero también con mayor costo de pruebas.
- `Business` conserva margen para heredar un subconjunto de ese catálogo o fijar una sola interfaz más adelante, pero ya no bloquea a `Home`.

## Riesgos a vigilar antes del ADR

- validar impacto real en tamano de ISO y manifiesto final de paquetes,
- medir memoria en idle sobre una VM fresca de laboratorio,
- fijar si `Wayland` sera default real o solo objetivo con fallback seguro,
- delimitar que branding entra en V1 y que queda fuera para no sobrepersonalizar,
- evitar que `w4-desktop-meta` termine absorbiendo demasiadas apps base antes de cerrar `MX-009`,
- y no abrir cuatro rutas de soporte si el proyecto no puede calificar arranque, sesión, accesibilidad, updates y recuperación para cada una.

## Siguiente paso recomendado

1. Delimitar `w4-desktop-meta` común y los metapaquetes `w4-desktop-gnome-meta`, `w4-desktop-kde-meta`, `w4-desktop-xfce-meta` y `w4-desktop-cinnamon-meta`.
2. Definir en qué medios reales apareceran las variantes no predeterminadas.
3. Abrir implementacion de `MX-007` sobre `GNOME` como ruta predeterminada y dejar el resto como variantes controladas.
