#!/usr/bin/env bash
set -euo pipefail

# Algunas live sessions no incluyen rutas sbin en PATH.
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:${PATH:-}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLAN_JSON="${SCRIPT_DIR}/installation-plan.json"
PROFILE_NAME='W4 OS Server'
TARGET_DISK='/dev/sda'
EXPECTED_SERIAL='VBOX_HARDDISK_VBcffb5596-de88949f'
EXPECTED_WWID=''
EXPECTED_BY_PATH='pci-0000:00:0d.0-ata-1.0'
EXPECTED_SIZE_BYTES='34359738368'
SIZE_TOLERANCE_BYTES='1048576'
HOSTNAME_VALUE='w4-server-vm'
LOCALE_VALUE='es_DO.UTF-8'
KEYBOARD_VALUE='latam'
CONSOLE_KEYMAP_VALUE='la-latin1'
USERNAME_VALUE='w4admin'
DISPLAY_NAME_VALUE='W4 Server Administrator'
PASSWORD_SOURCE='secret://install/server-local-password'
PASSPHRASE_SOURCE='secret://install/server-disk-passphrase'
CRYPT_NAME='cryptroot'
ROOT_LABEL='W4-SYSTEM'
ESP_LABEL='W4-ESP'
BOOT_LABEL='W4-BOOT'
ROOT_SUBVOLUME='@'
ESP_SIZE_MIB='512'
BOOT_SIZE_MIB='2048'
TARGET_ROOT="${W4_TARGET_ROOT:-/mnt/w4-install-target}"
STAGING_ROOT="${W4_STAGING_ROOT:-/mnt/w4-install-staging}"
STAGING_MOUNT="${W4_STAGING_MOUNT:-/mnt/w4-install-staging-subvol}"
EXECUTE_MODE="${W4_INSTALL_EXECUTE:-0}"
SOURCE_ROOTFS="${W4_INSTALL_SOURCE_ROOTFS:-}"
SOURCE_SQUASHFS="${W4_INSTALL_SOURCE_SQUASHFS:-}"
DISK_PASSPHRASE_FILE="${W4_DISK_PASSPHRASE_FILE:-}"
LOCAL_USER_PASSWORD_FILE="${W4_LOCAL_USER_PASSWORD_FILE:-}"

log() {
  echo "[w4-install] $*" >&2
}

warn() {
  echo "WARN: $*" >&2
}

fail() {
  echo "ERROR: $*" >&2
  exit 1
}

require_command() {
  if ! command -v "${1}" >/dev/null 2>&1; then
    fail "falta el comando requerido: ${1}"
  fi
}

has_command() {
  command -v "${1}" >/dev/null 2>&1
}

part_path() {
  local disk="${1}"
  local number="${2}"

  if [[ "${disk}" == *"nvme"* || "${disk}" == *"mmcblk"* || "${disk}" == *"loop"* ]]; then
    printf '%sp%s' "${disk}" "${number}"
    return 0
  fi

  printf '%s%s' "${disk}" "${number}"
}

udev_value() {
  local device="${1}"
  local key="${2}"

  udevadm info --query=property --name="${device}" | awk -F= -v key="${key}" '$1 == key { print $2; exit }'
}

assert_selector() {
  local device="${1}"
  local current_serial current_serial_short current_serial_full current_wwid current_by_path current_size size_delta

  current_serial_short="$(udev_value "${device}" "ID_SERIAL_SHORT")"
  current_serial_full="$(udev_value "${device}" "ID_SERIAL")"
  current_serial="${current_serial_short}"
  if [[ -z "${current_serial}" ]]; then
    current_serial="${current_serial_full}"
  fi

  current_wwid="$(udev_value "${device}" "ID_WWN")"
  if [[ -z "${current_wwid}" ]]; then
    current_wwid="$(udev_value "${device}" "ID_WWN_WITH_EXTENSION")"
  fi

  current_by_path="$(udev_value "${device}" "ID_PATH")"
  current_size="$(lsblk -bndo SIZE "${device}" | tr -d '[:space:]')"

  if [[ -n "${EXPECTED_SERIAL}" \
    && "${EXPECTED_SERIAL}" != "${current_serial}" \
    && "${EXPECTED_SERIAL}" != "${current_serial_short}" \
    && "${EXPECTED_SERIAL}" != "${current_serial_full}" ]]; then
    fail "el disco ya no coincide con el serial esperado"
  fi

  if [[ -n "${EXPECTED_WWID}" && "${EXPECTED_WWID}" != "${current_wwid}" ]]; then
    fail "el disco ya no coincide con el WWID esperado"
  fi

  if [[ -n "${EXPECTED_BY_PATH}" && "${EXPECTED_BY_PATH}" != "${current_by_path}" ]]; then
    fail "el disco ya no coincide con el path esperado"
  fi

  if [[ -z "${current_size}" || ! "${current_size}" =~ ^[0-9]+$ ]]; then
    fail "no se pudo determinar el tamaño actual del disco"
  fi

  size_delta=$(( EXPECTED_SIZE_BYTES - current_size ))
  if (( size_delta < 0 )); then
    size_delta=$(( -size_delta ))
  fi

  if (( size_delta > SIZE_TOLERANCE_BYTES )); then
    fail "el disco ya no coincide con el tamaño esperado"
  fi
}

disk_has_signatures() {
  local device="${1}"
  local fstype

  if has_command wipefs; then
    if wipefs -n "${device}" 2>/dev/null | tail -n +2 | grep -q .; then
      return 0
    fi

    return 1
  fi

  if has_command blkid; then
    if blkid "${device}" >/dev/null 2>&1; then
      return 0
    fi

    return 1
  fi

  fstype="$(lsblk -dn -o FSTYPE "${device}" 2>/dev/null | tr -d '[:space:]')"
  if [[ -n "${fstype}" ]]; then
    return 0
  fi

  return 1
}

assert_empty_target() {
  local device="${1}"
  local ro

  ro="$(lsblk -dn -o RO "${device}")"
  if [[ "${ro}" == "1" ]]; then
    fail "el disco seleccionado es de solo lectura"
  fi

  if lsblk -nr -o NAME "${device}" | tail -n +2 | grep -q .; then
    fail "el disco seleccionado ya contiene particiones"
  fi

  if disk_has_signatures "${device}"; then
    fail "el disco seleccionado contiene firmas de filesystem"
  fi

  if [[ "${EXECUTE_MODE}" != "1" ]] && ! has_command wipefs && ! has_command blkid; then
    warn "sin wipefs ni blkid; la validacion check-only se apoyo en lsblk/FSTYPE y ausencia de particiones"
  fi
}

cleanup() {
  set +e

  for path in \
    "${TARGET_ROOT}/boot/efi" \
    "${TARGET_ROOT}/boot" \
    "${TARGET_ROOT}/home" \
    "${TARGET_ROOT}/var/log" \
    "${TARGET_ROOT}/var/cache" \
    "${TARGET_ROOT}/var/lib/w4" \
    "${TARGET_ROOT}/sys/firmware/efi/efivars" \
    "${TARGET_ROOT}/sys" \
    "${TARGET_ROOT}/proc" \
    "${TARGET_ROOT}/dev/pts" \
    "${TARGET_ROOT}/dev"; do
    if mountpoint -q "${path}"; then
      umount "${path}"
    fi
  done

  if mountpoint -q "${TARGET_ROOT}"; then
    umount "${TARGET_ROOT}"
  fi

  if mountpoint -q "${STAGING_MOUNT}"; then
    umount "${STAGING_MOUNT}"
  fi

  if [[ -e "/dev/mapper/${CRYPT_NAME}" ]]; then
    cryptsetup close "${CRYPT_NAME}" >/dev/null 2>&1 || true
  fi
}

prepare_source_root() {
  if [[ -n "${SOURCE_ROOTFS}" ]]; then
    [[ -d "${SOURCE_ROOTFS}" ]] || fail "W4_INSTALL_SOURCE_ROOTFS no apunta a una carpeta valida"
    if [[ ! -f "${SOURCE_ROOTFS}/var/lib/dpkg/status" ]]; then
      warn "W4_INSTALL_SOURCE_ROOTFS no incluye var/lib/dpkg; se intentara restaurar el estado de paquetes desde W4_INSTALL_SOURCE_SQUASHFS"
    fi
    printf '%s' "${SOURCE_ROOTFS}"
    return 0
  fi

  [[ -n "${SOURCE_SQUASHFS}" ]] || fail "debe indicar W4_INSTALL_SOURCE_ROOTFS o W4_INSTALL_SOURCE_SQUASHFS"
  [[ -f "${SOURCE_SQUASHFS}" ]] || fail "W4_INSTALL_SOURCE_SQUASHFS no existe"

  require_command unsquashfs
  rm -rf "${STAGING_ROOT}"
  mkdir -p "${STAGING_ROOT}"
  log "Extrayendo squashfs fuente"
  unsquashfs -f -d "${STAGING_ROOT}" "${SOURCE_SQUASHFS}" >/dev/null
  printf '%s' "${STAGING_ROOT}"
}

source_has_package_state() {
  local source_root="${1}"
  [[ -f "${source_root}/var/lib/dpkg/status" ]]
}

restore_target_package_state_from_source_root() {
  local source_root="${1}"

  log "Repoblando estado de paquetes desde el arbol fuente"
  mkdir -p "${TARGET_ROOT}/var/lib"

  if [[ -d "${source_root}/var/lib/apt" ]]; then
    rsync -aHAX --numeric-ids "${source_root}/var/lib/apt/" "${TARGET_ROOT}/var/lib/apt/"
  fi

  rsync -aHAX --numeric-ids "${source_root}/var/lib/dpkg/" "${TARGET_ROOT}/var/lib/dpkg/"
}

restore_target_package_state_from_squashfs() {
  [[ -n "${SOURCE_SQUASHFS}" ]] || fail "la fuente seleccionada no contiene var/lib/dpkg y no se indico W4_INSTALL_SOURCE_SQUASHFS para restaurarlo"
  [[ -f "${SOURCE_SQUASHFS}" ]] || fail "W4_INSTALL_SOURCE_SQUASHFS no existe"

  require_command unsquashfs
  log "Restaurando estado de paquetes desde squashfs fuente"
  mkdir -p "${TARGET_ROOT}/var/lib"
  unsquashfs -f -d "${TARGET_ROOT}" "${SOURCE_SQUASHFS}" var/lib/apt var/lib/dpkg >/dev/null
}

ensure_target_package_state() {
  if [[ -f "${TARGET_ROOT}/var/lib/dpkg/status" ]]; then
    return 0
  fi

  if source_has_package_state "${SOURCE_ROOT}"; then
    restore_target_package_state_from_source_root "${SOURCE_ROOT}"
  else
    restore_target_package_state_from_squashfs
  fi

  [[ -f "${TARGET_ROOT}/var/lib/dpkg/status" ]] || fail "la instalacion no dejo un estado dpkg utilizable en ${TARGET_ROOT}/var/lib/dpkg"
}

normalize_target_security_permissions() {
  log "Normalizando permisos base del sistema destino"

  chown root:root "${TARGET_ROOT}" "${TARGET_ROOT}/etc" "${TARGET_ROOT}/usr" 2>/dev/null || true
  chmod 0755 "${TARGET_ROOT}" "${TARGET_ROOT}/etc" "${TARGET_ROOT}/usr" 2>/dev/null || true

  if [[ -d "${TARGET_ROOT}/etc/default" ]]; then
    chown root:root "${TARGET_ROOT}/etc/default" 2>/dev/null || true
    chmod 0755 "${TARGET_ROOT}/etc/default" 2>/dev/null || true
  fi

  if [[ -f "${TARGET_ROOT}/etc/default/ufw" ]]; then
    chown root:root "${TARGET_ROOT}/etc/default/ufw" 2>/dev/null || true
    chmod 0644 "${TARGET_ROOT}/etc/default/ufw" 2>/dev/null || true
  fi

  if [[ -d "${TARGET_ROOT}/etc/ufw" ]]; then
    chown root:root "${TARGET_ROOT}/etc/ufw" 2>/dev/null || true
    chmod 0755 "${TARGET_ROOT}/etc/ufw" 2>/dev/null || true
  fi

  if [[ -f "${TARGET_ROOT}/etc/ufw/ufw.conf" ]]; then
    chown root:root "${TARGET_ROOT}/etc/ufw/ufw.conf" 2>/dev/null || true
    chmod 0644 "${TARGET_ROOT}/etc/ufw/ufw.conf" 2>/dev/null || true
  fi

  if [[ -d "${TARGET_ROOT}/tmp" ]]; then
    chown root:root "${TARGET_ROOT}/tmp" 2>/dev/null || true
    chmod 1777 "${TARGET_ROOT}/tmp" 2>/dev/null || true
  fi

  if [[ -d "${TARGET_ROOT}/var/tmp" ]]; then
    chown root:root "${TARGET_ROOT}/var/tmp" 2>/dev/null || true
    chmod 1777 "${TARGET_ROOT}/var/tmp" 2>/dev/null || true
  fi
}

mount_chroot_support() {
  mkdir -p "${TARGET_ROOT}/dev/pts" "${TARGET_ROOT}/run" "${TARGET_ROOT}/run/lock"
  mount --bind /dev "${TARGET_ROOT}/dev"
  mount --bind /dev/pts "${TARGET_ROOT}/dev/pts"
  mount --bind /proc "${TARGET_ROOT}/proc"
  mount --bind /sys "${TARGET_ROOT}/sys"

  if [[ -d /sys/firmware/efi/efivars ]]; then
    mkdir -p "${TARGET_ROOT}/sys/firmware/efi/efivars"
    mount --bind /sys/firmware/efi/efivars "${TARGET_ROOT}/sys/firmware/efi/efivars"
  fi
}

chroot_has_command() {
  local command_name="${1}"
  chroot "${TARGET_ROOT}" /bin/bash -lc "command -v '${command_name}' >/dev/null 2>&1"
}

ensure_kernel_boot_artifacts() {
  local preferred_kernel_package=""
  local kernel_package_names=()
  local kernel_versions=()
  local kernel_version=""

  if compgen -G "${TARGET_ROOT}/boot/vmlinuz-*" >/dev/null 2>&1 \
    && compgen -G "${TARGET_ROOT}/boot/initrd.img-*" >/dev/null 2>&1; then
    return 0
  fi

  log "No se encontraron artefactos de kernel en /boot; reinstalando paquetes linux-image"
  chroot_has_command apt-get || fail "faltan artefactos de kernel en /boot y apt-get no existe en el sistema destino"
  chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get update || true
  log "Asegurando soporte initramfs para cryptroot"
  chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get install -y cryptsetup-initramfs

  if chroot "${TARGET_ROOT}" /bin/bash -lc "dpkg-query -W -f='\${db:Status-Abbrev} \${Package}\n' linux-image-amd64 2>/dev/null | grep '^ii ' >/dev/null 2>&1"; then
    preferred_kernel_package="linux-image-amd64"
  fi

  mapfile -t kernel_package_names < <(
    chroot "${TARGET_ROOT}" /bin/bash -lc "dpkg-query -W -f='\${db:Status-Abbrev} \${Package}\n' 'linux-image-[0-9]*' 2>/dev/null | awk '\$1 == \"ii\" { print \$2 }' | grep -v -- '-unsigned$' || true"
  )

  if [[ "${#kernel_package_names[@]}" -eq 0 ]]; then
    mapfile -t kernel_package_names < <(
      chroot "${TARGET_ROOT}" /bin/bash -lc "dpkg-query -W -f='\${db:Status-Abbrev} \${Package}\n' 'linux-image-[0-9]*' 2>/dev/null | awk '\$1 == \"ii\" { print \$2 }' || true"
    )
  fi

  if [[ -n "${preferred_kernel_package}" ]]; then
    log "Reinstalando metapaquete ${preferred_kernel_package}"
    chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get install -y --reinstall "${preferred_kernel_package}"
  fi

  if ! compgen -G "${TARGET_ROOT}/boot/vmlinuz-*" >/dev/null 2>&1; then
    if [[ "${#kernel_package_names[@]}" -eq 0 ]]; then
      fail "faltan artefactos de kernel en /boot y no se encontraron paquetes linux-image instalados"
    fi

    log "Reinstalando paquetes kernel especificos: ${kernel_package_names[*]}"
    chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get install -y --reinstall "${kernel_package_names[@]}"
  fi

  if ! compgen -G "${TARGET_ROOT}/boot/vmlinuz-*" >/dev/null 2>&1; then
    fail "la reinstalacion del kernel no genero vmlinuz en /boot"
  fi

  if ! compgen -G "${TARGET_ROOT}/boot/initrd.img-*" >/dev/null 2>&1; then
    chroot_has_command mkinitramfs || fail "la reinstalacion del kernel no genero initrd.img en /boot y mkinitramfs no existe en el sistema destino"
    mapfile -t kernel_versions < <(
      find "${TARGET_ROOT}/boot" -maxdepth 1 -type f -name 'vmlinuz-*' -printf '%f\n' | sed 's/^vmlinuz-//' | sort
    )

    if [[ "${#kernel_versions[@]}" -eq 0 ]]; then
      fail "no se encontraron versiones de kernel en /boot para regenerar initrd"
    fi

    for kernel_version in "${kernel_versions[@]}"; do
      if [[ ! -e "${TARGET_ROOT}/boot/initrd.img-${kernel_version}" ]]; then
        log "Generando initrd manual para ${kernel_version}"
        chroot "${TARGET_ROOT}" mkinitramfs -o "/boot/initrd.img-${kernel_version}" "${kernel_version}"
      fi
    done
  fi

  if ! compgen -G "${TARGET_ROOT}/boot/initrd.img-*" >/dev/null 2>&1; then
    fail "la reinstalacion del kernel no genero initrd.img en /boot"
  fi
}

ensure_directories() {
  mkdir -p \
    "${TARGET_ROOT}" \
    "${TARGET_ROOT}/boot" \
    "${TARGET_ROOT}/boot/efi" \
    "${TARGET_ROOT}/home" \
    "${TARGET_ROOT}/var/log" \
    "${TARGET_ROOT}/var/cache" \
    "${TARGET_ROOT}/var/lib/w4"
}

print_plan() {
  cat <<EOF
W4 OS Executor

Perfil: ${PROFILE_NAME}
Disco destino: ${TARGET_DISK}
Modo por defecto: check-only
Para ejecutar de verdad:
  export W4_INSTALL_EXECUTE=1
  export W4_INSTALL_SOURCE_ROOTFS=/ruta/rootfs   o W4_INSTALL_SOURCE_SQUASHFS=/ruta/filesystem.squashfs
  export W4_DISK_PASSPHRASE_FILE=/ruta/passphrase.txt
  export W4_LOCAL_USER_PASSWORD_FILE=/ruta/password.txt
  bash "${0}"
EOF
}

for cmd in lsblk udevadm awk grep sed tr tail; do
  require_command "${cmd}"
done

[[ -b "${TARGET_DISK}" ]] || fail "el disco objetivo no existe: ${TARGET_DISK}"

assert_selector "${TARGET_DISK}"
assert_empty_target "${TARGET_DISK}"

if [[ "${EXECUTE_MODE}" != "1" ]]; then
  print_plan
  exit 0
fi

for cmd in sgdisk partprobe blkid mkfs.vfat mkfs.ext4 cryptsetup mkfs.btrfs rsync mount umount chroot awk grep sed; do
  require_command "${cmd}"
done

[[ -n "${DISK_PASSPHRASE_FILE}" && -f "${DISK_PASSPHRASE_FILE}" ]] || fail "debe indicar W4_DISK_PASSPHRASE_FILE"
[[ -n "${LOCAL_USER_PASSWORD_FILE}" && -f "${LOCAL_USER_PASSWORD_FILE}" ]] || fail "debe indicar W4_LOCAL_USER_PASSWORD_FILE"

SOURCE_ROOT="$(prepare_source_root)"
trap cleanup EXIT

ESP_PART="$(part_path "${TARGET_DISK}" 1)"
BOOT_PART="$(part_path "${TARGET_DISK}" 2)"
ROOT_PART="$(part_path "${TARGET_DISK}" 3)"

log "Aplicando esquema GPT en ${TARGET_DISK}"
sgdisk --zap-all "${TARGET_DISK}"
if has_command wipefs; then
  wipefs -af "${TARGET_DISK}"
else
  warn "wipefs no esta disponible; se continua con sgdisk y recreacion completa del layout"
fi
sgdisk -og "${TARGET_DISK}"
sgdisk -n 1:1MiB:+"${ESP_SIZE_MIB}"MiB -t 1:ef00 -c 1:"${ESP_LABEL}" "${TARGET_DISK}"
sgdisk -n 2:0:+"${BOOT_SIZE_MIB}"MiB -t 2:8300 -c 2:"${BOOT_LABEL}" "${TARGET_DISK}"
sgdisk -n 3:0:0 -t 3:8309 -c 3:"W4-CRYPTROOT" "${TARGET_DISK}"
partprobe "${TARGET_DISK}"
udevadm settle
sleep 1

log "Formateando particiones"
mkfs.vfat -F 32 -n "${ESP_LABEL}" "${ESP_PART}"
mkfs.ext4 -F -L "${BOOT_LABEL}" "${BOOT_PART}"
cryptsetup luksFormat --batch-mode --type luks2 "${ROOT_PART}" "${DISK_PASSPHRASE_FILE}"
cryptsetup open "${ROOT_PART}" "${CRYPT_NAME}" --key-file "${DISK_PASSPHRASE_FILE}"
mkfs.btrfs -f -L "${ROOT_LABEL}" "/dev/mapper/${CRYPT_NAME}"

log "Creando subvolumenes Btrfs"
mkdir -p "${STAGING_MOUNT}"
mount "/dev/mapper/${CRYPT_NAME}" "${STAGING_MOUNT}"
btrfs subvolume create "${STAGING_MOUNT}/@"
btrfs subvolume create "${STAGING_MOUNT}/@srv"
btrfs subvolume create "${STAGING_MOUNT}/@log"
btrfs subvolume create "${STAGING_MOUNT}/@cache"
btrfs subvolume create "${STAGING_MOUNT}/@data"
umount "${STAGING_MOUNT}"

log "Montando layout destino"
ensure_directories
mount -o compress=zstd,noatime,subvol="${ROOT_SUBVOLUME}" "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}"
mkdir -p "${TARGET_ROOT}/srv"
mount -o compress=zstd,noatime,subvol=@srv "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}/srv"
mkdir -p "${TARGET_ROOT}/var/log"
mount -o compress=zstd,noatime,subvol=@log "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}/var/log"
mkdir -p "${TARGET_ROOT}/var/cache"
mount -o compress=zstd,noatime,subvol=@cache "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}/var/cache"
mkdir -p "${TARGET_ROOT}/var/lib/w4"
mount -o compress=zstd,noatime,subvol=@data "/dev/mapper/${CRYPT_NAME}" "${TARGET_ROOT}/var/lib/w4"
mkdir -p "${TARGET_ROOT}/boot"
mount "${BOOT_PART}" "${TARGET_ROOT}/boot"
mkdir -p "${TARGET_ROOT}/boot/efi"
mount "${ESP_PART}" "${TARGET_ROOT}/boot/efi"

log "Sincronizando sistema fuente"
rsync -aHAX --numeric-ids \
  --exclude=/dev/* \
  --exclude=/proc/* \
  --exclude=/sys/* \
  --exclude=/run/* \
  --exclude=/tmp/* \
  --exclude=/mnt/* \
  --exclude=/media/* \
  --exclude=lost+found \
  "${SOURCE_ROOT}/" "${TARGET_ROOT}/"

normalize_target_security_permissions
ensure_target_package_state

echo "${HOSTNAME_VALUE}" > "${TARGET_ROOT}/etc/hostname"
cat > "${TARGET_ROOT}/etc/hosts" <<EOF
127.0.0.1 localhost
127.0.1.1 ${HOSTNAME_VALUE}
EOF

mkdir -p "${TARGET_ROOT}/etc/default"
cat > "${TARGET_ROOT}/etc/default/keyboard" <<EOF
XKBMODEL="pc105"
XKBLAYOUT="${KEYBOARD_VALUE}"
XKBVARIANT=""
XKBOPTIONS=""
BACKSPACE="guess"
EOF
echo "LANG=${LOCALE_VALUE}" > "${TARGET_ROOT}/etc/default/locale"
cat > "${TARGET_ROOT}/etc/vconsole.conf" <<EOF
KEYMAP=${CONSOLE_KEYMAP_VALUE}
FONT=latarcyrheb-sun16
EOF
mkdir -p "${TARGET_ROOT}/etc/initramfs-tools/conf.d"
cat > "${TARGET_ROOT}/etc/initramfs-tools/conf.d/w4-keyboard" <<EOF
KEYMAP=${CONSOLE_KEYMAP_VALUE}
XKBLAYOUT=${KEYBOARD_VALUE}
EOF

if [[ -e /etc/resolv.conf ]]; then
  cp -L /etc/resolv.conf "${TARGET_ROOT}/etc/resolv.conf"
fi

ESP_UUID="$(blkid -s UUID -o value "${ESP_PART}")"
BOOT_UUID="$(blkid -s UUID -o value "${BOOT_PART}")"
ROOT_UUID="$(blkid -s UUID -o value "${ROOT_PART}")"
BTRFS_UUID="$(blkid -s UUID -o value "/dev/mapper/${CRYPT_NAME}")"

cat > "${TARGET_ROOT}/etc/crypttab" <<EOF
${CRYPT_NAME} UUID=${ROOT_UUID} none luks,discard
EOF

cat > "${TARGET_ROOT}/etc/fstab" <<EOF
UUID=${BTRFS_UUID} / btrfs defaults,compress=zstd,noatime,subvol=@ 0 0
UUID=${BTRFS_UUID} /srv btrfs defaults,compress=zstd,noatime,subvol=@srv 0 0
UUID=${BTRFS_UUID} /var/log btrfs defaults,compress=zstd,noatime,subvol=@log 0 0
UUID=${BTRFS_UUID} /var/cache btrfs defaults,compress=zstd,noatime,subvol=@cache 0 0
UUID=${BTRFS_UUID} /var/lib/w4 btrfs defaults,compress=zstd,noatime,subvol=@data 0 0
UUID=${BOOT_UUID} /boot ext4 defaults 0 2
UUID=${ESP_UUID} /boot/efi vfat umask=0077 0 1
EOF

if [[ -f "${TARGET_ROOT}/etc/locale.gen" ]]; then
  sed -i "s/^# *${LOCALE_VALUE} UTF-8/${LOCALE_VALUE} UTF-8/" "${TARGET_ROOT}/etc/locale.gen" || true
fi

mkdir -p "${TARGET_ROOT}/etc/systemd/system/multi-user.target.wants"
if [[ -f "${TARGET_ROOT}/lib/systemd/system/w4-firstboot.service" ]]; then
  ln -sf /lib/systemd/system/w4-firstboot.service "${TARGET_ROOT}/etc/systemd/system/multi-user.target.wants/w4-firstboot.service"
elif [[ -f "${TARGET_ROOT}/etc/systemd/system/w4-firstboot.service" ]]; then
  ln -sf ../w4-firstboot.service "${TARGET_ROOT}/etc/systemd/system/multi-user.target.wants/w4-firstboot.service"
fi

rm -f "${TARGET_ROOT}/etc/systemd/system/multi-user.target.wants/w4-live-prep.service"

mount_chroot_support
ensure_kernel_boot_artifacts

if ! chroot "${TARGET_ROOT}" id -u "${USERNAME_VALUE}" >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" useradd -m -s /bin/bash -c "${DISPLAY_NAME_VALUE}" "${USERNAME_VALUE}"
fi

if chroot "${TARGET_ROOT}" getent group sudo >/dev/null 2>&1; then
  chroot "${TARGET_ROOT}" usermod -aG sudo "${USERNAME_VALUE}"
fi

USER_HOME="$(chroot "${TARGET_ROOT}" getent passwd "${USERNAME_VALUE}" | cut -d: -f6 || true)"
if [[ -n "${USER_HOME}" ]] && [[ "${USER_HOME}" == /* ]] && [[ "${USER_HOME}" != "/" ]]; then
  mkdir -p "${TARGET_ROOT}${USER_HOME}"
  if [[ -d "${TARGET_ROOT}/etc/skel" ]]; then
    cp -an "${TARGET_ROOT}/etc/skel/." "${TARGET_ROOT}${USER_HOME}/" 2>/dev/null || true
  fi
  chroot "${TARGET_ROOT}" chown -R "${USERNAME_VALUE}:${USERNAME_VALUE}" "${USER_HOME}"
fi

LOCAL_USER_PASSWORD="$(tr -d '\r\n' < "${LOCAL_USER_PASSWORD_FILE}")"
printf '%s:%s\n' "${USERNAME_VALUE}" "${LOCAL_USER_PASSWORD}" | chroot "${TARGET_ROOT}" chpasswd

if chroot_has_command locale-gen; then
  chroot "${TARGET_ROOT}" locale-gen || true
fi

if chroot_has_command setupcon; then
  chroot "${TARGET_ROOT}" setupcon --save-only || warn "setupcon devolvio un error; se conserva la configuracion escrita en /etc/default/keyboard"
fi

if ! chroot_has_command grub-install; then
  log "grub-install no esta disponible; instalando paquetes EFI requeridos"
  chroot_has_command apt-get || fail "grub-install no esta disponible y apt-get tampoco existe en el sistema destino"
  chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get update
  chroot "${TARGET_ROOT}" env DEBIAN_FRONTEND=noninteractive apt-get install -y grub-efi-amd64 grub-efi-amd64-bin grub2-common shim-signed efibootmgr
fi

chroot_has_command grub-install || fail "grub-install sigue sin estar disponible en el sistema destino"
log "Instalando GRUB EFI"
chroot "${TARGET_ROOT}" grub-install --target=x86_64-efi --efi-directory=/boot/efi --bootloader-id="W4 OS" --recheck
log "Instalando ruta UEFI de fallback"
chroot "${TARGET_ROOT}" grub-install --target=x86_64-efi --efi-directory=/boot/efi --removable --recheck

[[ -e "${TARGET_ROOT}/boot/efi/EFI/BOOT/BOOTX64.EFI" ]] || fail "no se genero la ruta UEFI de fallback BOOTX64.EFI"

if chroot_has_command update-initramfs; then
  chroot "${TARGET_ROOT}" update-initramfs -u -k all || warn "update-initramfs devolvio un error; se conserva el initrd ya generado en /boot"
fi

if chroot_has_command update-grub; then
  chroot "${TARGET_ROOT}" update-grub
fi

normalize_target_security_permissions

log "Instalacion preparada. Ejecute verify-installation.sh antes de reiniciar."
