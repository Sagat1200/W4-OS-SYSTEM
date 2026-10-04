W4 OS Installation Executor

Perfil: W4 OS Server
Disco objetivo: /dev/sda
Target por defecto: multi-user.target
Hostname prefix: w4-server
Modo por defecto: verificacion sin escritura

Archivos generados:
- apply-installation.sh
- verify-installation.sh
- installation-executor.json

Variables requeridas para ejecutar de verdad:
- W4_INSTALL_EXECUTE=1
- W4_INSTALL_SOURCE_ROOTFS=/ruta/rootfs   o   W4_INSTALL_SOURCE_SQUASHFS=/ruta/filesystem.squashfs
- W4_DISK_PASSPHRASE_FILE=/ruta/passphrase.txt
- W4_LOCAL_USER_PASSWORD_FILE=/ruta/password.txt

El script revalida el disco antes de escribir, rechaza particiones o firmas existentes y esta pensado para disco vacio en una sesion live.
