# W4 OS · Development Table V1

Tabla priorizada de implementacion para convertir la coleccion documental en entregables tecnicos verificables. Esta tabla separa claramente decision pendiente, primer entregable tecnico y riesgo principal para evitar confundir estado documental con estado de producto.

## Criterios

- `P0`: bloquea MVP recuperable o decisiones base.
- `P1`: necesario para V1, pero depende de cierres P0.
- `P2`: importante para la evolucion o pilotos posteriores.
- `Madurez actual`: lectura ejecutiva del corpus documental, no evidencia de implementacion.

## Tabla priorizada

| Prioridad | Area | Madurez actual | Documentos base | Decision pendiente | Primer entregable tecnico | Riesgo principal |
| --- | --- | --- | --- | --- | --- | --- |
| P0 | ADRs y base de plataforma | Amarillo | 001, 004, 401, 406, 414, 415 | Cerrar ADR de codename, amd64 UEFI inicial, bootloader, layout, update V1 y escritorio oficial | Paquete de ADRs aprobados con responsables, alcance y criterios de prueba por hito H0 | Iniciar implementacion sobre supuestos no congelados y reabrir decisiones a mitad del MVP |
| P0 | Supply, build y repositorios | Amarillo | 041, 047, 048, 051, 061, 068, 069, 070 | Elegir pipeline real de build y firma; confirmar si OBS entra o no en V1 | Primera imagen amd64 reproducible, firmada y trazable desde repositorio controlado | No poder demostrar procedencia, firma o repetibilidad del artefacto |
| P0 | Instalacion | Amarillo tirando a verde | 031, 032, 033, 034, 037, 040 | Elegir motor Debian-compatible del instalador y congelar flujo destructivo | Instalador funcional en VM vacia con resumen final, validacion de plan y primer boot correcto | Corrupcion de disco por plan obsoleto o flujo no revalidado |
| P0 | Modelo de estado, update y recovery | Amarillo | 071, 072, 077, 083, 085, 086, 088, 090 | Congelar contrato de estado y alcance real del rollback V1; no prometer atomicidad no probada | Coordinador de update durable con `operation_id`, snapshot previo, reinicio y diagnostico post-fallo | Mezclar binarios, `dpkg`, kernel o ESP en una recuperacion inconsistente |
| P0 | Seguridad baseline | Verde operativo inicial | 156, 157, 158, 159, 160, 165, 169 | Congelar excepciones permitidas para MVP y decidir la siguiente capa de hardening verificable | Perfil minimo ya validado en Home y Business: usuario estandar, firewall disponible, SSH no habilitado por defecto, permisos criticos, update autenticada por evidencia firmada y cifrado visible | Mantener excepciones SSH de laboratorio o ampliar la baseline sin evidencia runtime Home/Business |
| P1 | Escritorio oficial | Amarillo tirando a rojo | 091, 092, 096, 097, 104, 283 | Elegir un solo escritorio oficial para V1 mediante ADR y matriz de pruebas | Prototipos comparables KDE Plasma vs GNOME con evidencia de accesibilidad, suspension, graficos y mantenimiento | Duplicar carga de QA o elegir por preferencia estetica sin datos |
| P1 | Shell y branding minimo | Amarillo | 096, 101, 102, 103, 301, 305 | Delimitar que personalizacion entra en V1 y que queda fuera | Paquete de defaults, tema, iconos y assets W4 desactivables sin romper la sesion | Derivar demasiado del shell upstream y encarecer mantenimiento |
| P1 | Control Center | Amarillo | 111, 112, 116, 117, 118, 119, 368 | Definir modulos V1 y congelar contratos API minimos | Centro de control con modulos esenciales y backend autorizado solo donde haga falta | Convertir la UI en un lanzador de acciones privilegiadas sin contrato estable |
| P1 | Home utilizable | Amarillo | 011, 131, 132, 133, 136, 140, 231, 232, 408 | Cerrar conjunto minimo de apps y recorrido de onboarding | Imagen Home que cubra tareas domesticas basicas, backup externo y accesibilidad aceptable | Ampliar demasiado el alcance de aplicaciones y perder foco del MVP |
| P2 | Business piloto | Amarillo conceptual, rojo operativo | 211, 212, 213, 214, 221, 222, 224, 409 | Congelar politicas iniciales, enrollment y tareas tipadas del piloto | Agente local + backend piloto multi-tenant con inventario, politica basica y ejecucion idempotente | Terminar implementando un control remoto generico o sin aislamiento suficiente entre organizaciones |
| P2 | Compliance, soporte y release final | Amarillo conceptual | 270, 271, 272, 317, 318, 329, 332, 402, 410 | Definir expediente de release V1, runbooks y alcance real de soporte | Checklist de candidato V1 con QA, licencias, fuentes publicables y plan de soporte | Declarar disponibilidad comercial sin capacidad operativa real |

## Orden recomendado de ejecucion

1. Cerrar `ADRs y base de plataforma`.
2. Construir `supply, build y repositorios`.
3. Entregar `instalacion`.
4. Demostrar `modelo de estado, update y recovery`.
5. Fijar `seguridad baseline`.
6. Elegir `escritorio oficial`.
7. Montar `shell`, `control center` y `Home utilizable`.
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
