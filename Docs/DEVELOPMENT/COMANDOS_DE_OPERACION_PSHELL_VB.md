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
cd ~/w4-transfer/runtime
bash run-check-only.sh
bash run-installation.sh
```

### Business por NAT con port forwarding 2223

```powershell
php .\scripts\prepare_installation_transfer.php --profile w4-os-business
ssh -p 2223 w4live@127.0.0.1
scp -P 2223 -r "c:\W4\Packages\W4-OS SYSTEM\build\install-transfer\w4-os-business" "w4live@127.0.0.1:/home/w4live/w4-transfer"
```

Dentro de la VM:

```bash
cd ~/w4-transfer/runtime
bash run-check-only.sh
bash run-installation.sh
```
