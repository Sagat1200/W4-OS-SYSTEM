# Debug Session: missing-initrd-boot
- **Status**: [OPEN]
- **Issue**: La instalación destructiva de `Home` repuebla GRUB EFI, pero tras reinstalar el kernel dentro del `chroot` sigue faltando `initrd.img-*` en `/boot`, por lo que GRUB queda sin entradas Linux útiles o la verificación del instalador falla.
- **Debug Server**: Pending startup
- **Log File**: .dbg/trae-debug-log-missing-initrd-boot.ndjson

## Reproduction Steps
1. Arrancar la live VM `W4-OS-Home-Test`.
2. Copiar `build/install-transfer/w4-os-home` a `~/w4-transfer`.
3. Ejecutar `bash run-check-only.sh`.
4. Ejecutar `sudo bash run-installation.sh`.
5. Observar la fase `No se encontraron artefactos de kernel en /boot; reinstalando paquetes linux-image`.

## Hypotheses & Verification
| ID | Hypothesis | Likelihood | Effort | Evidence |
|----|------------|------------|--------|----------|
| A | `update-initramfs` dentro del `chroot` queda contaminado por el entorno live montado en `/run` y evita generar `initrd.img-*` | High | Low | Rejected as sole cause |
| B | El kernel se instala, pero el script valida `/boot` demasiado pronto, antes de generar manualmente el `initrd` porque `update-initramfs` se desactiva en contexto live | High | Low | Confirmed |
| C | `/boot` está montado correctamente, pero la generación del `initrd` escribe en otra ruta del target por enlaces o layout incorrecto | Medium | Medium | Rejected |
| D | Falta una dependencia del target para generar initramfs durante la reinstalación del kernel | Low | Medium | Rejected |

## Log Evidence
- Evidencia actual del usuario: la reinstalación del kernel dentro del `chroot` sí instala `linux-image-6.12.107+deb13-amd64`, crea los symlinks `/initrd.img` y `/vmlinuz`, pero el instalador falla inmediatamente después con `la reinstalacion del kernel no genero initrd.img en /boot`.
- La misma reproducción mostró `E: Can not write log (Is /dev/pts mounted?) - posix_openpt (19: No such device)` mientras el `chroot` heredaba `/run` completo desde la live.
- El generador fue ajustado para montar `${TARGET_ROOT}/dev/pts`, dejar `run` local del target y no bindear `/run` desde la live antes de volver a validar.
- Evidencia post-fix: tras esa corrección, la reinstalación del kernel ya no muestra el error de `posix_openpt`, instala `linux-image-6.12.107+deb13-amd64` y crea los symlinks `/vmlinuz` e `/initrd.img`, pero el instalador sigue fallando con `la reinstalacion del kernel no genero initrd.img en /boot`.
- Verificación manual en el target: `/boot` contiene `vmlinuz-6.12.107+deb13-amd64`, `config-*` y `System.map-*`, `update-initramfs` existe, pero responde `update-initramfs is disabled (live system is running without media mounted on /run/live/medium)`, confirmando que la regeneración automática del initrd no ocurrirá en ese contexto.

## Verification Conclusion
Hipótesis B confirmada con evidencia manual: en este flujo live el instalador no puede depender de `update-initramfs` para poblar `/boot`. El fix correcto es generar los `initrd.img-*` faltantes con `mkinitramfs` y dejar `update-initramfs` como actualización no bloqueante.
