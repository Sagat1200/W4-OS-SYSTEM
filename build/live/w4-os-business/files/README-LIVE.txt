W4 OS Live Bundle

Perfil: W4 OS Business
Edicion: Business
Host live: w4-business-live
Usuario live: w4live

Este bundle compone una estructura live a partir de:
- rootfs ensamblado
- overlay de sistema aplicado
- pila live-boot/live-config instalada en copia temporal

El resultado incluye:
- kernel e initrd listos para arranque live
- filesystem.squashfs
- manifest de paquetes
- grub.cfg base para UEFI/GRUB

Si el host dispone de xorriso y grub-mkstandalone, la siguiente etapa sera empaquetar ISO arrancable.