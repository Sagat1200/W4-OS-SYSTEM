# 156 · W4 OS — Security Architecture

> **Edición documental:** 0.1 · **Fecha:** 2026-09-08 · **Idioma:** español  
> **Área:** Seguridad · **Responsabilidad propuesta:** Seguridad de producto  
> **Estado:** especificación de diseño; implementación y calificación no acreditadas por esta entrega.

## Propósito, contexto y objetivos

Definir amenazas y fronteras de confianza para el dispositivo y servicios W4.

## Alcance, arquitectura y decisiones

Activos: datos, credenciales, integridad de arranque, repositorios y control empresarial. Fronteras: app/usuario, usuario/root, dispositivo/red y build/publicación; controles se asignan por amenaza.

## Componentes y flujo operativo

1. Inventariar activo
2. identificar abuso
3. seleccionar control
4. crear prueba negativa
5. registrar riesgo residual
6. revisar al cambiar arquitectura.

## Seguridad y riesgos

No confundir cifrado, firma y aislamiento: cubren amenazas distintas. El administrador legítimo y el dispositivo comprometido requieren supuestos explícitos.

## Criterios de aceptación

Aceptar amenazas de actualización maliciosa, robo de equipo y política falsificada con controles y pruebas vinculadas.

## Rendimiento y evidencia

Medir riesgos sin dueño.

## Integración y dependencias

Contratos relacionados que deben mantenerse coherentes al implementar o cambiar este ámbito:

- [157 — Security Baseline](157_W4_OS_SECURITY_BASELINE.md)
- [169 — Supply Chain Security](169_W4_OS_SUPPLY_CHAIN_SECURITY.md)
- [175 — Incident Recovery Model](175_W4_OS_INCIDENT_RECOVERY_MODEL.md)

## Roadmap y condiciones de evolución

Modelo inicial antes de MVP y revisión por nuevas interfaces privilegiadas.

## Matriz inicial de amenazas

| Amenaza | Activo | Control propuesto | Evidencia exigida |
|---|---|---|---|
| Repositorio o mirror alterado | Integridad del sistema | Verificación de metadatos, hashes y origen | Cliente rechaza objeto alterado |
| Worker comprometido | Artefacto y claves | Aislamiento y firma fuera del worker | Trabajo no accede a clave ni promueve |
| Aplicación con permisos excesivos | Archivos de usuario | Permisos mínimos, portales y perfiles | Lectura fuera de concesión denegada |
| Equipo robado apagado | Datos en reposo | Cifrado y recuperación protegida | No acceso a volumen sin credencial |
| Política de otra organización | Flota y configuración | Autorización por recurso/tenant | Acceso cruzado rechazado |
| Operador de soporte abusivo | Sesión y datos | Consentimiento/autoridad, alcance y revocación | Corte de sesión elimina acceso |
| Actualización interrumpida | Disponibilidad y datos | Estado durable y recuperación | Arranque coherente y datos preservados |

## Supuestos que deben comprobarse

Un administrador local autorizado puede alterar gran parte del sistema; no se afirma protección absoluta frente a root. Cifrado en reposo no oculta datos de una sesión ya desbloqueada con permisos suficientes. Un canal TLS autenticado no implica que todas sus operaciones estén autorizadas. Una firma válida prueba vinculación al firmante conforme al mecanismo usado, no que el contenido sea benigno.

Estas distinciones permiten probar controles de manera precisa. Cada excepción debe indicar qué amenaza deja sin mitigar y qué función necesita la excepción. La aceptación de riesgo pertenece al rol responsable y debe quedar registrada; no se deduce de que una prueba funcional haya pasado.

---

[Anterior](155_W4_OS_PARENTAL_CONTROL_ARCHITECTURE.md) · [Índice de la colección](316_W4_OS_DOCUMENTATION_ARCHITECTURE.md#indice-completo) · [Siguiente](157_W4_OS_SECURITY_BASELINE.md)
