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
