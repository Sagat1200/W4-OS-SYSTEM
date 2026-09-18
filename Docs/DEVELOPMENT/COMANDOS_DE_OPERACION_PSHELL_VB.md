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
```
