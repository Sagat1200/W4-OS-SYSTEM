# W4 OS Server — bootstrap desde Business y desarrollo independiente

**Documento operativo y de arquitectura · 30 de septiembre de 2026 · revisión 6**

**Workspace Windows previsto:** `C:\W4\Packages\W4-OS SERVER`

**Producto:** W4 OS Server. **Plataforma compartida:** W4 Linux Base. **Upstream V1:** Debian Stable, fijado a Debian 13 `trixie`. **Arquitectura inicial:** amd64, UEFI, headless.

## 1. Alcance, evidencia y significado de «clonar»

Este documento entrega en una sola pieza el procedimiento de bootstrap, la arquitectura objetivo, los archivos que deben crearse, los cambios del motor compartido y los criterios para construir y validar Server. La revisión inicial documentaba la ejecución esperada; al cierre de `C-092` ya existe un primer corte técnico P0 en este checkout común: perfil Server, política separada, Base sin desktop obligatorio, propagación de `codename=trixie`, overlay con identidad Server, recetas `rootfs`/`live`/`iso` generadas y publisher con `--package-set server`. En `C-093` se añadió el perfil de instalación Server MVP, inventario Hyper-V de laboratorio, bundle `build/install/w4-os-server` y cobertura PHPUnit del instalador. En `C-094` se materializaron en WSL el `rootfs`, el arbol live y la ISO Server `build/iso-output/w4-os-server/w4-os-server-live-amd64.iso`, con manifiesto sin paquetes desktop prohibidos. En `C-095` ese contrato headless quedó cubierto por `tests/ServerIsoArtifactTest.php`. En `C-096` se añadió `preflight_server_vm_validation.php` y la ISO arrancó en VirtualBox EFI hasta TTY con autologin `w4live`, identidad `W4 OS Server`, SSH activo y disco desechable visible. En `C-097` Server completó instalación destructiva sobre VDI desechable, verificación local, primer boot LUKS, login `w4-server-vm` y validación SSH con `w4admin`. En `C-098` se ejecutó baseline runtime Server: el primer reporte detectó ausencia de `w4-firstboot.service` en la instalación y UFW inactivo; se corrigió el generador live para transportar/aplicar `files/system-overlay` antes del squashfs y el instalador para reconocer firstboot bajo `/etc/systemd/system`. En `C-099` se regeneró live/ISO Server con esos fixes, se corrigió la normalización de permisos raíz críticos en el squashfs, se reinstaló una VM fresca sobre VDI `VBOX_HARDDISK_VBcffb5596-de88949f`, `w4-firstboot.service` habilitó UFW sin remediación manual y el baseline runtime Server actualizado cerró con `9 passed`, `0 failed`, `1 skipped`; el contrato SSH Server queda formalizado como administración remota habilitada por política de edición.

Se recuperó la conversación «Análisis del repositorio» y se consultaron directamente archivos de `Sagat1200/W4-OS-SYSTEM` en su rama predeterminada. La inspección es selectiva, no una auditoría completa ni una reproducción de sus pruebas. Las validaciones Home/Business que describe el README son evidencia declarada por el proyecto; deben repetirse para Server.

| Evidencia consultada | Hallazgo que condiciona el trabajo |
|---|---|
| [Perfil Business](https://github.com/Sagat1200/W4-OS-SYSTEM/blob/main/manifests/w4-os-business.profile.json) | Hereda Base; exige `curl`, `jq`, `pipewire`, `xdg-desktop-portal`; recomienda VPN. |
| [Manifiesto Base](https://github.com/Sagat1200/W4-OS-SYSTEM/blob/main/manifests/w4-linux-base.manifest.json) | En la inspección inicial exigía **`w4-desktop-meta`** y `os-prober`; en `C-092` queda neutralizado para Server y conserva NetworkManager, UFW, PHP CLI y Btrfs. |
| [ManifestToolkit](https://github.com/Sagat1200/W4-OS-SYSTEM/blob/main/src/Manifest/ManifestToolkit.php) | Combina meta-paquetes por unión; `packages.remove` no retira meta-paquetes y no permite eliminar paquetes requeridos por Base. `inherits` debe apuntar a una base, no a Business. |
| [Generador de build-input](https://github.com/Sagat1200/W4-OS-SYSTEM/blob/main/scripts/generate_build_input.php) | La interfaz real es `--profile`; no admite `--edition=server`. |
| [Generador de overlay](https://github.com/Sagat1200/W4-OS-SYSTEM/blob/main/scripts/generate_system_overlay.php) | En la inspección inicial tenía decisiones binarias Business/«lo demás»; en `C-092` usa catálogo explícito para Home, Business y Server y falla ante perfiles sin política. |
| [Generador de repositorio](https://github.com/Sagat1200/W4-OS-SYSTEM/blob/main/scripts/generate_update_repository_bundle.php) | En la inspección inicial enumeraba Home/Business; en `C-092` incorpora `w4-server-meta` y acepta `--package-set server`, manteniendo `both` como compatibilidad Home+Business. |
| [composer.json](https://github.com/Sagat1200/W4-OS-SYSTEM/blob/main/composer.json) | PHP `^8.4`, PHPUnit `^11.5`; separar dependencias de desarrollo del runtime. |
| [README](https://github.com/Sagat1200/W4-OS-SYSTEM/blob/main/README.md) | Describe instalación UEFI/LUKS2/Btrfs, actualización durable y repositorio firmado; aclara `apply_mode=live-apt` aunque existan scripts llamados «offline». SSH aparece como excepción temporal de laboratorio en Business. |

Identificadores de blobs consultados, útiles para detectar cambios: Base `1c80d59083996d09436445640e64df326727f9c2`; Business `1c54c9f1850efcfdb3c5bf6e562ca6995f071108`; resolver `2197eca6c15ab67cbb5886cd38439d18346450bc`; build-input `567cbba425ce8e3017842ed766e1f47522a838fc`; overlay `78af1740cb04dca30a4b64b4eff3452931dead95`; generador de repositorio `e7bad82c2f2e6ebb40d6da5ec606c3a5aa9476d4`. Son blobs de archivos, **no commits**. El procedimiento fijará el commit real usado para el bootstrap.

En este plan, clonar significa obtener un checkout íntegro y trazable del repositorio y derivar **una composición Server** de Business. Los motores comunes permanecen una sola vez en `src/` y `scripts/`. No se crea `src/Server/InstallerToolkit.php`, ni una copia Server de cada generador.

La independencia de Server significa identidad, requisitos, configuración, pruebas, paquetes y ciclo de publicación propios. No significa mantener un fork divergente de la plataforma común. El checkout Windows puede ser independiente del checkout Business; el código común sigue teniendo un origen canónico.

## 2. Objetivos y límites de V1

1. Instalar y operar sin entorno gráfico, display manager ni aplicaciones desktop.
2. Reutilizar build, instalador, seguridad, APT, actualización y recuperación de W4 Linux Base.
3. Convertir SSH y administración remota en capacidades soportadas del producto, no excepciones de laboratorio.
4. Mantener Debian Stable durante V1; fijar el codename y registrar versiones resueltas.
5. Probar instalación, reinicio cifrado, actualización firmada y recuperación antes de añadir roles complejos.
6. Conservar Home/Business funcionales después de retirar desktop de Base.
7. Permitir evolución Server sin depender del roadmap gráfico Business.

Fuera de la primera PoC: Kubernetes, OpenStack, clústeres HA, almacenamiento distribuido, panel web, W4 Agent obligatorio, enrolamiento automático, interfaz gráfica y conversión en caliente de equipos Business existentes.

La instalación inicial recomendada es limpia. Migrar una máquina Business en producción constituye un proyecto diferente: inventario de servicios/datos, backup y ensayo de restauración antes de cualquier retirada de paquetes.

## 3. Arquitectura y propiedad de componentes

```text
Debian Stable, fijado a trixie
          |
    W4 Linux Base
    kernel / arranque / APT / runtime mínimo
    resolver / build / installer / security / update / recovery
          |
          +-- W4 Desktop: Home y Business
          |     sesiones gráficas / audio / portales / aplicaciones
          |
          +-- W4 OS Server
                headless / SSH / políticas de servidor
                health checks / operación / roles opcionales
```

| Componente | Propietario | Regla de reutilización |
|---|---|---|
| Debian, kernel, GRUB, initramfs, integración LUKS/Btrfs | Base | Implementación única; políticas de instalación parametrizadas. |
| Resolver y generadores rootfs/live/ISO | Base | Reciben perfil y entradas validadas; rechazan edición desconocida. |
| InstallerToolkit, UpdateToolkit, SecurityBaselineToolkit | Base | Compartidos; Server aporta políticas y pruebas específicas. |
| Firma y publicación APT | Base | Un motor; canales y composición Server diferenciados. |
| PHP/runtime W4 | Base | Paquete común mínimo, independiente de GUI y del host de build. |
| Desktop, audio, portales, display manager | Capa Desktop | Dependencias explícitas de Home/Business. |
| Perfil, branding, default target, SSH, políticas Server | Server | Versionados y mantenidos por el producto Server. |
| Integración empresarial reutilizable | Capa empresarial futura | Opcional; sin dependencia transitiva de desktop. |
| Web/Database/Virtualization/Container/Storage | Roles Server futuros | Instalación explícita, pruebas y lifecycle propios. |

La política inicial conserva **NetworkManager y UFW**, ya requeridos por Base. NetworkManager funciona sin GUI. Cambiar ahora a networkd/nftables directamente obligaría a refactorizar contratos comunes adicionales y aumentaría el riesgo del bootstrap. Una transición posterior deberá establecer un único propietario de interfaces y firewall.

## 4. Estrategia Git y evolución independiente

### Etapa A: checkout de bootstrap

Clonar el repositorio completo en el destino indicado y abrir una rama `bootstrap/w4-os-server`. Esto conserva historia, licencias y pruebas. No copiar una carpeta Business encima de otra ni reutilizar discos/ISOs ya instalados como imagen maestra.

La presencia de `src/` en el checkout es necesaria para construir. La duplicación que se evita es mantener otra implementación de esos motores dentro de una carpeta Server o repositorio de producto.

### Etapa B: separación lógica en el repositorio común

Crear perfil y políticas Server; extraer desktop de Base; generalizar los puntos binarios. Integrar los cambios comunes en el repositorio canónico. La rama bootstrap es transitoria, no un fork permanente que acumula parches de seguridad sin sincronizar.

### Etapa C: repositorio de producto opcional

Cuando exista una interfaz estable, Server puede tener un repositorio ligero con manifiestos, políticas, roles, pruebas y documentación, que consuma una versión fija de Base mediante paquete publicado o checkout/submódulo fijado por commit. Preferir paquetes versionados para runtime.

**El motor actual busca `manifests/` respecto a su propio root y no aporta aquí una interfaz verificada para overlays externos.** Antes de extraer el producto hay que implementar y probar carga de raíces de perfiles, resolución de Base por versión, precedencia y prohibición de escapes de ruta. No mover hoy archivos a un repositorio separado esperando que los scripts los descubran automáticamente.

Server mantiene su propia versión de producto y registra `base_version`, `base_commit`, contrato de perfil, versión de runtime y versión Debian. Las mejoras del motor se integran en Base; Server actualiza la dependencia mediante pruebas de compatibilidad.

## 5. Inventario: copiar, reutilizar, excluir y refactorizar

| Elemento actual | Acción |
|---|---|
| `manifests/w4-os-business.profile.json` | Usar como referencia; crear `w4-os-server.profile.json` con identidad y lista propias. |
| Base, `src/`, toolkits, `scripts/lib/`, bootstrap/autoload | Reutilizar; no duplicar por edición. |
| `scripts/generate_*`, `prepare_*`, publicación y update | Parametrizar donde aún existan supuestos Home/Business. |
| `tests/` comunes | Conservar; añadir casos Server y regresión Home/Business. |
| Configuración empresarial genérica | Extraer sólo si es funcional, necesaria y ajena al escritorio. |
| `business-policy-hooks` | No trasladar como feature de Server; sustituir por capacidades comunes concretas cuando existan. |
| `device-enrollment-ready`, `inventory-ready` | No presentarlas como implementación; reservar para Agent futuro. |
| PipeWire, WirePlumber, PulseAudio, portales XDG | Excluir de composición Server; comprobar dependencias transitivas. |
| Xorg/Wayland compositor, GNOME/KDE/Xfce, display managers | No instalar ni habilitar en Server. |
| Navegador, suite ofimática, tiendas gráficas, Flatpak | Fuera de Server base. |
| NetworkManager | Conservar servicio y `nmcli`; no requiere applet gráfico. |
| OpenVPN/WireGuard | Roles/opciones explícitas; no dependencias de PoC. |
| `os-prober` | Retirar de Base en este bootstrap y conservar explícitamente en Home/Business para evitar regresión de su selección actual. Server no hace dual boot. |
| `fwupd` | Opcional para hardware validado; no requisito PoC. |
| `snapper` | No obligatorio en PoC: elegir backend Btrfs nativo y probarlo; si se usa backend snapper debe ser dependencia explícita. |
| `build/`, ISO, rootfs, logs de VM, NVRAM, capturas | No usar como fuente de producto. Si ya están rastreados, el checkout puede incluirlos: mantener intactos al clonar y planificar limpieza aparte. |
| Claves de firma privadas, passphrases, tokens, machine-id, claves SSH host | Nunca copiar a una imagen ni nuevo producto. |
| `vendor/` | Reconstruir desde lock; runtime sólo dependencias de producción. |

No hacer un reemplazo global `Business → Server`: dañaría historia, pruebas compartidas y referencias legítimas.

## 6. Clonación segura desde PowerShell

Este bloque se ejecuta en **Windows**, con Git instalado y acceso autorizado al repositorio privado. No requiere cambiar execution policy, desactivar validación TLS ni poner tokens en la URL. Usa el gestor de credenciales habitual.

El procedimiento falla si el destino existe, incluso vacío. Nunca elimina, mueve ni sobreescribe un checkout previo. Ante fallo parcial, inspeccionar la carpeta creada antes de decidir cómo continuar.

```powershell
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$RepositoryUrl = 'https://github.com/Sagat1200/W4-OS-SYSTEM.git'
$PackagesRoot = [IO.Path]::GetFullPath('C:\W4\Packages')
$ServerRoot = [IO.Path]::GetFullPath('C:\W4\Packages\W4-OS SERVER')
$BootstrapBranch = 'bootstrap/w4-os-server'

function Invoke-GitChecked {
    param([Parameter(Mandatory)][string[]]$GitArgs)
    & git @GitArgs
    if ($LASTEXITCODE -ne 0) {
        throw "Git terminó con código $LASTEXITCODE. Detener y revisar."
    }
}

Get-Command git -ErrorAction Stop | Out-Null
if ([IO.Path]::GetDirectoryName($ServerRoot) -ne $PackagesRoot) {
    throw 'El destino no es hijo directo de C:\W4\Packages.'
}
if (Test-Path -LiteralPath $ServerRoot) {
    throw "El destino ya existe: $ServerRoot. No se sobrescribirá."
}
# Rechazar junctions/symlinks en ancestros existentes del destino.
$AncestorPath = $PackagesRoot
while ($AncestorPath) {
    if (Test-Path -LiteralPath $AncestorPath) {
        $AncestorItem = Get-Item -LiteralPath $AncestorPath -Force
        if (-not $AncestorItem.PSIsContainer) { throw 'Un ancestro no es directorio.' }
        if ($AncestorItem.Attributes -band [IO.FileAttributes]::ReparsePoint) {
            throw "Ancestro redirigido: $AncestorPath. Revisar destino físico."
        }
    }
    $ParentPath = [IO.Path]::GetDirectoryName($AncestorPath)
    if ($ParentPath -eq $AncestorPath) { break }
    $AncestorPath = $ParentPath
}

[IO.Directory]::CreateDirectory($PackagesRoot) | Out-Null
Invoke-GitChecked -GitArgs @('clone', '--origin', 'origin', '--', $RepositoryUrl, $ServerRoot)
Invoke-GitChecked -GitArgs @('-C', $ServerRoot, 'config', '--local', 'core.autocrlf', 'false')
$BootstrapCommit = (Invoke-GitChecked -GitArgs @('-C', $ServerRoot, 'rev-parse', 'HEAD')).Trim()
Invoke-GitChecked -GitArgs @('-C', $ServerRoot, 'switch', '-c', $BootstrapBranch)

$RequiredFiles = @(
    'manifests\w4-linux-base.manifest.json',
    'manifests\w4-os-business.profile.json',
    'manifests\w4-os-home.profile.json',
    'src\Manifest\ManifestToolkit.php',
    'scripts\generate_build_input.php',
    'composer.json'
)
foreach ($RelativeFile in $RequiredFiles) {
    if (-not (Test-Path -LiteralPath (Join-Path $ServerRoot $RelativeFile) -PathType Leaf)) {
        throw "Falta $RelativeFile. El layout cambió; adaptar el plan antes de editar."
    }
}

$ServerDocs = Join-Path $ServerRoot 'Docs\SERVER'
[IO.Directory]::CreateDirectory($ServerDocs) | Out-Null
$BootstrapRecord = Join-Path $ServerDocs 'bootstrap-origin.json'
if (Test-Path -LiteralPath $BootstrapRecord) { throw 'Ya existe bootstrap-origin.json.' }
$Record = [ordered]@{
    product = 'w4-os-server'
    source_repository = $RepositoryUrl
    source_commit = $BootstrapCommit
    bootstrap_profile = 'w4-os-business'
    inherits = 'w4-linux-base'
    created_utc = [DateTime]::UtcNow.ToString('o')
    workspace = $ServerRoot
}
$Utf8NoBom = New-Object System.Text.UTF8Encoding($false)
[IO.File]::WriteAllText($BootstrapRecord, ($Record | ConvertTo-Json -Depth 8) + "`n", $Utf8NoBom)
Invoke-GitChecked -GitArgs @('-C', $ServerRoot, 'status', '--short')
Write-Output "Bootstrap fijado en $BootstrapCommit"
```

`origin` sigue apuntando al repositorio original. No se publica nada con este bloque. No configurar un remoto Server inventado. Si se crea posteriormente un repositorio de producto, usar su URL real y revisar qué historia/archivos se desean publicar.

### Inspección inmediatamente posterior

```powershell
Set-Location -LiteralPath 'C:\W4\Packages\W4-OS SERVER'
git status --short
git log -1 --format=fuller
git ls-files manifests src scripts tests
git grep -n -E 'w4-os-business|w4-os-home|w4-desktop-meta|package-set' -- src scripts manifests tests
git grep -n -E 'testing|stable|trixie|forky' -- manifests scripts
git grep -n -E 'pipewire|xdg-desktop-portal|display-manager|graphical.target|w4-home' -- src scripts manifests
```

Una búsqueda sin coincidencias devuelve código 1; no significa que el checkout esté roto. Revisar también README, `AGENTS.md` si existe, reglas del repositorio y cualquier cambio posterior a los blobs registrados.

No usar `robocopy /MIR`, borrados recursivos ni `git reset --hard` para este bootstrap. Guardar avances pequeños y revisables con commits explícitos después de comprobar el diff.

## 7. Estructura objetivo del checkout

Las rutas nuevas de esta sección son propuestas; no se afirma que ya existan.

```text
C:\W4\Packages\W4-OS SERVER\
├── manifests/
│   ├── w4-linux-base.manifest.json       # común, sin desktop obligatorio
│   ├── w4-os-home.profile.json          # conserva desktop explícito
│   ├── w4-os-business.profile.json      # conserva desktop explícito
│   └── w4-os-server.profile.json        # nuevo
├── src/                                # única implementación común
├── scripts/                            # única familia de entrypoints
├── config/
│   └── editions/server/                # NUEVO: contrato/configuración Server
│       ├── policy.json
│       ├── headless-denylist.txt
│       ├── sshd_config.d/
│       └── systemd/
├── packaging/
│   ├── w4-server-meta/                 # receta generada desde el perfil
│   └── w4-server-defaults/             # configuración y unidades Server
├── tests/
│   └── Server/                         # integración, aceptación y regresión
├── Docs/SERVER/
│   ├── bootstrap-origin.json
│   ├── ARCHITECTURE.md
│   ├── OPERATIONS.md
│   ├── ACCEPTANCE.md
│   └── adr/
├── composer.json
├── composer.lock
└── build/                              # salida regenerable, fuera de nuevos commits
```

Mantener el documento maestro como especificación inicial; dividirlo en esos archivos sólo cuando beneficie el mantenimiento. No duplicar textos normativos en varios sitios.

Agregar reglas de atributos para nuevos JSON/Markdown/PHP/shell con LF. No normalizar todo el repositorio en el mismo cambio funcional. `.gitignore` sólo afecta archivos no rastreados; retirar artefactos históricos del índice requiere un cambio separado, revisado y con ubicación alternativa para la evidencia.

## 8. Primer cambio obligatorio: Base sin desktop

Estado `C-092`: el primer refactor declarativo ya quedo aplicado en manifests y artefactos inmediatos. Antes de crear una ISO Server todavia falta comprobar el grafo APT real y reconstruir los metapaquetes en el repositorio W4.

Secuencia base:

1. Registrar los perfiles resueltos Home y Business antes del cambio.
2. Mover `w4-desktop-meta` desde `Base.meta_packages.required` hacia `meta_packages.required` de Home **y** Business.
3. Retirar `os-prober` de `Base.packages.required` y añadirlo a Home y Business para conservar su composición previa.
4. Conservar temporalmente NetworkManager, UFW, AppArmor, PHP y Btrfs en Base.
5. No eliminar PipeWire/portales del perfil Business original; eliminarlos sólo de la composición Server.
6. Mantener las recomendaciones comunes actuales durante este primer refactor; Server excluye explícitamente Flatpak, fwupd y snapper. Posteriormente mover recomendaciones desktop a su capa correspondiente.
7. Regenerar meta-paquetes; no basta editar JSON si el `.deb` anterior continúa exigiendo desktop.
8. Comparar conjuntos resueltos antes/después para Home/Business y revisar el grafo APT real.

Resultado esperado de `meta_packages.required`:

```text
Base:     w4-base-meta
Home:     w4-desktop-meta + w4-home-meta
Business: w4-desktop-meta + w4-business-meta
Server:   w4-server-meta
```

No añadir `meta_packages.remove`: el resolver inspeccionado no implementa ese contrato. Tampoco poner `network-manager`, `ufw` o `os-prober` en `packages.remove` mientras sigan siendo `required` de Base: la validación lo rechaza.

Durante la primera PoC se puede conservar la inferencia actual de paquetes desktop por intersección Home/Business, pero V1 debe usar un catálogo explícito de capas. Una coincidencia como `curl` entre ediciones no convierte ese paquete en desktop.

## 9. Perfil Server que debe crearse

Estado `C-092`: `manifests/w4-os-server.profile.json` ya fue creado con este contrato inicial, después del refactor de Base:

```json
{
  "schema_version": 1,
  "kind": "edition-profile",
  "id": "w4-os-server",
  "name": "W4 OS Server",
  "inherits": "w4-linux-base",
  "meta_packages": {
    "required": ["w4-server-meta"],
    "recommended": []
  },
  "packages": {
    "required": [
      "cryptsetup-initramfs",
      "curl",
      "iproute2",
      "jq",
      "openssh-server",
      "systemd-sysv",
      "systemd-timesyncd"
    ],
    "recommended": [],
    "remove": ["flatpak", "fwupd", "snapper"]
  },
  "features": [
    "server-baseline",
    "headless-default",
    "ssh-administration",
    "btrfs-recovery"
  ],
  "notes": [
    "Business se utiliza solo como referencia de bootstrap.",
    "NetworkManager y UFW se reutilizan desde W4 Linux Base.",
    "Las features requieren consumidores y pruebas; no habilitan servicios por si solas.",
    "Agent, VPN y roles de workloads son opcionales y posteriores."
  ]
}
```

Es un perfil compatible con las claves del esquema actual, **condicionado a que Base ya no imponga desktop**. Las cadenas de `features` pasan como metadatos; deben conectarse al overlay, instalador y checks. Su presencia no implementa headless, SSH ni recovery automáticamente.

No incluir `pipewire` y `xdg-desktop-portal` en la lista requerida; al no heredar Business no entran por ese perfil. La exclusión real debe verificarse también en meta-paquetes, recomendaciones, overlay y paquete instalado final.

### Contrato de política Server nuevo

Estado `C-092`: `config/editions/server/policy.json` ya fue creado fuera de `manifests/` para no confundir el cargador actual, que lee todos los JSON de esa carpeta:

```json
{
  "schema_version": 1,
  "profile_id": "w4-os-server",
  "branding": {"edition": "Server", "hostname_prefix": "w4-server"},
  "upstream_policy": {"distribution": "debian", "codename": "trixie"},
  "boot": {"architecture": "amd64", "firmware": "uefi", "default_target": "multi-user.target"},
  "network": {"backend": "NetworkManager", "configuration": "installer-required"},
  "ssh": {"enabled": true, "root_login": false, "authentication": "publickey"},
  "firewall": {"backend": "ufw", "incoming": "deny", "outgoing": "allow", "admin_cidrs": []},
  "storage": {"root_filesystem": "btrfs", "encryption_default": "luks2", "unlock": "console"},
  "update": {"automatic_reboot": false, "snapshot_backend": "btrfs"},
  "roles": [],
  "enterprise_agent": {"installed": false, "enrolled": false}
}
```

**Contrato propuesto, todavía sin consumidor verificado.** Implementar esquema y loader común, propagar su hash al build-input y resolver valores efectivos. Lista vacía de `admin_cidrs` significa que el instalador debe pedirla o que SSH seguirá inaccesible por firewall; jamás equivale silenciosamente a `0.0.0.0/0`.

Mantener separados: perfil de paquetes, política de edición y respuestas concretas de una instalación. Direcciones IP, nombre de usuario, llaves y discos pertenecen a las respuestas de instalación, no al perfil publicado.

## 10. Meta-paquetes y runtime W4

### Grafo objetivo

```text
w4-server-meta
├── w4-base-meta                 [común]
├── w4-system-runtime            [común; materializar si aún no existe]
├── w4-recovery-tools            [común; comprobar payload funcional]
├── w4-server-defaults           [Server; nuevo]
└── requisitos específicos del perfil Server
```

`w4-system-runtime` es un nombre propuesto para empaquetar los ejecutables y bibliotecas necesarios en el target. No se da por existente. El metapaquete no puede depender de él hasta que su paquete esté construido y disponible. Durante el bootstrap hay que inventariar qué runtime instala hoy el overlay y migrarlo a esta propiedad de paquetes sin romper rutas.

Plantilla de control binario para `w4-server-meta`:

```debcontrol
Package: w4-server-meta
Version: 0.1.0~poc1
Section: metapackages
Priority: optional
Architecture: all
Maintainer: W4 OS Server Maintainers
Depends: w4-base-meta, w4-system-runtime, w4-recovery-tools, w4-server-defaults, cryptsetup-initramfs, curl, iproute2, jq, openssh-server, systemd-sysv, systemd-timesyncd
Description: W4 OS Server headless system composition
 Minimal server composition on W4 Linux Base with remote administration.
```

Para empaquetado formal, completar identidad de mantenedor, licencia, `debian/control` de fuente, changelog, reglas y copyright conforme a la política del proyecto. La plantilla anterior muestra el contrato binario, no un paquete Debian completo.

Generar la parte de dependencias procedente de Base/perfil; comprobar por tests que no diverge del JSON. Los paquetes funcionales comunes y `w4-server-defaults` se añaden mediante un catálogo explícito. Nunca crear un metapaquete vacío para que APT «pase» mientras falten runtime/recovery reales.

| Paquete | Contenido/contrato |
|---|---|
| `w4-base-meta` | Dependencias comunes mínimas, sin desktop ni meta-paquetes de edición. |
| `w4-desktop-meta` | Composición desktop exclusivamente; nunca dependencia de runtime o enterprise. |
| `w4-server-meta` | Selección Server, sin archivos de datos de usuario. |
| `w4-server-defaults` | Defaults de SSH/systemd y políticas Server; cambios del administrador preservados como conffiles o configuración gestionada documentada. |
| `w4-system-runtime` | Sólo CLI/bibliotecas necesarias para operaciones W4; autoload y dependencias de producción fijadas. |
| `w4-recovery-tools` | Recovery utilizable y probado; no sólo metadata descriptiva. |
| `w4-enterprise-meta` | Futuro opcional; no imponer Agent en PoC. |

En build usar instalación sin recomendaciones automáticas y seleccionar explícitamente las necesarias. Esto reduce arrastre accidental, pero no sustituye inspeccionar dependencias transitivas. No usar comodines de purga como mecanismo de construcción headless: construir un rootfs limpio.

El host puede necesitar Composer, PHPUnit, herramientas ISO, GPG y VM. El target necesita PHP CLI si el coordinador actual lo usa, sin Composer de desarrollo, sin PHPUnit, sin Apache/PHP-FPM por defecto. Enumerar extensiones PHP efectivamente usadas y probar el runtime fuera del checkout de desarrollo.

## 11. Debian Stable y repositorios upstream

Debian identifica actualmente Debian 13 `trixie` como Stable. V1 debe fijar ese codename: el alias `stable` no debe provocar un salto mayor implícito cuando cambie la versión estable. [Referencia oficial Debian](https://www.debian.org/releases/index.html).

Mantener `track: stable` como política conceptual del manifiesto si se desea, pero implementar un campo/lock de `codename: trixie` y propagarlo al generador. El `createBuildInput` actual selecciona campos explícitamente: añadir una clave al JSON sin modificar su consumidor puede perderla. El generador rootfs consultado usa `upstream.track` para bootstrap, de modo que este cambio debe llegar hasta el script generado.

Distinguir **canal W4 `testing`** de **suite Debian `testing`**. La PoC puede estar en W4 testing sobre Debian trixie; ningún argumento de canal debe cambiar la distribución upstream.

Ejemplo de sources Debian para target, sujeto a la política de firmware del hardware:

```text
Types: deb
URIs: https://deb.debian.org/debian
Suites: trixie trixie-updates
Components: main non-free-firmware
Architectures: amd64
Signed-By: /usr/share/keyrings/debian-archive-keyring.gpg

Types: deb
URIs: https://security.debian.org/debian-security
Suites: trixie-security
Components: main non-free-firmware
Architectures: amd64
Signed-By: /usr/share/keyrings/debian-archive-keyring.gpg
```

Si se exige sólo software de `main`, retirar `non-free-firmware` y documentar la limitación de hardware. El formato deb822 y `Signed-By` están documentados en [sources.list(5)](https://manpages.debian.org/trixie/apt/sources.list.5.en.html).

Registrar por build el snapshot o fecha de índices Debian, versiones exactas, checksums y claves. Fijar un codename no produce reproducibilidad bit a bit: también cambian paquetes, timestamps y herramientas. Usar entradas inmutables para reconstruir releases, con un proceso separado que incorpore actualizaciones de seguridad oportunamente.

## 12. Refactor del resolver y generadores

Implementación común, sin variantes `generate_server_iso.php`:

1. Resolver `w4-os-server` con Base neutral; conservar contratos v1 de Home/Business.
2. Añadir catálogo de políticas por perfil; fallar ante perfil no registrado en vez de caer en Home.
3. Cambiar hostname/MOTD/branding de overlay a datos del perfil. El hostname definitivo lo selecciona el instalador.
4. Hacer que reglas de live y target sean diferentes: usuario live y credenciales temporales no sobreviven a la instalación.
5. Generalizar selección de paquetes del publisher y filtros `home/business/both`; incorporar Server por identidad, no por posición de lista.
6. Validar cierre de dependencias real: no desktop, paquetes W4 presentes, arquitectura correcta y firmware UEFI.
7. Registrar procedencia de cada paquete: Base, Desktop, Server o rol.
8. Serializar edición en build-input, rootfs-manifest, overlay, live-manifest, ISO, installation-plan, update-plan y health report.
9. Incorporar política headless como comprobación obligatoria y no sólo `features` informativas.
10. Documentar compatibilidad y migración si cambia la versión de esquema; no aceptar campos desconocidos silenciosamente como si fueran operativos.

Alias futuro opcional: `--edition server` se traduce a `--profile w4-os-server`. No es necesario para PoC y no se usa en los comandos existentes de este documento.

## 13. Pipeline de construcción

```text
commit + lock Base + perfil Server + políticas
  -> validate / resolve
  -> paquetes W4 reales + repositorio de build firmado
  -> build-input
  -> rootfs Debian amd64
  -> overlay Server
  -> live headless
  -> ISO UEFI
  -> instalación VM
  -> primer arranque / SSH / seguridad
  -> update firmado / fallo controlado / recovery
  -> promoción de artefactos ya probados
```

### Generación de recetas con CLI existentes

Después de implementar los puntos obligatorios y tener PHP/Composer adecuados, ejecutar desde el checkout. `composer install` utiliza el lock si existe; si falta, resolver y revisar uno en un cambio explícito antes de exigir builds reproducibles.

```powershell
Set-Location -LiteralPath 'C:\W4\Packages\W4-OS SERVER'

function Invoke-PhpChecked {
    param([Parameter(Mandatory)][string[]]$PhpArgs)
    & php @PhpArgs
    if ($LASTEXITCODE -ne 0) { throw "PHP terminó con código $LASTEXITCODE" }
}

composer validate --strict
if ($LASTEXITCODE -ne 0) { throw 'composer.json o lock no válidos.' }
composer install --no-interaction --prefer-dist
if ($LASTEXITCODE -ne 0) { throw 'Falló la instalación de dependencias.' }
Invoke-PhpChecked -PhpArgs @('scripts/validate_manifests.php', '--resolve', 'w4-os-server')
Invoke-PhpChecked -PhpArgs @('scripts/generate_build_input.php', '--profile', 'w4-os-server', '--channel', 'testing', '--format', 'iso')
Invoke-PhpChecked -PhpArgs @('scripts/generate_rootfs_bundle.php', '--profile', 'w4-os-server')
Invoke-PhpChecked -PhpArgs @('scripts/generate_system_overlay.php', '--profile', 'w4-os-server')
Invoke-PhpChecked -PhpArgs @('scripts/generate_live_bundle.php', '--profile', 'w4-os-server')
Invoke-PhpChecked -PhpArgs @('scripts/generate_iso_bundle.php', '--profile', 'w4-os-server')
```

Estos scripts generan **bundles/recetas**, no prueban que haya una ISO construida. Leer los manifiestos y README generados; ejecutar sus scripts de materialización en Linux con los argumentos que declaren. No se inventan aquí flags para esos runners: fijarlos y probarlos contra el commit de bootstrap. Publicar después un único runner común de CI que ejecute la cadena y registre los comandos efectivos.

### Windows y Linux

Windows es el workspace de edición. Para chroot, APT, permisos Unix, enlaces simbólicos y filesystem root usar una VM Debian o WSL2 con filesystem Linux. No construir el rootfs directamente bajo NTFS/`/mnt/c`.

Con un commit de trabajo ya creado, se puede transferir fuente a Linux con un bundle Git, sin copiar artefactos ignorados ni secretos locales:

```powershell
# Ejecutar después de guardar y revisar los cambios en un commit.
$TransferPath = 'C:\W4\Packages\W4-OS SERVER-source.bundle'
if (Test-Path -LiteralPath $TransferPath) { throw 'El bundle de transferencia ya existe.' }
git -C 'C:\W4\Packages\W4-OS SERVER' bundle create $TransferPath HEAD
if ($LASTEXITCODE -ne 0) { throw 'No se pudo crear el bundle.' }
```

En Linux, reemplazar sólo la ruta de entrada si se usa VM en lugar de WSL:

```bash
test ! -e "$HOME/w4-server-build" || exit 1
git clone '/mnt/c/W4/Packages/W4-OS SERVER-source.bundle' "$HOME/w4-server-build"
cd "$HOME/w4-server-build"
git rev-parse HEAD
```

El bundle contiene el commit y su historia necesaria, incluidos artefactos históricamente versionados. No contiene cambios sin commit. Verificar SHA contra el registro de build. No habilitar montaje de discos físicos para estas pruebas. La VM de instalación debe tener un disco virtual desechable y firmware UEFI; WSL no sustituye esa prueba de arranque.

## 14. Instalador headless

Reutilizar InstallerToolkit y los entrypoints existentes para generar plan, executor, bundle, runtime y transferencia. Añadir selección Server al mismo motor y políticas, sin copiar el instalador Business.

Interfaz mínima: CLI/TUI por consola local y consola serial de VM. «Headless» significa sin escritorio; no implica instalación sin preguntas ni desbloqueo de disco automático.

### Entradas requeridas

| Entrada | Validación |
|---|---|
| Perfil | Exactamente `w4-os-server` y hash de política conocido. |
| Disco | Identidad estable, serial, tamaño, writable y distinto del medio live/disco host. |
| Destrucción de datos | Mostrar plan y exigir confirmación que identifique el disco antes de particionar. |
| Firmware/CPU | UEFI amd64; abortar si no se cumple el alcance. |
| Cifrado | LUKS2 por defecto; modo sin cifrar sólo elección explícita para laboratorio. |
| Identidad | Hostname válido y usuario administrador sin autologin. |
| SSH | Clave pública válida, política de acceso y CIDR de administración. |
| Red | DHCP o estática, interfaz/MAC seleccionada, DNS, gateway si procede. |
| Hora | Zona horaria explícita y sincronización para TLS/APT. |
| Recovery | Confirmación de custodia del material de recuperación. |

Antes de ejecutar: validar JSON como objeto, esquema, campos y referencias; producir un plan sin efectos destructivos; comprobar memoria/espacio, paquetes y payloads. Revalidar identidad de disco inmediatamente antes de escribir. Una reejecución tras fallo requiere reconciliación: nunca reparticionar automáticamente porque falte un archivo de estado.

### Orden de ejecución

1. Verificar firmas/checksums y precondiciones.
2. Confirmar disco y plan.
3. Crear GPT/ESP/boot/LUKS/Btrfs y subvolúmenes.
4. Copiar rootfs conservando permisos, enlaces, APT y dpkg.
5. Aplicar overlay Server y configuración de instalación.
6. Escribir `fstab`/`crypttab` con UUID; instalar kernel/initramfs/GRUB.
7. Crear administrador, llaves SSH, red, firewall y servicios.
8. Eliminar cuentas/autologin/secretos live del target y preparar identidad única.
9. Verificar target antes del reboot y guardar reporte sin secretos.
10. Reiniciar desde disco, desbloquear LUKS y validar acceso remoto real.

Reprobar los arreglos que menciona el README: `var/lib/dpkg` y APT válidos cuando se usa rootfs directo, existencia real de kernel/initrd, home del administrador y permisos críticos. No convertir el fallback de kernel de laboratorio en una versión hardcodeada de release.

No registrar passphrases en argumentos, archivos versionados, historial ni logs; leer por canal protegido o consola. Las respuestas desatendidas deben separar referencias a secretos de datos públicos y usar almacenamiento temporal con permisos restringidos.

## 15. Boot y systemd

Estado esperado tanto de ISO headless como de target:

- Default target: `multi-user.target`.
- Consola local utilizable; consola serial configurable para VM/BMC.
- Sin display manager, compositor ni sesión de escritorio.
- NetworkManager, SSH, AppArmor, firewall y sincronización de hora activos según política.
- Runtime W4 ejecutado por unidades acotadas o CLI explícita, sin servidor HTTP obligatorio.

En el target de laboratorio, con acceso local disponible:

```bash
sudo systemctl set-default multi-user.target
sudo systemctl enable NetworkManager.service ssh.service
systemctl get-default
systemctl is-enabled ssh.service
systemctl is-active NetworkManager.service
systemctl --failed
```

El paquete de defaults/instalador debe materializar esa configuración en el target offline usando la raíz correcta; no habilitar accidentalmente servicios del host de build. No ejecutar `systemctl isolate` sobre una sesión remota durante bootstrap.

El test headless inspecciona paquete instalado, unidad y proceso. Cambiar target dejando GNOME/SDDM instalados no cumple. No declarar fallo sólo porque exista el archivo genérico `graphical.target` de systemd o una biblioteca X11 requerida por otra dependencia: comprobar servidores gráficos, sesiones y aplicaciones prohibidas.

Secure Boot es un criterio separado: tener `shim-signed` en la lista no demuestra que toda la ISO y cadena kernel/GRUB funcionen con Secure Boot. PoC prueba UEFI; V1 documenta y valida expresamente Secure Boot o lo declara fuera de soporte de esa entrega.

## 16. Networking

**Decisión V1 inicial: NetworkManager sin applet.** Evitar que networkd, ifupdown y NetworkManager administren simultáneamente la misma interfaz.

- PoC: una NIC Ethernet seleccionada, DHCP o estática.
- V1: IPv4 e IPv6 coherentes con firewall, DNS y hora; pruebas de reinicio y renovación DHCP.
- Posterior: VLAN, bonds, bridges y múltiples rutas, según roles.
- No copiar UUID de conexiones, MAC o nombres de interfaz de la VM Business.

Comprobación sin modificar la red:

```bash
nmcli general status
nmcli device status
nmcli connection show
ip -br address
ip route
cat /etc/resolv.conf
```

El instalador escribe un perfil persistente para la NIC elegida y valida DNS con el backend efectivamente instalado; no asumir `systemd-resolved`. Prohibir activaciones automáticas de perfiles heredados para NICs desconocidas si pueden exponer SSH.

Cambios remotos de IP/ruta/firewall requieren consola de recuperación o rollback temporizado probado. Primero comprobar una segunda sesión, después confirmar persistencia. No ejecutar ejemplos estáticos con una interfaz/IP ficticia.

## 17. SSH y administración remota

En Server, `openssh-server` es dependencia permanente y `ssh.service` queda habilitado. No retirar SSH después de pruebas como hace el procedimiento temporal Business.

Default propuesto para `/etc/ssh/sshd_config.d/00-w4-server.conf`:

```text
PermitRootLogin no
PubkeyAuthentication yes
PasswordAuthentication no
KbdInteractiveAuthentication no
AuthenticationMethods publickey
UsePAM yes
X11Forwarding no
AllowAgentForwarding no
AllowTcpForwarding no
PermitTunnel no
```

La política de forwarding puede relajarse explícitamente para bastiones; no hacerlo de forma implícita al instalar roles. El orden de includes importa: verificar configuración efectiva, no confiar sólo en el nombre del archivo. [Opciones oficiales OpenSSH en Debian](https://manpages.debian.org/trixie/openssh-server/sshd_config.5.en.html).

Provisionar clave pública del administrador antes de deshabilitar contraseñas. Mantener una política de autenticación local/sudo operable; no dejar cuentas con llave pero sin forma aprobada de elevar privilegios o usar consola. No distribuir contraseña por defecto ni `NOPASSWD: ALL` global.

```bash
sudo sshd -t
sudo sshd -T | grep -E 'permitrootlogin|passwordauthentication|kbdinteractiveauthentication|authenticationmethods|x11forwarding'
```

Si hay bloques `Match`, verificar también `sshd -T -C` con usuario/dirección reales. Conservar sesión existente mientras se abre una segunda conexión y se valida sudo. Hacer reload sólo después de pasar sintaxis.

Generar claves host distintas en cada instalación y mostrar su fingerprint por consola para verificarlo en el primer acceso. No aceptar automáticamente cualquier host key en automatización. Home del administrador con propietario correcto, `.ssh` 0700 y `authorized_keys` 0600. No incorporar claves host a rootfs público.

## 18. Storage, LUKS2 y Btrfs

Layout inicial propuesto para disco virtual de laboratorio de al menos 40 GiB; dimensionar producción según workloads:

```text
GPT
├── ESP: 512 MiB, FAT32, /boot/efi
├── boot: 2 GiB, ext4, /boot
└── resto: LUKS2
     └── Btrfs
          ├── @                 -> /
          ├── @home             -> /home
          ├── @log              -> /var/log
          ├── @w4-state         -> /var/lib/w4/operations
          ├── @srv              -> /srv
          └── @snapshots        -> /.snapshots
```

Es una política objetivo que debe adaptarse al contrato del instalador actual. `/boot` y ESP están sin cifrar: esta arquitectura protege confidencialidad de root/datos, no garantiza integridad de todo el boot sin medidas adicionales.

**No separar todo `/var` ni `/var/lib` del snapshot de sistema.** El estado de dpkg/APT debe retroceder junto con `/usr` y `/etc`. Excluir específicamente logs, estado durable W4 y datos de servicios. Verificar que la ruta real del operation store coincida con `/var/lib/w4/operations` antes de adoptar el montaje propuesto.

Los subvolúmenes de datos no se incluyen automáticamente en snapshots del padre. Definir backup por dataset; un snapshot local no es una copia externa y un snapshot root no garantiza consistencia de bases de datos. [Documentación Btrfs](https://btrfs.readthedocs.io/en/latest/Subvolumes.html).

- Desbloqueo inicial: consola local/serial/BMC. SSH normal empieza después de montar root.
- Un servidor cifrado que reinicia no vuelve solo a SSH sin mecanismo adicional de desbloqueo.
- TPM2 o red/initramfs: fase posterior, con modelo de amenaza, fallback y revocación; nunca passphrase embebida en imagen.
- Guardar backup del header LUKS y recuperación fuera del disco; protegerlos como material sensible.
- PoC sin swap persistente. Si V1 añade swap/hibernación, diseñar cifrado y compatibilidad Btrfs específicamente.
- Registrar UUID de LUKS, Btrfs, ESP y boot; no asumir `/dev/sda` estable.
- Probar pérdida de espacio y metadatos Btrfs, retención de snapshots y alertas antes de producción.

La raíz seleccionada por GRUB, `rootflags=subvol=...`, fstab y mecanismo de rollback deben coincidir. Cambiar únicamente el default subvolume puede no modificar el arranque si existen rutas explícitas.

## 19. Baseline de seguridad

| Control | Implementación | Evidencia |
|---|---|---|
| Sin GUI | Perfil y cierre APT headless | Manifest de paquetes y procesos. |
| AppArmor | Instalado, activo y perfiles relevantes enforce | `aa-status`; casos de denegación esperados. |
| Firewall | UFW, deny incoming, allow outgoing inicial | Reglas efectivas IPv4/IPv6 y test desde otra VM. |
| SSH | Claves, root bloqueado, sin password remoto | Config efectiva y pruebas positivas/negativas. |
| Permisos | `/`, `/etc`, home, sudoers, claves | Reporte del toolkit común. |
| Repositorios | Firmas obligatorias, keyring controlado | APT rechaza firma desconocida/modificada. |
| Cifrado | LUKS2 por defecto | Header/UUID y reinicio probado, sin exponer secretos. |
| Runtime W4 | Privilegios mínimos y logs sin secretos | Unidad/permisos y pruebas de operaciones. |

No asegurar «AppArmor protege todo W4» sólo porque hay perfiles en enforce: un runtime nuevo requiere un perfil propio compatible con operaciones reales. El instalador necesita privilegios distintos de un health checker; no compartir indiscriminadamente su unidad o permisos.

Firewall: construir reglas desde CIDR de administración real antes de habilitarlo. En laboratorio, ejecutar desde consola:

```bash
# Sustituir por la red real de administración validada.
ADMIN_CIDR='192.0.2.0/24'
test "$ADMIN_CIDR" != '192.0.2.0/24' || { echo 'Configurar ADMIN_CIDR real'; exit 1; }
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow from "$ADMIN_CIDR" to any port 22 proto tcp
sudo ufw enable
sudo ufw status verbose
```

El rango de documentación provoca parada deliberada si no se sustituye. Repetir para CIDR IPv6 autorizado cuando corresponda. Comprobar SSH desde red autorizada y bloqueo desde otra, sin excluir ICMPv6 esencial. UFW permite reglas por origen y advierte sobre impacto de habilitarlo en acceso remoto. [ufw(8)](https://manpages.debian.org/trixie/ufw/ufw.8.en.html).

No gestionar UFW y reglas nftables independientes simultáneamente sin contrato de ownership. Los roles Container/Virtualization deberán probar forwarding/NAT y publicación de puertos: instalar Docker u otro motor puede alterar el firewall efectivo.

## 20. Actualización, snapshots y recuperación

Reutilizar UpdateToolkit, planes, executor, reconciliación y almacén durable. Las políticas Server añaden ventana de mantenimiento, prohibición de reboot automático y checks de acceso remoto. No crear un updater Server separado.

Flujo objetivo, cuya nomenclatura exacta debe mapearse a los estados del toolkit:

```text
plan -> validar repositorio/espacio -> capturar recuperación -> aplicar
     -> reiniciar si procede -> pending_health -> confirmed
                                      |
                                      +-> fallo registrado -> recovery controlado
```

El README declara `apply_mode=live-apt`: el nombre `run-update-offline.sh` no demuestra que exista actualización transaccional fuera del sistema activo. PoC debe describir honestamente el modo implementado. Una actualización verdaderamente offline, con target de systemd o slot alternativo, requiere diseño y pruebas adicionales.

### Precondiciones

- Bloqueo exclusivo W4 y coordinación con locks APT/dpkg.
- No correr unattended-upgrades en paralelo al coordinador sin integración; mantener una política explícita para actualizaciones de seguridad.
- Hora correcta, fuente firmada, espacio de root y boot suficiente.
- Identificador único de operación; reanudación idempotente, sin reutilizar snapshot ajeno por nombre.
- Preflight del solver: no retirar SSH, kernel operativo, runtime ni metapaquete Server.
- Datos de workload respaldados/consistentes cuando un rol los requiera.

### Qué debe capturar la recuperación

Snapshot root con `/etc`, `/usr` y dpkg/APT coherentes; copia/versionado de kernel, initrd, GRUB y ESP necesarios; manifiesto de montajes/UUID y relación entre snapshot y boot. `/boot` ext4 no queda protegido por el snapshot Btrfs de root.

Logs y operation store permanecen fuera del rollback del sistema para conservar la evidencia del fallo. Datos en `/srv` siguen su propio backup; no retrocederlos sin coordinación de aplicaciones.

### Health checks Server

Arranque correcto y edición; mountpoints y cifrado; estado dpkg; NetworkManager y dirección válida; DNS según entorno; SSH local y conexión desde verificador externo; UFW y AppArmor; runtime W4; repositorio y versión esperados. La ausencia de Internet no debe causar rollback de un servidor cuya política admite operación aislada.

### Runbook de recuperación

1. Acceder por consola/BMC o ISO de rescate conocida y verificar qué sistema/disco se está recuperando.
2. Desbloquear LUKS con material autorizado; montar inicialmente sólo lectura y exportar evidencia.
3. Seleccionar el checkpoint por operation ID y verificar integridad/compatibilidad.
4. Restaurar una raíz escribible desde snapshot conservando evidencia y datos excluidos.
5. Restaurar pareja kernel/initrd/boot compatible y actualizar referencias de subvolumen/GRUB.
6. Reiniciar, desbloquear y ejecutar checks locales y externos.
7. Registrar estado recovered/failed según contrato; no falsificar `confirmed` para ocultar una recuperación.

No publicar un comando genérico `btrfs subvolume delete` como receta universal. El executor compartido debe resolver las rutas exactas, impedir eliminar raíz activa y exigir confirmación para restauraciones que destruyan datos. PoC exige recuperación manual demostrada; V1 añade automatización sólo después de probar fallos de energía y boot.

## 21. Repositorio W4 y firma

Refactorizar el publisher actual para aceptar el perfil Server y materializar su catálogo. Estado `C-092`: el CLI ya admite `--package-set server` y conserva `both` como compatibilidad Home+Business; queda pendiente reconstruir/publicar el repositorio firmado Server y probar la instalación real desde ese catálogo.

Contrato de publicación:

```text
pool/main/w4/                 paquetes .deb inmutables
dists/<canal>/main/binary-amd64/Packages[.gz]
dists/<canal>/Release
dists/<canal>/InRelease
dists/<canal>/Release.gpg
keyrings/                    claves públicas distribuidas
publication-manifest.json    checksums, versiones, origen y evidencia
```

Separar versiones y canales W4 de suites Debian. Si se comparte un pool, asegurar que índices y dependencias promueven la versión Base compatible con Server. No sobrescribir un `.deb` publicado con mismo nombre/versión y distinto contenido.

Plantilla de source W4, **no utilizable hasta sustituir URI y publicar el canal**:

```text
Types: deb
URIs: https://REEMPLAZAR-POR-REPOSITORIO-W4/
Suites: testing
Components: main
Architectures: amd64
Signed-By: /usr/share/keyrings/w4-update-archive-keyring.gpg
```

La clave inicial se distribuye dentro del medio verificado o por un canal autenticado independiente. No confiar en una clave descargada del mismo endpoint no verificado sólo porque viene junto al repositorio.

Producción exige firma, expiración/rotación controlada, validación de metadatos y publicación atómica de índices. Probar clave desconocida, firma alterada, release vencido y replay conforme a la política. Evitar `trusted=yes`, `--allow-unauthenticated` o desactivar checks para «hacer pasar» pruebas.

La firma APT de metadatos, la firma de checksums de ISO y Secure Boot resuelven problemas distintos; verificar los tres si se prometen. Mantener claves privadas fuera de Git/rootfs/CI no confiable; usar un job de firma separado y acceso restringido. Un nombre como `W4-Update-Prod` en laboratorio no acredita custodia productiva.

Probar rotación con superposición: publicar nuevo keyring firmado por la confianza vigente, desplegarlo, cambiar firmante y retirar la clave anterior conforme a la política de recuperación.

## 22. Logging y observabilidad

PoC: journald persistente con límites, logs W4 estructurados, operation ID y exportación por CLI. Registrar versión de producto/Base, perfil, estado de instalación/update, timestamps UTC y resultado de checks. No registrar tokens, claves privadas, passphrases o respuestas sensibles de instalación.

Política inicial propuesta: reservar un presupuesto de logs acorde al disco y alertar por espacio; medirlo en VM antes de fijar valores V1. Evitar que logs ilimitados llenen Btrfs y bloqueen snapshots/actualizaciones.

Checks iniciales: disco libre, metadatos Btrfs, fallo de servicios, sincronización de hora, última actualización, último backup verificado y reinicio pendiente. La observación no necesita abrir un puerto HTTP.

Posterior: syslog remoto con transporte seguro, métricas opt-in y exporters restringidos a red de administración. No instalar obligatoriamente una plataforma de monitorización, recolector externo o telemetría en PoC.

## 23. Pruebas y evidencia

| Nivel | Casos mínimos | Criterio |
|---|---|---|
| Resolver | Perfil Server, IDs inválidos, dependencias y remociones | Sin desktop-meta; no fallback Home; errores claros. |
| Regresión | Home/Business antes/después | Mismo conjunto funcional esperado, desktop conservado. |
| Paquetes | Instalar meta desde APT limpio | Runtime/recovery reales, dependencias resueltas sin GUI. |
| Build | Rootfs, overlay, live, ISO | Perfil y codename propagados; checksums y manifiestos. |
| Boot | UEFI amd64, consola y serial | Sin display manager; medio usable. |
| Instalación | Disco virtual vacío, readonly, tamaño insuficiente | Éxito correcto o parada previa a escritura. |
| Cifrado | Passphrase válida/errónea, reboot | LUKS2 real y recuperación de acceso por consola. |
| Red | DHCP/estática, DNS, IPv4/IPv6 | Persistencia y acceso sólo según política. |
| SSH | Clave válida/inválida, root/password | Sólo acceso autorizado; claves host únicas. |
| Seguridad | UFW, AppArmor, permisos | Tests positivos y negativos desde otra máquina. |
| Update | Firmado válido, firma errónea, falta de espacio | Confirmación o fallo durable, sin degradación insegura. |
| Recovery | Paquete roto, kernel nuevo fallido, reinicio interrumpido | Sistema recuperado con boot y dpkg coherentes. |
| Datos | Datos en `/srv` antes de rollback | Preservados según política de datasets. |
| Hardware V1 | Hardware representativo de la matriz de soporte | NIC, firmware, almacenamiento y reboot validados. |

Comprobaciones runtime de laboratorio:

```bash
test "$(systemctl get-default)" = multi-user.target
test -d /sys/firmware/efi
test "$(dpkg --print-architecture)" = amd64
dpkg --audit
findmnt /
findmnt /boot
findmnt /boot/efi
lsblk -f
sudo aa-status
sudo ufw status verbose
sudo sshd -t
systemctl is-active ssh.service NetworkManager.service
systemctl --failed
sudo ss -lntup
```

Estas comprobaciones son auxiliares, no sustituyen assertions de CI. `dpkg --audit` debe tener salida vacía, `systemctl --failed` debe evaluarse y las conexiones remotas se prueban desde otra VM.

La denylist headless debe cubrir familias de paquetes desktop, display managers, audio de sesión, portales y aplicaciones; mantenerla versionada con excepciones justificadas para bibliotecas. Examinar el manifiesto completo de paquetes (`dpkg-query`), no sólo las listas JSON de entrada.

Cada corrida produce: commit fuente, hashes de perfiles/políticas, lock de paquetes, ISO checksum, modo firmware, hardware virtual, plan sanitizado, resultados de seguridad, logs de boot, operación update, snapshot-manifest y evidencia de recovery. No subir claves ni NVRAM que contenga secretos.

## 24. CI/CD

Implementar sobre los comandos y runners comunes verificados en el checkout. Este documento define jobs y gates; no presenta un workflow YAML ficticio como pipeline ya operativo.

| Job | Ejecuta | Acceso |
|---|---|---|
| `validate` | Composer, lint PHP, PHPUnit, manifests, política Server | Sin secretos. |
| `compose` | Matriz Home/Business/Server y comparación de dependencias | Sin claves productivas. |
| `package` | Paquetes y repo efímero firmado con clave de test | Entorno desechable. |
| `build-server` | Rootfs/live/ISO y reporte headless | Runner Linux aislado. |
| `vm-install` | UEFI/OVMF, disco virtual, instalación y reboot | VM anidada o runner dedicado protegido. |
| `update-recovery` | Update, fallos controlados y restauración | Sólo laboratorio. |
| `release-candidate` | SBOM, hashes, procedencia y validación | Artefactos inmutables. |
| `sign-promote` | Firma y promoción del artefacto probado | Entorno protegido y claves restringidas. |

Las PR no confiables no ejecutan shell privilegiado en runners persistentes ni acceden a secretos. Fijar acciones/imágenes por revisión/digest y limitar permisos. Limpiar runners mediante reprovisionamiento, no scripts que puedan borrar discos del host.

No reconstruir un binario distinto después de aprobar el candidato; promover los mismos hashes. Cache por arquitectura, codename y lock; invalidar al cambiar política de paquetes. Retener evidencia de releases y paquetes necesarios para rollback conforme al periodo de soporte.

## 25. Roadmap y criterios de aceptación

### P0 — separación segura de plataforma

- [ ] Checkout registrado con SHA y origen; Business no modificado en su workspace.
- [ ] Base no exige desktop; Home y Business lo exigen explícitamente.
- [ ] Perfil Server resuelve sin Business y sin meta-paquetes desktop.
- [ ] Los casos binarios overlay/publisher están inventariados y corregidos.
- [ ] Versiones de runtime y contratos identificadas.

Salida: composición Server válida, aún sin promesa de ISO operativa.

### P1 — primera PoC instalable

- [x] Debian trixie real en rootfs y sources; ningún uso accidental de testing upstream.
- [ ] Paquetes W4 requeridos existen y se instalan desde repo de laboratorio firmado.
- [ ] ISO amd64 arranca en UEFI sin GUI.
- [ ] Instalador por consola instala en disco virtual con GPT/ESP/boot/LUKS2/Btrfs.
- [ ] Reinicia desde disco sin ISO, pide desbloqueo y llega a consola/SSH.
- [ ] SSH permanente por clave; NetworkManager, UFW y AppArmor verificados.
- [ ] Runtime W4 funciona sin el checkout de desarrollo.
- [ ] Una actualización firmada llega a `confirmed` con checks Server.
- [ ] Una actualización fallida queda registrada y se recupera manualmente.
- [ ] Home/Business conservan validación de composición y pruebas comunes.

PoC no exige Agent, workloads, GUI, desbloqueo remoto ni recovery automático.

### P2 — estabilización V1

- [ ] Paquetes/configuraciones tienen ownership, upgrades y conffiles probados.
- [ ] Se prueban fallos de kernel/boot, interrupción de update, espacio insuficiente y firma inválida.
- [ ] Backup y restauración de datos/headers LUKS ensayados.
- [ ] Matriz de VM y hardware soportado definida y ejecutada.
- [ ] Política IPv6, hora, logs, mantenimiento y reboots documentada.
- [ ] Canal productivo, rotación de claves y respuesta a incidentes probados.
- [ ] CI genera evidencia repetible y promueve artefactos inmutables.
- [ ] SBOM/licencias, soporte, limitaciones conocidas y proceso de seguridad publicados.
- [ ] Estado de Secure Boot explícito; no se infiere de paquetes instalados.
- [ ] Owner de Base y owner de Server aceptan contrato de compatibilidad.

No fijar una fecha de V1 antes de medir estos gates. Ser instalable no equivale a ser mantenible en producción.

### P3 — roles y plataforma empresarial

| Rol opcional | Trabajo adicional necesario |
|---|---|
| Web | Servidor HTTP elegido, TLS, puertos, logs, backups y checks HTTP. |
| Database | Motor/versiones, volúmenes, consistencia, backup/restore y migraciones. |
| Virtualization | KVM/libvirt, IOMMU si aplica, bridges, políticas de acceso y backups VM. |
| Container | Motor seleccionado, storage, cgroups, redes, NAT/firewall y actualizaciones. |
| Storage | Protocolos seleccionados, permisos, cuotas, integridad y recuperación. |
| Enterprise node | Agent, enrolamiento, identidad, auditoría, actualización y revocación. |

Cada rol compone capacidades de Server, no clona su rootfs ni motores. Definir conflictos entre roles y pruebas de coexistencia. Un nuevo `role-profile` requerirá extender el resolver: el esquema actual sólo acepta base y edition-profile.

W4 Agent futuro consume APIs/CLI tipadas del runtime con autorización y mínimos privilegios. Requiere identidad por nodo, enrolamiento explícito, TLS/mTLS según diseño, rotación/revocación, logs auditables y operación desconectada. No exponer ejecución remota arbitraria como sustituto de contratos de administración.

## 26. Migración de Business y disciplina de separación

El bootstrap se cierra cuando Server tiene su perfil, defaults, tests y roadmap y deja de depender de supuestos Business. No importar periódicamente toda la edición Business.

Clasificar cada cambio entrante:

- Corrección de kernel/installer/APT/recovery/runtime: integrar en Base y consumir su versión.
- Política desktop: Home/Business; no aplicar a Server.
- Capacidad empresarial genérica: contrato opcional compartido, sin dependencias gráficas.
- Política Server: mantener en perfil/defaults/tests Server.

Mantener comparación de cambios de Base y matriz de compatibilidad. Si hay fork temporal, registrar cada parche compartido pendiente de integrar y su prueba; no dejar divergencia indefinida en seguridad o repositorios.

Para migrar máquinas Business existentes en el futuro: exportar configuración/datos, instalar Server limpio, restaurar servicios/datos y hacer pruebas de aceptación. Una conversión in-place necesitará un producto de migración específico con plan y rollback; este documento no autoriza ni prescribe purgas masivas sobre sistemas en servicio.

## 27. Riesgos y rollback del desarrollo

| Riesgo | Prevención | Recuperación |
|---|---|---|
| Desktop entra por meta-paquete | Refactor Base y test del cierre APT | Rechazar imagen; corregir receta y reconstruir. |
| Server identificado como Home | Catálogo de edición y tests negativos | Revertir cambio de overlay con commit correctivo. |
| Cambio Stable→nueva Debian | Codename/lock y gate de sources | Reconstruir con entradas registradas. |
| Pérdida de SSH | Clave/CIDR validados, segunda sesión y consola | Restaurar configuración por consola. |
| Reinicio cifrado queda esperando | Política de desbloqueo y ventana | Desbloqueo por consola/BMC. |
| Snapshot no cubre boot/dpkg | Contrato de checkpoint completo | Recovery con root y boot compatibles. |
| Publisher omite Server | Catálogo generalizado, install test | No promover release; publicar versión corregida. |
| Regresión Home/Business | Comparación de composición y tests compartidos | Revertir commit funcional sin borrar historia. |
| Secretos en artefactos | Imagen limpia y pruebas de contenido | Retirar artefacto, rotar secretos y reconstruir. |
| NTFS altera permisos | Construir en filesystem Linux | Regenerar rootfs desde fuente validada. |

Rollback de fuente: conservar commits pequeños y usar `git revert` sobre los cambios identificados tras revisar dependencias. No ejecutar `reset --hard` ni borrar el checkout para «volver atrás». Las modificaciones en máquinas instaladas se recuperan con el procedimiento operativo, no con Git.

Si la clonación falla a mitad, no volver a ejecutar sobre la misma carpeta con opciones de sobrescritura. Inspeccionar `.git`, estado y error; conservar datos y elegir una nueva ruta sólo tras decisión consciente. Este documento no incluye borrado automático de destinos.

## 28. ADRs iniciales

| ADR | Decisión | Motivo y consecuencia |
|---|---|---|
| SERVER-001 | Server hereda Base, no Business | Bootstrap compartido sin dependencia permanente del desktop empresarial. |
| SERVER-002 | Monorepo lógico durante bootstrap | Compatible con rutas actuales; extracción posterior exige API de composición externa. |
| SERVER-003 | Headless como contrato verificable | Ausencia de GUI tanto en composición como en runtime. |
| SERVER-004 | Debian Stable fijada a trixie para V1 | Control de cambios mayores y actualización de seguridad planificada. |
| SERVER-005 | NetworkManager y UFW iniciales | Reutilizar Base y reducir divergencia; alternativas quedan para ADR posterior. |
| SERVER-006 | UEFI amd64, LUKS2/Btrfs | Aprovechar arquitectura validada por el proyecto; desbloqueo por consola explícito. |
| SERVER-007 | Motores comunes por perfil | Evitar familias de scripts duplicados y fallbacks binarios. |
| SERVER-008 | Runtime mínimo empaquetado | Operar sin Composer/dev tools del build host. |
| SERVER-009 | Recovery de sistema distinto de backup de datos | Evitar falsa seguridad de snapshots parciales. |
| SERVER-010 | Firma y promoción separadas | Mantener integridad y custodia de claves. |
| SERVER-011 | Agent y roles opcionales | Reducir alcance PoC y superficie de servicio por defecto. |
| SERVER-012 | SSH permanente con acceso explícito | Server administrable de forma remota desde su primera instalación. |

Cada ADR debe registrar contexto, alternativas, consecuencias, responsable, fecha y estado. Este documento propone las decisiones; el commit que las adopte formaliza su aceptación en el proyecto.

## 29. Checklist de ejecución en orden

1. [ ] Ejecutar la clonación segura y guardar SHA de bootstrap.
2. [ ] Auditar cambios del repositorio respecto a esta inspección y revisar reglas locales.
3. [ ] Capturar resolución Home/Business antes del refactor.
4. [ ] Retirar desktop-meta y os-prober de Base, preservándolos en Home/Business.
5. [ ] Crear el JSON Server de la sección 9 y su política separada.
6. [ ] Implementar loader/consumidores de política y branding sin fallback Home.
7. [ ] Generalizar catálogo del repositorio y crear paquetes funcionales/metapaquete.
8. [ ] Fijar trixie hasta el bootstrap y sources efectivos.
9. [ ] Añadir defaults de systemd/SSH, red, firewall y limpieza de identidad live.
10. [x] Adaptar instalador al perfil Server y validar en modo plan.
11. [x] Ejecutar validación, generar recetas y materializar `rootfs`, live e ISO en Linux/WSL.
12. [x] Inspeccionar paquetes/servicios de rootfs y live/ISO: no desktop.
13. [x] Automatizar gate de checksum y manifiesto headless para la ISO Server.
14. [x] Ejecutar preflight VM y validar arranque live Server en VirtualBox EFI.
15. [x] Instalar en VM UEFI con disco virtual desechable y probar reboot LUKS/SSH.
16. [x] Probar seguridad runtime Server desde consola/SSH y registrar gaps.
17. [ ] Ejecutar update firmado, fallo controlado y recovery completo.
18. [ ] Ejecutar regresión Home/Business y guardar evidencias sanitizadas.
19. [ ] Cerrar PoC con limitaciones explícitas; continuar gates de V1.
20. [ ] Integrar correcciones comunes en Base y mantener roadmap Server propio.

**Definición final del producto:** W4 OS Server es una composición headless independiente sobre W4 Linux Base. Business aporta el punto de partida técnico y la experiencia de validación; Base conserva los motores comunes; Server es dueño de su política operativa, paquetes específicos, aceptación y evolución.
