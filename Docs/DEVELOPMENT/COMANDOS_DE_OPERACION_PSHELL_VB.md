# Comandos de operación de PSHELL en VB

## Maquinas Virtuales

| Nombre Maquina | --- |
| --- | --- |
| W4-OS-Home-Test | VirtualBox |
| W4-OS-Business-Test | VirtualBox |

## PowerShell

| Comando | Función | Ejemplo |
| --- | --- | --- |
| & "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" list vms | Listar máquinas virtuales | |
| & "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" list runningvms | Listar maquinas virtuales encendidas | |
| & "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" startvm "NombreDeTuVM" | Iniciar una máquina virtual (con interfaz gráfica) | |
| & "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" startvm "NombreDeTuVM" --type headless | Iniciar una máquina virtual en segundo plano (modo headless / sin ventana) | |
| & "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" controlvm "NombreDeTuVM" acpipowerbutton | Apagar una máquina virtual de forma segura (envía señal de apagado ACPI) | |
| & "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" controlvm "NombreDeTuVM" poweroff | Apagar una máquina virtual de golpe (forzar apagado) | |

## Procedimiento de operación para conección de terminal PowerShell con maquina virtual VB

1- Iniciar la maquina virtual VB
2- Dentro de debian ejecuta el comando whoami para mostrar tu usuario actual.
3- Confirmar si el usuario tiene contraseña ejecutando el comando passwd
5- Si tiene contraseña, ejecuta el comando sudo passwd tu_usuario para cambiar la contraseña.
6- Ejecuta el comando sudo apt update para actualizar el repositorio de paquetes.
7- Ejecuta el comando sudo apt install -y openssh-server para instalar el servidor SSH
8- Levanta el servidor ssh ejecutando el comando sudo systemctl enable --now ssh
9- Ejecuta el comando ip addr para obtener la dirección IP de la maquina virtual VB.
10- En la terminal PowerShell ejecuta el comando ssh -p 2222 w4live@127.0.0.1 para conectarte a la maquina virtual VB.
11- Ingresa la contraseña para poder conectarte desde power shell a la maquina virtual.
12- Si llega a fallar la conexion con la maquina virtual en Power Shell ejecuta en Power Shell ssh-keygen -R "[127.0.0.1]:2222" para eliminar la clave del host en la terminal PowerShell.

## Flujo de instalacion W4 OS en VirtualBox

### Home por NAT con port forwarding 2222

```powershell
php .\scripts\prepare_installation_transfer.php --profile w4-os-home
ssh -p 2222 w4live@127.0.0.1
scp -P 2222 -r "c:\W4\Packages\W4-OS SYSTEM\build\install-transfer\w4-os-home" "w4live@127.0.0.1:/home/w4live/w4-transfer"
```

Dentro de la VM:

```bash
sudo apt update
sudo apt install -y openssh-server gdisk parted util-linux dosfstools e2fsprogs cryptsetup btrfs-progs rsync squashfs-tools
cd ~/w4-transfer/runtime
bash run-check-only.sh
sudo bash run-installation.sh
sudo cryptsetup open /dev/sda3 cryptroot --key-file ./disk-passphrase.txt
sudo mkdir -p /mnt/w4-install-target/boot/efi
sudo mount -o subvol=@ /dev/mapper/cryptroot /mnt/w4-install-target
sudo mount /dev/sda2 /mnt/w4-install-target/boot
sudo mount /dev/sda1 /mnt/w4-install-target/boot/efi
sudo bash ../verify-installation.sh
sudo poweroff
```

En PowerShell tras la verificacion:

```powershell
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" storageattach "W4-OS-Home-Test" --storagectl "IDE" --port 0 --device 0 --type dvddrive --medium none
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" modifyvm "W4-OS-Home-Test" --boot1 disk --boot2 dvd --boot3 none --boot4 none
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" startvm "W4-OS-Home-Test"
```

Notas operativas validadas en Home:

- El primer arranque instalado ya fue validado en `W4-OS-Home-Test` con `LUKS2 + Btrfs`, login local y mounts finales correctos.
- El runtime regenerado ahora produce secretos efimeros ASCII-safe por defecto y el instalador persiste el layout de teclado tambien en consola e `initramfs`; conviene revalidar el siguiente primer boot con payload regenerado antes de volver a asumir el workaround manual.
- Si la consola de `initramfs` o `tty1` interpreta mal caracteres del teclado, anadir temporalmente una passphrase LUKS y/o password de login simples en ASCII desde la live antes de reintentar el boot.

### Business por NAT con port forwarding 2223

```powershell
php .\scripts\prepare_installation_transfer.php --profile w4-os-business
ssh -p 2223 w4live@127.0.0.1
scp -P 2223 -r "c:\W4\Packages\W4-OS SYSTEM\build\install-transfer\w4-os-business" "w4live@127.0.0.1:/home/w4live/w4-transfer"
```

Dentro de la VM:

```bash
sudo apt update
sudo apt install -y openssh-server gdisk parted util-linux dosfstools e2fsprogs cryptsetup btrfs-progs rsync squashfs-tools
cd ~/w4-transfer/runtime
bash run-check-only.sh
sudo bash run-installation.sh
sudo bash ../verify-installation.sh
sudo poweroff
```

En PowerShell tras la verificacion:

```powershell
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" storageattach "W4-OS-Business-Test" --storagectl "IDE" --port 0 --device 0 --type dvddrive --medium none
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" modifyvm "W4-OS-Business-Test" --boot1 disk --boot2 dvd --boot3 none --boot4 none
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" startvm "W4-OS-Business-Test"
```

Notas operativas validadas en Business:

- `W4-OS-Business-Test` ya quedo validado de punta a punta: instalacion destructiva, `verify-installation.sh`, primer boot cifrado, login local de `w4admin` y layout final correcto.
- El payload regenerado de instalacion ya exporta un `PATH` con rutas `sbin`, evitando falsos negativos de `sgdisk` y `partprobe` en sesiones live minimales.
- El runtime regenerado ahora produce secretos efimeros ASCII-safe por defecto y el instalador persiste el layout de teclado tambien en consola e `initramfs`; la siguiente revalidacion debe hacerse con payload regenerado para medir si el workaround manual sigue siendo necesario.
- Si el layout de teclado en `initramfs` o `tty1` impide escribir correctamente las credenciales complejas, se puede repetir el workaround validado: agregar temporalmente una passphrase LUKS ASCII simple y/o cambiar temporalmente la password de login desde la live antes de reintentar el arranque desde disco.

## Flujo recomendado para `MX-004` con repo APT tipo `dists`

### Home por NAT con port forwarding 2222

```powershell
scp -P 2222 -r "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T120000Z" "w4@127.0.0.1:/home/w4/w4-update-repo"
scp -P 2222 -r "c:\W4\Packages\W4-OS SYSTEM\build\update\executors\w4-update-smoke-003" "w4@127.0.0.1:/home/w4/w4-update-executor"
ssh -p 2222 w4@127.0.0.1
```

Dentro de la VM:

```bash
cd ~/w4-update-executor
chmod +x run-update-offline.sh run-update-with-repo-env.sh run-health-checks.sh reconcile-after-reboot.sh
sudo W4_UPDATE_EXECUTE=1 ./run-update-with-repo-env.sh --repo-dir /home/w4/w4-update-repo
sudo reboot
```

Tras el reinicio:

```bash
cd ~/w4-update-executor
./run-health-checks.sh
sudo ./reconcile-after-reboot.sh
```

### Business por NAT con port forwarding 2223

```powershell
scp -P 2223 -r "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T120000Z" "w4admin@127.0.0.1:/home/w4admin/w4-update-repo"
scp -P 2223 -r "c:\W4\Packages\W4-OS SYSTEM\build\update\executors\w4-update-business-smoke-001" "w4admin@127.0.0.1:/home/w4admin/w4-update-executor"
ssh -p 2223 w4admin@127.0.0.1
```

Dentro de la VM:

```bash
cd ~/w4-update-executor
chmod +x run-update-offline.sh run-update-with-repo-env.sh run-health-checks.sh reconcile-after-reboot.sh
sudo W4_UPDATE_EXECUTE=1 ./run-update-with-repo-env.sh --repo-dir /home/w4admin/w4-update-repo
sudo reboot
```

Tras el reinicio:

```bash
cd ~/w4-update-executor
./run-health-checks.sh
sudo ./reconcile-after-reboot.sh
```

Notas:

- El wrapper `run-update-with-repo-env.sh` deriva la source APT desde la ruta real del repo copiado a la VM y evita reutilizar las rutas locales del host presentes en `repo.env`.
- Si se necesita otro canal, puede sobreescribirse con `W4_UPDATE_REPOSITORY_CHANNEL=<canal>` antes de lanzar el wrapper.

## Automatizacion desde el host con Paramiko

### Corrida completa de Home hasta reboot + desbloqueo LUKS

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_validation_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2222 `
  --username w4 `
  --password W4login1234 `
  --repo-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T120000Z" `
  --executor-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\executors\w4-update-smoke-003" `
  --plan-path "c:\W4\Packages\W4-OS SYSTEM\build\update\plans\w4-update-smoke-003.update-plan.json" `
  --remote-root /home/w4/w4-update-dists-home `
  --vm-name "W4-OS-Home-Test" `
  --luks-passphrase W4boot1234 `
  --evidence-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\validation\w4-update-smoke-003-dists-home"
```

### Corrida completa de Business hasta reboot + desbloqueo LUKS

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_validation_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2223 `
  --username w4admin `
  --password W4login1234 `
  --repo-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T120000Z" `
  --executor-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\executors\w4-update-business-smoke-001" `
  --plan-path "c:\W4\Packages\W4-OS SYSTEM\build\update\plans\w4-update-business-smoke-001.update-plan.json" `
  --remote-root /home/w4admin/w4-update-dists-business-php `
  --vm-name "W4-OS-Business-Test" `
  --luks-passphrase 94628153 `
  --evidence-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\validation\w4-update-business-smoke-001-dists-business-php"
```

### Cierre de una operacion ya en `pending_health`

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\complete_pending_health_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2222 `
  --username w4 `
  --password W4login1234 `
  --remote-root /home/w4/w4-update-dists-home-php `
  --evidence-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\validation\w4-update-smoke-003-dists-home-php"
```

Notas operativas del laboratorio:

- `run_update_validation_via_paramiko.php` normaliza a `LF` los `.sh` copiados a la VM, prepara el store durable remoto y copia un `ENGINE_ROOT` minimo (`scripts/` + `src/`) para que el coordinador PHP funcione fuera del arbol local.
- En Business, la passphrase LUKS simple temporal validada para el laboratorio actual es `94628153`; no reutilizar la de Home.
- El wrapper `run-update-with-repo-env.sh` ya exporta `W4_UPDATE_APT_CHECK_DATE=0` por defecto para tolerar desfases horarios del `Release` en el repo `file:` de laboratorio.
- Si el reboot llega al prompt LUKS antes de que el helper principal consiga retomar SSH, se puede desbloquear con `VBoxManage controlvm ... keyboardputstring ...` y luego rematar con `complete_pending_health_via_paramiko.php`.

## Preparar un repo APT firmado

La firma sigue siendo opcional en laboratorio, pero el bundle ya la soporta. El flujo recomendado es firmar el layout `dists` y luego usar `W4_UPDATE_APT_SOURCE_LINE_SIGNED`.

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_repository_bundle_in_wsl.php" `
  --bundle "c:\W4\Packages\W4-OS SYSTEM\build\update\repositories\w4-main-2026-09-20T180000Z" `
  --output-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T180000Z-signed-auto" `
  --distribution Ubuntu `
  --signing-mode gpg `
  --gpg-key-id W4-Update-Lab `
  --generate-lab-key
```

Si se quiere inspeccionar primero el comando WSL sin ejecutarlo:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_repository_bundle_in_wsl.php" `
  --bundle "c:\W4\Packages\W4-OS SYSTEM\build\update\repositories\w4-main-2026-09-20T180000Z" `
  --output-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T180000Z-signed-auto" `
  --distribution Ubuntu `
  --signing-mode gpg `
  --gpg-key-id W4-Update-Lab `
  --generate-lab-key `
  --check-only
```

El runner soporta tambien una clave persistente ya provisionada:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_repository_bundle_in_wsl.php" `
  --bundle "c:\W4\Packages\W4-OS SYSTEM\build\update\repositories\w4-main-2026-09-20T180000Z" `
  --distribution Ubuntu `
  --signing-profile prod
```

La ruta oficial recomendada para publicar el repo firmado ya puede ejecutarse con un wrapper dedicado que fija `prod` y valida la salida:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\publish_update_repository.php" `
  --bundle "c:\W4\Packages\W4-OS SYSTEM\build\update\repositories\w4-main-2026-09-20T180000Z" `
  --distribution Ubuntu
```

Si se quiere inspeccionar primero el comando sin reconstruir el repo:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\publish_update_repository.php" `
  --bundle "c:\W4\Packages\W4-OS SYSTEM\build\update\repositories\w4-main-2026-09-20T180000Z" `
  --distribution Ubuntu `
  --check-only
```

Con `--signing-profile prod`, el runner ya fija por convenio:

- `signing-mode=gpg`
- `gpg-key-id=W4-Update-Prod`
- `gpg-homedir=/var/tmp/w4-os-system/signing-w4`
- `output-dir` por defecto terminado en `-signed-prod` cuando no se indica uno manualmente

La variante expandida sigue siendo valida si se quiere sobreescribir algun valor concreto:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_repository_bundle_in_wsl.php" `
  --bundle "c:\W4\Packages\W4-OS SYSTEM\build\update\repositories\w4-main-2026-09-20T180000Z" `
  --output-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T180000Z-signed-prod" `
  --distribution Ubuntu `
  --signing-profile prod `
  --gpg-homedir /var/tmp/w4-os-system/signing-w4
```

Si el material persistente vive en archivos exportados desde Windows y todavia no existe un `homedir` GPG preparado dentro de WSL, el runner ya puede importarlo automaticamente:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_repository_bundle_in_wsl.php" `
  --bundle "c:\W4\Packages\W4-OS SYSTEM\build\update\repositories\w4-main-2026-09-20T180000Z" `
  --output-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T180000Z-signed-prod" `
  --distribution Ubuntu `
  --signing-mode gpg `
  --gpg-key-id W4-Update-Prod `
  --gpg-secret-key-file "c:\W4\Secrets\w4-update-prod-private.asc" `
  --gpg-ownertrust-file "c:\W4\Secrets\w4-update-prod-ownertrust.txt"
```

Notas:

- Si tambien se indica `--gpg-homedir`, la importacion se realiza sobre ese homedir persistente.
- Si no se indica `--gpg-homedir`, el runner crea automaticamente un homedir reproducible en WSL bajo `/var/tmp/w4-os-system/update-repositories/...-signing-imported`.
- `--generate-lab-key` sigue reservado al laboratorio efimero y no debe mezclarse con archivos persistentes.
- `--signing-profile prod` es ya la ruta operativa recomendada para la publicacion oficial; `--generate-lab-key` queda como camino de laboratorio.
- `publish_update_repository.php` deja ademas `publication-manifest.json` en la salida firmada, verificando `repo.env`, `InRelease`, `Release.gpg` y `keyrings/w4-update-archive-keyring.gpg` antes de dar la publicacion por buena.

Si el directorio firmado contiene `keyrings/w4-update-archive-keyring.gpg`, el wrapper `run-update-with-repo-env.sh` ya puede derivar automaticamente una source APT estilo:

```text
deb [signed-by=/ruta/al/repositorio/keyrings/w4-update-archive-keyring.gpg] file:/ruta/al/repositorio testing main
```

Estado actual validado:

- El homedir persistente de firma ya quedo materializado en WSL como `/var/tmp/w4-os-system/signing-w4` con la identidad `W4-Update-Prod`.
- La salida firmada vigente para esa identidad quedo reconstruida en `build/update/repository-output/w4-main-2026-09-20T180000Z-signed-prod/`.
- Tanto Home como Business ya fueron revalidadas contra `signed-prod` hasta `stage=confirmed`.

## Validacion firmada de `MX-004` en Home

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_validation_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2222 `
  --username w4 `
  --password W4login1234 `
  --repo-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T180000Z-signed-prod" `
  --executor-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\executors\w4-update-smoke-003" `
  --plan-path "c:\W4\Packages\W4-OS SYSTEM\build\update\plans\w4-update-smoke-003.update-plan.json" `
  --remote-root /home/w4/w4-update-signed-prod-home `
  --vm-name "W4-OS-Home-Test" `
  --luks-passphrase W4boot1234 `
  --evidence-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\validation\w4-update-smoke-003-signed-prod-home"
```

Notas operativas del flujo firmado validado:

- `run_update_validation_via_paramiko.php` ya sincroniza la hora UTC remota antes de la fase APT cuando detecta desfase grande, evitando rechazos `Not live until ...` de `sqv` sobre `InRelease`.
- El helper ya copia el repo a una ruta publica temporal en `/var/tmp/...`, de modo que `_apt` y `sqv` puedan leer `keyrings/w4-update-archive-keyring.gpg` aunque el `home` remoto del usuario SSH tenga permisos `700`.
- El helper reforzado ya intenta cubrir tambien el primer arranque desde VM apagada usando la misma ventana automatica de reinyeccion LUKS antes de que exista SSH. Si aun asi la VM queda detenida en el prompt, el desbloqueo manual desde `VBoxManage controlvm ... keyboardputstring ...` sigue siendo el fallback valido.
- Si la VM tarda mas en mostrar el prompt LUKS, se puede ampliar la espera antes de inyectar la passphrase con `--unlock-wait <segundos>`. El valor por defecto del helper ahora es `20`.
- El helper ahora tambien soporta `--unlock-retry-interval <segundos>`, `--unlock-retries <cantidad>` y `--unlock-window <segundos>` para mantener una ventana automatica de reinyeccion de la passphrase LUKS si el primer intento se adelanta al prompt real. Los nuevos defaults quedan en `10`, `12` y `180`, respectivamente.
- En las revalidaciones recientes con `signed-prod`, Home y Business confirmaron ese mismo timing del prompt LUKS; aun con el helper reforzado, el remate manual con `VBoxManage controlvm ... keyboardputstring ...` sigue siendo un fallback valido si hiciera falta.
- La repeticion mas reciente desde VMs completamente apagadas ya cerro en `stage=confirmed` tanto para Home como para Business usando solo el helper reforzado; el remate manual queda relegado a fallback y ya no forma parte del camino normal validado.
- Si el desbloqueo LUKS ocurre mas tarde de lo esperado y el helper no consigue retomar SSH por si solo, se puede reenviar la passphrase desde VirtualBox y luego rematar con:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\complete_pending_health_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2222 `
  --username w4 `
  --password W4login1234 `
  --remote-root /home/w4/w4-update-signed-prod-home `
  --evidence-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\validation\w4-update-smoke-003-signed-prod-home" `
  --connect-wait 600
```

## Validacion firmada de `MX-004` en Business

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_validation_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2223 `
  --username w4admin `
  --password W4login1234 `
  --repo-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T180000Z-signed-prod" `
  --executor-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\executors\w4-update-business-smoke-001" `
  --plan-path "c:\W4\Packages\W4-OS SYSTEM\build\update\plans\w4-update-business-smoke-001.update-plan.json" `
  --remote-root /home/w4admin/w4-update-signed-prod-business `
  --vm-name "W4-OS-Business-Test" `
  --luks-passphrase 94628153 `
  --evidence-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\validation\w4-update-business-smoke-001-signed-prod-business"
```

Si una corrida anterior ya dejo el snapshot `pre-update-w4-update-business-smoke-001`, conviene limpiarlo antes de repetir la validacion:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_remote_command_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2223 `
  --username w4admin `
  --password W4login1234 `
  --command "sudo -S -p '' btrfs subvolume delete /.snapshots/pre-update-w4-update-business-smoke-001" `
  --sudo-password W4login1234 `
  --pty
```

## Validacion firmada de `MX-004` en Business

Antes de repetir el flujo firmado en `W4-OS-Business-Test`, dejar la VM arrancando desde disco y sin la live ISO acoplada:

```powershell
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" controlvm "W4-OS-Business-Test" acpipowerbutton
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" storageattach "W4-OS-Business-Test" --storagectl "IDE" --port 0 --device 0 --type dvddrive --medium none
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" modifyvm "W4-OS-Business-Test" --boot1 disk --boot2 dvd --boot3 none --boot4 none
& "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" startvm "W4-OS-Business-Test" --type headless
```

Luego ejecutar:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_update_validation_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2223 `
  --username w4admin `
  --password W4login1234 `
  --repo-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\repository-output\w4-main-2026-09-20T180000Z-signed-auto" `
  --executor-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\executors\w4-update-business-smoke-001" `
  --plan-path "c:\W4\Packages\W4-OS SYSTEM\build\update\plans\w4-update-business-smoke-001.update-plan.json" `
  --remote-root /home/w4admin/w4-update-signed-business `
  --vm-name "W4-OS-Business-Test" `
  --luks-passphrase 94628153 `
  --unlock-wait 30 `
  --evidence-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\validation\w4-update-business-smoke-001-signed-business"
```

Notas operativas del flujo firmado validado en Business:

- Si una corrida vuelve a fallar en `prepared` con `Could not create subvolume: File exists`, limpiar antes `/.snapshots/pre-update-w4-update-business-smoke-001` y relanzar la validacion.
- Si aparece `ERROR: Could not create subvolume: File exists`, limpiar solo el snapshot sobrante con:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\run_remote_command_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2223 `
  --username w4admin `
  --password W4login1234 `
  --command "sudo -S -p '' btrfs subvolume delete /.snapshots/pre-update-w4-update-business-smoke-001" `
  --sudo-password W4login1234 `
  --pty
```

- Si el helper principal llega a `pending_health` pero no recupera SSH tras el reboot, se puede reenviar la passphrase LUKS desde VirtualBox y rematar con:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\complete_pending_health_via_paramiko.php" `
  --host 127.0.0.1 `
  --port 2223 `
  --username w4admin `
  --password W4login1234 `
  --remote-root /home/w4admin/w4-update-signed-business `
  --evidence-dir "c:\W4\Packages\W4-OS SYSTEM\build\update\validation\w4-update-business-smoke-001-signed-business" `
  --connect-wait 600
```

## MX-005 · Seguridad baseline

Generar el bundle base de seguridad por perfil en el host:

```powershell
php "c:\W4\Packages\W4-OS SYSTEM\scripts\generate_security_baseline_bundle.php" --profile w4-os-home
php "c:\W4\Packages\W4-OS SYSTEM\scripts\generate_security_baseline_bundle.php" --profile w4-os-business
```

Validar el baseline dentro de una imagen ya instalada:

```bash
cd ~/w4-security-baseline
php ./verify-security-baseline.php
```

Notas operativas del primer ciclo de `MX-005`:

- El bundle queda en `build/security/<perfil>/` con `security-baseline.json`, `verify-security-baseline.php` y `SECURITY_BASELINE_README.txt`.
- En esta primera capa, los gaps esperados y explicitados por el propio baseline son `firewall-control-plane` y `mac-enforcement`; no son un fallo del generador, sino el backlog inmediato del siguiente paquete de hardening.
- El control `authenticated-updates` se reporta como `skipped` en la verificacion local porque su evidencia sigue viniendo del pipeline firmado ya validado en `MX-004`.
