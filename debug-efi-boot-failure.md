# Debug Session: efi-boot-failure

- **Status**: [OPEN]
- **Issue**: La instalacion de `W4 OS Home` termina en la VM, pero el primer arranque UEFI desde disco falla en VirtualBox con `BdsDxe: No bootable option or device was found`.
- **Debug Server**: N/A para esta depuracion de instalador/VM
- **Log File**: N/A para esta depuracion de instalador/VM

## Reproduction Steps

1. Arrancar `W4-OS-Home-Test` con la ISO live.
2. Copiar el payload `build/install-transfer/w4-os-home`.
3. Ejecutar `bash run-check-only.sh`.
4. Ejecutar `sudo bash run-installation.sh`.
5. Apagar la VM, retirar la ISO y arrancar desde disco.
6. Observar el fallo UEFI de VirtualBox al intentar bootear el disco instalado.

## Hypotheses & Verification

| ID | Hypothesis | Likelihood | Effort | Evidence |
| ---- | ---------- | ---------- | ------ | -------- |
| A | La ESP no contiene `EFI/BOOT/BOOTX64.EFI` tras la instalacion | High | Low | Pending |
| B | `grub-install` no se ejecuto correctamente dentro del `chroot` o no genero `grubx64.efi` | High | Low | Pending |
| C | El `chroot` no tenia `efivars` montado y la instalacion EFI quedo incompleta | Medium | Medium | Pending |
| D | La particion ESP existe, pero no esta montada o poblada en el target al final de la instalacion | Medium | Low | Pending |
| E | El sistema instalado carece de `boot/grub/grub.cfg` o de los paquetes EFI requeridos | Medium | Low | Pending |

## Log Evidence
- `lsblk -f` confirma que el disco instalado existe y conserva el esquema esperado:
  - `/dev/sda1` -> `vfat` `W4-ESP`
  - `/dev/sda2` -> `ext4` `W4-BOOT`
  - `/dev/sda3` -> `crypto_LUKS`
- `blkid` confirma labels y UUIDs coherentes con la instalacion esperada.
- La live actual no trae `cryptsetup`, por lo que no fue posible abrir `sda3` ni montar el subvolumen raiz Btrfs para inspeccion completa del target.
- El chequeo directo sobre las particiones montadas de `boot` y `efi` no encontro:
  - `boot/grub/grub.cfg`
  - `boot/efi/EFI/BOOT/BOOTX64.EFI`
- El intento anterior de arranque UEFI en VirtualBox ya habia fallado con `BdsDxe: No bootable option or device was found`.

## Verification Conclusion
| ID | Hypothesis | Status | Evidence Summary |
|----|------------|--------|------------------|
| A | La ESP no contiene `EFI/BOOT/BOOTX64.EFI` tras la instalacion | ✅ Confirmed | El test directo reporto `BOOTX64.EFI: MISSING` |
| B | `grub-install` no se ejecuto correctamente dentro del `chroot` o no genero `grubx64.efi` | ✅ Confirmed | No aparece ningun artefacto EFI esperado y el primer boot UEFI falla |
| C | El `chroot` no tenia `efivars` montado y la instalacion EFI quedo incompleta | ⏳ Inconclusive | Es consistente con el sintoma, pero esta corrida fue hecha antes del endurecimiento que ya se aplico al generador |
| D | La particion ESP existe, pero no esta montada o poblada en el target al final de la instalacion | ✅ Confirmed | La ESP existe, pero no contiene el fallback EFI esperado |
| E | El sistema instalado carece de `boot/grub/grub.cfg` o de los paquetes EFI requeridos | ✅ Confirmed | El test directo reporto `grub.cfg: MISSING` |

### Nueva evidencia de reproduccion
- La reinstalacion con el payload endurecido ya no falla por orden de montaje ni por `rsync`.
- La corrida actual alcanza la fase posterior a la sincronizacion y falla exactamente con:
  - `ERROR: grub-install no esta disponible en el sistema destino`
  - luego `verify-installation.sh` confirma `ERROR: falta /etc/fstab`, coherente con un target desmontado por `cleanup` despues del fallo previo.
- Esto confirma que el siguiente fix minimo debe garantizar la disponibilidad de GRUB EFI dentro del `chroot` antes de invocar `grub-install`.

### Evidencia post-fix
- En la live actual se abrio correctamente `cryptroot` usando `runtime/disk-passphrase.txt`.
- Se monto manualmente el target instalado y se ejecuto la reparacion UEFI dentro del `chroot`.
- `apt-get update` dentro del target tuvo fallo DNS hacia `deb.debian.org`, y `efibootmgr` no tuvo candidato disponible; aun asi, ambos comandos de `grub-install` completaron sin error.
- `update-grub` genero configuracion correctamente.
- Verificaciones finales:
  - `grub.cfg: OK`
  - `BOOTX64.EFI: OK`
