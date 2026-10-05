# W4 Linux Base

## 00 — Project Context

**Documento:** `00_PROJECT_CONTEXT.md`  
**Proyecto:** W4 Linux Base  
**Familia:** W4 OS / W4 Enterprise Platform  
**Estado:** Arquitectura inicial  
**Base inicial:** Debian GNU/Linux  
**Versión objetivo inicial:** V1  

---

# 1. Introducción

**W4 Linux Base** es la plataforma fundamental de sistema operativo desarrollada por W4 sobre la cual se construyen los diferentes productos de la familia W4 OS.

Su propósito es proporcionar una capa común, estable, segura, administrable y extensible para gestionar las capacidades fundamentales de un sistema Linux sin obligar a las aplicaciones, interfaces gráficas o plataformas empresariales de W4 a interactuar directamente con las implementaciones internas de Debian o con herramientas individuales del sistema.

W4 Linux Base no debe entenderse únicamente como una distribución Linux, una colección de configuraciones Debian, una interfaz gráfica o documentación arquitectónica.

Es una **plataforma de software del sistema operativo**.

La arquitectura inicial utiliza Debian GNU/Linux como fundamento tecnológico y construye sobre él una capa W4 encargada progresivamente de abstraer, coordinar y administrar los diferentes subsistemas del sistema.

La relación conceptual inicial es:

```text
Hardware
   │
Linux Kernel
   │
Debian GNU/Linux
   │
W4 Linux Base
   │
W4 Platform Services
   │
W4 Product Profiles
   │
┌───────────────┬──────────────────┬───────────────┐
│ W4 OS Home    │ W4 OS Business   │ W4 OS Server │
└───────────────┴──────────────────┴───────────────┘
```

---

# 2. Visión

La visión de W4 Linux Base es construir una plataforma Linux propia de W4 que permita desarrollar múltiples sistemas operativos y soluciones empresariales utilizando una arquitectura común.

La plataforma deberá evolucionar desde una integración inicialmente basada ampliamente en Debian hacia una infraestructura W4 progresivamente más independiente en sus capas superiores.

Esto no implica reemplazar Debian por principio.

Cada sustitución deberá responder a una necesidad técnica, estratégica, operativa, de seguridad, rendimiento o mantenibilidad claramente identificada.

La filosofía será:

> Utilizar componentes Linux maduros donde aporten valor y desarrollar tecnología W4 donde proporcione una ventaja arquitectónica, operativa o empresarial.

---

# 3. Naturaleza del proyecto

W4 Linux Base tendrá cuatro dimensiones principales:

1. **Arquitectura**
2. **Software**
3. **Integración**
4. **Documentación**

La documentación define el comportamiento esperado.

La arquitectura define las responsabilidades y límites.

El código implementa esas decisiones.

Los paquetes resultantes constituyen W4 Linux Base.

```text
Documentation
      │
      ▼
Architecture
      │
      ▼
Implementation
      │
      ▼
W4 Packages
      │
      ▼
W4 Linux Base
      │
      ▼
W4 OS Products
```

Por lo tanto:

```text
W4 Linux Base != Documentation
```

La documentación es la especificación del proyecto.

W4 Linux Base es la implementación resultante.

---

# 4. Problema que resuelve

Construir W4 OS Home, Business y Server directamente sobre Debian sin una plataforma intermedia produciría dependencias profundas entre cada producto y los detalles internos del sistema base.

Por ejemplo:

```text
W4 OS Home ────────► apt
                    systemd
                    NetworkManager
                    udev
                    PAM
                    nftables

W4 OS Business ────► apt
                     systemd
                     NetworkManager
                     udev
                     PAM
                     nftables

W4 OS Server ──────► apt
                     systemd
                     NetworkManager
                     udev
                     PAM
                     nftables
```

Esto duplicaría lógica y dificultaría:

- mantenimiento;
- actualizaciones;
- automatización;
- seguridad;
- administración remota;
- evolución tecnológica;
- pruebas;
- compatibilidad;
- sustitución de componentes.

W4 Linux Base introduce una capa común:

```text
W4 OS Home ────────┐
                   │
W4 OS Business ────┼──► W4 Linux Base
                   │
W4 OS Server ──────┘
                        │
                        ▼
                     Debian
                        │
                        ▼
                   Linux Kernel
```

Los productos W4 dejan así de depender directamente de numerosos componentes individuales del sistema operativo.

---

# 5. Objetivo principal

El objetivo principal de W4 Linux Base es proporcionar una **plataforma común de servicios del sistema operativo** para toda la familia W4.

La plataforma deberá proporcionar interfaces estables para capacidades como:

- hardware;
- drivers;
- paquetes;
- actualizaciones;
- servicios;
- almacenamiento;
- redes;
- identidad;
- usuarios;
- sesiones;
- seguridad;
- configuración;
- logging;
- telemetría;
- diagnóstico;
- recuperación;
- instalación;
- inventario;
- administración local;
- administración remota.

---

# 6. Principio API-First

W4 Linux Base será diseñado siguiendo un principio **API-first**.

La lógica del sistema no deberá depender de una interfaz gráfica determinada.

Conceptualmente:

```text
                 W4 System API
                       │
       ┌───────────────┼────────────────┐
       │               │                │
       ▼               ▼                ▼
    W4 GUI          w4ctl           W4 Agent
       │               │                │
       └───────────────┼────────────────┘
                       │
                       ▼
                W4 Linux Base
```

Esto permitirá que una misma operación pueda ejecutarse desde diferentes interfaces.

Por ejemplo:

```text
Install Update
      │
      ├── W4 Settings
      │
      ├── w4ctl
      │
      ├── W4 Agent
      │
      └── Automation
              │
              ▼
       W4 Update Service
              │
              ▼
       Package Provider
              │
              ▼
           apt/dpkg
```

La lógica permanecerá centralizada independientemente de quién solicite la operación.

---

# 7. W4 System API

La **W4 System API** será la interfaz programática principal de W4 Linux Base.

Permitirá acceder de forma controlada a las capacidades del sistema.

Ejemplos conceptuales:

```text
system.info()
hardware.list()
hardware.rescan()

network.status()
network.interfaces()

storage.devices()
storage.volumes()

packages.search()
packages.install()
packages.remove()

updates.check()
updates.install()

services.list()
services.start()
services.stop()

security.status()

users.list()

telemetry.status()

recovery.status()
```

La implementación exacta del protocolo, transporte, permisos y versionado será definida en documentos posteriores.

---

# 8. Arquitectura conceptual

La arquitectura general será:

```text
┌───────────────────────────────────────────────┐
│              W4 PRODUCT LAYER                 │
│                                               │
│ Home          Business           Server       │
├───────────────────────────────────────────────┤
│              W4 EXPERIENCE                    │
│                                               │
│ GUI │ Settings │ Admin Tools │ Applications  │
├───────────────────────────────────────────────┤
│              W4 SYSTEM API                    │
├───────────────────────────────────────────────┤
│             W4 PLATFORM SERVICES              │
│                                               │
│ Hardware │ Network │ Storage │ Security       │
│ Packages │ Update  │ Identity │ Recovery      │
│ Services │ Config  │ Logging │ Telemetry      │
├───────────────────────────────────────────────┤
│            W4 PROVIDER / ADAPTER LAYER        │
├───────────────────────────────────────────────┤
│                 DEBIAN BASE                   │
│                                               │
│ apt │ dpkg │ systemd │ udev │ D-Bus │ PAM    │
│ nftables │ NetworkManager │ GNU utilities     │
├───────────────────────────────────────────────┤
│                LINUX KERNEL                   │
├───────────────────────────────────────────────┤
│                   HARDWARE                    │
└───────────────────────────────────────────────┘
```

---

# 9. Provider / Adapter Layer

Uno de los componentes arquitectónicos fundamentales será la capa de proveedores y adaptadores.

W4 Linux Base no deberá asumir que cada capacidad estará permanentemente implementada mediante una tecnología determinada.

Por ejemplo:

```text
W4 Package Service
        │
        ▼
PackageProvider
        │
        ├── AptProvider
        ├── FlatpakProvider
        └── FutureProvider
```

Otro ejemplo:

```text
W4 Network Service
        │
        ▼
NetworkProvider
        │
        ├── NetworkManagerProvider
        ├── systemd-networkd Provider
        └── FutureProvider
```

Esto permite desacoplar la API W4 de las implementaciones concretas.

---

# 10. Servicios fundamentales

W4 Linux Base podrá evolucionar hacia un conjunto de servicios especializados.

Ejemplos conceptuales:

```text
w4-systemd
w4-hardwared
w4-networkd
w4-storaged
w4-packaged
w4-updated
w4-securityd
w4-identityd
w4-telemetryd
w4-recoveryd
```

Los nombres definitivos y la separación entre procesos serán definidos durante el diseño detallado.

No todos los módulos deberán necesariamente ejecutarse como daemons independientes.

La arquitectura deberá justificar cada proceso persistente considerando:

- seguridad;
- aislamiento;
- consumo de memoria;
- rendimiento;
- resiliencia;
- superficie de ataque;
- mantenibilidad.

---

# 11. W4 Control CLI

W4 Linux Base deberá proporcionar una herramienta administrativa común.

Nombre conceptual:

```text
w4ctl
```

Ejemplos:

```bash
w4ctl system info

w4ctl hardware list

w4ctl network status

w4ctl storage list

w4ctl package search nginx

w4ctl package install nginx

w4ctl update check

w4ctl update install

w4ctl service list

w4ctl service restart nginx

w4ctl security status

w4ctl telemetry status

w4ctl recovery status
```

`w4ctl` no implementará directamente toda la lógica.

Será un cliente de los servicios y APIs de W4 Linux Base.

```text
Administrator
      │
      ▼
    w4ctl
      │
      ▼
W4 System API
      │
      ▼
W4 Services
```

---

# 12. Base Debian

Durante V1, W4 Linux Base utilizará Debian como fundamento tecnológico principal.

Esto permite aprovechar tecnologías maduras como:

```text
Linux Kernel
GNU userspace
systemd
udev
D-Bus
APT
dpkg
PAM
nftables
journald
NetworkManager
system libraries
Debian repositories
```

W4 Linux Base no intentará reemplazar indiscriminadamente estos componentes durante V1.

Inicialmente actuará principalmente como:

```text
Management Layer
        +
Abstraction Layer
        +
Integration Layer
        +
Policy Layer
        +
Automation Layer
```

sobre Debian.

---

# 13. Estrategia de independencia

La dependencia inicial de Debian deberá estar encapsulada.

En lugar de:

```text
W4 Application
      │
      ▼
apt
```

se utilizará:

```text
W4 Application
      │
      ▼
W4 Package API
      │
      ▼
W4 Package Service
      │
      ▼
AptProvider
      │
      ▼
apt/dpkg
```

Esto permite que en una versión futura:

```text
AptProvider
```

pueda coexistir con:

```text
AlternativeProvider
W4NativeProvider
```

sin modificar las aplicaciones consumidoras.

Este patrón deberá aplicarse siempre que resulte técnicamente razonable.

---

# 14. W4 OS Home

W4 OS Home utilizará W4 Linux Base como plataforma común y añadirá un perfil orientado al usuario personal.

```text
W4 Linux Base
      +
W4 Home Profile
      +
Desktop Environment
      +
Consumer Applications
      +
Home UX
      =
W4 OS Home
```

Sus prioridades serán principalmente:

- facilidad de uso;
- hardware de consumo;
- multimedia;
- productividad;
- conectividad;
- aplicaciones personales;
- experiencia gráfica;
- recuperación sencilla.

---

# 15. W4 OS Business

W4 OS Business utilizará la misma plataforma base, incorporando capacidades empresariales.

```text
W4 Linux Base
      +
W4 Business Profile
      +
Enterprise Security
      +
Policy Management
      +
W4 Agent
      +
Business Applications
      =
W4 OS Business
```

Sus prioridades incluirán:

- seguridad empresarial;
- administración centralizada;
- políticas;
- inventario;
- automatización;
- identidad empresarial;
- administración remota;
- telemetría;
- integración con W4 Enterprise Platform.

---

# 16. W4 OS Server

W4 OS Server utilizará W4 Linux Base con un perfil orientado a servidores e infraestructura.

```text
W4 Linux Base
      +
W4 Server Profile
      +
Server Services
      +
Virtualization
      +
Containers
      +
Storage
      +
Networking
      =
W4 OS Server
```

Entre sus posibles capacidades estarán:

- servicios de red;
- servidores de aplicaciones;
- bases de datos;
- virtualización;
- contenedores;
- almacenamiento;
- administración remota;
- observabilidad;
- automatización;
- alta disponibilidad.

W4 OS Server podrá disponer de una interfaz gráfica de administración desde V1 sin que dicha interfaz sea requisito para la operación del sistema.

---

# 17. W4 Agent

W4 Agent será el puente principal entre una instalación administrada y W4 Enterprise Platform.

```text
W4 Enterprise Platform
          │
          ▼
      W4 Agent
          │
          ▼
    W4 System API
          │
          ▼
   W4 Linux Base
```

El Agent no deberá duplicar la lógica del sistema operativo.

Por ejemplo, para instalar una actualización:

```text
W4 Enterprise Platform
          │
          ▼
      W4 Agent
          │
          ▼
W4 Update API
          │
          ▼
W4 Update Service
          │
          ▼
Package Provider
```

De esta manera las operaciones locales y remotas utilizan la misma infraestructura.

---

# 18. W4 Enterprise Platform

W4 Linux Base deberá integrarse de forma nativa con W4 Enterprise Platform.

La relación conceptual será:

```text
                 W4 Enterprise Platform

              W4 Control
                   │
      ┌────────────┼─────────────┐
      │            │             │
Automation      Security    Observability
      │            │             │
      └────────────┼─────────────┘
                   │
               W4 Agent
                   │
               W4 Linux Base
                   │
        ┌──────────┼──────────┐
        │          │          │
       Home     Business    Server
```

W4 Enterprise Platform deberá poder administrar infraestructura W4 sin depender de detalles internos de Debian.

---

# 19. Independencia de W4 Enterprise Platform

Aunque W4 Linux Base tendrá integración privilegiada con W4 Enterprise Platform, la plataforma empresarial no deberá depender exclusivamente de W4 OS.

W4 Agent deberá evolucionar para poder administrar otros sistemas soportados.

Conceptualmente:

```text
                 W4 Enterprise Platform
                          │
                       W4 Agent
                          │
        ┌─────────────────┼─────────────────┐
        │                 │                 │
 W4 Linux Base         RHEL-family       Debian-family
        │
 ┌──────┼──────┐
 │      │      │
Home Business Server
```

Esto permitirá que W4 Enterprise Platform gestione entornos heterogéneos.

---

# 20. W4 Linux Base vs W4 Enterprise Base

Ambos proyectos tienen responsabilidades diferentes.

## W4 Linux Base

Responsable del funcionamiento local del sistema operativo.

```text
Hardware
Drivers
Boot
Packages
Updates
Networking
Storage
Users
Services
Security
Recovery
Telemetry
System APIs
```

## W4 Enterprise Base

Responsable de capacidades compartidas de administración empresarial.

```text
Fleet Management
Enterprise Identity
Policy Management
Inventory
Automation
Security Management
Observability
Enterprise APIs
Device Management
```

La relación será aproximadamente:

```text
W4 Enterprise Platform
          │
W4 Enterprise Base
          │
      W4 Agent
          │
   W4 Linux Base
          │
      W4 OS
```

---

# 21. Hardware

W4 Linux Base deberá proporcionar una abstracción común para detectar, identificar y administrar hardware.

El subsistema deberá poder trabajar con información procedente de tecnologías Linux existentes como:

```text
sysfs
udev
procfs
PCI
USB
ACPI
DMI
kernel interfaces
```

Sobre estas fuentes se construirá una representación W4 normalizada.

Ejemplo:

```text
Physical Hardware
       │
Linux Kernel
       │
udev / sysfs / procfs
       │
W4 Hardware Provider
       │
W4 Hardware Service
       │
W4 Hardware API
```

---

# 22. Drivers

La gestión de drivers deberá integrarse con el subsistema de hardware.

El sistema deberá poder determinar:

```text
Detected Device
      │
      ▼
Hardware Identification
      │
      ▼
Driver Requirement
      │
      ▼
Available Driver
      │
      ├── Kernel
      ├── Installed
      ├── Repository
      ├── Vendor
      └── Unsupported
```

La automatización de drivers será tratada como un subsistema separado y deberá respetar estrictamente las restricciones de seguridad del kernel.

---

# 23. Actualizaciones

W4 Linux Base deberá proporcionar una infraestructura de actualización superior a la simple ejecución directa de APT.

Conceptualmente:

```text
Update Request
      │
      ▼
W4 Update Service
      │
      ├── Policy
      ├── Dependency Check
      ├── Security Check
      ├── Snapshot
      ├── Installation
      ├── Verification
      └── Rollback
              │
              ▼
         Package Provider
              │
              ▼
           apt/dpkg
```

A largo plazo podrán incorporarse:

- actualizaciones transaccionales;
- snapshots;
- rollback;
- canales;
- actualizaciones escalonadas;
- actualizaciones empresariales;
- mantenimiento programado.

---

# 24. Seguridad

La seguridad será una característica transversal y no un módulo añadido posteriormente.

W4 Linux Base deberá considerar desde su arquitectura:

```text
Least Privilege
Privilege Separation
Authentication
Authorization
Policy Enforcement
Secure IPC
Package Verification
Update Verification
Audit
Secrets Protection
Service Isolation
Filesystem Permissions
Kernel Security
Network Security
Recovery Security
```

Las aplicaciones gráficas no deberán recibir privilegios administrativos permanentes simplemente por necesitar ejecutar ocasionalmente operaciones privilegiadas.

---

# 25. Observabilidad

W4 Linux Base deberá proporcionar una visión normalizada del estado del sistema.

Esto incluirá progresivamente:

```text
Logs
Metrics
Events
Health
Hardware Status
Service Status
Update Status
Security Status
Resource Usage
Diagnostics
```

Los datos podrán ser consumidos localmente o, bajo políticas explícitas, enviados a W4 Enterprise Platform mediante W4 Agent.

---

# 26. Recuperación

La capacidad de recuperación será parte fundamental de la plataforma.

El subsistema deberá evolucionar para soportar:

- diagnóstico;
- reparación;
- rollback;
- snapshots;
- recuperación de paquetes;
- recuperación de configuración;
- modo de emergencia;
- recuperación del arranque;
- restauración controlada.

Conceptualmente:

```text
Failure
   │
   ▼
W4 Recovery
   │
   ├── Diagnose
   ├── Repair
   ├── Rollback
   ├── Restore
   └── Emergency Environment
```

---

# 27. Estructura de archivos

W4 deberá seguir inicialmente las convenciones estándar de Linux y Debian.

Una posible estructura W4 será:

```text
/etc/w4/
/usr/bin/w4ctl
/usr/lib/w4/
/usr/libexec/w4/
/usr/share/w4/
/var/lib/w4/
/var/cache/w4/
/var/log/w4/
/run/w4/
```

Ejemplo:

```text
/etc/w4/
├── system/
├── network/
├── security/
├── update/
└── policies/

/usr/lib/w4/
├── hardware/
├── network/
├── storage/
├── package/
├── update/
├── security/
└── recovery/

/var/lib/w4/
├── state/
├── inventory/
├── history/
└── recovery/
```

La estructura definitiva será documentada independientemente.

---

# 28. Repositorio

Una estructura inicial del repositorio podría evolucionar hacia:

```text
W4-Linux-Base/
│
├── docs/
│
├── src/
│   ├── system/
│   ├── hardware/
│   ├── network/
│   ├── storage/
│   ├── packages/
│   ├── update/
│   ├── security/
│   ├── identity/
│   ├── telemetry/
│   └── recovery/
│
├── cli/
│   └── w4ctl/
│
├── services/
│
├── providers/
│   └── debian/
│
├── config/
│
├── packaging/
│
├── tests/
│
└── tools/
```

Esta estructura es conceptual y no constituye todavía una decisión definitiva sobre lenguajes, procesos o límites de paquetes.

---

# 29. Principios arquitectónicos

W4 Linux Base seguirá los siguientes principios.

### 29.1 API First

Las capacidades fundamentales deberán estar disponibles mediante interfaces programáticas estables.

### 29.2 Separation of Concerns

Cada subsistema tendrá responsabilidades claramente delimitadas.

### 29.3 Provider Architecture

Las tecnologías externas deberán encapsularse cuando resulte razonable.

### 29.4 Secure by Default

La configuración predeterminada deberá priorizar seguridad.

### 29.5 Least Privilege

Los componentes deberán utilizar únicamente los privilegios necesarios.

### 29.6 Automation First

Toda operación administrativa importante deberá poder automatizarse.

### 29.7 Observable by Design

Los subsistemas deberán exponer información suficiente para diagnóstico y operación.

### 29.8 Recoverable by Design

Las operaciones críticas deberán considerar mecanismos de recuperación.

### 29.9 Backward Compatibility

Las APIs públicas deberán evolucionar mediante políticas explícitas de compatibilidad.

### 29.10 Replaceability

Las dependencias importantes deberán evitar acoplamientos innecesarios.

---

# 30. Lo que W4 Linux Base no será

W4 Linux Base no será simplemente:

```text
Debian + Theme
```

Tampoco será únicamente:

```text
Debian + W4 Applications
```

Ni:

```text
Debian + Custom Installer
```

Ni:

```text
Documentation Project
```

La meta será:

```text
Linux
  │
Debian Foundation
  │
W4 System Platform
  │
W4 Services
  │
W4 APIs
  │
W4 Product Profiles
  │
W4 Operating Systems
```

---

# 31. Estrategia para V1

V1 deberá priorizar una arquitectura viable y mantenible sobre una independencia artificial de Debian.

La estrategia será:

```text
V1
│
├── Debian stable foundation
├── W4 package repositories
├── W4 configuration
├── W4 System API
├── W4 CLI
├── W4 hardware abstraction
├── W4 update abstraction
├── W4 network abstraction
├── W4 storage abstraction
├── W4 security layer
├── W4 telemetry
├── W4 recovery foundation
├── W4 Agent integration
└── Product profiles
```

V1 deberá demostrar que Home, Business y Server pueden compartir realmente una plataforma común.

---

# 32. Evolución futura

La evolución podrá producirse progresivamente:

```text
V1
Debian + W4 Management Platform

        ↓

V2
Expanded W4 System Services

        ↓

V3
Greater W4 abstraction

        ↓

V4+
Selective native W4 components
```

No se establecerá como objetivo reemplazar componentes Linux maduros únicamente para obtener independencia nominal.

Cada cambio deberá demostrar beneficios concretos.

---

# 33. Modelo de producto

La arquitectura final deberá permitir pensar en:

```text
                    W4 Linux Base
                          │
          ┌───────────────┼───────────────┐
          │               │               │
     Home Profile    Business Profile  Server Profile
          │               │               │
          ▼               ▼               ▼
     W4 OS Home      W4 OS Business   W4 OS Server
```

Los perfiles determinarán:

- paquetes;
- servicios;
- políticas;
- configuración;
- interfaz;
- aplicaciones;
- capacidades habilitadas;
- comportamiento predeterminado.

---

# 34. Objetivo estratégico

W4 Linux Base debe permitir que W4 evolucione desde la construcción de distribuciones individuales hacia la construcción de una **familia coherente de sistemas operativos y plataformas administrables**.

La ventaja arquitectónica buscada es:

```text
One Platform
     │
     ├── Multiple Products
     ├── Multiple Interfaces
     ├── Central Management
     ├── Shared Security
     ├── Shared Updates
     ├── Shared Hardware Layer
     ├── Shared Automation
     └── Shared Engineering
```

---

# 35. Definición oficial

> **W4 Linux Base es la plataforma fundamental de sistemas operativos Linux desarrollada por W4, responsable de proporcionar una arquitectura común de ejecución, hardware, controladores, paquetes, actualizaciones, almacenamiento, red, identidad, seguridad, servicios, configuración, observabilidad, diagnóstico, recuperación y administración sobre la cual se construyen los sistemas operativos de la familia W4.**
>
> **Durante sus primeras versiones, W4 Linux Base utiliza Debian GNU/Linux como fundamento tecnológico y construye sobre él una capa de servicios, APIs, proveedores y herramientas propias de W4, permitiendo que W4 OS Home, W4 OS Business y W4 OS Server compartan una infraestructura común sin quedar directamente acoplados a las implementaciones internas de Debian.**
>
> **W4 Linux Base está diseñado además para integrarse nativamente con W4 Agent y W4 Enterprise Platform, permitiendo que las capacidades locales del sistema operativo puedan utilizarse de forma coherente desde interfaces gráficas, herramientas CLI, automatización y sistemas de administración empresarial.**

---

# 36. Principio rector

El principio rector del proyecto será:

> **W4 Linux Base debe convertir Linux en una plataforma coherente, programable, administrable y extensible para todo el ecosistema W4, aprovechando la madurez del ecosistema Linux sin sacrificar la capacidad de W4 para evolucionar su propia arquitectura.**

---

# 37. Próximo documento

El siguiente documento de la especificación será:

```text
01_V1_SCOPE_AND_OBJECTIVES.md
```

Este documento deberá establecer exactamente qué componentes de la arquitectura descrita pertenecen a **W4 Linux Base V1**, cuáles dependerán directamente de Debian, cuáles serán implementaciones W4 y cuáles quedarán explícitamente fuera de alcance para versiones posteriores.