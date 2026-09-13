
# Comandos de Terminal Linux Debian

## Comandos de gestion de paquetes

| Comando | Función | Ejemplo |
| --- | --- | --- |
| `apt` | Gestión de paquetes | `apt update` |
| `apt install` | Instala paquetes | `apt install w4` |
| `apt remove` | Elimina paquetes | `apt remove w4` |
| `apt purge` | Elimina paquetes y su configuración | `apt purge w4` |

## Comandos de gestion de usuarios

| Comando | Función | Ejemplo |
| --- | --- | --- |
| `useradd` | Crea usuarios usuario | `useradd w4` |
| `userdel` | Elimina usuarios | `userdel w4` |
| `passwd` | Cambia contraseña de usuarios | `passwd w4` |
| `usermod` | Modifica configuración de usuarios | `usermod -s /bin/bash w4` |
| `usermod -a` | Agrega usuario a un grupo | `usermod -aG sudo w4` |
| `usermod -G` | Modifica grupos de usuarios | `usermod -G sudo w4` |
| `usermod -u` | Modifica UID de usuarios | `usermod -u 1000 w4` |
| `usermod -g` | Modifica GID de usuarios | `usermod -g 1000 w4` |
| `usermod -s` | Modifica shell de usuarios | `usermod -s /bin/bash w4` |

## Comandos de gestion de contraseñas

| Comando | Función | Ejemplo |
| --- | --- | --- |
| `passwd` | Modifica contraseña de usuarios | `passwd w4` |
| `chage` | Modifica fecha de expiración de contraseñas | `chage -E 2025-01-01 w4` |
| `chage -I` | Modifica fecha de inactividad de contraseñas | `chage -I 2025-01-01 w4` |

## Comandos de Administrador

| Comando | Función | Ejemplo |
| --- | --- | --- |
| `su` | Cambia de usuario a root | `su` |
| `sudo` | Ejecuta comandos con privilegios de root | `sudo reboot` |
| `sudo -i` | Cambia de usuario a root con privilegios de sesión | `sudo -i` |
| `sudo -u` | Cambia de usuario a root con privilegios de sesión | `sudo -u w4` |

## Comandos de gestion de sistema operativo

| Comando | Función | Ejemplo |
| --- | --- | --- |
| `reboot` | Reinicia el sistema | `reboot` |
| `shutdown` | Apaga el sistema | `shutdown` |
| `shutdown -h now` | Apaga el sistema inmediatamente | `shutdown -h now` |
| `shutdown -r now` | Reinicia el sistema inmediatamente | `shutdown -r now` |

## Comandos de gestion de archivos y directorios

| Comando | Función | Ejemplo |
| --- | --- | --- |
| `ls` | Lista archivos y directorios | `ls` |
| `cd` | Cambia de directorio | `cd /home/w4` |
| `mkdir` | Crea directorios | `mkdir /home/w4/Downloads` |
| `rm` | Elimina archivos y directorios | `rm /home/w4/Downloads` |
| `mv` | Mueve archivos y directorios | `mv /home/w4/Downloads /home/w4/Downloads/Backup` |
| `cp` | Copia archivos y directorios | `cp /home/w4/Downloads /home/w4/Downloads/Backup` |

## Comandos de gestion de red y redireccionamiento

| Comando | Función | Ejemplo |
| --- | --- | --- |
| `ifconfig` | Muestra configuración de red | `ifconfig` |
| `route` | Muestra tablas de enrutamiento | `route` |
| `ping` | Prueba conectividad a un host | `ping google.com` |
| `traceroute` | Muestra ruta de paquetes a un host | `traceroute google.com` |
| `netstat` | Muestra estadísticas de red | `netstat -an` |
| `iptables` | Configura firewall | `iptables -L` |
| `iptables -A` | Agrega regla de firewall | `iptables -A INPUT -s 192.168.1.100 -j DROP` |
| `iptables -D` | Elimina regla de firewall | `iptables -D INPUT 1` |
