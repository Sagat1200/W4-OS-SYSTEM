# Contraseñas operativas de W4 OS

Documento de referencia rápida para las credenciales usadas durante la validación real de `W4 OS Home` y `W4 OS Business` en VirtualBox.

## W4 OS Home

- Usuario local validado: `w4`
- Password generada original del usuario local: `p6ObL_aJugo4Nl1MDoEl4Pe9`
- Password simple temporal validada para login: `W4login1234`
- Passphrase LUKS original: `9y4YUCtZJ_e_RQ5H5qZHrMNBTJ3dmujm`
- Passphrase LUKS simple temporal validada: `W4boot1234`

### Reinstalacion preparada el 2026-09-25

- Password temporal regenerada del usuario local para el siguiente payload Home: `Sg9hl2QOL0dD3HjZTqM6oEir`
- Passphrase LUKS temporal regenerada para el siguiente payload Home: `aMkiJb5EKX8W1LrqZOTCJYPvxQAmgks`

### Reparacion y payload corregido el 2026-09-26

- Passphrase LUKS limpia agregada offline a `W4-OS-Home-GUI`: `W4boot2026`
- Password temporal regenerada del usuario local para el payload Home corregido LF-only y ajustado al VDI `W4-OS-Home-GUI-fixed`: `xlt7vdhKEKCPkpL9VfKzlO6`
- Passphrase LUKS temporal regenerada para el payload Home corregido sin terminador final y ajustado al VDI `W4-OS-Home-GUI-fixed`: `M39DeuNq9EhcUjzu4qYUOtHnmJZRv6Wf`
- Nota: la password `Sg9hl2QOL0dD3HjZTqM6oEir` del payload anterior quedo afectada por terminador `CRLF`; no debe usarse como credencial limpia de login interactivo.

## W4 OS Business

- Usuario local validado: `w4admin`
- Password generada original del usuario local: `CEzH3e2CwwPZ7DjavO0xgpQZ`
- Password simple temporal validada para login: `W4login1234`
- Passphrase LUKS original: `H538cR8kzOJRXZri8gKtUjEWpcM5XQ6S`
- Passphrase LUKS simple temporal validada: `94628153`

## Nota operativa

- Las credenciales simples temporales se usaron como workaround por diferencias de layout de teclado en `initramfs` y `tty1`.
- Si más adelante se rotan las credenciales o se automatiza otro mecanismo de acceso, este documento debe actualizarse en el mismo ciclo.
